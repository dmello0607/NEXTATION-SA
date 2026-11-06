<?php
// 1. retomar a sessao
session_start();
// 2. limpar as variaveis
$_SESSION = [];
// 3. expirar o cookie da sessao no navegador
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
// 4. destruir a sessao no servidor
session_destroy();
// 5. redirecionar e encerrar
header('Location: index.php');
exit;
