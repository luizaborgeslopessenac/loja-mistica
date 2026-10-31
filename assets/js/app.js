(function () {
    'use strict';

    var formatador = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    // 1) Confirmação antes de ações destrutivas: <form data-confirm="Mensagem">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                ev.preventDefault();
            }
        });
    });

    // 2) Carrinho: recalcula subtotais e total na tela enquanto a quantidade muda
    //    (o servidor recalcula tudo de novo ao salvar; isto é só a prévia dinâmica)
    function recalcularCarrinho() {
        var total = 0;
        document.querySelectorAll('input[data-preco-centavos]').forEach(function (campo) {
            var max = parseInt(campo.max, 10) || 1;
            var qtd = parseInt(campo.value, 10);
            if (isNaN(qtd) || qtd < 1) { qtd = 1; }
            if (qtd > max) { qtd = max; }
            var sub = qtd * parseInt(campo.getAttribute('data-preco-centavos'), 10);
            total += sub;
            var alvo = document.getElementById('subtotal-' + campo.getAttribute('data-id'));
            if (alvo) { alvo.textContent = formatador.format(sub / 100); }
        });
        var totalEl = document.getElementById('totalGeral');
        if (totalEl) { totalEl.textContent = formatador.format(total / 100); }
    }

    document.querySelectorAll('input[data-preco-centavos]').forEach(function (campo) {
        var temporizador;
        campo.addEventListener('input', recalcularCarrinho);
        campo.addEventListener('change', function () {
            recalcularCarrinho();
            clearTimeout(temporizador);
            temporizador = setTimeout(function () { campo.form.submit(); }, 350);
        });
    });

    // 3) Checkout: destaca a forma de pagamento escolhida e mostra a explicação
    var opcoes = document.querySelectorAll('input[name="metodo"]');
    function atualizarPagamento() {
        opcoes.forEach(function (op) {
            var cartao = op.closest('.opcao-pagamento');
            if (cartao) { cartao.classList.toggle('selecionada', op.checked); }
            var info = document.getElementById('info-' + op.value);
            if (info) { info.classList.toggle('d-none', !op.checked); }
        });
    }
    opcoes.forEach(function (op) { op.addEventListener('change', atualizarPagamento); });
    if (opcoes.length) { atualizarPagamento(); }

    // 4) Validação visual de formulários Bootstrap: <form class="needs-validation" novalidate>
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!form.checkValidity()) {
                ev.preventDefault();
                ev.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // 5) Evita envio duplo (ex.: clicar duas vezes em "Confirmar pagamento")
    document.querySelectorAll('form[data-uma-vez]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (ev.defaultPrevented) { return; }
            var botao = form.querySelector('button[type="submit"]');
            if (botao) {
                setTimeout(function () { botao.disabled = true; }, 0);
            }
        });
    });
})();
