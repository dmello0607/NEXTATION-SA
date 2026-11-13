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
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Sensores</h1>
</section>

<form class="filtros" method="GET" action="sensores.php" role="search">
    <div class="campo campo-busca">
        <label class="visualmente-oculto" for="busca">Buscar</label>
        <input class="campo-entrada" type="search" id="busca" name="busca" placeholder="Buscar por código, localização ou segmento..." value="<?= e($busca) ?>">
    </div>
    <div class="campo">
        <label class="visualmente-oculto" for="tipo">Tipo</label>
        <select class="campo-entrada" id="tipo" name="tipo">
            <option value="">Todos os tipos</option>
            <?php foreach (tiposSensor() as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $tipo === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label class="visualmente-oculto" for="id_trem">Trem</label>
        <select class="campo-entrada" id="id_trem" name="id_trem">
            <option value="0">Todos os trens</option>
            <?php while ($t = $lista_trens->fetch_assoc()): ?>
                <option value="<?= (int)$t['id_trem'] ?>" <?= $id_trem === (int)$t['id_trem'] ? 'selected' : '' ?>><?= e($t['prefixo']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="filtros-acoes">
        <button class="botao botao-secundario" type="submit">Filtrar</button>
        <a class="botao-link" href="sensores.php">Limpar</a>
    </div>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p class="estado-vazio">Nenhum sensor encontrado.</p>
<?php else: ?>
<table class="tabela tabela-cartoes tabela-sensores">
    <thead>
        <tr>
            <th scope="col">Código</th><th scope="col">Trem</th><th scope="col">Tipo</th><th scope="col">Localização</th>
            <th scope="col">Segmento</th><th scope="col">Última leitura</th><?php if ($eh_admin): ?><th scope="col">Ações</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php while ($s = $resultado->fetch_assoc()): ?>
        <?php
        if ($s['ult_valor'] === null) {
            $indicador = 'Sem leitura';
            $classe = 'sem-leitura';
        } else {
            $sev = classificarValor((float)$s['ult_valor'], $s['limite_max'] === null ? null : (float)$s['limite_max']);
            $indicador = number_format((float)$s['ult_valor'], 2, ',', '.') . ' ' . $s['ult_unidade'] . ' (' . rotulosSeveridade()[$sev] . ')';
            $classe = 'sev-' . $sev;
        }
        ?>
        <tr class="linha">
            <td class="celula-principal" data-label="Código"><?= e($s['codigo']) ?></td>
            <td data-label="Trem"><?= e($s['prefixo']) ?> - <?= e($s['modelo']) ?></td>
            <td data-label="Tipo"><?= e(tiposSensor()[$s['tipo']] ?? $s['tipo']) ?></td>
            <td data-label="Localização"><?= e($s['localizacao']) ?></td>
            <td data-label="Segmento"><?= e($s['segmento']) ?></td>
            <td class="<?= e($classe) ?>" data-label="Última leitura"><?= e($indicador) ?></td>
            <?php if ($eh_admin): ?>
            <td class="celula-acoes" data-label="Ações">
                <a class="botao botao-editar" href="sensores_form.php?id_sensor=<?= (int)$s['id_sensor'] ?>">Editar</a>
                <form class="form-linha" method="POST" action="sensores.php" onsubmit="return confirm('Confirma a exclusão? As leituras e alertas deste sensor também serão removidos.');">
                    <input type="hidden" name="excluir_id" value="<?= (int)$s['id_sensor'] ?>">
                    <button class="botao botao-perigo" type="submit">Excluir</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php endif; $stmt->close(); ?>

<?php if ($eh_admin): ?>
<div class="acoes-pagina">
    <a class="botao botao-primario botao-largo" href="sensores_form.php">Adicionar</a>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
