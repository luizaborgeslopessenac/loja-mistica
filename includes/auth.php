<?php
declare(strict_types=1);

function usuario_logado(): ?array
{
    return isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

function esta_logado(): bool
{
    return usuario_logado() !== null;
}

function fazer_login(array $usuario): void
{
    session_regenerate_id(true); // evita fixação de sessão
    $_SESSION['usuario'] = [
        'id'     => (int) $usuario['id'],
        'nome'   => (string) $usuario['nome'],
        'email'  => (string) $usuario['email'],
        'perfil' => (string) $usuario['perfil'],
    ];
}

function fazer_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Destinos permitidos após o login (lista fechada = sem open redirect). */
function destinos_login(): array
{
    return [
        'checkout'     => 'checkout.php',
        'carrinho'     => 'carrinho.php',
        'meus_pedidos' => 'meus_pedidos.php',
        'admin'        => 'admin/index.php',
    ];
}

/** Restringe a página a usuários autenticados (validação no servidor). */
function exigir_login(string $destino = ''): void
{
    if (!esta_logado()) {
        flash('warning', 'Entre na sua conta ou cadastre-se para continuar.');
        redirecionar('login.php' . ($destino !== '' ? '?next=' . urlencode($destino) : ''));
    }
}

/**
 * Restringe o painel administrativo. Confere a sessão E o perfil atual no banco,
 * assim um admin rebaixado perde o acesso imediatamente.
 */
function exigir_admin(): void
{
    if (!esta_logado()) {
        flash('warning', 'Faça login como administrador para acessar o painel.');
        redirecionar('login.php?next=admin');
    }
    $st = db()->prepare('SELECT perfil FROM usuarios WHERE id = :id');
    $st->execute(['id' => usuario_logado()['id']]);
    $linha = $st->fetch();
    if (!$linha || $linha['perfil'] !== 'admin') {
        pagina_erro(403, 'Acesso negado', 'Esta área é exclusiva para administradores.');
    }
}

function eh_admin(): bool
{
    $u = usuario_logado();
    return $u !== null && $u['perfil'] === 'admin';
}
