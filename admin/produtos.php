<?php
require_once __DIR__ . '/../includes/init.php';
exigir_admin();

$pdo = db();

if (metodo_post()) {
    exigir_csrf();
    $acao = (string) ($_POST['acao'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && $acao === 'alternar') {
        $st = $pdo->prepare('UPDATE produtos SET ativo = 1 - ativo WHERE id = :id');
        $st->execute(['id' => $id]);
        flash('success', 'Situação do produto atualizada.');
    } elseif ($id > 0 && $acao === 'excluir') {
        $sel = $pdo->prepare('SELECT imagem FROM produtos WHERE id = :id');
        $sel->execute(['id' => $id]);
        $img = $sel->fetchColumn();
        try {
            $del = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $del->execute(['id' => $id]);
            if ($del->rowCount() === 1) {
                remover_imagem_upload(is_string($img) ? $img : null);
                flash('success', 'Produto excluído.');
            } else {
                flash('danger', 'Produto não encontrado.');
            }
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                // Produto já vendido: o histórico de pedidos precisa ser preservado
                $des = $pdo->prepare('UPDATE produtos SET ativo = 0 WHERE id = :id');
                $des->execute(['id' => $id]);
                flash('warning', 'Este produto já aparece em pedidos e não pode ser excluído; ele foi desativado e saiu da vitrine.');
            } else {
                error_log('Erro ao excluir produto: ' . $ex->getMessage());
                flash('danger', 'Não foi possível excluir o produto.');
            }
        }
    } else {
        flash('danger', 'Ação inválida.');
    }
    redirecionar('admin/produtos.php');
}

$produtos = $pdo->query(
    'SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, p.ativo, c.nome AS categoria
       FROM produtos p
       JOIN categorias c ON c.id = p.categoria_id
      ORDER BY p.id DESC'
)->fetchAll();

$tituloPagina = 'Produtos (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="mb-0">Produtos</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/index.php')) ?>">Painel</a>
            <a class="btn btn-ouro" href="<?= e(url('admin/produto_form.php')) ?>">Cadastrar produto</a>
        </div>
    </div>
    <div class="painel p-0 table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Produto</th><th scope="col">Categoria</th><th scope="col">Preço</th><th scope="col">Estoque</th><th scope="col">Situação</th><th scope="col" class="text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><div class="d-flex align-items-center gap-3"><img class="img-mini" src="<?= e(imagem_url($p['imagem'])) ?>" alt=""><span><?= e($p['nome']) ?></span></div></td>
                    <td><?= e($p['categoria']) ?></td>
                    <td><?= e(moeda($p['preco'])) ?></td>
                    <td><?= (int) $p['estoque'] ?></td>
                    <td><?= (int) $p['ativo'] === 1 ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Desativado</span>' ?></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                            <a class="btn btn-sm btn-outline-ouro" href="<?= e(url('admin/produto_form.php?id=' . (int) $p['id'])) ?>">Editar</a>
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="acao" value="alternar">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= (int) $p['ativo'] === 1 ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                            <form method="post" action="<?= e(url('admin/produtos.php')) ?>" data-confirm="Excluir &quot;<?= e($p['nome']) ?>&quot;? Se já houver pedidos com ele, será apenas desativado.">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
