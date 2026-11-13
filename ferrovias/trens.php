<?php
require __DIR__ . '/includes/proteger.php';
require __DIR__ . '/includes/permissao.php';
exigirPapel(['administrador']);
require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/funcoes.php';

// Exclusao: apenas por POST, com papel verificado antes do DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $id = (int)$_POST['excluir_id'];
    $stmt = $conexao->prepare('DELETE FROM trens WHERE id_trem = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            flash('Trem excluído com sucesso.');
        } else {
            flash('Trem não encontrado.', 'erro');
        }
    } elseif ($conexao->errno === 1451) {
        flash('Não é possível excluir: o trem possui sensores ou rotas vinculados.', 'erro');
    } else {
        flash('Não foi possível excluir o trem.', 'erro');
    }
    $stmt->close();
    header('Location: trens.php');
    exit;
}

// Busca e filtro por status (condicoes acumuladas, valores sempre via bind_param)
$busca  = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$condicoes = [];
$valores = [];
$tipos = '';

if ($busca !== '') {
    $condicoes[] = '(trens.prefixo LIKE ? OR trens.modelo LIKE ?)';
    $termo = '%' . $busca . '%';
    $valores[] = $termo;
    $valores[] = $termo;
    $tipos .= 'ss';
}
if ($status !== '' && array_key_exists($status, statusTrens())) {
    $condicoes[] = 'trens.status = ?';
    $valores[] = $status;
    $tipos .= 's';
}

$sql = 'SELECT trens.*, COUNT(sensores.id_sensor) AS qtd_sensores
        FROM trens
        LEFT JOIN sensores ON sensores.id_trem = trens.id_trem';
if (count($condicoes) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}
$sql .= ' GROUP BY trens.id_trem ORDER BY trens.prefixo';

$stmt = $conexao->prepare($sql);
if (count($valores) > 0) {
    $stmt->bind_param($tipos, ...$valores);
}
$stmt->execute();
$resultado = $stmt->get_result();

$titulo = 'Trens';
$voltar = 'dashboard.php';
require __DIR__ . '/includes/cabecalho.php';
mostrarFlash();
?>
<section class="pagina-cabecalho">
    <h1 class="titulo-pagina">Trens</h1>
</section>

<form class="filtros" method="GET" action="trens.php" role="search">
    <div class="campo campo-busca">
        <label class="visualmente-oculto" for="busca">Buscar</label>
        <input class="campo-entrada" type="search" id="busca" name="busca" placeholder="Buscar por prefixo ou modelo..." value="<?= e($busca) ?>">
    </div>
    <div class="campo">
        <label class="visualmente-oculto" for="status">Status</label>
        <select class="campo-entrada" id="status" name="status">
            <option value="">Todos os status</option>
            <?php foreach (statusTrens() as $valor => $rotulo): ?>
                <option value="<?= e($valor) ?>" <?= $status === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filtros-acoes">
        <button class="botao botao-secundario" type="submit">Filtrar</button>
        <a class="botao-link" href="trens.php">Limpar</a>
    </div>
</form>

<?php if ($resultado->num_rows === 0): ?>
    <p class="estado-vazio">Nenhum trem encontrado.</p>
<?php else: ?>
<table class="tabela tabela-cartoes tabela-trens">
    <thead>
        <tr>
            <th scope="col">Prefixo</th><th scope="col">Modelo</th><th scope="col">Ano</th><th scope="col">Status</th>
            <th scope="col">Capacidade (t)</th><th scope="col">Última inspeção</th><th scope="col">Sensores</th><th scope="col">Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($trem = $resultado->fetch_assoc()): ?>
        <tr class="linha">
            <td class="celula-principal" data-label="Prefixo"><?= e($trem['prefixo']) ?></td>
            <td data-label="Modelo"><?= e($trem['modelo']) ?></td>
            <td data-label="Ano"><?= e($trem['ano']) ?></td>
            <td data-label="Status"><span class="selo <?= e(classeStatusTrem($trem['status'])) ?>"><?= e(statusTrens()[$trem['status']] ?? $trem['status']) ?></span></td>
            <td data-label="Capacidade (t)"><?= e(number_format((float)$trem['capacidade'], 2, ',', '.')) ?></td>
            <td data-label="Última inspeção"><?= $trem['ultima_inspecao'] ? e(date('d/m/Y', strtotime($trem['ultima_inspecao']))) : 'Sem inspeção' ?></td>
            <td data-label="Sensores"><?= e($trem['qtd_sensores']) ?></td>
            <td class="celula-acoes" data-label="Ações">
                <a class="botao botao-editar" href="trens_form.php?id_trem=<?= (int)$trem['id_trem'] ?>">Editar</a>
                <form class="form-linha" method="POST" action="trens.php" onsubmit="return confirm('Confirma a exclusão?');">
                    <input type="hidden" name="excluir_id" value="<?= (int)$trem['id_trem'] ?>">
                    <button class="botao botao-perigo" type="submit">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<?php endif; $stmt->close(); ?>

<div class="acoes-pagina">
    <a class="botao botao-primario botao-largo" href="trens_form.php">Adicionar</a>
</div>
<?php require __DIR__ . '/includes/rodape.php'; ?>
