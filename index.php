<?php
require_once __DIR__ . '/includes/init.php';

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) > 100) {
    $q = mb_substr($q, 0, 100);
}
$catId = filter_var($_GET['categoria'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($catId === false) {
    $catId = 0;
}

$pdo = db();
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

// Filtros opcionais sem concatenar nada na SQL: parâmetros nomeados distintos
$like = '%' . addcslashes($q, '%_\\') . '%';
$st = $pdo->prepare(
    'SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, c.nome AS categoria
       FROM produtos p
       JOIN categorias c ON c.id = p.categoria_id
      WHERE p.ativo = 1
        AND (:cat_filtro = 0 OR p.categoria_id = :cat_id)
        AND (:q_filtro = \'\' OR p.nome LIKE :q_nome OR p.descricao LIKE :q_desc)
      ORDER BY p.nome'
);
$st->bindValue(':cat_filtro', $catId, PDO::PARAM_INT);
$st->bindValue(':cat_id', $catId, PDO::PARAM_INT);
$st->bindValue(':q_filtro', $q, PDO::PARAM_STR);
$st->bindValue(':q_nome', $like, PDO::PARAM_STR);
$st->bindValue(':q_desc', $like, PDO::PARAM_STR);
$st->execute();
$produtos = $st->fetchAll();

$filtrando = ($q !== '' || $catId > 0);
$tituloPagina = 'Vitrine';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$filtrando): ?>
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <h1 class="titulo-arcano">Pedras, cartas e velas para os seus rituais</h1>
                <p class="fs-5 mb-4">Cristais, tarôs, incensos e amuletos selecionados para quem leva o simbólico a sério. Escolha, pague e receba.</p>
                <a href="#vitrine" class="btn btn-ouro btn-lg">Ver produtos</a>
            </div>
            <div class="col-md-4 d-none d-md-block"><div class="hero-lua" aria-hidden="true"></div></div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="container <?= $filtrando ? 'pt-4' : '' ?>" id="vitrine">
    <div class="d-flex flex-wrap gap-2 mb-4" aria-label="Filtrar por categoria">
        <a class="chip-categoria <?= $catId === 0 ? 'ativa' : '' ?>" href="<?= e(url('index.php' . ($q !== '' ? '?q=' . urlencode($q) : ''))) ?>">Todas</a>
        <?php foreach ($categorias as $c): ?>
            <a class="chip-categoria <?= $catId === (int) $c['id'] ? 'ativa' : '' ?>"
               href="<?= e(url('index.php?categoria=' . (int) $c['id'] . ($q !== '' ? '&q=' . urlencode($q) : ''))) ?>"><?= e($c['nome']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($q !== ''): ?>
        <p class="text-secondary">Resultados para <strong class="text-light"><?= e($q) ?></strong>: <?= count($produtos) ?> produto(s).</p>
    <?php endif; ?>

    <?php if (!$produtos): ?>
        <div class="painel text-center py-5">
            <h2 class="h3">Nenhum produto encontrado</h2>
            <p class="text-secondary">Tente outra palavra ou volte para todas as categorias.</p>
            <a class="btn btn-outline-ouro" href="<?= e(url('index.php')) ?>">Limpar filtros</a>
        </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
        <?php foreach ($produtos as $p): [$classe, $texto] = estoque_info((int) $p['estoque']); ?>
        <div class="col">
            <article class="card-produto d-flex flex-column">
                <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>">
                    <img src="<?= e(imagem_url($p['imagem'])) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
                </a>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <span class="small text-secondary"><?= e($p['categoria']) ?></span>
                    <a class="nome mb-2" href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>"><?= e($p['nome']) ?></a>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="preco"><?= e(moeda($p['preco'])) ?></span>
                        <span class="badge text-bg-<?= e($classe) ?>"><?= e($texto) ?></span>
                    </div>
                    <div class="mt-auto d-flex gap-2">
                        <a class="btn btn-outline-ouro btn-sm flex-fill" href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>">Detalhes</a>
                        <?php if ((int) $p['estoque'] > 0): ?>
                        <form action="<?= e(url('carrinho.php')) ?>" method="post" class="flex-fill">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="adicionar">
                            <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="quantidade" value="1">
                            <button class="btn btn-ouro btn-sm w-100" type="submit">Comprar</button>
                        </form>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm flex-fill" type="button" disabled>Esgotado</button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
