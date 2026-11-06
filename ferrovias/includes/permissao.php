<?php
// Retorna true se o papel do usuario logado esta na lista
function temPapel($papeis_permitidos)
{
    return isset($_SESSION['usuario_papel'])
        && in_array($_SESSION['usuario_papel'], $papeis_permitidos, true);
}

// Usar no topo da pagina: encerra a execucao se o papel nao for permitido
function exigirPapel($papeis_permitidos)
{
    if (!temPapel($papeis_permitidos)) {
        http_response_code(403);
        die('Acesso negado: seu perfil nao tem permissao para esta tela. <a href="dashboard.php">Voltar</a>');
    }
}
