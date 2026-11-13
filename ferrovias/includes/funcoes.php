<?php
// Escapa texto para exibir na tela
function e($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Marcador de icone (o desenho e feito no CSS: icone-inicio, icone-frota, icone-sino...)
function icone($nome)
{
    return '<span class="icone icone-' . e($nome) . '" aria-hidden="true"></span>';
}

// Mensagem que sobrevive ao redirecionamento (apos gravar/excluir)
function flash($mensagem, $tipo = 'sucesso')
{
    $_SESSION['flash'] = ['msg' => $mensagem, 'tipo' => $tipo];
}

function mostrarFlash()
{
    if (isset($_SESSION['flash'])) {
        $tipo = $_SESSION['flash']['tipo'] === 'erro' ? 'erro' : 'sucesso';
        echo '<div class="mensagens" role="' . ($tipo === 'erro' ? 'alert' : 'status') . '">'
           . '<p class="mensagem mensagem-' . $tipo . '">' . e($_SESSION['flash']['msg']) . '</p></div>';
        unset($_SESSION['flash']);
    }
}

// Lista de erros de validacao
function mostrarErros($erros)
{
    if (count($erros) === 0) {
        return;
    }
    echo '<div class="mensagens" role="alert">';
    foreach ($erros as $erro) {
        echo '<p class="mensagem mensagem-erro">' . e($erro) . '</p>';
    }
    echo '</div>';
}

function mostrarSucesso($texto)
{
    if ($texto !== '') {
        echo '<div class="mensagens" role="status"><p class="mensagem mensagem-sucesso">' . e($texto) . '</p></div>';
    }
}

// Nomes de perfil mostrados na tela (os valores gravados no banco continuam os do guia):
// Gestor = administrador | Maquinista = maquinista | Funcionario = usuario_comum
function papeisSistema()
{
    return [
        'administrador' => 'Gestor',
        'maquinista'    => 'Máquinista',
        'usuario_comum' => 'Funcionário',
    ];
}

function rotuloPapel($papel)
{
    return papeisSistema()[$papel] ?? $papel;
}

// Status do trem -> texto da tela (valores do banco: em_operacao, manutencao, inativo)
function statusTrens()
{
    return [
        'em_operacao' => 'Ativo',
        'manutencao'  => 'Manutenção',
        'inativo'     => 'Parado',
    ];
}

// Classe de cor semantica (verde/amarelo/vermelho)
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
    echo '<nav class="paginacao" aria-label="Paginação">';
    if ($pagina > 1) {
        echo '<a class="paginacao-anterior" href="' . e($arquivo . '?' . http_build_query($params + ['pagina' => $pagina - 1])) . '">Anterior</a>';
    }
    echo '<span class="paginacao-atual">Página ' . (int)$pagina . ' de ' . (int)$total_paginas . '</span>';
    if ($pagina < $total_paginas) {
        echo '<a class="paginacao-proxima" href="' . e($arquivo . '?' . http_build_query($params + ['pagina' => $pagina + 1])) . '">Próxima</a>';
    }
    echo '</nav>';
}

function rotulosSeveridade()
{
    return ['normal' => 'Normal', 'atencao' => 'Atenção', 'critico' => 'Crítico'];
}
