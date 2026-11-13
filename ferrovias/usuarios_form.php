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
$voltar = 'usuarios.php';
require __DIR__ . '/includes/cabecalho.php';
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
</section>

<?php mostrarErros($erros); ?>

<form class="formulario formulario-usuario" method="POST" action="usuarios_form.php">
    <input type="hidden" name="id_usuario" value="<?= (int)$id_usuario ?>">

    <fieldset class="grupo grupo-dados-pessoais">
        <legend class="grupo-titulo">Dados pessoais</legend>
        <div class="campo">
            <label class="campo-rotulo" for="nome">Nome</label>
            <input class="campo-entrada" type="text" id="nome" name="nome" maxlength="80" value="<?= e($dados['nome']) ?>" required>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="login">Login</label>
            <?php if ($editando): ?>
                <input class="campo-entrada" type="text" id="login" value="<?= e($dados['login']) ?>" disabled>
                <small class="campo-ajuda">O login não pode ser alterado.</small>
            <?php else: ?>
                <input class="campo-entrada" type="text" id="login" name="login" maxlength="30" value="<?= e($dados['login']) ?>" autocomplete="off" required>
            <?php endif; ?>
        </div>
    </fieldset>

    <fieldset class="grupo grupo-cargo">
        <legend class="grupo-titulo">Cargo</legend>
        <div class="campo campo-opcoes">
            <div class="opcoes" role="radiogroup" aria-label="Cargo">
                <?php foreach (papeisSistema() as $valor => $rotulo): ?>
                    <label class="opcao cargo-<?= e($valor) ?>">
                        <input type="radio" name="papel" value="<?= e($valor) ?>" <?= $dados['papel'] === $valor ? 'checked' : '' ?> required>
                        <span class="opcao-texto"><?= e($rotulo) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </fieldset>

    <fieldset class="grupo grupo-acesso">
        <legend class="grupo-titulo">Acesso</legend>
        <div class="campo">
            <label class="campo-rotulo" for="senha"><?= $editando ? 'Nova senha' : 'Senha' ?></label>
            <input class="campo-entrada" type="password" id="senha" name="senha" autocomplete="new-password" <?= $editando ? '' : 'required' ?>>
            <small class="campo-ajuda"><?= $editando ? 'Deixe em branco para manter a senha atual. ' : '' ?>Mínimo de 6 caracteres.</small>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="senha2">Confirmar senha</label>
            <input class="campo-entrada" type="password" id="senha2" name="senha2" autocomplete="new-password" <?= $editando ? '' : 'required' ?>>
        </div>
    </fieldset>

    <div class="formulario-acoes">
        <button class="botao botao-primario botao-largo" type="submit"><?= $editando ? 'Salvar' : 'Adicionar' ?></button>
    </div>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
