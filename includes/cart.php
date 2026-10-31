<?php
declare(strict_types=1);

/**
 * Carrinho armazenado em $_SESSION['carrinho'] no formato [produto_id => quantidade].
 * Preços e estoque são SEMPRE lidos do banco (o navegador nunca decide valores).
 */
function carrinho_obter(): array
{
    $c = $_SESSION['carrinho'] ?? [];
    return is_array($c) ? $c : [];
}

function carrinho_quantidade_total(): int
{
    return (int) array_sum(array_map('intval', carrinho_obter()));
}

function produto_ativo(int $id): ?array
{
    $st = db()->prepare('SELECT id, nome, preco, estoque, imagem FROM produtos WHERE id = :id AND ativo = 1');
    $st->execute(['id' => $id]);
    $p = $st->fetch();
    return $p ?: null;
}

/** @return array{0:bool,1:string} */
function carrinho_adicionar(int $id, int $qtd): array
{
    $p = produto_ativo($id);
    if ($p === null) {
        return [false, 'Este produto não está disponível.'];
    }
    if ($qtd < 1 || $qtd > 999) {
        return [false, 'Quantidade inválida.'];
    }
    $estoque = (int) $p['estoque'];
    if ($estoque < 1) {
        return [false, '"' . $p['nome'] . '" está esgotado.'];
    }
    $c = carrinho_obter();
    $atual = (int) ($c[$id] ?? 0);
    if ($atual + $qtd > $estoque) {
        return [false, 'Estoque insuficiente: há apenas ' . $estoque . ' unidade(s) de "' . $p['nome'] . '" (você já tem ' . $atual . ' no carrinho).'];
    }
    $c[$id] = $atual + $qtd;
    $_SESSION['carrinho'] = $c;
    return [true, '"' . $p['nome'] . '" foi adicionado ao carrinho.'];
}

/** @return array{0:bool,1:string} */
function carrinho_atualizar(int $id, int $qtd): array
{
    $c = carrinho_obter();
    if (!isset($c[$id])) {
        return [false, 'Este item não está no carrinho.'];
    }
    if ($qtd < 1) {
        carrinho_remover($id);
        return [true, 'Item removido do carrinho.'];
    }
    $p = produto_ativo($id);
    if ($p === null) {
        carrinho_remover($id);
        return [false, 'Produto indisponível; ele foi removido do carrinho.'];
    }
    $estoque = (int) $p['estoque'];
    if ($qtd > $estoque) {
        return [false, 'A quantidade não pode exceder o estoque disponível (' . $estoque . ' un. de "' . $p['nome'] . '").'];
    }
    $c[$id] = $qtd;
    $_SESSION['carrinho'] = $c;
    return [true, 'Quantidade atualizada.'];
}

function carrinho_remover(int $id): void
{
    $c = carrinho_obter();
    unset($c[$id]);
    $_SESSION['carrinho'] = $c;
}

function carrinho_limpar(): void
{
    unset($_SESSION['carrinho']);
}

/**
 * Monta o carrinho com dados atuais do banco, calcula subtotais/total (em centavos)
 * e corrige itens que ficaram indisponíveis ou acima do estoque.
 */
function carrinho_detalhado(): array
{
    $itens = [];
    $avisos = [];
    $total = 0;
    $novo = [];

    foreach (carrinho_obter() as $id => $qtd) {
        $id = (int) $id;
        $qtd = (int) $qtd;
        $p = produto_ativo($id);
        if ($p === null || (int) $p['estoque'] < 1) {
            $avisos[] = 'Um produto ficou indisponível e foi removido do carrinho.';
            continue;
        }
        $estoque = (int) $p['estoque'];
        if ($qtd > $estoque) {
            $qtd = $estoque;
            $avisos[] = 'A quantidade de "' . $p['nome'] . '" foi ajustada para o estoque disponível (' . $estoque . ').';
        }
        if ($qtd < 1) {
            continue;
        }
        $unit = para_centavos($p['preco']);
        $sub = $unit * $qtd;
        $total += $sub;
        $novo[$id] = $qtd;
        $itens[] = [
            'id'       => $id,
            'nome'     => $p['nome'],
            'imagem'   => $p['imagem'],
            'estoque'  => $estoque,
            'quantidade' => $qtd,
            'unitario' => $unit,
            'subtotal' => $sub,
        ];
    }
    $_SESSION['carrinho'] = $novo;

    return ['itens' => $itens, 'total' => $total, 'avisos' => $avisos];
}
