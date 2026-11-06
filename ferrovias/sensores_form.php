<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

$erros = [];
$id_sensor = 0;
$dados = ['id_trem' => '', 'codigo' => '', 'tipo' => '', 'localizacao' => '', 'segmento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sensor = (int)($_POST['id_sensor'] ?? 0);
    $dados['id_trem']     = (int)($_POST['id_trem'] ?? 0);
    $dados['codigo']      = strtoupper(trim($_POST['codigo'] ?? ''));
    $dados['tipo']        = $_POST['tipo'] ?? '';
    $dados['localizacao'] = trim($_POST['localizacao'] ?? '');
    $dados['segmento']    = trim($_POST['segmento'] ?? '');

    if ($dados['codigo'] === '' || strlen($dados['codigo']) > 20) {
        $erros[] = 'Informe o código do sensor (até 20 caracteres, ex.: S-TEMP-001).';
    }
    if (!array_key_exists($dados['tipo'], tiposSensor())) {
        $erros[] = 'Selecione um tipo de sensor válido.';
    }
    if (strlen($dados['localizacao']) > 80 || strlen($dados['segmento']) > 80) {
        $erros[] = 'Localização e segmento aceitam até 80 caracteres.';
    }

    // O trem recebido precisa existir (a lista de selecao pode ser adulterada)
    $stmt = $conexao->prepare('SELECT id_trem FROM trens WHERE id_trem = ?');
    $stmt->bind_param('i', $dados['id_trem']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $erros[] = 'Selecione um trem válido.';
    }
    $stmt->close();

    if (count($erros) === 0) {
        $stmt = $conexao->prepare('SELECT id_sensor FROM sensores WHERE codigo = ? AND id_sensor <> ?');
        $stmt->bind_param('si', $dados['codigo'], $id_sensor);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Já existe um sensor com este código.';
        }
        $stmt->close();
    }

    if (count($erros) === 0) {
        if ($id_sensor > 0) {
            $stmt = $conexao->prepare('UPDATE sensores SET id_trem = ?, codigo = ?, tipo = ?, localizacao = ?, segmento = ? WHERE id_sensor = ?');
            $stmt->bind_param('issssi', $dados['id_trem'], $dados['codigo'], $dados['tipo'], $dados['localizacao'], $dados['segmento'], $id_sensor);
        } else {
            $stmt = $conexao->prepare('INSERT INTO sensores (id_trem, codigo, tipo, localizacao, segmento) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issss', $dados['id_trem'], $dados['codigo'], $dados['tipo'], $dados['localizacao'], $dados['segmento']);
        }
        if ($stmt->execute()) {
            $stmt->close();
            flash($id_sensor > 0 ? 'Sensor atualizado com sucesso.' : 'Sensor cadastrado com sucesso.');
            header('Location: sensores.php');
            exit;
        }
        $stmt->close();
        $erros[] = 'Não foi possível gravar o sensor. Tente novamente.';
    }
} elseif (isset($_GET['id_sensor'])) {
    $id_sensor = (int)$_GET['id_sensor'];
    $stmt = $conexao->prepare('SELECT id_trem, codigo, tipo, localizacao, segmento FROM sensores WHERE id_sensor = ?');
    $stmt->bind_param('i', $id_sensor);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$linha) {
        flash('Sensor não encontrado.', 'erro');
        header('Location: sensores.php');
        exit;
    }
    $dados = array_merge($dados, array_map(fn($v) => $v ?? '', $linha));
}

$lista_trens = $conexao->query('SELECT id_trem, prefixo, modelo FROM trens ORDER BY prefixo');

$titulo = $id_sensor > 0 ? 'Editar sensor' : 'Cadastrar sensor';
require __DIR__ . '/includes/cabecalho.php';
?>
<h1><?= e($titulo) ?></h1>
<?php foreach ($erros as $erro): ?><p class="erro"><?= e($erro) ?></p><?php endforeach; ?>
<form method="POST" action="sensores_form.php">
    <input type="hidden" name="id_sensor" value="<?= (int)$id_sensor ?>">

    <label for="id_trem">Trem</label>
    <select id="id_trem" name="id_trem" required>
        <option value="">Selecione o trem</option>
        <?php while ($t = $lista_trens->fetch_assoc()): ?>
            <option value="<?= (int)$t['id_trem'] ?>" <?= (int)$dados['id_trem'] === (int)$t['id_trem'] ? 'selected' : '' ?>>
                <?= e($t['prefixo']) ?> — <?= e($t['modelo']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="codigo">Código</label>
    <input type="text" id="codigo" name="codigo" maxlength="20" placeholder="S-TEMP-001" value="<?= e($dados['codigo']) ?>" required>

    <label for="tipo">Tipo</label>
    <select id="tipo" name="tipo" required>
        <option value="">Selecione o tipo</option>
        <?php foreach (tiposSensor() as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $dados['tipo'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="localizacao">Localização no trem</label>
    <input type="text" id="localizacao" name="localizacao" maxlength="80" placeholder="Motor, eixo dianteiro, cabine" value="<?= e($dados['localizacao']) ?>">

    <label for="segmento">Segmento da malha</label>
    <input type="text" id="segmento" name="segmento" maxlength="80" value="<?= e($dados['segmento']) ?>">

    <button type="submit">Salvar</button>
    <a href="sensores.php">Cancelar</a>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
