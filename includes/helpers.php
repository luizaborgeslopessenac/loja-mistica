<?php
declare(strict_types=1);

/** Escapa texto para HTML (prevenção de XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

function metodo_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/* ---------- Dinheiro (cálculos em centavos para evitar erro de ponto flutuante) ---------- */
function para_centavos($valor): int
{
    return (int) round(((float) $valor) * 100);
}

function moeda_centavos(int $centavos): string
{
    return 'R$ ' . number_format($centavos / 100, 2, ',', '.');
}

function moeda($valor): string
{
    return moeda_centavos(para_centavos($valor));
}

function decimal_sql(int $centavos): string
{
    return number_format($centavos / 100, 2, '.', '');
}

/* ---------- Mensagens (flash) ---------- */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flashes'][] = ['tipo' => $tipo, 'msg' => $mensagem];
}

function obter_flashes(): array
{
    $f = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($f) ? $f : [];
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function exigir_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessão expirada ou requisição inválida. Volte à página anterior, recarregue e tente de novo.');
    }
}

/* ---------- Produtos ---------- */
function imagem_url(?string $caminho): string
{
    if ($caminho === null || $caminho === '') {
        return url('assets/img/placeholder.svg');
    }
    return url($caminho);
}

/** Indicador de disponibilidade de estoque: [classe bootstrap, texto]. */
function estoque_info(int $estoque): array
{
    if ($estoque <= 0) {
        return ['danger', 'Esgotado'];
    }
    if ($estoque <= 5) {
        return ['warning', 'Últimas ' . $estoque . ' unidade' . ($estoque > 1 ? 's' : '')];
    }
    return ['success', 'Em estoque (' . $estoque . ' un.)'];
}

function rotulo_pagamento(string $metodo): string
{
    $mapa = ['pix' => 'PIX', 'boleto' => 'Boleto', 'cartao' => 'Cartão'];
    return $mapa[$metodo] ?? $metodo;
}

function formatar_data(string $data): string
{
    $t = strtotime($data);
    return $t === false ? $data : date('d/m/Y H:i', $t);
}

/** Exibe uma página de erro amigável usando o layout padrão. */
function pagina_erro(int $codigo, string $titulo, string $mensagem): void
{
    http_response_code($codigo);
    $tituloPagina = $titulo;
    require RAIZ . '/includes/header.php';
    echo '<div class="container py-5 text-center">';
    echo '<h1 class="display-5 titulo-arcano">' . e($titulo) . '</h1>';
    echo '<p class="lead text-secondary">' . e($mensagem) . '</p>';
    echo '<a class="btn btn-ouro" href="' . e(url('index.php')) . '">Voltar à vitrine</a>';
    echo '</div>';
    require RAIZ . '/includes/footer.php';
    exit;
}

/* ---------- Upload de imagem de produto (painel admin) ---------- */
function salvar_upload_imagem(array $arquivo): ?string
{
    $erro = $arquivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($erro === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($erro !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem. Tente novamente.');
    }
    if (($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 2 MB.');
    }
    $tmp = (string) $arquivo['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Arquivo de imagem inválido.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($permitidos[$mime]) || @getimagesize($tmp) === false) {
        throw new RuntimeException('Formato inválido. Envie JPG, PNG, WEBP ou GIF.');
    }
    $nome = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
    $pasta = RAIZ . '/uploads/produtos';
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
        throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    }
    if (!move_uploaded_file($tmp, $pasta . '/' . $nome)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return 'uploads/produtos/' . $nome;
}

/** Apaga o arquivo físico apenas se ele estiver dentro de uploads/produtos. */
function remover_imagem_upload(?string $caminho): void
{
    if ($caminho === null || strpos($caminho, 'uploads/produtos/') !== 0) {
        return;
    }
    $arquivo = RAIZ . '/uploads/produtos/' . basename($caminho);
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}
