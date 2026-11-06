<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador', 'maquinista']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

$eh_admin = temPapel(['administrador']);

// Exclusao: somente administrador, somente POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    exigirPapel(['administrador']);
    $id = (int)$_POST['excluir_id'];
    $stmt = $conexao->prepare('DELETE FROM sensores WHERE id_sensor = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        flash('Sensor excluído com sucesso (leituras e alertas dele também foram removidos).');
    } else {
        flash('Não foi possível excluir o sensor.', 'erro');
    }
    $stmt->close();
    header('Location: sensores.php');
    exit;
}

// Filtros: tipo, trem e busca por codigo (todos opcionais)
$tipo    = $_GET['tipo'] ?? '';
$id_trem = (int)($_GET['id_trem'] ?? 0);
$busca   = trim($_GET['busca'] ?? '');
$condicoes = [];
$valores = [];
$tipos = '';

if ($tipo !== '' && array_key_exists($tipo, tiposSensor())) {
    $condicoes[] = 'sensores.tipo = ?';
    $valores[] = $tipo;
    $tipos .= 's';
}
if ($id_trem > 0) {
    $condicoes[] = 'sensores.id_trem = ?';
    $valores[] = $id_trem;
    $tipos .= 'i';
}
if ($busca !== '') {
    $condicoes[] = '(sensores.codigo LIKE ? OR sensores.localizacao LIKE ? OR sensores.segmento LIKE ?)';
    $termo = '%' . $busca . '%';
    $valores[] = $termo;
    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'sss';
}

// LEFT JOIN: sensor sem leitura continua na lista. A ultima leitura vem de subconsulta.
$sql = 'SELECT sensores.*, trens.prefixo, trens.modelo,
               ult.valor AS ult_valor, ult.unidade AS ult_unidade, ult.data_hora AS ult_data,
               limites.limite_max
        FROM sensores
        INNER JOIN trens ON trens.id_trem = sensores.id_trem
        LEFT JOIN leituras ult ON ult.id_leitura = (
            SELECT l2.id_leitura FROM leituras l2
            WHERE l2.id_sensor = sensores.id_sensor
            ORDER BY l2.data_hora DESC, l2.id_leitura DESC LIMIT 1)
        LEFT JOIN limites ON limites.tipo = sensores.tipo';
if (count($condicoes) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' ORDER BY trens.prefixo, sensores.codigo';

$stmt = $conexao->prepare($sql);
if (count($valores) > 0) {
    $stmt->bind_param($tipos, ...$valores);
}
$stmt->execute();
$resultado = $stmt->get_result();
$lista_trens = $conexao->query('SELECT id_trem, prefixo FROM trens ORDER BY prefixo');

$titulo = 'Sensores';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<h1>Sensores</h1>
<?php if ($eh_admin): ?><p><a href="sensores_form.php">Cadastrar novo sensor</a></p><?php endif; ?>

<form method="GET" action="sensores.php">
    <input type="text" name="busca" placeholder="Código, localização ou segmento" value="<?= e($busca) ?>">
    <select name="tipo">
        <option value="">Todos os tipos</option>
        <?php foreach (tiposSensor() as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $tipo === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="id_trem">
        <option value="0">Todos os trens</option>
        <?php while ($t = $lista_trens->fetch_assoc()): ?>
            <option value="<?= (int)$t['id_trem'] ?>" <?= $id_trem === (int)$t['id_trem'] ? 'selected' : '' ?>><?= e($t['prefixo']) ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit">Filtrar</button>
    <a href="sensores.php">Limpar</a>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p>Nenhum sensor encontrado.</p>
<?php else: ?>
<table>
    <thead>
        <tr><th>Código</th><th>Trem</th><th>Tipo</th><th>Localização</th><th>Segmento</th><th>Última leitura</th><?php if ($eh_admin): ?><th>Ações</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php while ($s = $resultado->fetch_assoc()): ?>
        <?php
        if ($s['ult_valor'] === null) {
            $indicador = 'Sem leitura';
            $classe = '';
        } else {
            $sev = classificarValor((float)$s['ult_valor'], $s['limite_max'] === null ? null : (float)$s['limite_max']);
            $indicador = number_format((float)$s['ult_valor'], 2, ',', '.') . ' ' . $s['ult_unidade'] . ' (' . $sev . ')';
            $classe = 'sev-' . $sev;
        }
        ?>
        <tr>
            <td><?= e($s['codigo']) ?></td>
            <td><?= e($s['prefixo']) ?> - <?= e($s['modelo']) ?></td>
            <td><?= e(tiposSensor()[$s['tipo']] ?? $s['tipo']) ?></td>
            <td><?= e($s['localizacao']) ?></td>
            <td><?= e($s['segmento']) ?></td>
            <td class="<?= e($classe) ?>"><?= e($indicador) ?></td>
            <?php if ($eh_admin): ?>
            <td>
                <a href="sensores_form.php?id_sensor=<?= (int)$s['id_sensor'] ?>">Editar</a>
                <form method="POST" action="sensores.php" style="display:inline" onsubmit="return confirm('Confirma a exclusão? As leituras e alertas deste sensor também serão removidos.');">
                    <input type="hidden" name="excluir_id" value="<?= (int)$s['id_sensor'] ?>">
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
