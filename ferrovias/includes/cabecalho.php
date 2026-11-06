<?php
// Requer: proteger.php e permissao.php ja incluidos. Opcional: $titulo
require_once __DIR__ . '/funcoes.php';
$titulo = isset($titulo) ? $titulo : 'Sistema Ferroviario';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?></title>
    <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
<header>
    <span>Conectado como <?= e($_SESSION['usuario_nome']) ?> (<?= e($_SESSION['usuario_papel']) ?>)</span>
    <nav>
        <a href="dashboard.php">Painel</a>
        <?php if (temPapel(['administrador'])): ?>
            <a href="trens.php">Trens</a>
        <?php endif; ?>
        <?php if (temPapel(['administrador', 'maquinista'])): ?>
            <a href="sensores.php">Sensores</a>
            <a href="leituras.php">Leituras</a>
            <a href="notificacoes_alertas.php">Alertas</a>
            <a href="rotas.php">Rotas</a>
        <?php endif; ?>
        <?php if (temPapel(['administrador'])): ?>
            <a href="simulador.php">Simulador</a>
            <a href="usuarios.php">Usuarios</a>
        <?php endif; ?>
        <a href="sair.php">Sair</a>
    </nav>
</header>
<main>
