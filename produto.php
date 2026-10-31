<?php
require_once __DIR__ . '/includes/init.php';

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$produto = null;
if ($id !== false) {
    $st = db()->prepare(
        'SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.imagem, c.id AS categoria_id, c.nome AS categoria
           FROM produtos p
           JOIN categorias c ON c.id = p.categoria_id
          WHERE p.id = :id AND p.ativo = 1'
    );
    $st->execute(['id' => $id]);
    $produto = $st->fetch();
}
if (!$produto) {
    pagina_erro(404, 'Produto não encontrado', 'Este produto não existe ou foi retirado da loja.');
}

$estoque = (int) $produto['estoque'];
[$classe, $texto] = estoque_info($estoque);
$tituloPagina = $produto['nome'];
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Vitrine</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('index.php?categoria=' . (int) $produto['categoria_id'])) ?>"><?= e($produto['categoria']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($produto['nome']) ?></li>
        </ol>
    </nav>
    <div class="row g-5">
        <div class="col-md-5">
            <img class="img-detalhe" src="<?= e(imagem_url($produto['imagem'])) ?>" alt="<?= e($produto['nome']) ?>">
        </div>
        <div class="col-md-7">
            <span class="text-secondary"><?= e($produto['categoria']) ?></span>
            <h1 class="display-5 mb-2"><?= e($produto['nome']) ?></h1>
            <div class="preco-grande mb-2"><?= e(moeda($produto['preco'])) ?></div>
            <p class="mb-3"><span class="badge text-bg-<?= e($classe) ?> fs-6"><?= e($texto) ?></span></p>
            <p class="fs-5" style="max-width: 60ch;"><?= nl2br(e($produto['descricao'])) ?></p>

            <?php if ($estoque > 0): ?>
            <form action="<?= e(url('carrinho.php')) ?>" method="post" class="d-flex align-items-end gap-3 mt-4">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                <div>
                    <label class="form-label" for="quantidade">Quantidade</label>
                    <input class="form-control input-qtd" type="number" id="quantidade" name="quantidade" value="1" min="1" max="<?= $estoque ?>" required>
                </div>
                <button class="btn btn-ouro btn-lg" type="submit">Adicionar ao carrinho</button>
            </form>
            <?php else: ?>
                <div class="alert alert-secondary mt-4" role="status">Este produto está esgotado no momento.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
