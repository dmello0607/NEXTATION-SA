<?php
session_start();
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

// Quem ja esta logado vai direto ao painel
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha_digitada = $_POST['senha'] ?? '';

    if ($login === '' || $senha_digitada === '') {
        $erro = 'Informe login e senha.';
    } else {
        $stmt = $conexao->prepare(
            'SELECT id_usuario, nome, login, senha, papel FROM usuarios WHERE login = ? AND ativo = 1'
        );
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Mesma mensagem para login inexistente e senha errada
        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']    = $usuario['id_usuario'];
            $_SESSION['usuario_login'] = $usuario['login'];
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['usuario_papel'] = $usuario['papel'];
            header('Location: dashboard.php');
            exit;
        }
        $erro = 'Login ou senha invalidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistema Ferroviario</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
<main>
    <h1>Sistema de Monitoramento Ferroviario</h1>
    <?php if ($erro !== ''): ?>
        <p class="erro"><?= e($erro) ?></p>
    <?php endif; ?>
    <form method="POST" action="index.php">
        <label for="login">Login</label>
        <input type="text" id="login" name="login" value="<?= e($login) ?>" required>
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
        <button type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
