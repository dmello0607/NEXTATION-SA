<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

$id_relatorio = (int)($_GET['id_relatorio'] ?? 0);
$stmt = $conexao->prepare(
    'SELECT relatorios.*, usuarios.nome FROM relatorios
     INNER JOIN usuarios ON usuarios.id_usuario = relatorios.id_usuario WHERE relatorios.id_relatorio = ?'
);
$stmt->bind_param('i', $id_relatorio);
$stmt->execute();
$rel = $stmt->get_result()->fetch_assoc();
$stmt->close();

$f = $rel ? json_decode($rel['filtros'], true) : null;
if (!$rel || !is_array($f) || !isset($f['data_inicio'], $f['data_fim']) || !dataValida($f['data_inicio']) || !dataValida($f['data_fim'])) {
    flash('Relatório não encontrado.', 'erro');
    header('Location: gera_relatorio.php');
    exit;
}
$id_trem    = (int)($f['id_trem'] ?? 0);
$severidade = in_array($f['severidade'] ?? '', ['atencao', 'critico'], true) ? $f['severidade'] : '';

// Periodo: do inicio do primeiro dia ate o FIM do ultimo dia (data_hora < dia seguinte),
// para um alerta das 14h do ultimo dia nao ficar de fora.
$condicoes = ['alertas.data_hora >= ?', 'alertas.data_hora < DATE_ADD(?, INTERVAL 1 DAY)'];
$valores = [$f['data_inicio'], $f['data_fim']];
$tipos = 'ss';
if ($id_trem > 0) {
    $condicoes[] = 'sensores.id_trem = ?';
    $valores[] = $id_trem;
    $tipos .= 'i';
}
if ($severidade !== '') {
    $condicoes[] = 'alertas.severidade = ?';
    $valores[] = $severidade;
    $tipos .= 's';
}
$where = ' WHERE ' . implode(' AND ', $condicoes);
$from = ' FROM alertas
          INNER JOIN leituras ON leituras.id_leitura = alertas.id_leitura
          INNER JOIN sensores ON sensores.id_sensor = leituras.id_sensor
          INNER JOIN trens ON trens.id_trem = sensores.id_trem';

function executar($conexao, $sql, $tipos, $valores)
{
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    $res = $stmt->get_result();
    $linhas = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $linhas;
}

// Resumo por trem (agrupamento no banco)
$por_trem = executar($conexao,
    "SELECT trens.prefixo, COUNT(*) AS total,
            SUM(alertas.severidade = 'atencao') AS atencao,
            SUM(alertas.severidade = 'critico') AS critico"
    . $from . $where . ' GROUP BY trens.id_trem, trens.prefixo ORDER BY critico DESC, total DESC, trens.prefixo',
    $tipos, $valores);

// Resumo por tipo de alerta
$por_tipo = executar($conexao,
    'SELECT alertas.tipo, COUNT(*) AS total' . $from . $where . ' GROUP BY alertas.tipo ORDER BY total DESC',
    $tipos, $valores);

// Detalhe (limitado para a pagina nao ficar enorme)
const LIMITE_DETALHE = 500;
$detalhe = executar($conexao,
    'SELECT alertas.data_hora, alertas.severidade, alertas.tipo, alertas.mensagem, sensores.codigo, trens.prefixo'
    . $from . $where . ' ORDER BY alertas.data_hora DESC, alertas.id_alerta DESC LIMIT ' . LIMITE_DETALHE,
    $tipos, $valores);

$total = $atencao = $critico = 0;
foreach ($por_trem as $l) {
    $total   += (int)$l['total'];
    $atencao += (int)$l['atencao'];
    $critico += (int)$l['critico'];
}

$nome_trem = 'Todos os trens';
if ($id_trem > 0) {
    $stmt = $conexao->prepare('SELECT prefixo, modelo FROM trens WHERE id_trem = ?');
    $stmt->bind_param('i', $id_trem);
    $stmt->execute();
    $t = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $nome_trem = $t ? $t['prefixo'] . ' - ' . $t['modelo'] : 'Trem removido';
}

$titulo = 'Relatório de alertas';
$voltar = 'gera_relatorio.php';
require __DIR__ . '/includes/cabecalho.php';
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Relatório de alertas por trem</h1>
</section>

<section class="relatorio-filtros" aria-label="Filtros do relatório">
    <dl class="resumo-lista">
        <div class="resumo-item"><dt>Trem</dt><dd><?= e($nome_trem) ?></dd></div>
        <div class="resumo-item"><dt>Período</dt><dd><?= e(date('d/m/Y', strtotime($f['data_inicio']))) ?> a <?= e(date('d/m/Y', strtotime($f['data_fim']))) ?></dd></div>
        <div class="resumo-item"><dt>Severidade</dt><dd><?= $severidade === '' ? 'Atenção e crítico' : e(rotulosSeveridade()[$severidade]) ?></dd></div>
        <div class="resumo-item"><dt>Gerado em</dt><dd><?= e(date('d/m/Y H:i', strtotime($rel['gerado_em']))) ?> por <?= e($rel['nome']) ?></dd></div>
    </dl>
    <div class="acoes-pagina nao-imprimir">
        <button class="botao botao-secundario" type="button" onclick="window.print()">Imprimir / salvar em PDF</button>
        <a class="botao-link" href="gera_relatorio.php">Novo relatório</a>
    </div>
</section>

<?php if ($total === 0): ?>
    <p class="estado-vazio">Nenhum alerta encontrado para os filtros deste relatório.</p>
<?php else: ?>
<section class="painel-secao relatorio-resumo" aria-labelledby="titulo-resumo">
    <h2 id="titulo-resumo" class="subtitulo-secao">Resumo</h2>
    <ul class="indicadores">
        <li class="indicador"><span class="indicador-valor"><?= (int)$total ?></span><span class="indicador-rotulo">Total de alertas</span></li>
        <li class="indicador sev-atencao"><span class="indicador-valor"><?= (int)$atencao ?></span><span class="indicador-rotulo">Atenção</span></li>
        <li class="indicador sev-critico"><span class="indicador-valor"><?= (int)$critico ?></span><span class="indicador-rotulo">Crítico</span></li>
    </ul>
</section>

<section class="painel-secao" aria-labelledby="titulo-por-trem">
    <h2 id="titulo-por-trem" class="subtitulo-secao">Por trem</h2>
    <table class="tabela tabela-cartoes tabela-resumo-trem">
        <thead><tr><th scope="col">Trem</th><th scope="col">Total</th><th scope="col">Atenção</th><th scope="col">Crítico</th></tr></thead>
        <tbody>
        <?php foreach ($por_trem as $l): ?>
            <tr class="linha">
                <td class="celula-principal" data-label="Trem"><?= e($l['prefixo']) ?></td>
                <td data-label="Total"><?= (int)$l['total'] ?></td>
                <td class="sev-atencao" data-label="Atenção"><?= (int)$l['atencao'] ?></td>
                <td class="sev-critico" data-label="Crítico"><?= (int)$l['critico'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="painel-secao" aria-labelledby="titulo-por-tipo">
    <h2 id="titulo-por-tipo" class="subtitulo-secao">Por tipo de alerta</h2>
    <table class="tabela tabela-cartoes tabela-resumo-tipo">
        <thead><tr><th scope="col">Tipo</th><th scope="col">Total</th></tr></thead>
        <tbody>
        <?php foreach ($por_tipo as $l): ?>
            <tr class="linha">
                <td class="celula-principal" data-label="Tipo"><?= e(tiposSensor()[$l['tipo']] ?? $l['tipo']) ?></td>
                <td data-label="Total"><?= (int)$l['total'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="painel-secao" aria-labelledby="titulo-detalhe">
    <h2 id="titulo-detalhe" class="subtitulo-secao">Detalhe dos alertas</h2>
    <?php if ($total > LIMITE_DETALHE): ?>
        <p class="aviso">Exibindo os <?= (int)LIMITE_DETALHE ?> alertas mais recentes de <?= (int)$total ?>.</p>
    <?php endif; ?>
    <table class="tabela tabela-cartoes tabela-detalhe">
        <thead><tr><th scope="col">Data e hora</th><th scope="col">Trem</th><th scope="col">Sensor</th><th scope="col">Severidade</th><th scope="col">Mensagem</th></tr></thead>
        <tbody>
        <?php foreach ($detalhe as $d): ?>
            <tr class="linha<?= $d['severidade'] === 'critico' ? ' linha-critica' : '' ?>">
                <td data-label="Data e hora"><?= e(date('d/m/Y H:i', strtotime($d['data_hora']))) ?></td>
                <td class="celula-principal" data-label="Trem"><?= e($d['prefixo']) ?></td>
                <td data-label="Sensor"><?= e($d['codigo']) ?></td>
                <td class="sev-<?= e($d['severidade']) ?>" data-label="Severidade"><?= e(rotulosSeveridade()[$d['severidade']] ?? $d['severidade']) ?></td>
                <td data-label="Mensagem"><?= e($d['mensagem']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
