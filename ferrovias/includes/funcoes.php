<?php
// Escapa texto para exibir na tela
function e($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Mensagem que sobrevive ao redirecionamento (apos gravar/excluir)
function flash($mensagem, $tipo = 'sucesso')
{
    $_SESSION['flash'] = ['msg' => $mensagem, 'tipo' => $tipo];
}

function mostrarFlash()
{
    if (isset($_SESSION['flash'])) {
        echo '<p class="' . e($_SESSION['flash']['tipo']) . '">' . e($_SESSION['flash']['msg']) . '</p>';
        unset($_SESSION['flash']);
    }
}

// Status do trem -> texto e classe de cor semantica (verde/amarelo/vermelho)
function statusTrens()
{
    return [
        'em_operacao' => 'Em operação',
        'manutencao'  => 'Em manutenção',
        'inativo'     => 'Inativo',
    ];
}

function classeStatusTrem($status)
{
    $mapa = ['em_operacao' => 'normal', 'manutencao' => 'atencao', 'inativo' => 'critico'];
    return 'status-' . ($mapa[$status] ?? 'normal');
}

// Valida data no formato AAAA-MM-DD
function dataValida($texto)
{
    $d = DateTime::createFromFormat('Y-m-d', $texto);
    return $d && $d->format('Y-m-d') === $texto;
}

// Status da rota -> texto
function statusRotas()
{
    return [
        'programada'   => 'Programada',
        'em_andamento' => 'Em andamento',
        'concluida'    => 'Concluída',
        'cancelada'    => 'Cancelada',
    ];
}

// Valida hora (HH:MM ou HH:MM:SS) e devolve HH:MM:SS, ou null se invalida
function horaValida($texto)
{
    foreach (['H:i:s', 'H:i'] as $formato) {
        $h = DateTime::createFromFormat('!' . $formato, $texto);
        if ($h && $h->format($formato) === $texto) {
            return $h->format('H:i:s');
        }
    }
    return null;
}

// Monta a query string so com os filtros permitidos da tela de rotas
// (usada para manter os filtros aplicados ao voltar de editar/excluir)
function filtrosRotasQuery($origem)
{
    if (is_string($origem)) {
        parse_str($origem, $origem);
    }
    $permitidos = ['status', 'id_trem', 'data_inicio', 'data_fim', 'busca'];
    $limpo = [];
    foreach ($permitidos as $chave) {
        if (isset($origem[$chave]) && is_string($origem[$chave]) && $origem[$chave] !== '' && $origem[$chave] !== '0') {
            $limpo[$chave] = $origem[$chave];
        }
    }
    return http_build_query($limpo);
}

// Links "anterior / proxima" que preservam os filtros da tela
function paginacaoLinks($arquivo, $pagina, $total_paginas, $params)
{
    if ($total_paginas <= 1) {
        return;
    }
    unset($params['pagina']);
    echo '<p class="paginacao">';
    if ($pagina > 1) {
        echo '<a href="' . e($arquivo . '?' . http_build_query($params + ['pagina' => $pagina - 1])) . '">&laquo; Anterior</a> ';
    }
    echo 'Página ' . (int)$pagina . ' de ' . (int)$total_paginas;
    if ($pagina < $total_paginas) {
        echo ' <a href="' . e($arquivo . '?' . http_build_query($params + ['pagina' => $pagina + 1])) . '">Próxima &raquo;</a>';
    }
    echo '</p>';
}

function rotulosSeveridade()
{
    return ['normal' => 'Normal', 'atencao' => 'Atenção', 'critico' => 'Crítico'];
}
