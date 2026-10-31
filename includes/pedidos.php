<?php
declare(strict_types=1);

/**
 * Finaliza a compra em UMA transação:
 *  1) cria o pedido; 2) grava cada item com o preço congelado;
 *  3) baixa o estoque; 4) limpa o carrinho da sessão.
 * Se qualquer passo falhar, tudo é desfeito (rollback).
 *
 * @throws RuntimeException com mensagem segura para exibir ao usuário
 */
function finalizar_pedido(int $usuarioId, string $metodo): int
{
    if (!in_array($metodo, ['pix', 'boleto', 'cartao'], true)) {
        throw new RuntimeException('Selecione uma forma de pagamento válida.');
    }
    $carrinho = carrinho_obter();
    if (count($carrinho) === 0) {
        throw new RuntimeException('Seu carrinho está vazio.');
    }

    $ids = array_map('intval', array_keys($carrinho));
    sort($ids); // ordem fixa de bloqueio evita deadlocks entre compras simultâneas

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $busca = $pdo->prepare('SELECT id, nome, preco, estoque, ativo FROM produtos WHERE id = :id FOR UPDATE');
        $linhas = [];
        $totalCent = 0;

        foreach ($ids as $id) {
            $qtd = (int) $carrinho[$id];
            $busca->execute(['id' => $id]);
            $p = $busca->fetch();
            if (!$p || (int) $p['ativo'] !== 1) {
                throw new RuntimeException('Um dos produtos do carrinho não está mais disponível.');
            }
            if ($qtd < 1 || $qtd > (int) $p['estoque']) {
                throw new RuntimeException('Estoque insuficiente para "' . $p['nome'] . '". Ajuste a quantidade no carrinho.');
            }
            $unit = para_centavos($p['preco']);
            $totalCent += $unit * $qtd;
            $linhas[] = ['id' => $id, 'qtd' => $qtd, 'unit' => $unit];
        }

        $inserePedido = $pdo->prepare(
            'INSERT INTO pedidos (usuario_id, data_pedido, forma_pagamento, total, status)
             VALUES (:usuario, :data, :forma, :total, :status)'
        );
        $inserePedido->execute([
            'usuario' => $usuarioId,
            'data'    => date('Y-m-d H:i:s'),
            'forma'   => $metodo,
            'total'   => decimal_sql($totalCent),
            'status'  => 'pago',
        ]);
        $pedidoId = (int) $pdo->lastInsertId();

        $insereItem = $pdo->prepare(
            'INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (:pedido, :produto, :qtd, :preco)'
        );
        $baixa = $pdo->prepare(
            'UPDATE produtos SET estoque = estoque - :qtd WHERE id = :id AND estoque >= :minimo'
        );

        foreach ($linhas as $l) {
            $insereItem->execute([
                'pedido'  => $pedidoId,
                'produto' => $l['id'],
                'qtd'     => $l['qtd'],
                'preco'   => decimal_sql($l['unit']),
            ]);
            $baixa->execute(['qtd' => $l['qtd'], 'id' => $l['id'], 'minimo' => $l['qtd']]);
            if ($baixa->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível reservar o estoque. Tente novamente.');
            }
        }

        $pdo->commit();
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $erro;
    }

    carrinho_limpar();
    return $pedidoId;
}

/** Itens de um pedido (nome do produto + quantidade + preço congelado). */
function itens_do_pedido(int $pedidoId): array
{
    $st = db()->prepare(
        'SELECT ip.quantidade, ip.preco_unitario, pr.nome
           FROM itens_pedido ip
           JOIN produtos pr ON pr.id = ip.produto_id
          WHERE ip.pedido_id = :id
          ORDER BY ip.id'
    );
    $st->execute(['id' => $pedidoId]);
    return $st->fetchAll();
}
