<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

// Exclusao: apenas por POST, com papel verificado antes do DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $id = (int)$_POST['excluir_id'];
    $stmt = $conexao->prepare('DELETE FROM trens WHERE id_trem = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            flash('Trem excluído com sucesso.');
        } else {
            flash('Trem não encontrado.', 'erro');
        }
    } elseif ($conexao->errno === 1451) {
        flash('Não é possível excluir: o trem possui sensores ou rotas vinculados.', 'erro');
    } else {
        flash('Não foi possível excluir o trem.', 'erro');
    }
    $stmt->close();
    header('Location: trens.php');
    exit;
}

// Busca e filtro por status (condicoes acumuladas, valores sempre via bind_param)
$busca  = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$condicoes = [];
$valores = [];
$tipos = '';

if ($busca !== '') {
    $condicoes[] = '(trens.prefixo LIKE ? OR trens.modelo LIKE ?)';
    $termo = '%' . $busca . '%';
    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'ss';
}
if ($status !== '' && array_key_exists($status, statusTrens())) {
    $condicoes[] = 'trens.status = ?';
    $valores[] = $status;
    $tipos .= 's';
}

$sql = 'SELECT trens.*, COUNT(sensores.id_sensor) AS qtd_sensores
        FROM trens
        LEFT JOIN sensores ON sensores.id_trem = trens.id_trem';
if (count($condicoes) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' GROUP BY trens.id_trem ORDER BY trens.prefixo';

$stmt = $conexao->prepare($sql);
if (count($valores) > 0) {
    $stmt->bind_param($tipos, ...$valores);
}
$stmt->execute();
$resultado = $stmt->get_result();

$titulo = 'Trens';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<h1>Trens</h1>
<p><a href="trens_form.php">Cadastrar novo trem</a></p>

<form method="GET" action="trens.php">
    <input type="text" name="busca" placeholder="Prefixo ou modelo" value="<?= e($busca) ?>">
    <select name="status">
        <option value="">Todos os status</option>
        <?php foreach (statusTrens() as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $status === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
    <a href="trens.php">Limpar</a>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p>Nenhum trem encontrado.</p>
<?php else: ?>
<table>
    <thead>
        <tr><th>Prefixo</th><th>Modelo</th><th>Ano</th><th>Status</th><th>Capacidade (t)</th><th>Última inspeção</th><th>Sensores</th><th>Ações</th></tr>
    </thead>
    <tbody>
    <?php while ($trem = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= e($trem['prefixo']) ?></td>
            <td><?= e($trem['modelo']) ?></td>
            <td><?= e($trem['ano']) ?></td>
            <td class="<?= e(classeStatusTrem($trem['status'])) ?>"><?= e(statusTrens()[$trem['status']] ?? $trem['status']) ?></td>
            <td><?= e(number_format((float)$trem['capacidade'], 2, ',', '.')) ?></td>
            <td><?= $trem['ultima_inspecao'] ? e(date('d/m/Y', strtotime($trem['ultima_inspecao']))) : 'Sem inspeção' ?></td>
            <td><?= e($trem['qtd_sensores']) ?></td>
            <td>
                <a href="trens_form.php?id_trem=<?= (int)$trem['id_trem'] ?>">Editar</a>
                <form method="POST" action="trens.php" style="display:inline" onsubmit="return confirm('Confirma a exclusão?');">
                    <input type="hidden" name="excluir_id" value="<?= (int)$trem['id_trem'] ?>">
                    <button type="submit">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php endif; $stmt->close(); ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
