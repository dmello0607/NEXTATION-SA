-- Sistema de Monitoramento Ferroviario - script completo (pode ser importado varias vezes)
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS ferrovias_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ferrovias_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS relatorios;
DROP TABLE IF EXISTS recuperacoes_senha;
DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS leituras;
DROP TABLE IF EXISTS rotas;
DROP TABLE IF EXISTS sensores;
DROP TABLE IF EXISTS trens;
DROP TABLE IF EXISTS limites;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(80) NOT NULL,
    login VARCHAR(30) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    papel ENUM('administrador','maquinista','usuario_comum') NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_login (login)
);

CREATE TABLE trens (
    id_trem INT NOT NULL AUTO_INCREMENT,
    prefixo VARCHAR(10) NOT NULL,
    modelo VARCHAR(60) NOT NULL,
    ano INT NOT NULL,
    status ENUM('em_operacao','manutencao','inativo') NOT NULL DEFAULT 'em_operacao',
    capacidade DECIMAL(8,2) NOT NULL,
    ultima_inspecao DATE NULL,
    PRIMARY KEY (id_trem),
    UNIQUE KEY uq_trens_prefixo (prefixo)
);

-- ON DELETE RESTRICT: nao se exclui trem que tem sensores (preserva historico)
CREATE TABLE sensores (
    id_sensor INT NOT NULL AUTO_INCREMENT,
    id_trem INT NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    localizacao VARCHAR(80),
    segmento VARCHAR(80),
    PRIMARY KEY (id_sensor),
    UNIQUE KEY uq_sensores_codigo (codigo),
    CONSTRAINT fk_sensores_trens FOREIGN KEY (id_trem) REFERENCES trens (id_trem) ON DELETE RESTRICT
);

CREATE TABLE rotas (
    id_rota INT NOT NULL AUTO_INCREMENT,
    id_trem INT NOT NULL,
    origem VARCHAR(80) NOT NULL,
    destino VARCHAR(80) NOT NULL,
    data_viagem DATE NOT NULL,
    hora_partida TIME NOT NULL,
    hora_chegada TIME,
    status ENUM('programada','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'programada',
    PRIMARY KEY (id_rota),
    CONSTRAINT fk_rotas_trens FOREIGN KEY (id_trem) REFERENCES trens (id_trem) ON DELETE RESTRICT
);

CREATE TABLE leituras (
    id_leitura INT NOT NULL AUTO_INCREMENT,
    id_sensor INT NOT NULL,
    data_hora DATETIME NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    unidade VARCHAR(10) NOT NULL,
    PRIMARY KEY (id_leitura),
    CONSTRAINT fk_leituras_sensores FOREIGN KEY (id_sensor) REFERENCES sensores (id_sensor) ON DELETE CASCADE
);
CREATE INDEX ix_leituras_sensor ON leituras (id_sensor);
CREATE INDEX ix_leituras_data ON leituras (data_hora);

CREATE TABLE alertas (
    id_alerta INT NOT NULL AUTO_INCREMENT,
    id_leitura INT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    severidade ENUM('normal','atencao','critico') NOT NULL,
    mensagem VARCHAR(200) NOT NULL,
    data_hora DATETIME NOT NULL,
    PRIMARY KEY (id_alerta),
    CONSTRAINT fk_alertas_leituras FOREIGN KEY (id_leitura) REFERENCES leituras (id_leitura) ON DELETE CASCADE
);

-- Limites ajustaveis sem mexer no codigo (decisao: tabela no banco)
CREATE TABLE limites (
    tipo VARCHAR(40) NOT NULL,
    limite_max DECIMAL(10,2) NOT NULL,
    unidade VARCHAR(10) NOT NULL,
    PRIMARY KEY (tipo)
);

-- Historico de relatorios gerados (quem gerou, quando e com quais filtros)
CREATE TABLE relatorios (
    id_relatorio INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    filtros VARCHAR(255) NOT NULL,
    gerado_em DATETIME NOT NULL,
    PRIMARY KEY (id_relatorio),
    CONSTRAINT fk_relatorios_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE RESTRICT
);

-- Recuperacao de senha: guarda so o HASH do token (como a senha, o token nunca fica legivel)
CREATE TABLE recuperacoes_senha (
    id_recuperacao INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_recuperacao),
    UNIQUE KEY uq_recuperacoes_token (token_hash),
    CONSTRAINT fk_recuperacoes_usuarios FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
);

-- ---------- DADOS DE EXEMPLO ----------
-- Senha de todos os usuarios de teste: 123456 (guardada em hash bcrypt)
INSERT INTO usuarios (nome, login, senha, papel) VALUES
('Administrador do Sistema', 'admin',      '$2y$10$Elfhm3B83RwR9NhcJG/l9.qLmvT5xSQck44trODMweQDIE/Y0hrOO', 'administrador'),
('Carlos Mendes',            'maquinista', '$2y$10$ReZUyj7ZcTlo7l4jl6JPue/LhxSB.Uz2qv6S2PMsy451jm9nthUBa', 'maquinista'),
('Fernanda Lima',            'usuario',    '$2y$10$3vN7YdF3bFk75kUvi.7poOE9.Y8W3Set8GPQHIUscz1ALQ8ky/emm', 'usuario_comum');

INSERT INTO trens (prefixo, modelo, ano, status, capacidade, ultima_inspecao) VALUES
('TR-204', 'Alstom Coradia', 2012, 'em_operacao', 180.00, '2026-09-15'),
('TR-310', 'GE ES44',        2016, 'em_operacao', 240.50, '2026-10-02'),
('TR-118', 'Stadler KISS',   2009, 'manutencao',  150.00, '2026-08-20'),
('TR-420', 'EMD GT22',       2003, 'inativo',     200.00, NULL);

INSERT INTO sensores (id_trem, codigo, tipo, localizacao, segmento) VALUES
(1, 'S-VEL-001',  'velocidade',        'Cabine',           'Joinville - Curitiba'),
(1, 'S-TEMP-001', 'temperatura_motor', 'Motor',            'Joinville - Curitiba'),
(2, 'S-ENER-001', 'consumo_energia',   'Painel eletrico',  'Curitiba - Paranagua'),
(2, 'S-TEMP-002', 'temperatura_motor', 'Motor',            'Curitiba - Paranagua'),
(3, 'S-VEL-002',  'velocidade',        'Eixo dianteiro',   'Oficina Joinville'),
(3, 'S-LOC-001',  'localizacao',       'Cabine',           'Oficina Joinville');

INSERT INTO rotas (id_trem, origem, destino, data_viagem, hora_partida, hora_chegada, status) VALUES
(1, 'Joinville', 'Curitiba',  '2026-11-10', '06:30:00', '09:45:00', 'programada'),
(2, 'Curitiba',  'Paranagua', '2026-11-09', '08:00:00', '11:20:00', 'em_andamento'),
(1, 'Curitiba',  'Joinville', '2026-11-05', '14:00:00', '17:10:00', 'concluida'),
(3, 'Joinville', 'Sao Francisco do Sul', '2026-11-12', '07:15:00', NULL, 'cancelada');

-- Limites: velocidade km/h, temperatura do motor graus C, consumo kWh
INSERT INTO limites (tipo, limite_max, unidade) VALUES
('velocidade',        80.00,  'km/h'),
('temperatura_motor', 90.00,  'C'),
('consumo_energia',   500.00, 'kWh');

INSERT INTO leituras (id_sensor, data_hora, valor, unidade) VALUES
(1, '2026-11-05 14:10:00',  62.00, 'km/h'),
(1, '2026-11-05 14:40:00',  85.00, 'km/h'),
(1, '2026-11-05 15:10:00',  95.00, 'km/h'),
(2, '2026-11-05 14:10:00',  78.00, 'C'),
(2, '2026-11-05 15:10:00',  96.00, 'C'),
(2, '2026-11-05 16:10:00', 110.00, 'C'),
(3, '2026-11-09 08:30:00', 430.00, 'kWh'),
(3, '2026-11-09 09:30:00', 560.00, 'kWh');

-- Criterio: ate o limite = normal; ate 10% acima = atencao; mais que isso = critico
INSERT INTO alertas (id_leitura, tipo, severidade, mensagem, data_hora) VALUES
(2, 'velocidade',        'atencao', 'Velocidade 85.00 km/h acima do limite de 80.00 km/h', '2026-11-05 14:40:00'),
(3, 'velocidade',        'critico', 'Velocidade 95.00 km/h acima do limite de 80.00 km/h', '2026-11-05 15:10:00'),
(5, 'temperatura_motor', 'atencao', 'Temperatura 96.00 C acima do limite de 90.00 C',      '2026-11-05 15:10:00'),
(6, 'temperatura_motor', 'critico', 'Temperatura 110.00 C acima do limite de 90.00 C',     '2026-11-05 16:10:00'),
(8, 'consumo_energia',   'critico', 'Consumo 560.00 kWh acima do limite de 500.00 kWh',    '2026-11-09 09:30:00');

INSERT INTO relatorios (id_usuario, tipo, filtros, gerado_em) VALUES
(1, 'alertas_por_trem', '{"id_trem":0,"data_inicio":"2026-11-01","data_fim":"2026-11-09","severidade":""}', '2026-11-09 10:00:00'),
(1, 'alertas_por_trem', '{"id_trem":1,"data_inicio":"2026-11-05","data_fim":"2026-11-05","severidade":"critico"}', '2026-11-09 10:05:00'),
(1, 'alertas_por_trem', '{"id_trem":2,"data_inicio":"2026-11-01","data_fim":"2026-11-30","severidade":""}', '2026-11-09 10:12:00');

-- Tokens de exemplo (ja usados ou expirados; nenhum funciona)
INSERT INTO recuperacoes_senha (id_usuario, token_hash, expira_em, usado) VALUES
(2, SHA2('exemplo-token-1', 256), '2026-10-01 10:30:00', 1),
(3, SHA2('exemplo-token-2', 256), '2026-10-15 15:30:00', 0),
(3, SHA2('exemplo-token-3', 256), '2026-11-02 09:30:00', 1);
