<?php
require_once __DIR__ . '/../includes/init.php';
exigir_admin();

$pdo = db();
$totProdutos = (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totAtivos   = (int) $pdo->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1')->fetchColumn();
$totPedidos  = (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
$receita     = para_centavos($pdo->query('SELECT COALESCE(SUM(total), 0) FROM pedidos')->fetchColumn());
$baixos = $pdo->query('SELECT id, nome, estoque FROM produtos WHERE ativo = 1 AND estoque <= 5 ORDER BY estoque, nome')->fetchAll();

$tituloPagina = 'Painel administrativo';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <h1 class="mb-1">Painel administrativo</h1>
    <p class="text-secondary mb-4">Olá, <?= e(usuario_logado()['nome']) ?>.</p>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="painel"><div class="text-secondary small">Produtos cadastrados</div><div class="preco-grande"><?= $totProdutos ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="painel"><div class="text-secondary small">Produtos ativos</div><div class="preco-grande"><?= $totAtivos ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="painel"><div class="text-secondary small">Pedidos realizados</div><div class="preco-grande"><?= $totPedidos ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="painel"><div class="text-secondary small">Receita total</div><div class="preco-grande fs-2"><?= e(moeda_centavos($receita)) ?></div></div></div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-ouro" href="<?= e(url('admin/produtos.php')) ?>">Gerenciar produtos</a>
        <a class="btn btn-outline-ouro" href="<?= e(url('admin/produto_form.php')) ?>">Cadastrar produto</a>
        <a class="btn btn-outline-ouro" href="<?= e(url('admin/pedidos.php')) ?>">Ver pedidos</a>
    </div>

    <div class="painel admin-aviso">
        <h2 class="h5">Estoque baixo (5 unidades ou menos)</h2>
        <?php if (!$baixos): ?>
            <p class="text-secondary mb-0">Nenhum produto ativo com estoque baixo.</p>
        <?php else: ?>
            <ul class="mb-0">
                <?php foreach ($baixos as $b): ?>
                    <li><a href="<?= e(url('admin/produto_form.php?id=' . (int) $b['id'])) ?>"><?= e($b['nome']) ?></a>: <?= (int) $b['estoque'] === 0 ? 'esgotado' : (int) $b['estoque'] . ' un.' ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
