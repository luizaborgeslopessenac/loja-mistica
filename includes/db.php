<?php
declare(strict_types=1);

/**
 * Conexão PDO (singleton). Credenciais vêm exclusivamente do .env.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host  = env('DB_HOST');
    $porta = env('DB_PORT', '3306');
    $nome  = env('DB_NAME');
    $user  = env('DB_USER');
    $pass  = env('DB_PASS');

    if ($host === null || $nome === null || $user === null || $pass === null) {
        http_response_code(500);
        exit('Configuração do banco ausente. Copie o arquivo .env.example para .env e preencha as credenciais.');
    }

    $dsn = 'mysql:host=' . $host . ';port=' . $porta . ';dbname=' . $nome . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $ex) {
        error_log('Falha na conexão com o banco: ' . $ex->getMessage());
        http_response_code(500);
        exit('Não foi possível conectar ao banco de dados. Verifique o .env e se o MySQL está ligado.');
    }
    return $pdo;
}
