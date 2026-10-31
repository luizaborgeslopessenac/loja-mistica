<?php
require_once __DIR__ . '/includes/init.php';

exigir_login('checkout'); // conclusão da compra restrita a usuários autenticados

if (metodo_post()) {
    exigir_csrf();
    $metodo = (string) ($_POST['metodo'] ?? '');
    try {
        $pedidoId = finalizar_pedido((int) usuario_logado()['id'], $metodo);
        redirecionar('pedido_confirmado.php?id=' . $pedidoId);
    } catch (RuntimeException $erro) {
        flash('danger', $erro->getMessage());
        redirecionar('carrinho.php');
    } catch (Throwable $erro) {
        error_log('Erro ao finalizar pedido: ' . $erro->getMessage());
        flash('danger', 'Não foi possível concluir o pedido agora. Nada foi cobrado; tente novamente.');
        redirecionar('carrinho.php');
    }
}

$cesta = carrinho_detalhado();
if (!$cesta['itens']) {
    flash('warning', 'Seu carrinho está vazio.');
    redirecionar('carrinho.php');
}
foreach ($cesta['avisos'] as $aviso) {
    flash('warning', $aviso);
}

$tituloPagina = 'Finalizar compra';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="mb-4">Finalizar compra</h1>
    <div class="row g-4">
        <div class="col-lg-7">
            <form action="<?= e(url('checkout.php')) ?>" method="post" class="painel" data-uma-vez>
                <?= csrf_campo() ?>
                <h2 class="h4 mb-3">Forma de pagamento</h2>
                <p class="small text-secondary">Simulação: o pagamento é confirmado na hora e nenhum dado financeiro é coletado.</p>

                <div class="d-grid gap-3 mb-3">
                    <label class="opcao-pagamento">
                        <input class="form-check-input me-2" type="radio" name="metodo" value="pix" required checked>
                        <strong>PIX</strong>
                        <div class="small text-secondary ms-4" id="info-pix">Pagamento instantâneo. O pedido é confirmado assim que você clicar em pagar.</div>
                    </label>
                    <label class="opcao-pagamento">
                        <input class="form-check-input me-2" type="radio" name="metodo" value="boleto">
                        <strong>Boleto</strong>
                        <div class="small text-secondary ms-4 d-none" id="info-boleto">Nesta simulação o boleto é considerado pago imediatamente.</div>
                    </label>
                    <label class="opcao-pagamento">
                        <input class="form-check-input me-2" type="radio" name="metodo" value="cartao">
                        <strong>Cartão</strong>
                        <div class="small text-secondary ms-4 d-none" id="info-cartao">Nesta simulação não é necessário informar número de cartão.</div>
                    </label>
                </div>
                <button class="btn btn-ouro btn-lg w-100" type="submit">Confirmar pagamento de <?= e(moeda_centavos($cesta['total'])) ?></button>
            </form>
        </div>
        <div class="col-lg-5">
            <div class="painel">
                <h2 class="h4">Resumo do pedido</h2>
                <ul class="list-unstyled my-3">
                    <?php foreach ($cesta['itens'] as $i): ?>
                    <li class="d-flex justify-content-between py-2 border-bottom border-secondary-subtle">
                        <span><?= e($i['nome']) ?> <span class="text-secondary">× <?= $i['quantidade'] ?></span></span>
                        <span><?= e(moeda_centavos($i['subtotal'])) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between">
                    <strong>Total</strong><span class="preco"><?= e(moeda_centavos($cesta['total'])) ?></span>
                </div>
                <p class="small text-secondary mt-3 mb-0">Comprador: <?= e(usuario_logado()['nome']) ?> (<?= e(usuario_logado()['email']) ?>)</p>
                <a class="small" href="<?= e(url('carrinho.php')) ?>">Voltar ao carrinho</a>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
