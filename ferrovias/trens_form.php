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

$editando = $id_trem > 0;
$titulo = $editando ? 'Editar ' . ($dados['prefixo'] !== '' ? $dados['prefixo'] : 'trem') : 'Cadastrar trem';
$voltar = 'trens.php';

// Situacao atual do trem (so na edicao): ultima velocidade e ultima atualizacao vindas das leituras
$velocidade_atual = null;
$ultima_atualizacao = null;
if ($editando) {
    $stmt = $conexao->prepare(
        "SELECT leituras.valor, leituras.unidade FROM leituras
         INNER JOIN sensores ON sensores.id_sensor = leituras.id_sensor
         WHERE sensores.id_trem = ? AND sensores.tipo = 'velocidade'
         ORDER BY leituras.data_hora DESC, leituras.id_leitura DESC LIMIT 1"
    );
    $stmt->bind_param('i', $id_trem);
    $stmt->execute();
    $velocidade_atual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conexao->prepare(
        'SELECT MAX(leituras.data_hora) AS ultima FROM leituras
         INNER JOIN sensores ON sensores.id_sensor = leituras.id_sensor WHERE sensores.id_trem = ?'
    );
    $stmt->bind_param('i', $id_trem);
    $stmt->execute();
    $ultima_atualizacao = $stmt->get_result()->fetch_assoc()['ultima'];
    $stmt->close();
}

require __DIR__ . '/includes/cabecalho.php';
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
</section>

<?php if ($editando): ?>
<section class="resumo-trem" aria-label="Situação atual do trem">
    <p class="resumo-status"><span class="selo <?= e(classeStatusTrem($dados['status'])) ?>"><?= e(statusTrens()[$dados['status']] ?? '') ?></span></p>
    <dl class="resumo-lista">
        <div class="resumo-item">
            <dt>Velocidade atual</dt>
            <dd><?= $velocidade_atual ? e(number_format((float)$velocidade_atual['valor'], 0, ',', '.') . ' ' . $velocidade_atual['unidade']) : 'Sem leitura' ?></dd>
        </div>
        <div class="resumo-item">
            <dt>Última atualização</dt>
            <dd><?= $ultima_atualizacao ? e(date('d/m/Y H:i', strtotime($ultima_atualizacao))) : 'Sem leitura' ?></dd>
        </div>
    </dl>
</section>
<?php endif; ?>

<?php mostrarErros($erros); ?>

<form class="formulario formulario-trem" method="POST" action="trens_form.php">
    <input type="hidden" name="id_trem" value="<?= (int)$id_trem ?>">

    <fieldset class="grupo grupo-identificacao">
        <legend class="grupo-titulo">Identificação</legend>
        <div class="campo">
            <label class="campo-rotulo" for="prefixo">ID do trem (prefixo)</label>
            <input class="campo-entrada" type="text" id="prefixo" name="prefixo" maxlength="10" placeholder="TR-204" value="<?= e($dados['prefixo']) ?>" required>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="modelo">Modelo</label>
            <input class="campo-entrada" type="text" id="modelo" name="modelo" maxlength="60" value="<?= e($dados['modelo']) ?>" required>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="ano">Ano</label>
            <input class="campo-entrada" type="number" id="ano" name="ano" min="1900" max="2100" value="<?= e($dados['ano']) ?>" required>
        </div>
    </fieldset>

    <fieldset class="grupo grupo-especificacoes">
        <legend class="grupo-titulo">Especificações</legend>
        <div class="campo">
            <label class="campo-rotulo" for="capacidade">Capacidade (toneladas)</label>
            <input class="campo-entrada" type="text" id="capacidade" name="capacidade" inputmode="decimal" value="<?= e($dados['capacidade']) ?>" required>
        </div>
    </fieldset>

    <fieldset class="grupo grupo-operacional">
        <legend class="grupo-titulo">Operacional</legend>
        <div class="campo campo-opcoes">
            <span class="campo-rotulo" id="rotulo-status">Status</span>
            <div class="opcoes" role="radiogroup" aria-labelledby="rotulo-status">
                <?php foreach (statusTrens() as $valor => $rotulo): ?>
                    <label class="opcao <?= e(classeStatusTrem($valor)) ?>">
                        <input type="radio" name="status" value="<?= e($valor) ?>" <?= $dados['status'] === $valor ? 'checked' : '' ?> required>
                        <span class="opcao-texto"><?= e($rotulo) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="ultima_inspecao">Última inspeção</label>
            <input class="campo-entrada" type="date" id="ultima_inspecao" name="ultima_inspecao" value="<?= e($dados['ultima_inspecao']) ?>">
            <small class="campo-ajuda">Opcional.</small>
        </div>
    </fieldset>

    <div class="formulario-acoes">
        <button class="botao botao-primario botao-largo" type="submit"><?= $editando ? 'Salvar' : 'Adicionar' ?></button>
    </div>
</form>
<?php require __DIR__ . '/includes/rodape.php'; ?>
