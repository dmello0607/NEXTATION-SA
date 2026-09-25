<?php

// Arquivo desenvolvido com auxilio de IA (ClaudeIA)

require_once 'config.php';
 
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Tabela 'users' criada com sucesso (ou já existia).";
} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}