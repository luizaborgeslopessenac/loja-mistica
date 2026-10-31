<?php
require_once __DIR__ . '/../includes/init.php';
exigir_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$produto = ['nome' => '', 'categoria_id' => 0, 'descricao' => '', 'preco' => '', 'estoque' => '0', 'imagem' => null, 'ativo' => 1];

if ($id > 0) {
    $st = $pdo->prepare('SELECT id, nome, categoria_id, descricao, preco, estoque, imagem, ativo FROM produtos WHERE id = :id');
    $st->execute(['id' => $id]);
    $encontrado = $st->fetch();
    if (!$encontrado) {
        pagina_erro(404, 'Produto não encontrado', 'Não existe produto com este código.');
    }
    $produto = $encontrado;
    $produto['preco'] = number_format((float) $produto['preco'], 2, '.', '');
}

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
$erros = [];

if (metodo_post()) {
    exigir_csrf();
    $produto['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $produto['categoria_id'] = (int) ($_POST['categoria_id'] ?? 0);
    $produto['descricao'] = trim((string) ($_POST['descricao'] ?? ''));
    $produto['preco'] = str_replace(',', '.', trim((string) ($_POST['preco'] ?? '')));
    $produto['estoque'] = trim((string) ($_POST['estoque'] ?? ''));
    $produto['ativo'] = isset($_POST['ativo']) ? 1 : 0;

    if (mb_strlen($produto['nome']) < 3 || mb_strlen($produto['nome']) > 150) {
        $erros[] = 'O nome deve ter de 3 a 150 caracteres.';
    }
    $catOk = false;
    foreach ($categorias as $c) {
        if ((int) $c['id'] === $produto['categoria_id']) {
            $catOk = true;
        }
    }
    if (!$catOk) {
        $erros[] = 'Selecione uma categoria válida.';
    }
    if ($produto['descricao'] === '' || mb_strlen($produto['descricao']) > 2000) {
        $erros[] = 'Informe a descrição (até 2000 caracteres).';
    }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $produto['preco']) || (float) $produto['preco'] <= 0) {
        $erros[] = 'Informe um preço válido maior que zero (ex.: 49.90).';
    }
    if (!preg_match('/^\d{1,9}$/', $produto['estoque'])) {
        $erros[] = 'O estoque deve ser um número inteiro maior ou igual a zero.';
    }

    $novaImagem = null;
    if (!$erros) {
        try {
            $novaImagem = salvar_upload_imagem($_FILES['imagem'] ?? []);
        } catch (RuntimeException $ex) {
            $erros[] = $ex->getMessage();
        }
    }

    if (!$erros) {
        $imagemFinal = $novaImagem ?? $produto['imagem'];
        try {
            if ($id > 0) {
                $up = $pdo->prepare(
                    'UPDATE produtos
                        SET nome = :nome, categoria_id = :cat, descricao = :desc, preco = :preco,
                            estoque = :estoque, imagem = :img, ativo = :ativo
                      WHERE id = :id'
                );
                $up->execute([
                    'nome' => $produto['nome'], 'cat' => $produto['categoria_id'], 'desc' => $produto['descricao'],
                    'preco' => $produto['preco'], 'estoque' => (int) $produto['estoque'], 'img' => $imagemFinal,
                    'ativo' => $produto['ativo'], 'id' => $id,
                ]);
                if ($novaImagem !== null) {
                    remover_imagem_upload($produto['imagem'] !== null ? (string) $produto['imagem'] : null);
                }
                flash('success', 'Produto atualizado.');
            } else {
                $ins = $pdo->prepare(
                    'INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, ativo)
                     VALUES (:cat, :nome, :desc, :preco, :estoque, :img, :ativo)'
                );
                $ins->execute([
                    'cat' => $produto['categoria_id'], 'nome' => $produto['nome'], 'desc' => $produto['descricao'],
                    'preco' => $produto['preco'], 'estoque' => (int) $produto['estoque'], 'img' => $imagemFinal,
                    'ativo' => $produto['ativo'],
                ]);
                flash('success', 'Produto cadastrado.');
            }
            redirecionar('admin/produtos.php');
        } catch (PDOException $ex) {
            remover_imagem_upload($novaImagem);
            error_log('Erro ao salvar produto: ' . $ex->getMessage());
            $erros[] = 'Não foi possível salvar o produto. Tente novamente.';
        }
    }
}

$tituloPagina = $id > 0 ? 'Editar produto' : 'Cadastrar produto';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width: 820px;">
    <h1 class="mb-3"><?= e($tituloPagina) ?></h1>
    <?php if ($erros): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" action="<?= e(url('admin/produto_form.php' . ($id > 0 ? '?id=' . $id : ''))) ?>" class="painel needs-validation" novalidate data-uma-vez>
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="nome">Nome</label>
                <input class="form-control" id="nome" name="nome" value="<?= e($produto['nome']) ?>" required minlength="3" maxlength="150">
                <div class="invalid-feedback">Informe o nome (mínimo 3 caracteres).</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="categoria_id">Categoria</label>
                <select class="form-select" id="categoria_id" name="categoria_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $produto['categoria_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Escolha uma categoria.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="descricao">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4" required maxlength="2000"><?= e($produto['descricao']) ?></textarea>
                <div class="invalid-feedback">Informe a descrição.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="preco">Preço (R$)</label>
                <input class="form-control" type="number" id="preco" name="preco" value="<?= e($produto['preco']) ?>" step="0.01" min="0.01" required>
                <div class="invalid-feedback">Informe um preço maior que zero.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="estoque">Estoque</label>
                <input class="form-control" type="number" id="estoque" name="estoque" value="<?= e($produto['estoque']) ?>" step="1" min="0" required>
                <div class="invalid-feedback">Informe o estoque (0 ou mais).</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" <?= (int) $produto['ativo'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">Visível na loja</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="imagem">Imagem do produto</label>
                <input class="form-control" type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="form-text">JPG, PNG, WEBP ou GIF, até 2 MB. <?= $id > 0 ? 'Deixe vazio para manter a imagem atual.' : 'Se não enviar, será usada uma imagem padrão.' ?></div>
                <?php if (!empty($produto['imagem'])): ?>
                    <img class="img-mini mt-2" src="<?= e(imagem_url($produto['imagem'])) ?>" alt="Imagem atual">
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-ouro" type="submit">Salvar produto</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/produtos.php')) ?>">Cancelar</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
