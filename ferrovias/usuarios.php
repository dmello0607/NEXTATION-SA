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
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<h1>Usuários</h1>
<p><a href="usuarios_form.php">Cadastrar novo usuário</a></p>

<form method="GET" action="usuarios.php">
    <input type="text" name="busca" placeholder="Nome ou login" value="<?= e($busca) ?>">
    <select name="papel">
        <option value="">Todos os perfis</option>
        <?php foreach ($papeis as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $papel === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="ativo">
        <option value="">Ativos e inativos</option>
        <option value="1" <?= $ativo === '1' ? 'selected' : '' ?>>Somente ativos</option>
        <option value="0" <?= $ativo === '0' ? 'selected' : '' ?>>Somente inativos</option>
    </select>
    <button type="submit">Filtrar</button>
    <a href="usuarios.php">Limpar</a>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p>Nenhum usuário encontrado.</p>
<?php else: ?>
<table>
    <thead><tr><th>Nome</th><th>Login</th><th>Perfil</th><th>Situação</th><th>Ações</th></tr></thead>
    <tbody>
    <?php while ($u = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= e($u['nome']) ?></td>
            <td><?= e($u['login']) ?></td>
            <td><?= e($papeis[$u['papel']] ?? $u['papel']) ?></td>
            <td class="<?= $u['ativo'] ? 'status-normal' : 'status-critico' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <a href="usuarios_form.php?id_usuario=<?= (int)$u['id_usuario'] ?>">Editar</a>
                <?php if ((int)$u['id_usuario'] !== (int)$_SESSION['usuario_id']): ?>
                <form method="POST" action="usuarios.php" style="display:inline" onsubmit="return confirm('Confirma a alteração da situação deste usuário?');">
                    <input type="hidden" name="alternar_id" value="<?= (int)$u['id_usuario'] ?>">
                    <button type="submit"><?= $u['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php endif; $stmt->close(); ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
