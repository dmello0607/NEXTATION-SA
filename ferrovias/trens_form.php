<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

$erros = [];
$id_trem = 0;
$dados = ['prefixo' => '', 'modelo' => '', 'ano' => '', 'status' => 'em_operacao', 'capacidade' => '', 'ultima_inspecao' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_trem = (int)($_POST['id_trem'] ?? 0);
    $dados['prefixo']         = strtoupper(trim($_POST['prefixo'] ?? ''));
    $dados['modelo']          = trim($_POST['modelo'] ?? '');
    $dados['ano']             = trim($_POST['ano'] ?? '');
    $dados['status']          = $_POST['status'] ?? '';
    $dados['capacidade']      = str_replace(',', '.', trim($_POST['capacidade'] ?? ''));
    $dados['ultima_inspecao'] = trim($_POST['ultima_inspecao'] ?? '');

    if ($dados['prefixo'] === '' || strlen($dados['prefixo']) > 10) {
        $erros[] = 'Informe o prefixo do trem (até 10 caracteres, ex.: TR-204).';
    }
    if ($dados['modelo'] === '' || strlen($dados['modelo']) > 60) {
        $erros[] = 'Informe o modelo do trem (até 60 caracteres).';
    }
    if (!ctype_digit($dados['ano']) || $dados['ano'] < 1900 || $dados['ano'] > 2100) {
        $erros[] = 'Informe um ano entre 1900 e 2100.';
    }
    if (!is_numeric($dados['capacidade']) || $dados['capacidade'] <= 0 || $dados['capacidade'] > 999999) {
        $erros[] = 'Informe uma capacidade maior que zero (em toneladas).';
    }
    if (!array_key_exists($dados['status'], statusTrens())) {
        $erros[] = 'Selecione um status válido.';
    }
    if ($dados['ultima_inspecao'] !== '' && !dataValida($dados['ultima_inspecao'])) {
        $erros[] = 'Informe uma data de inspeção válida.';
    }

    // Prefixo nao pode repetir (ignora o proprio registro na edicao)
    if (count($erros) === 0) {
        $stmt = $conexao->prepare('SELECT id_trem FROM trens WHERE prefixo = ? AND id_trem <> ?');
        $stmt->bind_param('si', $dados['prefixo'], $id_trem);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erros[] = 'Já existe um trem com este prefixo.';
        }
        $stmt->close();
    }

    if (count($erros) === 0) {
        $ano = (int)$dados['ano'];
        $capacidade = (float)$dados['capacidade'];
        $inspecao = $dados['ultima_inspecao'] === '' ? null : $dados['ultima_inspecao'];

        if ($id_trem > 0) {
            $stmt = $conexao->prepare(
                'UPDATE trens SET prefixo = ?, modelo = ?, ano = ?, status = ?, capacidade = ?, ultima_inspecao = ? WHERE id_trem = ?'
            );
            $stmt->bind_param('ssisdsi', $dados['prefixo'], $dados['modelo'], $ano, $dados['status'], $capacidade, $inspecao, $id_trem);
        } else {
            $stmt = $conexao->prepare(
                'INSERT INTO trens (prefixo, modelo, ano, status, capacidade, ultima_inspecao) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('ssisds', $dados['prefixo'], $dados['modelo'], $ano, $dados['status'], $capacidade, $inspecao);
        }
        if ($stmt->execute()) {
            $stmt->close();
            flash($id_trem > 0 ? 'Trem atualizado com sucesso.' : 'Trem cadastrado com sucesso.');
            header('Location: trens.php');
            exit;
        }
        $stmt->close();
        $erros[] = 'Não foi possível gravar o trem. Tente novamente.';
    }
} elseif (isset($_GET['id_trem'])) {
    // Edicao: carrega os dados atuais
    $id_trem = (int)$_GET['id_trem'];
    $stmt = $conexao->prepare('SELECT prefixo, modelo, ano, status, capacidade, ultima_inspecao FROM trens WHERE id_trem = ?');
    $stmt->bind_param('i', $id_trem);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$linha) {
        flash('Trem não encontrado.', 'erro');
        header('Location: trens.php');
        exit;
    }
    $dados = array_merge($dados, array_map('strval', array_map(fn($v) => $v ?? '', $linha)));
}

$titulo = $id_trem > 0 ? 'Editar trem' : 'Cadastrar trem';
require __DIR__ . '/includes/cabecalho.php';
?>
<h1><?= e($titulo) ?></h1>
<?php foreach ($erros as $erro): ?><p class="erro"><?= e($erro) ?></p><?php endforeach; ?>
<form method="POST" action="trens_form.php">
    <input type="hidden" name="id_trem" value="<?= (int)$id_trem ?>">

    <label for="prefixo">Prefixo</label>
    <input type="text" id="prefixo" name="prefixo" maxlength="10" placeholder="TR-204" value="<?= e($dados['prefixo']) ?>" required>

    <label for="modelo">Modelo</label>
    <input type="text" id="modelo" name="modelo" maxlength="60" value="<?= e($dados['modelo']) ?>" required>

    <label for="ano">Ano</label>
    <input type="number" id="ano" name="ano" min="1900" max="2100" value="<?= e($dados['ano']) ?>" required>

    <label for="status">Status</label>
    <select id="status" name="status" required>
        <?php foreach (statusTrens() as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $dados['status'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="capacidade">Capacidade (toneladas)</label>
    <input type="text" id="capacidade" name="capacidade" value="<?= e($dados['capacidade']) ?>" required>

    <label for="ultima_inspecao">Última inspeção (opcional)</label>
    <input type="date" id="ultima_inspecao" name="ultima_inspecao" value="<?= e($dados['ultima_inspecao']) ?>">

    <button type="submit">Salvar</button>
    <a href="trens.php">Cancelar</a>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
