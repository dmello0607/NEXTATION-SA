<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$erros = [];
$id_trem = 0;
$data_inicio = date('Y-m-d', strtotime('-30 days'));
$data_fim = date('Y-m-d');
$severidade = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_trem     = (int)($_POST['id_trem'] ?? 0);
    $data_inicio = trim($_POST['data_inicio'] ?? '');
    $data_fim    = trim($_POST['data_fim'] ?? '');
    $severidade  = $_POST['severidade'] ?? '';

    if ($id_trem > 0) {
        $stmt = $conexao->prepare('SELECT id_trem FROM trens WHERE id_trem = ?');
        $stmt->bind_param('i', $id_trem);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $erros[] = 'Selecione um trem válido.';
        }
        $stmt->close();
    }
    if (!dataValida($data_inicio) || !dataValida($data_fim)) {
        $erros[] = 'Informe o período com datas válidas.';
    } elseif ($data_inicio > $data_fim) {
        $erros[] = 'A data inicial não pode ser depois da data final.';
    }
    if ($severidade !== '' && !in_array($severidade, ['atencao', 'critico'], true)) {
        $erros[] = 'Selecione uma severidade válida.';
    }

    if (count($erros) === 0) {
        $filtros = json_encode(
            ['id_trem' => $id_trem, 'data_inicio' => $data_inicio, 'data_fim' => $data_fim, 'severidade' => $severidade]
        );
        $tipo = 'alertas_por_trem';
        $id_usuario = (int)$_SESSION['usuario_id'];
        $stmt = $conexao->prepare('INSERT INTO relatorios (id_usuario, tipo, filtros, gerado_em) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('iss', $id_usuario, $tipo, $filtros);
        if ($stmt->execute()) {
            $novo_id = $conexao->insert_id;
            $stmt->close();
            header('Location: visualiza_relatorio.php?id_relatorio=' . $novo_id);
            exit;
        }
        $stmt->close();
        $erros[] = 'Não foi possível gerar o relatório. Tente novamente.';
    }
}

$lista_trens = $conexao->query('SELECT id_trem, prefixo FROM trens ORDER BY prefixo');
$prefixos = [];
$res = $conexao->query('SELECT id_trem, prefixo FROM trens');
while ($l = $res->fetch_assoc()) {
    $prefixos[(int)$l['id_trem']] = $l['prefixo'];
}

$historico = $conexao->query(
    'SELECT relatorios.id_relatorio, relatorios.filtros, relatorios.gerado_em, usuarios.nome
     FROM relatorios INNER JOIN usuarios ON usuarios.id_usuario = relatorios.id_usuario
     ORDER BY relatorios.gerado_em DESC, relatorios.id_relatorio DESC LIMIT 10'
);

$titulo = 'Relatórios';
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Relatório de alertas por trem</h1>
</section>

<?php mostrarErros($erros); ?>

<form class="formulario formulario-relatorio" method="POST" action="gera_relatorio.php">
    <fieldset class="grupo grupo-filtros-relatorio">
        <legend class="grupo-titulo">Filtros</legend>
        <div class="campo">
            <label class="campo-rotulo" for="id_trem">Trem</label>
            <select class="campo-entrada" id="id_trem" name="id_trem">
                <option value="0">Todos os trens</option>
                <?php while ($t = $lista_trens->fetch_assoc()): ?>
                    <option value="<?= (int)$t['id_trem'] ?>" <?= $id_trem === (int)$t['id_trem'] ? 'selected' : '' ?>><?= e($t['prefixo']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="data_inicio">Data inicial</label>
            <input class="campo-entrada" type="date" id="data_inicio" name="data_inicio" value="<?= e($data_inicio) ?>" required>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="data_fim">Data final</label>
            <input class="campo-entrada" type="date" id="data_fim" name="data_fim" value="<?= e($data_fim) ?>" required>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="severidade">Severidade</label>
            <select class="campo-entrada" id="severidade" name="severidade">
                <option value="">Atenção e crítico</option>
                <option value="atencao" <?= $severidade === 'atencao' ? 'selected' : '' ?>>Somente atenção</option>
                <option value="critico" <?= $severidade === 'critico' ? 'selected' : '' ?>>Somente crítico</option>
            </select>
        </div>
    </fieldset>

    <div class="formulario-acoes">
        <button class="botao botao-primario botao-largo" type="submit">Gerar relatório</button>
    </div>
</form>

<section class="secao-historico" aria-labelledby="titulo-historico">
    <h2 id="titulo-historico" class="subtitulo-secao">Relatórios gerados recentemente</h2>
    <?php if ($historico->num_rows === 0): ?>
        <p class="estado-vazio">Nenhum relatório gerado ainda.</p>
    <?php else: ?>
    <table class="tabela tabela-cartoes tabela-historico">
        <thead>
            <tr><th scope="col">Gerado em</th><th scope="col">Por</th><th scope="col">Trem</th><th scope="col">Período</th><th scope="col">Severidade</th><th scope="col">Ações</th></tr>
        </thead>
        <tbody>
        <?php while ($h = $historico->fetch_assoc()): ?>
            <?php
            $f = json_decode($h['filtros'], true) ?: [];
            $trem_txt = !empty($f['id_trem']) ? ($prefixos[(int)$f['id_trem']] ?? '?') : 'Todos';
            $sev_txt = !empty($f['severidade']) ? (rotulosSeveridade()[$f['severidade']] ?? '?') : 'Atenção e crítico';
            $ini_txt = isset($f['data_inicio']) && dataValida($f['data_inicio']) ? date('d/m/Y', strtotime($f['data_inicio'])) : '?';
            $fim_txt = isset($f['data_fim']) && dataValida($f['data_fim']) ? date('d/m/Y', strtotime($f['data_fim'])) : '?';
            ?>
            <tr class="linha">
                <td class="celula-principal" data-label="Gerado em"><?= e(date('d/m/Y H:i', strtotime($h['gerado_em']))) ?></td>
                <td data-label="Por"><?= e($h['nome']) ?></td>
                <td data-label="Trem"><?= e($trem_txt) ?></td>
                <td data-label="Período"><?= e($ini_txt) ?> a <?= e($fim_txt) ?></td>
                <td data-label="Severidade"><?= e($sev_txt) ?></td>
                <td class="celula-acoes" data-label="Ações"><a class="botao botao-editar" href="visualiza_relatorio.php?id_relatorio=<?= (int)$h['id_relatorio'] ?>">Abrir</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/rodape.php'; ?>
