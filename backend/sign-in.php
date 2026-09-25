<?php

// Arquivo desenvolvido com auxilio de IA (ClaudeIA)

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.html');
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if ($name === '') {
    $errors[] = 'Nome é obrigatório.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email inválido.';
}
if (strlen($password) < 8) {
    $errors[] = 'A senha precisa ter no mínimo 8 caracteres.';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: cadastro.html?erro=1');
    exit;
}

try {
    // Verifica se o email já está cadastrado
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['errors'] = ['Este email já está cadastrado.'];
        header('Location: cadastro.html?erro=email_existente');
        exit;
    }

    // Nunca salve a senha em texto puro
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
    );
    $stmt->execute([$name, $email, $passwordHash]);

    // Cadastro concluído com sucesso
    $_SESSION['user_id'] = $pdo->lastInsertId();
    header('Location: ../main-page/main-page.html');
    exit;

} catch (PDOException $e) {
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
    $_SESSION['errors'] = ['Erro interno. Tente novamente mais tarde.'];
    header('Location: cadastro.html?erro=servidor');
    exit;
}