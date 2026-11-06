<?php
// Se a conexao ja foi aberta nesta requisicao (ex.: por proteger.php), nao abre outra.
if (isset($conexao) && $conexao instanceof mysqli) {
    return;
}

// Unico arquivo que conhece os dados de acesso ao banco.
$servidor = 'localhost';
$usuario  = 'root';
$senha    = '';
$banco    = 'ferrovias_db';

// Nunca mostrar erro tecnico do PHP ao usuario (fica no log do Apache)
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Desliga excecoes automaticas do mysqli para tratarmos a falha com a nossa mensagem
mysqli_report(MYSQLI_REPORT_OFF);

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    http_response_code(500);
    die('Nao foi possivel conectar ao banco de dados. Verifique se o MySQL esta ligado e se o script banco/script.sql foi importado.');
}

$conexao->set_charset('utf8mb4');
