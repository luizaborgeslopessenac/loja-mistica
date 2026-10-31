<?php
declare(strict_types=1);

/**
 * Inicialização comum a todas as páginas: .env, sessão segura e funções.
 */
define('RAIZ', dirname(__DIR__));

require_once RAIZ . '/config/env.php';
carregar_env(RAIZ . '/.env');

date_default_timezone_set('America/Sao_Paulo');

$ambiente = env('APP_ENV', 'production');
error_reporting(E_ALL);
ini_set('display_errors', $ambiente === 'development' ? '1' : '0');
ini_set('log_errors', '1');

define('BASE_URL', rtrim((string) env('APP_BASE_URL', ''), '/'));
define('APP_NOME', 'Arcana Lunar');

// Cabeçalhos básicos de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Sessão com cookie protegido
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ARCANA_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once RAIZ . '/includes/helpers.php';
require_once RAIZ . '/includes/db.php';
require_once RAIZ . '/includes/auth.php';
require_once RAIZ . '/includes/cart.php';
require_once RAIZ . '/includes/pedidos.php';
