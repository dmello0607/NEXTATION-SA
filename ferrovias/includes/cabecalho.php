<?php
// Requer: proteger.php e permissao.php ja incluidos.
// Opcionais definidas pela pagina antes do require: $titulo (titulo da aba) e $voltar (endereco do botao Voltar).
require_once __DIR__ . '/funcoes.php';
$titulo = isset($titulo) ? $titulo : 'Nextation';
$pagina_atual = basename($_SERVER['SCRIPT_NAME'], '.php');

$eh_gestor  = temPapel(['administrador']);
$ve_operacao = temPapel(['administrador', 'maquinista']);

// Grupo de menu ao qual a pagina pertence (para marcar o item ativo com aria-current)
$grupos = [
    'inicio'     => ['dashboard'],
    'frota'      => ['trens', 'trens_form'] + ($eh_gestor ? [] : [2 => 'sensores']),
    'relatorios' => ['gera_relatorio', 'visualiza_relatorio'],
];
function itemAtual($grupos, $grupo, $pagina)
{
    return in_array($pagina, $grupos[$grupo], true) ? ' aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> - Nextation</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body class="pagina pagina-<?= e($pagina_atual) ?>">
<div class="app">

    <header class="topo">
        <div class="topo-esquerda">
            <?php if (isset($voltar)): ?>
                <a class="botao-voltar" href="<?= e($voltar) ?>"><?= icone('voltar') ?><span class="botao-voltar-texto">Voltar</span></a>
            <?php endif; ?>
            <a class="marca" href="dashboard.php">
                <img class="marca-logo" src="assets/img/logo.png" alt="">
                <span class="marca-nome">Nextation</span>
            </a>
        </div>
        <div class="topo-direita">
            <p class="usuario-logado">Conectado como <strong class="usuario-nome"><?= e($_SESSION['usuario_nome']) ?></strong> <span class="usuario-papel">(<?= e(rotuloPapel($_SESSION['usuario_papel'])) ?>)</span></p>
            <?php if ($ve_operacao): ?>
                <a class="botao-alertas" href="notificacoes_alertas.php" aria-label="Alertas"><?= icone('sino') ?></a>
            <?php endif; ?>
        </div>
    </header>

    <nav class="navegacao" aria-label="Navegação principal">
        <ul class="navegacao-lista">
            <li class="navegacao-item">
                <a class="navegacao-link" href="dashboard.php"<?= itemAtual($grupos, 'inicio', $pagina_atual) ?>>
                    <?= icone('inicio') ?><span class="navegacao-texto">Início</span>
                </a>
            </li>
            <?php if ($eh_gestor): ?>
            <li class="navegacao-item">
                <a class="navegacao-link" href="trens.php"<?= itemAtual($grupos, 'frota', $pagina_atual) ?>>
                    <?= icone('frota') ?><span class="navegacao-texto">Frota</span>
                </a>
            </li>
            <li class="navegacao-item">
                <a class="navegacao-link" href="gera_relatorio.php"<?= itemAtual($grupos, 'relatorios', $pagina_atual) ?>>
                    <?= icone('relatorios') ?><span class="navegacao-texto">Relatórios</span>
                </a>
            </li>
            <?php elseif ($ve_operacao): ?>
            <li class="navegacao-item">
                <a class="navegacao-link" href="sensores.php"<?= itemAtual($grupos, 'frota', $pagina_atual) ?>>
                    <?= icone('frota') ?><span class="navegacao-texto">Frota</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($eh_gestor): ?>
            <li class="navegacao-item navegacao-desktop">
                <a class="navegacao-link" href="usuarios.php"<?= in_array($pagina_atual, ['usuarios', 'usuarios_form'], true) ? ' aria-current="page"' : '' ?>>
                    <?= icone('usuarios') ?><span class="navegacao-texto">Usuários</span>
                </a>
            </li>
            <li class="navegacao-item navegacao-desktop">
                <a class="navegacao-link" href="sensores.php"<?= in_array($pagina_atual, ['sensores', 'sensores_form'], true) ? ' aria-current="page"' : '' ?>>
                    <?= icone('sensores') ?><span class="navegacao-texto">Sensores</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="navegacao-item navegacao-mais">
                <details class="menu-mais">
                    <summary class="navegacao-link"><?= icone('mais') ?><span class="navegacao-texto">Mais</span></summary>
                    <ul class="menu-mais-lista">
                        <?php if ($ve_operacao): ?>
                            <li><a href="sensores.php">Sensores</a></li>
                            <li><a href="leituras.php">Leituras</a></li>
                            <li><a href="notificacoes_alertas.php">Alertas</a></li>
                            <li><a href="rotas.php">Rotas</a></li>
                        <?php endif; ?>
                        <?php if ($eh_gestor): ?>
                            <li><a href="simulador.php">Simulador</a></li>
                            <li><a href="usuarios.php">Usuários</a></li>
                        <?php endif; ?>
                        <li><a class="menu-mais-sair" href="sair.php">Sair</a></li>
                    </ul>
                </details>
            </li>
        </ul>
    </nav>

    <main class="conteudo">
