<?php
// Deve ser a PRIMEIRA instrucao de toda pagina interna (antes de qualquer saida).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Impede que o navegador guarde a pagina em cache (botao voltar apos sair)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Confere no banco se o usuario continua ativo e atualiza nome/perfil na sessao.
// Assim, desativar ou mudar o perfil de alguem vale na proxima pagina que ele abrir.
require_once __DIR__ . '/../config/conexao.php';
$stmt = $conexao->prepare('SELECT nome, papel FROM usuarios WHERE id_usuario = ? AND ativo = 1');
$stmt->bind_param('i', $_SESSION['usuario_id']);
$stmt->execute();
$usuario_atual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario_atual) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['usuario_nome']  = $usuario_atual['nome'];
$_SESSION['usuario_papel'] = $usuario_atual['papel'];
