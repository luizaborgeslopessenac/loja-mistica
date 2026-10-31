<?php
require_once __DIR__ . '/includes/init.php';
exigir_login('meus_pedidos');

$st = db()->prepare('SELECT id, data_pedido, forma_pagamento, total, status FROM pedidos WHERE usuario_id = :usuario ORDER BY data_pedido DESC, id DESC');
$st->execute(['usuario' => usuario_logado()['id']]);
$pedidos = $st->fetchAll();

$tituloPagina = 'Meus pedidos';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="mb-4">Meus pedidos</h1>
    <?php if (!$pedidos): ?>
        <div class="painel text-center py-5">
            <p class="text-secondary">Você ainda não fez nenhum pedido.</p>
            <a class="btn btn-ouro" href="<?= e(url('index.php')) ?>">Ir para a vitrine</a>
        </div>
    <?php else: foreach ($pedidos as $p): ?>
        <div class="painel mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                <strong>Pedido #<?= (int) $p['id'] ?></strong>
                <span class="text-secondary"><?= e(formatar_data($p['data_pedido'])) ?> · <?= e(rotulo_pagamento($p['forma_pagamento'])) ?> · <?= e(ucfirst($p['status'])) ?></span>
            </div>
            <ul class="mb-2">
                <?php foreach (itens_do_pedido((int) $p['id']) as $i): ?>
                    <li><?= e($i['nome']) ?> × <?= (int) $i['quantidade'] ?> <span class="text-secondary">(<?= e(moeda($i['preco_unitario'])) ?> cada)</span></li>
                <?php endforeach; ?>
            </ul>
            <div class="text-end"><span class="text-secondary">Total </span><span class="preco"><?= e(moeda($p['total'])) ?></span></div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
