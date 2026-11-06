<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$volta = filtrosRotasQuery($_POST['volta'] ?? ($_GET['volta'] ?? ''));
$destino_volta = 'rotas.php' . ($volta !== '' ? '?' . $volta : '');

$erros = [];
$id_rota = 0;
$dados = ['id_trem' => '', 'origem' => '', 'destino' => '', 'data_viagem' => '', 'hora_partida' => '', 'hora_chegada' => '', 'status' => 'programada'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_rota = (int)($_POST['id_rota'] ?? 0);
    $dados['id_trem']      = (int)($_POST['id_trem'] ?? 0);
    $dados['origem']       = trim($_POST['origem'] ?? '');
    $dados['destino']      = trim($_POST['destino'] ?? '');
    $dados['data_viagem']  = trim($_POST['data_viagem'] ?? '');
    $dados['hora_partida'] = trim($_POST['hora_partida'] ?? '');
    $dados['hora_chegada'] = trim($_POST['hora_chegada'] ?? '');
    $dados['status']       = $_POST['status'] ?? '';

    // O trem recebido precisa existir
    $stmt = $conexao->prepare('SELECT id_trem FROM trens WHERE id_trem = ?');
    $stmt->bind_param('i', $dados['id_trem']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $erros[] = 'Selecione um trem válido.';
    }
    $stmt->close();

    if ($dados['origem'] === '' || strlen($dados['origem']) > 80) {
        $erros[] = 'Informe a origem (até 80 caracteres).';
    }
    if ($dados['destino'] === '' || strlen($dados['destino']) > 80) {
        $erros[] = 'Informe o destino (até 80 caracteres).';
    }
    if ($dados['origem'] !== '' && strcasecmp($dados['origem'], $dados['destino']) === 0) {
        $erros[] = 'Origem e destino devem ser diferentes.';
    }
    if (!dataValida($dados['data_viagem'])) {
        $erros[] = 'Informe uma data de viagem válida.';
    }
    $partida = horaValida($dados['hora_partida']);
    if ($partida === null) {
        $erros[] = 'Informe uma hora de partida válida.';
    }
    $chegada = null;
    if ($dados['hora_chegada'] !== '') {
        $chegada = horaValida($dados['hora_chegada']);
        if ($chegada === null) {
            $erros[] = 'Informe uma hora de chegada válida.';
        } elseif ($partida !== null && $chegada <= $partida) {
            $erros[] = 'A chegada deve ser depois da partida (viagens dentro do mesmo dia).';
        }
    }
    if (!array_key_exists($dados['status'], statusRotas())) {
        $erros[] = 'Selecione um status válido.';
    }

    // Avancado: o mesmo trem nao pode ter duas viagens com horarios sobrepostos no mesmo dia.
    // Duas faixas se sobrepoem quando: partida_existente < fim_novo E fim_existente > partida_novo.
    // Viagem sem chegada definida ocupa o resto do dia.
    if (count($erros) === 0 && $dados['status'] !== 'cancelada') {
        $fim_novo = $chegada ?? '23:59:59';
        $stmt = $conexao->prepare(
            "SELECT origem, destino, hora_partida FROM rotas
             WHERE id_trem = ? AND data_viagem = ? AND status <> 'cancelada' AND id_rota <> ?
               AND hora_partida < ? AND COALESCE(hora_chegada, '23:59:59') > ?"
        );
        $stmt->bind_param('isiss', $dados['id_trem'], $dados['data_viagem'], $id_rota, $fim_novo, $partida);
        $stmt->execute();
        $conflito = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($conflito) {
            $erros[] = 'Este trem já tem a viagem ' . $conflito['origem'] . ' → ' . $conflito['destino']
                     . ' (partida às ' . substr($conflito['hora_partida'], 0, 5) . ') em horário sobreposto neste dia.';
        }
    }

    if (count($erros) === 0) {
        if ($id_rota > 0) {
            $stmt = $conexao->prepare(
                'UPDATE rotas SET id_trem = ?, origem = ?, destino = ?, data_viagem = ?, hora_partida = ?, hora_chegada = ?, status = ? WHERE id_rota = ?'
            );
            $stmt->bind_param('issssssi', $dados['id_trem'], $dados['origem'], $dados['destino'], $dados['data_viagem'], $partida, $chegada, $dados['status'], $id_rota);
        } else {
            $stmt = $conexao->prepare(
                'INSERT INTO rotas (id_trem, origem, destino, data_viagem, hora_partida, hora_chegada, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('issssss', $dados['id_trem'], $dados['origem'], $dados['destino'], $dados['data_viagem'], $partida, $chegada, $dados['status']);
        }
        if ($stmt->execute()) {
            $stmt->close();
            flash($id_rota > 0 ? 'Rota atualizada com sucesso.' : 'Rota cadastrada com sucesso.');
            header('Location: ' . $destino_volta);
            exit;
        }
        $stmt->close();
        $erros[] = 'Não foi possível gravar a rota. Tente novamente.';
    }
} elseif (isset($_GET['id_rota'])) {
    $id_rota = (int)$_GET['id_rota'];
    $stmt = $conexao->prepare('SELECT id_trem, origem, destino, data_viagem, hora_partida, hora_chegada, status FROM rotas WHERE id_rota = ?');
    $stmt->bind_param('i', $id_rota);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$linha) {
        flash('Rota não encontrada.', 'erro');
        header('Location: ' . $destino_volta);
        exit;
    }
    $linha['hora_partida'] = substr($linha['hora_partida'], 0, 5);
    $linha['hora_chegada'] = $linha['hora_chegada'] ? substr($linha['hora_chegada'], 0, 5) : '';
    $dados = $linha;
}

$lista_trens = $conexao->query('SELECT id_trem, prefixo, modelo FROM trens ORDER BY prefixo');

$titulo = $id_rota > 0 ? 'Editar rota' : 'Cadastrar rota';
require __DIR__ . '/includes/cabecalho.php';
?>
<h1><?= e($titulo) ?></h1>
<?php foreach ($erros as $erro): ?><p class="erro"><?= e($erro) ?></p><?php endforeach; ?>
<form method="POST" action="rotas_form.php">
    <input type="hidden" name="id_rota" value="<?= (int)$id_rota ?>">
    <input type="hidden" name="volta" value="<?= e($volta) ?>">

    <label for="id_trem">Trem</label>
    <select id="id_trem" name="id_trem" required>
        <option value="">Selecione o trem</option>
        <?php while ($t = $lista_trens->fetch_assoc()): ?>
            <option value="<?= (int)$t['id_trem'] ?>" <?= (int)$dados['id_trem'] === (int)$t['id_trem'] ? 'selected' : '' ?>>
                <?= e($t['prefixo']) ?> — <?= e($t['modelo']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="origem">Origem</label>
    <input type="text" id="origem" name="origem" maxlength="80" value="<?= e($dados['origem']) ?>" required>

    <label for="destino">Destino</label>
    <input type="text" id="destino" name="destino" maxlength="80" value="<?= e($dados['destino']) ?>" required>

    <label for="data_viagem">Data da viagem</label>
    <input type="date" id="data_viagem" name="data_viagem" value="<?= e($dados['data_viagem']) ?>" required>

    <label for="hora_partida">Hora de partida</label>
    <input type="time" id="hora_partida" name="hora_partida" value="<?= e($dados['hora_partida']) ?>" required>

    <label for="hora_chegada">Hora de chegada (opcional)</label>
    <input type="time" id="hora_chegada" name="hora_chegada" value="<?= e($dados['hora_chegada']) ?>">

    <label for="status">Status</label>
    <select id="status" name="status" required>
        <?php foreach (statusRotas() as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $dados['status'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Salvar</button>
    <a href="<?= e($destino_volta) ?>">Cancelar</a>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
