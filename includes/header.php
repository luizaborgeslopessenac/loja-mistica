<?php
$usuario = usuario_logado();
$qtdCarrinho = carrinho_quantidade_total();
$tituloPagina = $tituloPagina ?? APP_NOME;
$buscaAtual = isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina) ?> | <?= e(APP_NOME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<a class="visually-hidden-focusable" href="#conteudo">Ir para o conteúdo</a>
<nav class="navbar navbar-expand-lg navbar-arcana sticky-top">
    <div class="container">
        <a class="navbar-brand marca" href="<?= e(url('index.php')) ?>"><span class="marca-lua" aria-hidden="true"></span><?= e(APP_NOME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <form class="d-flex my-3 my-lg-0 mx-lg-3 flex-grow-1 busca-nav" role="search" action="<?= e(url('index.php')) ?>" method="get">
                <input class="form-control" type="search" name="q" value="<?= e($buscaAtual) ?>" placeholder="Buscar cristais, tarô, velas..." maxlength="100" aria-label="Buscar produtos">
                <button class="btn btn-outline-ouro ms-2" type="submit">Buscar</button>
            </form>
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php')) ?>">Vitrine</a></li>
                <?php if ($usuario): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('meus_pedidos.php')) ?>">Meus pedidos</a></li>
                    <?php if ($usuario['perfil'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/index.php')) ?>">Painel admin</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('carrinho.php')) ?>">Carrinho
                        <span class="badge rounded-pill badge-carrinho" id="badgeCarrinho"><?= (int) $qtdCarrinho ?></span>
                    </a>
                </li>
                <?php if ($usuario): ?>
                    <li class="nav-item ms-lg-2">
                        <form action="<?= e(url('logout.php')) ?>" method="post" class="d-flex align-items-center gap-2">
                            <?= csrf_campo() ?>
                            <span class="small text-secondary">Olá, <?= e(explode(' ', $usuario['nome'])[0]) ?></span>
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Sair</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2"><a class="btn btn-sm btn-outline-ouro" href="<?= e(url('login.php')) ?>">Entrar</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-ouro" href="<?= e(url('cadastro.php')) ?>">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main id="conteudo">
<?php $flashes = obter_flashes(); if ($flashes): ?>
    <div class="container mt-3">
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
