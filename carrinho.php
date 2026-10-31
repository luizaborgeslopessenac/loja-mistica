<?php
require_once __DIR__ . '/includes/init.php';

if (metodo_post()) {
    exigir_csrf();
    $acao = (string) ($_POST['acao'] ?? '');
    $id   = (int) ($_POST['produto_id'] ?? 0);
    $qtd  = (int) ($_POST['quantidade'] ?? 1);

    switch ($acao) {
        case 'adicionar':
            [$ok, $msg] = carrinho_adicionar($id, $qtd);
            flash($ok ? 'success' : 'danger', $msg);
            break;
        case 'atualizar':
            [$ok, $msg] = carrinho_atualizar($id, $qtd);
            flash($ok ? 'success' : 'danger', $msg);
            break;
        case 'remover':
            carrinho_remover($id);
            flash('success', 'Item removido do carrinho.');
            break;
        case 'limpar':
            carrinho_limpar();
            flash('success', 'Carrinho esvaziado.');
            break;
        default:
            flash('danger', 'Ação inválida.');
    }
    redirecionar('carrinho.php');
}

$cesta = carrinho_detalhado();
foreach ($cesta['avisos'] as $aviso) {
    flash('warning', $aviso);
}
$tituloPagina = 'Carrinho';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="mb-4">Seu carrinho</h1>

    <?php if (!$cesta['itens']): ?>
        <div class="painel text-center py-5">
            <h2 class="h3">Seu carrinho está vazio</h2>
            <p class="text-secondary">Escolha algo na vitrine para começar.</p>
            <a class="btn btn-ouro" href="<?= e(url('index.php')) ?>">Ir para a vitrine</a>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="painel p-0 table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th scope="col">Produto</th><th scope="col">Preço</th><th scope="col">Quantidade</th><th scope="col">Subtotal</th><th scope="col"><span class="visually-hidden">Remover</span></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cesta['itens'] as $i): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img class="img-mini" src="<?= e(imagem_url($i['imagem'])) ?>" alt="">
                                    <a href="<?= e(url('produto.php?id=' . $i['id'])) ?>"><?= e($i['nome']) ?></a>
                                </div>
                            </td>
                            <td><?= e(moeda_centavos($i['unitario'])) ?></td>
                            <td>
                                <form action="<?= e(url('carrinho.php')) ?>" method="post" class="d-flex gap-2 align-items-center">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="produto_id" value="<?= $i['id'] ?>">
                                    <input class="form-control form-control-sm input-qtd" type="number" name="quantidade"
                                           value="<?= $i['quantidade'] ?>" min="1" max="<?= $i['estoque'] ?>"
                                           data-id="<?= $i['id'] ?>" data-preco-centavos="<?= $i['unitario'] ?>"
                                           aria-label="Quantidade de <?= e($i['nome']) ?>">
                                    <noscript><button class="btn btn-sm btn-outline-ouro" type="submit">Atualizar</button></noscript>
                                </form>
                                <div class="form-text">Máx.: <?= $i['estoque'] ?></div>
                            </td>
                            <td class="fw-semibold" id="subtotal-<?= $i['id'] ?>"><?= e(moeda_centavos($i['subtotal'])) ?></td>
                            <td>
                                <form action="<?= e(url('carrinho.php')) ?>" method="post" data-confirm="Remover este item do carrinho?">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="acao" value="remover">
                                    <input type="hidden" name="produto_id" value="<?= $i['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <form action="<?= e(url('carrinho.php')) ?>" method="post" class="mt-3" data-confirm="Esvaziar todo o carrinho?">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="limpar">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Esvaziar carrinho</button>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="painel">
                <h2 class="h4">Resumo</h2>
                <div class="d-flex justify-content-between my-3">
                    <span>Total geral</span>
                    <span class="preco" id="totalGeral"><?= e(moeda_centavos($cesta['total'])) ?></span>
                </div>
                <a class="btn btn-ouro w-100 mb-2" href="<?= e(url('checkout.php')) ?>">Finalizar compra</a>
                <a class="btn btn-outline-secondary w-100" href="<?= e(url('index.php')) ?>">Continuar comprando</a>
                <?php if (!esta_logado()): ?>
                    <p class="small text-secondary mt-3 mb-0">Para concluir a compra você precisará entrar ou criar uma conta.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
