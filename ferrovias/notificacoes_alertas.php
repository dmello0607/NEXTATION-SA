<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador', 'maquinista']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

const POR_PAGINA = 50;

$severidade = $_GET['severidade'] ?? '';
$tipo       = $_GET['tipo'] ?? '';
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));

$condicoes = [];
$valores = [];
$tipos = '';
if ($severidade !== '' && array_key_exists($severidade, rotulosSeveridade())) {
    $condicoes[] = 'alertas.severidade = ?';
    $valores[] = $severidade;
    $tipos .= 's';
} else {
    $severidade = '';
}
if ($tipo !== '' && array_key_exists($tipo, tiposSensor())) {
    $condicoes[] = 'alertas.tipo = ?';
    $valores[] = $tipo;
    $tipos .= 's';
} else {
    $tipo = '';
}
$where = count($condicoes) > 0 ? ' WHERE ' . implode(' AND ', $condicoes) : '';

// O alerta percorre quatro tabelas: alerta -> leitura -> sensor -> trem
$from = ' FROM alertas
          INNER JOIN leituras ON leituras.id_leitura = alertas.id_leitura
          INNER JOIN sensores ON sensores.id_sensor = leituras.id_sensor
          INNER JOIN trens ON trens.id_trem = sensores.id_trem';

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

$sql = 'SELECT alertas.*, sensores.codigo, sensores.tipo AS tipo_sensor, trens.prefixo, leituras.valor, leituras.unidade'
     . $from . $where
     . ' ORDER BY alertas.data_hora DESC, alertas.id_alerta DESC LIMIT ? OFFSET ?';
$valores_pg = array_merge($valores, [POR_PAGINA, $offset]);
$stmt = $conexao->prepare($sql);
$stmt->bind_param($tipos . 'ii', ...$valores_pg);
$stmt->execute();
$resultado = $stmt->get_result();

$filtros = array_filter(['severidade' => $severidade, 'tipo' => $tipo]);

$titulo = 'Central de alertas';
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Central de alertas</h1>
</section>

<form class="filtros" method="GET" action="notificacoes_alertas.php" role="search">
    <div class="campo">
        <label class="visualmente-oculto" for="severidade">Severidade</label>
        <select class="campo-entrada" id="severidade" name="severidade">
            <option value="">Todas as severidades</option>
            <?php foreach (rotulosSeveridade() as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $severidade === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
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
    <div class="filtros-acoes">
        <button class="botao botao-secundario" type="submit">Filtrar</button>
        <a class="botao-link" href="notificacoes_alertas.php">Limpar</a>
    </div>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p class="estado-vazio"><?= count($filtros) > 0 ? 'Nenhum alerta encontrado para os filtros selecionados.' : 'Nenhum alerta registrado. Tudo dentro do esperado.' ?></p>
<?php else: ?>
<p class="total-resultados"><strong><?= (int)$total ?></strong> alerta(s) encontrado(s).</p>
<table class="tabela tabela-cartoes tabela-alertas">
    <thead>
        <tr><th scope="col">Data e hora</th><th scope="col">Severidade</th><th scope="col">Tipo</th><th scope="col">Trem</th><th scope="col">Sensor</th><th scope="col">Valor</th><th scope="col">Mensagem</th></tr>
    </thead>
    <tbody>
    <?php while ($a = $resultado->fetch_assoc()): ?>
        <tr class="linha<?= $a['severidade'] === 'critico' ? ' linha-critica' : '' ?>">
            <td data-label="Data e hora"><?= e(date('d/m/Y H:i', strtotime($a['data_hora']))) ?></td>
            <td data-label="Severidade"><span class="selo sev-<?= e($a['severidade']) ?>"><?= e(rotulosSeveridade()[$a['severidade']] ?? $a['severidade']) ?></span></td>
            <td data-label="Tipo"><?= e(tiposSensor()[$a['tipo_sensor']] ?? $a['tipo_sensor']) ?></td>
            <td class="celula-principal" data-label="Trem"><?= e($a['prefixo']) ?></td>
            <td data-label="Sensor"><?= e($a['codigo']) ?></td>
            <td data-label="Valor"><?= e(number_format((float)$a['valor'], 2, ',', '.')) ?> <?= e($a['unidade']) ?></td>
            <td data-label="Mensagem"><?= e($a['mensagem']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php paginacaoLinks('notificacoes_alertas.php', $pagina, $total_paginas, $filtros); ?>
<?php endif; $stmt->close(); ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
