<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$papeis_validos = ['administrador' => 'Administrador', 'maquinista' => 'Maquinista', 'usuario_comum' => 'Usuário comum'];
$erros = [];
$id_usuario = 0;
$dados = ['nome' => '', 'login' => '', 'papel' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $editando   = $id_usuario > 0;
    $dados['nome']  = trim($_POST['nome'] ?? '');
    $dados['login'] = trim($_POST['login'] ?? '');
    $dados['papel'] = $_POST['papel'] ?? '';
    $senha  = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if ($editando) {
        // Na edicao o login nao muda: busca o atual no banco
        $stmt = $conexao->prepare('SELECT login FROM usuarios WHERE id_usuario = ?');
        $stmt->bind_param('i', $id_usuario);
        $stmt->execute();
        $atual = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$atual) {
            flash('Usuário não encontrado.', 'erro');
            header('Location: usuarios.php');
            exit;
        }
        $dados['login'] = $atual['login'];
    }

    if ($dados['nome'] === '' || strlen($dados['nome']) > 80) {
        $erros[] = 'Informe o nome (até 80 caracteres).';
    }
    if (!$editando && ($dados['login'] === '' || strlen($dados['login']) > 30)) {
        $erros[] = 'Informe o login (até 30 caracteres).';
    }
    if (!array_key_exists($dados['papel'], $papeis_validos)) {
        $erros[] = 'Selecione um perfil válido.';
    }
    // Ninguem rebaixa a propria conta: garante que sempre exista um administrador ativo
    if ($editando && $id_usuario === (int)$_SESSION['usuario_id'] && $dados['papel'] !== 'administrador') {
        $erros[] = 'Você não pode alterar o seu próprio perfil de administrador.';
    }
    // Senha: obrigatoria no cadastro; na edicao so troca se for preenchida
    if (!$editando || $senha !== '' || $senha2 !== '') {
        if (strlen($senha) < 6) {
            $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
        }
        if ($senha !== $senha2) {
            $erros[] = 'As senhas não conferem.';
        }
    }

    if (count($erros) === 0 && !$editando) {
        $stmt = $conexao->prepare('SELECT id_usuario FROM usuarios WHERE login = ?');
        $stmt->bind_param('s', $dados['login']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Este login já está cadastrado.';
        }
        $stmt->close();
    }

    if (count($erros) === 0) {
        if ($editando && $senha === '') {
            $stmt = $conexao->prepare('UPDATE usuarios SET nome = ?, papel = ? WHERE id_usuario = ?');
            $stmt->bind_param('ssi', $dados['nome'], $dados['papel'], $id_usuario);
        } elseif ($editando) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conexao->prepare('UPDATE usuarios SET nome = ?, papel = ?, senha = ? WHERE id_usuario = ?');
            $stmt->bind_param('sssi', $dados['nome'], $dados['papel'], $hash, $id_usuario);
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conexao->prepare('INSERT INTO usuarios (nome, login, senha, papel) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $dados['nome'], $dados['login'], $hash, $dados['papel']);
        }
        if ($stmt->execute()) {
            $stmt->close();
            flash($editando ? 'Usuário atualizado com sucesso.' : 'Usuário cadastrado com sucesso.');
            header('Location: usuarios.php');
            exit;
        }
        $stmt->close();
        $erros[] = 'Não foi possível gravar o usuário. Tente novamente.';
    }
} elseif (isset($_GET['id_usuario'])) {
    $id_usuario = (int)$_GET['id_usuario'];
    $stmt = $conexao->prepare('SELECT nome, login, papel FROM usuarios WHERE id_usuario = ?');
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$linha) {
        flash('Usuário não encontrado.', 'erro');
        header('Location: usuarios.php');
        exit;
    }
    $dados = $linha;
}

$editando = $id_usuario > 0;
$titulo = $editando ? 'Editar usuário' : 'Cadastrar usuário';
require __DIR__ . '/includes/cabecalho.php';
?>
<h1><?= e($titulo) ?></h1>
<?php foreach ($erros as $erro): ?><p class="erro"><?= e($erro) ?></p><?php endforeach; ?>
<form method="POST" action="usuarios_form.php">
    <input type="hidden" name="id_usuario" value="<?= (int)$id_usuario ?>">

    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" maxlength="80" value="<?= e($dados['nome']) ?>" required>

    <label for="login">Login</label>
    <?php if ($editando): ?>
        <input type="text" id="login" value="<?= e($dados['login']) ?>" disabled>
    <?php else: ?>
        <input type="text" id="login" name="login" maxlength="30" value="<?= e($dados['login']) ?>" required>
    <?php endif; ?>

    <label for="papel">Perfil</label>
    <select id="papel" name="papel" required>
        <option value="">Selecione</option>
        <?php foreach ($papeis_validos as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $dados['papel'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="senha"><?= $editando ? 'Nova senha (deixe em branco para manter a atual)' : 'Senha' ?></label>
    <input type="password" id="senha" name="senha" <?= $editando ? '' : 'required' ?>>

    <label for="senha2">Confirmar senha</label>
    <input type="password" id="senha2" name="senha2" <?= $editando ? '' : 'required' ?>>

    <button type="submit">Salvar</button>
    <a href="usuarios.php">Cancelar</a>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
