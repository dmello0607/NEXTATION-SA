<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador', 'maquinista']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

const POR_PAGINA = 50;

$id_sensor = (int)($_GET['id_sensor'] ?? 0);
$id_trem   = (int)($_GET['id_trem'] ?? 0);
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));

$condicoes = [];
$valores = [];
$tipos = '';
if ($id_sensor > 0) {
    $condicoes[] = 'leituras.id_sensor = ?';
    $valores[] = $id_sensor;
    $tipos .= 'i';
}
if ($id_trem > 0) {
    $condicoes[] = 'sensores.id_trem = ?';
    $valores[] = $id_trem;
    $tipos .= 'i';
}
$where = count($condicoes) > 0 ? ' WHERE ' . implode(' AND ', $condicoes) : '';

$from = ' FROM leituras
          INNER JOIN sensores ON sensores.id_sensor = leituras.id_sensor
          INNER JOIN trens ON trens.id_trem = sensores.id_trem
          LEFT JOIN limites ON limites.tipo = sensores.tipo';

// Total (para a paginacao)
$stmt = $conexao->prepare('SELECT COUNT(*) AS total' . $from . $where);
if (count($valores) > 0) {
    $stmt->bind_param($tipos, ...$valores);
}
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_paginas = max(1, (int)ceil($total / POR_PAGINA));
$pagina = min($pagina, $total_paginas);
$offset = ($pagina - 1) * POR_PAGINA;

// A leitura chega ao trem atraves do sensor (JOIN encadeado). LIMIT evita carregar milhares de linhas.
$sql = 'SELECT leituras.*, sensores.codigo, sensores.tipo, trens.prefixo, limites.limite_max'
     . $from . $where
     . ' ORDER BY leituras.data_hora DESC, leituras.id_leitura DESC LIMIT ? OFFSET ?';
$valores_pg = array_merge($valores, [POR_PAGINA, $offset]);
$stmt = $conexao->prepare($sql);
$stmt->bind_param($tipos . 'ii', ...$valores_pg);
$stmt->execute();
$resultado = $stmt->get_result();

$lista_sensores = $conexao->query(
    'SELECT sensores.id_sensor, sensores.codigo, trens.prefixo FROM sensores
     INNER JOIN trens ON trens.id_trem = sensores.id_trem ORDER BY trens.prefixo, sensores.codigo'
);
$lista_trens = $conexao->query('SELECT id_trem, prefixo FROM trens ORDER BY prefixo');

$filtros = array_filter(['id_sensor' => $id_sensor, 'id_trem' => $id_trem]);

$titulo = 'Leituras dos sensores';
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Leituras dos sensores</h1>
</section>

<form class="filtros" method="GET" action="leituras.php" role="search">
    <div class="campo">
        <label class="visualmente-oculto" for="id_sensor">Sensor</label>
        <select class="campo-entrada" id="id_sensor" name="id_sensor">
            <option value="0">Todos os sensores</option>
            <?php while ($s = $lista_sensores->fetch_assoc()): ?>
                <option value="<?= (int)$s['id_sensor'] ?>" <?= $id_sensor === (int)$s['id_sensor'] ? 'selected' : '' ?>><?= e($s['codigo']) ?> (<?= e($s['prefixo']) ?>)</option>
            <?php endwhile; ?>
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
        <a class="botao-link" href="leituras.php">Limpar</a>
    </div>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p class="estado-vazio"><?= count($filtros) > 0 ? 'Nenhuma leitura encontrada para os filtros selecionados.' : 'Nenhuma leitura registrada ainda. Use o simulador para gerar leituras.' ?></p>
<?php else: ?>
<p class="total-resultados"><strong><?= (int)$total ?></strong> leitura(s) encontrada(s).</p>
<table class="tabela tabela-cartoes tabela-leituras">
    <thead>
        <tr><th scope="col">Data e hora</th><th scope="col">Sensor</th><th scope="col">Trem</th><th scope="col">Tipo</th><th scope="col">Valor</th><th scope="col">Situação</th></tr>
    </thead>
    <tbody>
    <?php while ($l = $resultado->fetch_assoc()): ?>
        <?php $sev = classificarValor((float)$l['valor'], $l['limite_max'] === null ? null : (float)$l['limite_max']); ?>
        <tr class="linha">
            <td data-label="Data e hora"><?= e(date('d/m/Y H:i', strtotime($l['data_hora']))) ?></td>
            <td class="celula-principal" data-label="Sensor"><?= e($l['codigo']) ?></td>
            <td data-label="Trem"><?= e($l['prefixo']) ?></td>
            <td data-label="Tipo"><?= e(tiposSensor()[$l['tipo']] ?? $l['tipo']) ?></td>
            <td data-label="Valor"><?= e(number_format((float)$l['valor'], 2, ',', '.')) ?> <?= e($l['unidade']) ?></td>
            <td class="sev-<?= e($sev) ?>" data-label="Situação"><?= e(rotulosSeveridade()[$sev]) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php paginacaoLinks('leituras.php', $pagina, $total_paginas, $filtros); ?>
<?php endif; $stmt->close(); ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
