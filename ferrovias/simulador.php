<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';
require __DIR__ . '/includes/limites.php';

$erros = [];
$id_sensor = 0;
$quantidade = 10;
$fora = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sensor  = (int)($_POST['id_sensor'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $fora       = isset($_POST['fora_do_limite']);

    if ($quantidade < 1 || $quantidade > 200) {
        $erros[] = 'Informe uma quantidade de leituras entre 1 e 200.';
    }

    // O sensor precisa existir e ter limite cadastrado (o tipo define a faixa de valores)
    $stmt = $conexao->prepare('SELECT id_sensor, tipo FROM sensores WHERE id_sensor = ?');
    $stmt->bind_param('i', $id_sensor);
    $stmt->execute();
    $sensor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $limite = null;
    if (!$sensor) {
        $erros[] = 'Selecione um sensor válido.';
    } else {
        $limite = limiteDoTipo($conexao, $sensor['tipo']);
        if ($limite === null) {
            $erros[] = 'Este tipo de sensor não possui limite cadastrado, então não gera leituras numéricas.';
        }
    }

    if (count($erros) === 0) {
        $tipo    = $sensor['tipo'];
        $unidade = unidadeDoTipo($tipo);
        $rotulo  = tiposSensor()[$tipo] ?? $tipo;

        // As consultas sao preparadas UMA vez, fora do laco; dentro dele so mudam os valores
        $stmtLeitura = $conexao->prepare('INSERT INTO leituras (id_sensor, data_hora, valor, unidade) VALUES (?, ?, ?, ?)');
        $stmtAlerta  = $conexao->prepare('INSERT INTO alertas (id_leitura, tipo, severidade, mensagem, data_hora) VALUES (?, ?, ?, ?, ?)');

        if (!$stmtLeitura || !$stmtAlerta) {
            $erros[] = 'Não foi possível preparar a gravação das leituras.';
        } else {
            // Leitura e alerta na mesma transacao: ou gravam os dois, ou nenhum
            $conexao->begin_transaction();
            $ok = true;
            $total_alertas = 0;
            $contagem = ['atencao' => 0, 'critico' => 0];

            for ($i = 0; $i < $quantidade; $i++) {
                // Calculado a cada volta: cada leitura tem um horario diferente (5 min entre elas)
                $data_hora = date('Y-m-d H:i:s', time() - ($i * 300));
                $valor = sortearValor($tipo, $limite, $fora);

                $stmtLeitura->bind_param('isds', $id_sensor, $data_hora, $valor, $unidade);
                if (!$stmtLeitura->execute()) {
                    $ok = false;
                    break;
                }
                $id_leitura = $conexao->insert_id;

                $severidade = classificarValor($valor, $limite);
                if ($severidade !== 'normal') {
                    $mensagem = sprintf('%s %.2f %s acima do limite de %.2f %s', $rotulo, $valor, $unidade, $limite, $unidade);
                    $stmtAlerta->bind_param('issss', $id_leitura, $tipo, $severidade, $mensagem, $data_hora);
                    if (!$stmtAlerta->execute()) {
                        $ok = false;
                        break;
                    }
                    $total_alertas++;
                    $contagem[$severidade]++;
                }
            }

            $stmtLeitura->close();
            $stmtAlerta->close();

            if ($ok) {
                $conexao->commit();
                flash(sprintf('%d leituras geradas. Alertas criados: %d (%d atenção, %d crítico).',
                    $quantidade, $total_alertas, $contagem['atencao'], $contagem['critico']));
                header('Location: leituras.php?id_sensor=' . $id_sensor);
                exit;
            }
            $conexao->rollback();
            $erros[] = 'Erro ao gravar as leituras. Nada foi gravado.';
        }
    }
}

// Sensores numericos (os que tem limite) para a lista de escolha
$lista = $conexao->query(
    'SELECT sensores.id_sensor, sensores.codigo, sensores.tipo, trens.prefixo
     FROM sensores
     INNER JOIN trens ON trens.id_trem = sensores.id_trem
     INNER JOIN limites ON limites.tipo = sensores.tipo
     ORDER BY trens.prefixo, sensores.codigo'
);

$titulo = 'Simulador de leituras';
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Simulador de leituras</h1>
    <p class="subtitulo">Gera leituras como se viessem dos sensores. Com "fora do limite" marcado, os valores ficam acima do limite e geram alertas.</p>
</section>

<?php mostrarErros($erros); ?>

<?php if ($lista->num_rows === 0): ?>
    <p class="estado-vazio">Nenhum sensor disponível. Cadastre um sensor de velocidade, temperatura ou consumo primeiro.</p>
<?php else: ?>
<form class="formulario formulario-simulador" method="POST" action="simulador.php">
    <fieldset class="grupo grupo-simulacao">
        <legend class="grupo-titulo">Simulação</legend>
        <div class="campo">
            <label class="campo-rotulo" for="id_sensor">Sensor</label>
            <select class="campo-entrada" id="id_sensor" name="id_sensor" required>
                <option value="">Selecione o sensor</option>
                <?php while ($s = $lista->fetch_assoc()): ?>
                    <option value="<?= (int)$s['id_sensor'] ?>" <?= $id_sensor === (int)$s['id_sensor'] ? 'selected' : '' ?>>
                        <?= e($s['codigo']) ?> — <?= e($s['prefixo']) ?> (<?= e(tiposSensor()[$s['tipo']] ?? $s['tipo']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo-rotulo" for="quantidade">Quantidade de leituras</label>
            <input class="campo-entrada" type="number" id="quantidade" name="quantidade" min="1" max="200" value="<?= (int)$quantidade ?>" required>
            <small class="campo-ajuda">De 1 a 200.</small>
        </div>
        <div class="campo campo-opcoes">
            <label class="opcao opcao-caixa">
                <input type="checkbox" name="fora_do_limite" <?= $fora ? 'checked' : '' ?>>
                <span class="opcao-texto">Gerar valores fora do limite</span>
            </label>
        </div>
    </fieldset>

    <div class="formulario-acoes">
        <button class="botao botao-primario botao-largo" type="submit">Gerar leituras</button>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/rodape.php'; ?>
