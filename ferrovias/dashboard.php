<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

$ve_operacao = temPapel(['administrador', 'maquinista']);   // dados restritos
$eh_admin    = temPapel(['administrador']);

// Trens por status
$trens_status = [];
$res = $conexao->query('SELECT status, COUNT(*) AS qtd FROM trens GROUP BY status');
while ($l = $res->fetch_assoc()) {
    $trens_status[$l['status']] = (int)$l['qtd'];
}
$total_trens = array_sum($trens_status);

// Proximas viagens (informacao aberta a todos os perfis)
$proximas = $conexao->query(
    "SELECT rotas.origem, rotas.destino, rotas.data_viagem, rotas.hora_partida, rotas.status, trens.prefixo
     FROM rotas INNER JOIN trens ON trens.id_trem = rotas.id_trem
     WHERE rotas.status IN ('programada', 'em_andamento') AND rotas.data_viagem >= CURDATE()
     ORDER BY rotas.data_viagem, rotas.hora_partida LIMIT 5"
);

if ($ve_operacao) {
    $total_sensores = (int)$conexao->query('SELECT COUNT(*) AS n FROM sensores')->fetch_assoc()['n'];
    $total_leituras = (int)$conexao->query('SELECT COUNT(*) AS n FROM leituras')->fetch_assoc()['n'];

    $alertas_sev = ['atencao' => 0, 'critico' => 0];
    $res = $conexao->query("SELECT severidade, COUNT(*) AS qtd FROM alertas GROUP BY severidade");
    while ($l = $res->fetch_assoc()) {
        $alertas_sev[$l['severidade']] = (int)$l['qtd'];
    }

    // Sensores cuja leitura MAIS RECENTE esta critica, agrupados por trem
    // (a classificacao usa a mesma funcao do resto do sistema)
    $criticos_por_trem = [];
    $res = $conexao->query(
        'SELECT trens.prefixo, ult.valor, limites.limite_max
         FROM sensores
         INNER JOIN trens ON trens.id_trem = sensores.id_trem
         INNER JOIN limites ON limites.tipo = sensores.tipo
         INNER JOIN leituras ult ON ult.id_leitura = (
             SELECT l2.id_leitura FROM leituras l2
             WHERE l2.id_sensor = sensores.id_sensor
             ORDER BY l2.data_hora DESC, l2.id_leitura DESC LIMIT 1)'
    );
    while ($l = $res->fetch_assoc()) {
        if (classificarValor((float)$l['valor'], (float)$l['limite_max']) === 'critico') {
            $criticos_por_trem[$l['prefixo']] = ($criticos_por_trem[$l['prefixo']] ?? 0) + 1;
        }
    }
    ksort($criticos_por_trem);

    $ultimos_alertas = $conexao->query(
        'SELECT alertas.severidade, alertas.mensagem, alertas.data_hora, trens.prefixo, sensores.codigo
         FROM alertas
         INNER JOIN leituras ON leituras.id_leitura = alertas.id_leitura
         INNER JOIN sensores ON sensores.id_sensor = leituras.id_sensor
         INNER JOIN trens ON trens.id_trem = sensores.id_trem
         ORDER BY alertas.data_hora DESC, alertas.id_alerta DESC LIMIT 5'
    );
}

if ($eh_admin) {
    $total_usuarios = (int)$conexao->query('SELECT COUNT(*) AS n FROM usuarios WHERE ativo = 1')->fetch_assoc()['n'];
}

$titulo = 'Painel';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Painel</h1>
    <p class="subtitulo">Bem-vindo, <?= e($_SESSION['usuario_nome']) ?>.</p>
</section>

<section class="painel-secao painel-frota" aria-labelledby="titulo-frota">
    <h2 id="titulo-frota" class="subtitulo-secao">Frota</h2>
    <p class="painel-total">Total de trens: <strong class="painel-total-valor"><?= (int)$total_trens ?></strong></p>
    <ul class="indicadores">
        <?php foreach (statusTrens() as $valor => $rotulo): ?>
            <li class="indicador <?= e(classeStatusTrem($valor)) ?>">
                <?= icone('status-' . $valor) ?>
                <span class="indicador-valor"><?= (int)($trens_status[$valor] ?? 0) ?></span>
                <span class="indicador-rotulo"><?= e($rotulo) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php if ($ve_operacao): ?>
<section class="painel-secao painel-monitoramento" aria-labelledby="titulo-monitoramento">
    <h2 id="titulo-monitoramento" class="subtitulo-secao">Monitoramento</h2>
    <ul class="indicadores">
        <li class="indicador"><span class="indicador-valor"><?= (int)$total_sensores ?></span><span class="indicador-rotulo">Sensores cadastrados</span></li>
        <li class="indicador"><span class="indicador-valor"><?= (int)$total_leituras ?></span><span class="indicador-rotulo">Leituras registradas</span></li>
        <?php if ($eh_admin): ?>
        <li class="indicador"><span class="indicador-valor"><?= (int)$total_usuarios ?></span><span class="indicador-rotulo">Usuários ativos</span></li>
        <?php endif; ?>
        <li class="indicador sev-atencao"><span class="indicador-valor"><?= (int)$alertas_sev['atencao'] ?></span><span class="indicador-rotulo">Alertas de atenção</span></li>
        <li class="indicador sev-critico"><span class="indicador-valor"><?= (int)$alertas_sev['critico'] ?></span><span class="indicador-rotulo">Alertas críticos</span></li>
    </ul>
</section>

<section class="painel-secao painel-criticos" aria-labelledby="titulo-criticos">
    <h2 id="titulo-criticos" class="subtitulo-secao">Sensores com leitura crítica, por trem</h2>
    <?php if (count($criticos_por_trem) === 0): ?>
        <p class="estado-vazio">Nenhum sensor com leitura crítica no momento.</p>
    <?php else: ?>
        <table class="tabela tabela-cartoes tabela-criticos">
            <thead><tr><th scope="col">Trem</th><th scope="col">Sensores críticos</th></tr></thead>
            <tbody>
            <?php foreach ($criticos_por_trem as $prefixo => $qtd): ?>
                <tr class="linha linha-critica">
                    <td class="celula-principal" data-label="Trem"><?= e($prefixo) ?></td>
                    <td class="sev-critico" data-label="Sensores críticos"><?= (int)$qtd ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="painel-secao painel-alertas" aria-labelledby="titulo-ultimos-alertas">
    <h2 id="titulo-ultimos-alertas" class="subtitulo-secao">Últimos alertas</h2>
    <?php if ($ultimos_alertas->num_rows === 0): ?>
        <p class="estado-vazio">Nenhum alerta registrado.</p>
    <?php else: ?>
        <table class="tabela tabela-cartoes tabela-alertas">
            <thead><tr><th scope="col">Data e hora</th><th scope="col">Severidade</th><th scope="col">Trem</th><th scope="col">Sensor</th><th scope="col">Mensagem</th></tr></thead>
            <tbody>
            <?php while ($a = $ultimos_alertas->fetch_assoc()): ?>
                <tr class="linha<?= $a['severidade'] === 'critico' ? ' linha-critica' : '' ?>">
                    <td data-label="Data e hora"><?= e(date('d/m/Y H:i', strtotime($a['data_hora']))) ?></td>
                    <td class="sev-<?= e($a['severidade']) ?>" data-label="Severidade"><?= e(rotulosSeveridade()[$a['severidade']] ?? $a['severidade']) ?></td>
                    <td class="celula-principal" data-label="Trem"><?= e($a['prefixo']) ?></td>
                    <td data-label="Sensor"><?= e($a['codigo']) ?></td>
                    <td data-label="Mensagem"><?= e($a['mensagem']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <p class="link-secao"><a href="notificacoes_alertas.php">Ver todos os alertas</a></p>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="painel-secao painel-viagens" aria-labelledby="titulo-viagens">
    <h2 id="titulo-viagens" class="subtitulo-secao">Próximas viagens</h2>
    <?php if ($proximas->num_rows === 0): ?>
        <p class="estado-vazio">Nenhuma viagem programada.</p>
    <?php else: ?>
        <table class="tabela tabela-cartoes tabela-viagens">
            <thead>
                <tr><th scope="col">Data</th><th scope="col">Partida</th><th scope="col">Origem</th><th scope="col">Destino</th><?php if ($ve_operacao): ?><th scope="col">Trem</th><?php endif; ?><th scope="col">Status</th></tr>
            </thead>
            <tbody>
            <?php while ($r = $proximas->fetch_assoc()): ?>
                <tr class="linha">
                    <td data-label="Data"><?= e(date('d/m/Y', strtotime($r['data_viagem']))) ?></td>
                    <td data-label="Partida"><?= e(substr($r['hora_partida'], 0, 5)) ?></td>
                    <td class="celula-principal" data-label="Origem"><?= e($r['origem']) ?></td>
                    <td class="celula-principal" data-label="Destino"><?= e($r['destino']) ?></td>
                    <?php if ($ve_operacao): ?><td data-label="Trem"><?= e($r['prefixo']) ?></td><?php endif; ?>
                    <td data-label="Status"><?= e(statusRotas()[$r['status']] ?? $r['status']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/rodape.php'; ?>
