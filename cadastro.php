<?php
require_once __DIR__ . '/includes/init.php';

$destinos = destinos_login();
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? '');
if (!isset($destinos[$next]) || $next === 'admin') {
    $next = '';
}
if (esta_logado()) {
    redirecionar('index.php');
}

$erros = [];
$nome = '';
$email = '';
if (metodo_post()) {
    exigir_csrf();
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirma = (string) ($_POST['confirma'] ?? '');

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 120) {
        $erros[] = 'Informe seu nome (3 a 120 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if ($senha !== $confirma) {
        $erros[] = 'A confirmação não confere com a senha.';
    }

    if (!$erros) {
        $pdo = db();
        $existe = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $existe->execute(['email' => $email]);
        if ($existe->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado. Tente entrar na sua conta.';
        } else {
            try {
                // Perfil SEMPRE "cliente" no cadastro público; senha criptografada
                $ins = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, :perfil)');
                $ins->execute([
                    'nome'   => $nome,
                    'email'  => $email,
                    'hash'   => password_hash($senha, PASSWORD_DEFAULT),
                    'perfil' => 'cliente',
                ]);
                $novoId = (int) $pdo->lastInsertId();
                $carrinhoAnterior = carrinho_obter();
                fazer_login(['id' => $novoId, 'nome' => $nome, 'email' => $email, 'perfil' => 'cliente']);
                $_SESSION['carrinho'] = $carrinhoAnterior;
                flash('success', 'Conta criada com sucesso. Bem-vindo(a), ' . $nome . '!');
                redirecionar($next !== '' ? $destinos[$next] : 'index.php');
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $erros[] = 'Este e-mail já está cadastrado.';
                } else {
                    error_log('Erro no cadastro: ' . $ex->getMessage());
                    $erros[] = 'Não foi possível criar a conta agora. Tente novamente.';
                }
            }
        }
    }
}

$tituloPagina = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width: 520px;">
    <div class="painel">
        <h1 class="h2 mb-3">Criar conta</h1>
        <?php if ($erros): ?>
            <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('cadastro.php')) ?>" class="needs-validation" novalidate data-uma-vez>
            <?= csrf_campo() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="mb-3">
                <label class="form-label" for="nome">Nome completo</label>
                <input class="form-control" id="nome" name="nome" value="<?= e($nome) ?>" required minlength="3" maxlength="120" autocomplete="name">
                <div class="invalid-feedback">Informe seu nome.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="150" autocomplete="email">
                <div class="invalid-feedback">Informe um e-mail válido.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="senha">Senha</label>
                <input class="form-control" type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password">
                <div class="form-text">Mínimo de 8 caracteres.</div>
                <div class="invalid-feedback">A senha precisa ter 8 caracteres ou mais.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirma">Confirmar senha</label>
                <input class="form-control" type="password" id="confirma" name="confirma" required minlength="8" autocomplete="new-password">
                <div class="invalid-feedback">Repita a senha.</div>
            </div>
            <button class="btn btn-ouro w-100" type="submit">Criar conta</button>
        </form>
        <p class="small text-secondary mt-3 mb-0">Já tem conta? <a href="<?= e(url('login.php' . ($next !== '' ? '?next=' . urlencode($next) : ''))) ?>">Entrar</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
