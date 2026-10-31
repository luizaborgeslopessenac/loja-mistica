<?php
require_once __DIR__ . '/includes/init.php';

if (!metodo_post()) {
    redirecionar('index.php');
}
exigir_csrf();
fazer_logout();
flash('success', 'Você saiu da sua conta.');
redirecionar('index.php');
