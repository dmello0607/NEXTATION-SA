<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$papeis = ['administrador' => 'Administrador', 'maquinista' => 'Maquinista', 'usuario_comum' => 'Usuário comum'];

// Ativar/desativar (nao se exclui usuario: preserva o historico). Somente POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alternar_id'])) {
    $id = (int)$_POST['alternar_id'];
    if ($id === (int)$_SESSION['usuario_id']) {
        flash('Você não pode desativar a sua própria conta.', 'erro');
    } else {
        $stmt = $conexao->prepare('UPDATE usuarios SET ativo = 1 - ativo WHERE id_usuario = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            flash('Situação do usuário atualizada.');
        } else {
            flash('Usuário não encontrado.', 'erro');
        }
        $stmt->close();
    }
    header('Location: usuarios.php');
    exit;
}

$busca = trim($_GET['busca'] ?? '');
$papel = $_GET['papel'] ?? '';
$ativo = $_GET['ativo'] ?? '';

$condicoes = [];
$valores = [];
$tipos = '';
if ($busca !== '') {
    $condicoes[] = '(nome LIKE ? OR login LIKE ?)';
    $termo = '%' . $busca . '%';
    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'ss';
}
if ($papel !== '' && array_key_exists($papel, $papeis)) {
    $condicoes[] = 'papel = ?';
    $valores[] = $papel;
    $tipos .= 's';
}
if ($ativo === '1' || $ativo === '0') {
    $condicoes[] = 'ativo = ?';
    $valores[] = (int)$ativo;
    $tipos .= 'i';
}

$sql = 'SELECT id_usuario, nome, login, papel, ativo FROM usuarios';
if (count($condicoes) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY nome';
$stmt = $conexao->prepare($sql);
if (count($valores) > 0) {
    $stmt->bind_param($tipos, ...$valores);
}
$stmt->execute();
$resultado = $stmt->get_result();

$titulo = 'Usuários';
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Usuários</h1>
</section>

<form class="filtros" method="GET" action="usuarios.php" role="search">
    <div class="campo campo-busca">
        <label class="visualmente-oculto" for="busca">Buscar</label>
        <input class="campo-entrada" type="search" id="busca" name="busca" placeholder="Buscar por nome ou login..." value="<?= e($busca) ?>">
    </div>
    <div class="campo">
        <label class="visualmente-oculto" for="papel">Cargo</label>
        <select class="campo-entrada" id="papel" name="papel">
            <option value="">Todos os cargos</option>
            <?php foreach (papeisSistema() as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $papel === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label class="visualmente-oculto" for="ativo">Situação</label>
        <select class="campo-entrada" id="ativo" name="ativo">
            <option value="">Ativos e inativos</option>
            <option value="1" <?= $ativo === '1' ? 'selected' : '' ?>>Somente ativos</option>
            <option value="0" <?= $ativo === '0' ? 'selected' : '' ?>>Somente inativos</option>
        </select>
    </div>
    <div class="filtros-acoes">
        <button class="botao botao-secundario" type="submit">Filtrar</button>
        <a class="botao-link" href="usuarios.php">Limpar</a>
    </div>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p class="estado-vazio">Nenhum usuário encontrado.</p>
<?php else: ?>
<table class="tabela tabela-cartoes tabela-usuarios">
    <thead>
        <tr><th scope="col">Nome</th><th scope="col">Cargo</th><th scope="col">Login</th><th scope="col">Situação</th><th scope="col">Ações</th></tr>
    </thead>
    <tbody>
    <?php while ($u = $resultado->fetch_assoc()): ?>
        <tr class="linha<?= $u['ativo'] ? '' : ' linha-inativa' ?>">
            <td class="celula-principal" data-label="Nome"><span class="avatar" aria-hidden="true"></span><?= e($u['nome']) ?></td>
            <td class="celula-cargo cargo-<?= e($u['papel']) ?>" data-label="Cargo"><?= e(rotuloPapel($u['papel'])) ?></td>
            <td data-label="Login"><?= e($u['login']) ?></td>
            <td data-label="Situação"><span class="selo <?= $u['ativo'] ? 'status-normal' : 'status-critico' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
            <td class="celula-acoes" data-label="Ações">
                <a class="botao botao-editar" href="usuarios_form.php?id_usuario=<?= (int)$u['id_usuario'] ?>">Editar</a>
                <?php if ((int)$u['id_usuario'] !== (int)$_SESSION['usuario_id']): ?>
                <form class="form-linha" method="POST" action="usuarios.php" onsubmit="return confirm('Confirma a alteração da situação deste usuário?');">
                    <input type="hidden" name="alternar_id" value="<?= (int)$u['id_usuario'] ?>">
                    <button class="botao <?= $u['ativo'] ? 'botao-perigo' : 'botao-secundario' ?>" type="submit"><?= $u['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php endif; $stmt->close(); ?>

<div class="acoes-pagina">
    <a class="botao botao-primario botao-largo" href="usuarios_form.php">Adicionar</a>
</div>
<?php require __DIR__ . '/includes/rodape.php'; ?>
