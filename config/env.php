<?php
declare(strict_types=1);

/**
 * Leitor simples de arquivo .env (sem dependências externas, ideal para XAMPP).
 * Formato: CHAVE=valor | linhas iniciadas por # são comentários.
 */
function carregar_env(string $caminho): void
{
    if (!is_readable($caminho)) {
        return;
    }
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($linhas === false) {
        return;
    }
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
            continue;
        }
        [$chave, $valor] = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);
        $tam = strlen($valor);
        if ($tam >= 2 && (($valor[0] === '"' && $valor[$tam - 1] === '"') || ($valor[0] === "'" && $valor[$tam - 1] === "'"))) {
            $valor = substr($valor, 1, -1);
        }
        if ($chave !== '') {
            $_ENV[$chave] = $valor;
        }
    }
}

function env(string $chave, ?string $padrao = null): ?string
{
    if (array_key_exists($chave, $_ENV)) {
        return (string) $_ENV[$chave];
    }
    $valor = getenv($chave);
    return $valor === false ? $padrao : $valor;
}
