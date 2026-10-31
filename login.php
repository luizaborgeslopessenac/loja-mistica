<?php
require_once __DIR__ . '/includes/init.php';

$destinos = destinos_login();
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? '');
if (!isset($destinos[$next])) {
    $next = '';
}
if (esta_logado()) {
    redirecionar($next !== '' ? $destinos[$next] : 'index.php');
}

$erro = '';
$email = '';
if (metodo_post()) {
    exigir_csrf();
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    // Freio simples contra tentativa de adivinhar senha
    $bloqueadoAte = (int) ($_SESSION['login_bloqueado_ate'] ?? 0);
    if ($bloqueadoAte > time()) {
        $erro = 'Muitas tentativas. Aguarde ' . ($bloqueadoAte - time()) . ' segundos e tente de novo.';
    } else {
        $st = db()->prepare('SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email');
        $st->execute(['email' => $email]);
        $u = $st->fetch();

        if ($u && password_verify($senha, $u['senha_hash'])) {
            if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) {
                $up = db()->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id');
                $up->execute(['h' => password_hash($senha, PASSWORD_DEFAULT), 'id' => $u['id']]);
            }
            $carrinhoAnterior = carrinho_obter(); // mantém o carrinho ao logar
            fazer_login($u);
            $_SESSION['carrinho'] = $carrinhoAnterior;
            unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueado_ate']);
            flash('success', 'Bem-vindo(a), ' . $u['nome'] . '!');
            if ($next !== '') {
                redirecionar($destinos[$next]);
            }
            redirecionar($u['perfil'] === 'admin' ? 'admin/index.php' : 'index.php');
        }

        $_SESSION['login_tentativas'] = (int) ($_SESSION['login_tentativas'] ?? 0) + 1;
        if ($_SESSION['login_tentativas'] >= 5) {
            $_SESSION['login_bloqueado_ate'] = time() + 60;
            $_SESSION['login_tentativas'] = 0;
        }
        $erro = 'E-mail ou senha incorretos.';
    }
}

$tituloPagina = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width: 460px;">
    <div class="painel">
        <h1 class="h2 mb-3">Entrar</h1>
        <?php if ($erro): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('login.php')) ?>" class="needs-validation" novalidate data-uma-vez>
            <?= csrf_campo() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="150" autocomplete="email">
                <div class="invalid-feedback">Informe um e-mail válido.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="senha">Senha</label>
                <input class="form-control" type="password" id="senha" name="senha" required autocomplete="current-password">
                <div class="invalid-feedback">Informe sua senha.</div>
            </div>
            <button class="btn btn-ouro w-100" type="submit">Entrar</button>
        </form>
        <p class="small text-secondary mt-3 mb-0">Ainda não tem conta? <a href="<?= e(url('cadastro.php' . ($next !== '' ? '?next=' . urlencode($next) : ''))) ?>">Cadastre-se</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
