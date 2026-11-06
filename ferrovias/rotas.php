<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador', 'maquinista']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$eh_admin = temPapel(['administrador']);

// Exclusao: somente administrador, somente POST. Mantem os filtros ao voltar.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    exigirPapel(['administrador']);
    $id = (int)$_POST['excluir_id'];
    $stmt = $conexao->prepare('DELETE FROM rotas WHERE id_rota = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        flash('Rota excluída com sucesso.');
    } else {
        flash('Não foi possível excluir a rota.', 'erro');
    }
    $stmt->close();
    $volta = filtrosRotasQuery($_POST['volta'] ?? '');
    header('Location: rotas.php' . ($volta !== '' ? '?' . $volta : ''));
    exit;
}

// Quatro filtros opcionais: status, data (periodo), trem e busca por origem/destino
$status      = $_GET['status'] ?? '';
$id_trem     = (int)($_GET['id_trem'] ?? 0);
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim'] ?? '';
$busca       = trim($_GET['busca'] ?? '');

$condicoes = [];
$valores = [];
$tipos = '';

if ($status !== '' && array_key_exists($status, statusRotas())) {
    $condicoes[] = 'rotas.status = ?';
    $valores[] = $status;
    $tipos .= 's';
}
if ($id_trem > 0) {
    $condicoes[] = 'rotas.id_trem = ?';
    $valores[] = $id_trem;
    $tipos .= 'i';
}
if ($data_inicio !== '' && dataValida($data_inicio)) {
    $condicoes[] = 'rotas.data_viagem >= ?';
    $valores[] = $data_inicio;
    $tipos .= 's';
}
if ($data_fim !== '' && dataValida($data_fim)) {
    $condicoes[] = 'rotas.data_viagem <= ?';
    $valores[] = $data_fim;
    $tipos .= 's';
}
if ($busca !== '') {
    $condicoes[] = '(rotas.origem LIKE ? OR rotas.destino LIKE ?)';
    $termo = '%' . $busca . '%';
    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'ss';
}

$sql = 'SELECT rotas.*, trens.prefixo, trens.modelo
        FROM rotas
        INNER JOIN trens ON trens.id_trem = rotas.id_trem';
if (count($condicoes) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY rotas.data_viagem DESC, rotas.hora_partida';

$stmt = $conexao->prepare($sql);
if (count($valores) > 0) {
    $stmt->bind_param($tipos, ...$valores);
}
$stmt->execute();
$resultado = $stmt->get_result();
$lista_trens = $conexao->query('SELECT id_trem, prefixo FROM trens ORDER BY prefixo');

// Filtros atuais (para os links de editar/excluir devolverem o usuario ao mesmo ponto)
$qs = filtrosRotasQuery($_GET);

$titulo = 'Rotas e viagens programadas';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<h1>Rotas e viagens programadas</h1>
<?php if ($eh_admin): ?><p><a href="rotas_form.php<?= $qs !== '' ? '?volta=' . urlencode($qs) : '' ?>">Cadastrar nova rota</a></p><?php endif; ?>

<form method="GET" action="rotas.php">
    <select name="status">
        <option value="">Todos os status</option>
        <?php foreach (statusRotas() as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $status === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="id_trem">
        <option value="0">Todos os trens</option>
        <?php while ($t = $lista_trens->fetch_assoc()): ?>
            <option value="<?= (int)$t['id_trem'] ?>" <?= $id_trem === (int)$t['id_trem'] ? 'selected' : '' ?>><?= e($t['prefixo']) ?></option>
        <?php endwhile; ?>
    </select>
    <label for="data_inicio">De</label>
    <input type="date" id="data_inicio" name="data_inicio" value="<?= e($data_inicio) ?>">
    <label for="data_fim">Até</label>
    <input type="date" id="data_fim" name="data_fim" value="<?= e($data_fim) ?>">
    <input type="text" name="busca" placeholder="Origem ou destino" value="<?= e($busca) ?>">
    <button type="submit">Filtrar</button>
    <a href="rotas.php">Limpar</a>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p>Nenhuma rota encontrada<?= $qs !== '' ? ' para os filtros selecionados' : '' ?>.</p>
<?php else: ?>
<table>
    <thead>
        <tr><th>Trem</th><th>Origem</th><th>Destino</th><th>Data</th><th>Partida</th><th>Chegada</th><th>Status</th><?php if ($eh_admin): ?><th>Ações</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php while ($r = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= e($r['prefixo']) ?> - <?= e($r['modelo']) ?></td>
            <td><?= e($r['origem']) ?></td>
            <td><?= e($r['destino']) ?></td>
            <td><?= e(date('d/m/Y', strtotime($r['data_viagem']))) ?></td>
            <td><?= e(substr($r['hora_partida'], 0, 5)) ?></td>
            <td><?= $r['hora_chegada'] ? e(substr($r['hora_chegada'], 0, 5)) : 'A definir' ?></td>
            <td><?= e(statusRotas()[$r['status']] ?? $r['status']) ?></td>
            <?php if ($eh_admin): ?>
            <td>
                <a href="rotas_form.php?id_rota=<?= (int)$r['id_rota'] ?><?= $qs !== '' ? '&volta=' . urlencode($qs) : '' ?>">Editar</a>
                <form method="POST" action="rotas.php" style="display:inline" onsubmit="return confirm('Confirma a exclusão?');">
                    <input type="hidden" name="excluir_id" value="<?= (int)$r['id_rota'] ?>">
                    <input type="hidden" name="volta" value="<?= e($qs) ?>">
                    <button type="submit">Excluir</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php endif; $stmt->close(); ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
