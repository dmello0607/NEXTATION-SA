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
    <title>Entrar - Nextation</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body class="pagina pagina-index pagina-publica">
<main class="tela-entrada">

    <header class="entrada-marca">
        <img class="marca-logo" src="assets/img/logo.png" alt="Logo do Nextation">
        <p class="marca-nome">Nextation</p>
    </header>

    <section class="cartao-formulario" aria-labelledby="titulo-entrar">
        <h1 id="titulo-entrar" class="titulo-pagina">Entrar</h1>

        <?php if ($erro !== ''): ?>
            <div class="mensagens" role="alert"><p class="mensagem mensagem-erro"><?= e($erro) ?></p></div>
        <?php endif; ?>

        <form class="formulario" method="POST" action="index.php">
            <div class="campo">
                <label class="campo-rotulo" for="login">Login</label>
                <input class="campo-entrada" type="text" id="login" name="login" value="<?= e($login) ?>" autocomplete="username" required>
            </div>
            <div class="campo">
                <label class="campo-rotulo" for="senha">Senha</label>
                <input class="campo-entrada" type="password" id="senha" name="senha" autocomplete="current-password" required>
            </div>
            <div class="formulario-acoes">
                <button class="botao botao-primario" type="submit">Entrar</button>
            </div>
        </form>

        <p class="links-auxiliares"><a href="recupera_senha.php">Esqueci minha senha</a></p>
    </section>

</main>
</body>
</html>
