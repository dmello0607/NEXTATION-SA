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
<h1>Painel</h1>
<p>Bem-vindo, <?= e($_SESSION['usuario_nome']) ?>.</p>

<h2>Frota</h2>
<p>Total de trens: <?= (int)$total_trens ?></p>
<ul>
    <?php foreach (statusTrens() as $valor => $rotulo): ?>
        <li class="<?= e(classeStatusTrem($valor)) ?>"><?= e($rotulo) ?>: <?= (int)($trens_status[$valor] ?? 0) ?></li>
    <?php endforeach; ?>
</ul>

<?php if ($ve_operacao): ?>
<h2>Monitoramento</h2>
<ul>
    <li>Sensores cadastrados: <?= (int)$total_sensores ?></li>
    <li>Leituras registradas: <?= (int)$total_leituras ?></li>
    <?php if ($eh_admin): ?><li>Usuários ativos: <?= (int)$total_usuarios ?></li><?php endif; ?>
    <li class="sev-atencao">Alertas de atenção: <?= (int)$alertas_sev['atencao'] ?></li>
    <li class="sev-critico">Alertas críticos: <?= (int)$alertas_sev['critico'] ?></li>
</ul>

<h2>Sensores com leitura crítica, por trem</h2>
<?php if (count($criticos_por_trem) === 0): ?>
    <p>Nenhum sensor com leitura crítica no momento.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Trem</th><th>Sensores críticos</th></tr></thead>
        <tbody>
        <?php foreach ($criticos_por_trem as $prefixo => $qtd): ?>
            <tr class="linha-critica"><td><?= e($prefixo) ?></td><td class="sev-critico"><?= (int)$qtd ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Últimos alertas</h2>
<?php if ($ultimos_alertas->num_rows === 0): ?>
    <p>Nenhum alerta registrado.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Data e hora</th><th>Severidade</th><th>Trem</th><th>Sensor</th><th>Mensagem</th></tr></thead>
        <tbody>
        <?php while ($a = $ultimos_alertas->fetch_assoc()): ?>
            <tr class="<?= $a['severidade'] === 'critico' ? 'linha-critica' : '' ?>">
                <td><?= e(date('d/m/Y H:i', strtotime($a['data_hora']))) ?></td>
                <td class="sev-<?= e($a['severidade']) ?>"><?= e(rotulosSeveridade()[$a['severidade']] ?? $a['severidade']) ?></td>
                <td><?= e($a['prefixo']) ?></td>
                <td><?= e($a['codigo']) ?></td>
                <td><?= e($a['mensagem']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <p><a href="notificacoes_alertas.php">Ver todos os alertas</a></p>
<?php endif; ?>
<?php endif; ?>

<h2>Próximas viagens</h2>
<?php if ($proximas->num_rows === 0): ?>
    <p>Nenhuma viagem programada.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Data</th><th>Partida</th><th>Origem</th><th>Destino</th><?php if ($ve_operacao): ?><th>Trem</th><?php endif; ?><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $proximas->fetch_assoc()): ?>
            <tr>
                <td><?= e(date('d/m/Y', strtotime($r['data_viagem']))) ?></td>
                <td><?= e(substr($r['hora_partida'], 0, 5)) ?></td>
                <td><?= e($r['origem']) ?></td>
                <td><?= e($r['destino']) ?></td>
                <?php if ($ve_operacao): ?><td><?= e($r['prefixo']) ?></td><?php endif; ?>
                <td><?= e(statusRotas()[$r['status']] ?? $r['status']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
