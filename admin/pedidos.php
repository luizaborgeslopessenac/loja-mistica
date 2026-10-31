<?php
require_once __DIR__ . '/../includes/init.php';
exigir_admin();

$pdo = db();
$pedidos = $pdo->query(
    'SELECT p.id, p.data_pedido, p.forma_pagamento, p.total, p.status, u.nome AS cliente, u.email
       FROM pedidos p
       JOIN usuarios u ON u.id = p.usuario_id
      ORDER BY p.data_pedido DESC, p.id DESC'
)->fetchAll();

$itensPorPedido = [];
$linhas = $pdo->query(
    'SELECT ip.pedido_id, ip.quantidade, ip.preco_unitario, pr.nome
       FROM itens_pedido ip
       JOIN produtos pr ON pr.id = ip.produto_id
      ORDER BY ip.pedido_id, ip.id'
)->fetchAll();
foreach ($linhas as $l) {
    $itensPorPedido[(int) $l['pedido_id']][] = $l;
}

$tituloPagina = 'Pedidos (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Pedidos realizados</h1>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin/index.php')) ?>">Painel</a>
    </div>
    <?php if (!$pedidos): ?>
        <div class="painel text-center py-5 text-secondary">Nenhum pedido realizado ainda.</div>
    <?php else: foreach ($pedidos as $p): ?>
        <div class="painel mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <strong>Pedido #<?= (int) $p['id'] ?></strong>
                <span class="text-secondary"><?= e(formatar_data($p['data_pedido'])) ?> · <?= e(rotulo_pagamento($p['forma_pagamento'])) ?> · <?= e(ucfirst($p['status'])) ?></span>
            </div>
            <div class="small text-secondary mb-2">Cliente: <?= e($p['cliente']) ?> (<?= e($p['email']) ?>)</div>
            <table class="table table-sm align-middle mb-2">
                <thead><tr><th scope="col">Item</th><th scope="col">Qtd.</th><th scope="col">Preço unit.</th><th scope="col" class="text-end">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($itensPorPedido[(int) $p['id']] ?? [] as $i): ?>
                    <tr>
                        <td><?= e($i['nome']) ?></td>
                        <td><?= (int) $i['quantidade'] ?></td>
                        <td><?= e(moeda($i['preco_unitario'])) ?></td>
                        <td class="text-end"><?= e(moeda_centavos(para_centavos($i['preco_unitario']) * (int) $i['quantidade'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-end"><span class="text-secondary">Valor total </span><span class="preco"><?= e(moeda($p['total'])) ?></span></div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
