<?php
// Regras de limite e classificacao (um lugar so). Os limites ficam na tabela "limites".

function tiposSensor()
{
    return [
        'velocidade'        => 'Velocidade',
        'temperatura_motor' => 'Temperatura do motor',
        'consumo_energia'   => 'Consumo de energia',
        'localizacao'       => 'Localização',
    ];
}

function unidadeDoTipo($tipo)
{
    $mapa = ['velocidade' => 'km/h', 'temperatura_motor' => 'C', 'consumo_energia' => 'kWh', 'localizacao' => ''];
    return $mapa[$tipo] ?? '';
}

// Funcao pura: ate o limite = normal; ate 10% acima = atencao; mais que isso = critico
function classificarValor($valor, $limite)
{
    if ($limite === null) {
        return 'normal';
    }
    if ($valor <= $limite) {
        return 'normal';
    }
    if ($valor <= $limite * 1.10) {
        return 'atencao';
    }
    return 'critico';
}

function limiteDoTipo($conexao, $tipo)
{
    $stmt = $conexao->prepare('SELECT limite_max FROM limites WHERE tipo = ?');
    $stmt->bind_param('s', $tipo);
    $stmt->execute();
    $linha = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $linha ? (float)$linha['limite_max'] : null;
}

function classificar($conexao, $tipo, $valor)
{
    return classificarValor($valor, limiteDoTipo($conexao, $tipo));
}

// Faixa de valores "normais" (do minimo plausivel ate o limite) por tipo de sensor
function faixaNormal($tipo, $limite)
{
    $minimos = ['velocidade' => 20, 'temperatura_motor' => 60, 'consumo_energia' => 250];
    $min = $minimos[$tipo] ?? ($limite * 0.5);
    return [$min, $limite];
}

// Sorteia um valor coerente com o tipo do sensor.
// $fora = false: valor dentro do limite. $fora = true: acima do limite (metade atencao, metade critico).
function sortearValor($tipo, $limite, $fora)
{
    if ($fora) {
        if (mt_rand(0, 1) === 0) {
            $ini = $limite * 1.01;
            $fim = $limite * 1.10;
        } else {
            $ini = $limite * 1.11;
            $fim = $limite * 1.35;
        }
    } else {
        list($ini, $fim) = faixaNormal($tipo, $limite);
    }
    $valor = $ini + (mt_rand() / mt_getrandmax()) * ($fim - $ini);
    return round($valor, 2);
}
