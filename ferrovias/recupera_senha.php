<?php
session_start();
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$erros = [];
$sucesso = '';
$token_exibido = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'solicitar') {
        $login = trim($_POST['login'] ?? '');
        if ($login === '') {
            $erros[] = 'Informe o seu login.';
        } else {
            // Sempre mostra um token, exista o login ou nao: assim a tela nao revela quais logins existem.
            $token = bin2hex(random_bytes(16));
            $stmt = $conexao->prepare('SELECT id_usuario FROM usuarios WHERE login = ? AND ativo = 1');
            $stmt->bind_param('s', $login);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario) {
                $id_usuario = (int)$usuario['id_usuario'];
                // Invalida pedidos anteriores ainda abertos e grava so o HASH do novo token (valido por 30 minutos)
                $stmt = $conexao->prepare('UPDATE recuperacoes_senha SET usado = 1 WHERE id_usuario = ? AND usado = 0');
                $stmt->bind_param('i', $id_usuario);
                $stmt->execute();
                $stmt->close();

                $token_hash = hash('sha256', $token);
                $stmt = $conexao->prepare(
                    'INSERT INTO recuperacoes_senha (id_usuario, token_hash, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
                );
                $stmt->bind_param('is', $id_usuario, $token_hash);
                $stmt->execute();
                $stmt->close();
            }
            $token_exibido = $token;
            $sucesso = 'Pedido registrado. Use o código abaixo, válido por 30 minutos, para criar uma nova senha.';
        }
    } elseif ($acao === 'redefinir') {
        $token  = strtolower(trim($_POST['token'] ?? ''));
        $senha  = $_POST['senha'] ?? '';
        $senha2 = $_POST['senha2'] ?? '';

        if ($token === '') {
            $erros[] = 'Informe o código de recuperação.';
        }
        if (strlen($senha) < 6) {
            $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        if ($senha !== $senha2) {
            $erros[] = 'As senhas não conferem.';
        }

        if (count($erros) === 0) {
            $token_hash = hash('sha256', $token);
            $stmt = $conexao->prepare(
                'SELECT recuperacoes_senha.id_usuario
                 FROM recuperacoes_senha
                 INNER JOIN usuarios ON usuarios.id_usuario = recuperacoes_senha.id_usuario
                 WHERE recuperacoes_senha.token_hash = ? AND recuperacoes_senha.usado = 0
                   AND recuperacoes_senha.expira_em > NOW() AND usuarios.ativo = 1'
            );
            $stmt->bind_param('s', $token_hash);
            $stmt->execute();
            $linha = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$linha) {
                $erros[] = 'Código inválido ou expirado. Peça um novo código.';
            } else {
                $id_usuario = (int)$linha['id_usuario'];
                $novo_hash = password_hash($senha, PASSWORD_DEFAULT);
                $conexao->begin_transaction();
                $stmt1 = $conexao->prepare('UPDATE usuarios SET senha = ? WHERE id_usuario = ?');
                $stmt1->bind_param('si', $novo_hash, $id_usuario);
                $ok1 = $stmt1->execute();
                $stmt1->close();
                $stmt2 = $conexao->prepare('UPDATE recuperacoes_senha SET usado = 1 WHERE id_usuario = ? AND usado = 0');
                $stmt2->bind_param('i', $id_usuario);
                $ok2 = $stmt2->execute();
                $stmt2->close();
                if ($ok1 && $ok2) {
                    $conexao->commit();
                    $sucesso = 'Senha redefinida com sucesso. Você já pode entrar com a nova senha.';
                } else {
                    $conexao->rollback();
                    $erros[] = 'Não foi possível redefinir a senha. Tente novamente.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha - Nextation</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body class="pagina pagina-recupera_senha pagina-publica">
<main class="tela-entrada">

    <header class="entrada-marca">
        <img class="marca-logo" src="assets/img/logo.png" alt="Logo do Nextation">
        <p class="marca-nome">Nextation</p>
    </header>

    <section class="cartao-formulario" aria-labelledby="titulo-recuperar">
        <h1 id="titulo-recuperar" class="titulo-pagina">Recuperar senha</h1>

        <?php mostrarSucesso($sucesso); ?>
        <?php mostrarErros($erros); ?>

        <?php if ($token_exibido !== ''): ?>
        <div class="codigo-recuperacao">
            <p class="codigo-rotulo">Código de recuperação</p>
            <p class="codigo-valor"><strong><?= e($token_exibido) ?></strong></p>
            <p class="campo-ajuda">Neste projeto o envio de e-mail é simulado: em um sistema real, este código chegaria por e-mail.</p>
        </div>
        <?php endif; ?>

        <section class="formulario-etapa" aria-labelledby="etapa-1">
            <h2 id="etapa-1" class="subtitulo-secao">1. Pedir um código</h2>
            <form class="formulario" method="POST" action="recupera_senha.php">
                <input type="hidden" name="acao" value="solicitar">
                <div class="campo">
                    <label class="campo-rotulo" for="login">Login</label>
                    <input class="campo-entrada" type="text" id="login" name="login" value="<?= e($login) ?>" autocomplete="username" required>
                </div>
                <div class="formulario-acoes">
                    <button class="botao botao-primario" type="submit">Gerar código</button>
                </div>
            </form>
        </section>

        <section class="formulario-etapa" aria-labelledby="etapa-2">
            <h2 id="etapa-2" class="subtitulo-secao">2. Criar a nova senha</h2>
            <form class="formulario" method="POST" action="recupera_senha.php">
                <input type="hidden" name="acao" value="redefinir">
                <div class="campo">
                    <label class="campo-rotulo" for="token">Código de recuperação</label>
                    <input class="campo-entrada" type="text" id="token" name="token" autocomplete="off" required>
                </div>
                <div class="campo">
                    <label class="campo-rotulo" for="senha">Nova senha</label>
                    <input class="campo-entrada" type="password" id="senha" name="senha" autocomplete="new-password" required>
                    <small class="campo-ajuda">Mínimo de 6 caracteres.</small>
                </div>
                <div class="campo">
                    <label class="campo-rotulo" for="senha2">Confirmar nova senha</label>
                    <input class="campo-entrada" type="password" id="senha2" name="senha2" autocomplete="new-password" required>
                </div>
                <div class="formulario-acoes">
                    <button class="botao botao-primario" type="submit">Redefinir senha</button>
                </div>
            </form>
        </section>

        <p class="links-auxiliares"><a href="index.php">Voltar ao login</a></p>
    </section>

</main>
</body>
</html>
