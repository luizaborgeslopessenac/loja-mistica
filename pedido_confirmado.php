<?php
require_once __DIR__ . '/includes/init.php';
exigir_login('meus_pedidos');

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pedido = null;
if ($id !== false) {
    // Só o dono do pedido pode vê-lo
    $st = db()->prepare('SELECT id, data_pedido, forma_pagamento, total, status FROM pedidos WHERE id = :id AND usuario_id = :usuario');
    $st->execute(['id' => $id, 'usuario' => usuario_logado()['id']]);
    $pedido = $st->fetch();
}
if (!$pedido) {
    pagina_erro(404, 'Pedido não encontrado', 'Não encontramos este pedido na sua conta.');
}
$itens = itens_do_pedido((int) $pedido['id']);
$tituloPagina = 'Pedido confirmado';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width: 760px;">
    <div class="painel">
        <h1 class="mb-1">Pagamento confirmado</h1>
        <p class="text-secondary">Pedido <strong class="text-light">#<?= (int) $pedido['id'] ?></strong> · <?= e(formatar_data($pedido['data_pedido'])) ?> · <?= e(rotulo_pagamento($pedido['forma_pagamento'])) ?> (simulação)</p>
        <table class="table align-middle">
            <thead><tr><th scope="col">Produto</th><th scope="col">Qtd.</th><th scope="col">Preço unit.</th><th scope="col" class="text-end">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($itens as $i): ?>
                <tr>
                    <td><?= e($i['nome']) ?></td>
                    <td><?= (int) $i['quantidade'] ?></td>
                    <td><?= e(moeda($i['preco_unitario'])) ?></td>
                    <td class="text-end"><?= e(moeda_centavos(para_centavos($i['preco_unitario']) * (int) $i['quantidade'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3">Total</th><th class="text-end preco"><?= e(moeda($pedido['total'])) ?></th></tr></tfoot>
        </table>
        <div class="d-flex gap-2">
            <a class="btn btn-ouro" href="<?= e(url('index.php')) ?>">Continuar comprando</a>
            <a class="btn btn-outline-secondary" href="<?= e(url('meus_pedidos.php')) ?>">Meus pedidos</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
