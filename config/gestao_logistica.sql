-- =============================================================
--  gestao_logistica.sql
--  Script completo para criação do banco de dados
--  Gerado em: 2026-06-12
--
--  Alterações em relação a projeto_teste.sql:
--    - usuario: adicionado campo trocar_senha
--    - estoque: id_produto deixa de ser UNIQUE sozinho;
--               adicionado id_armazem (estoque por armazém)
--    - movimentacao_estoque: adicionado id_armazem (nullable)
--    - entrega: adicionado id_armazem (armazém de origem)
--    - Charset atualizado para utf8mb4
-- =============================================================

CREATE DATABASE IF NOT EXISTS gestao_logistica
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestao_logistica;

SET FOREIGN_KEY_CHECKS = 0;

-- Remove tabelas existentes (ordem inversa das dependências)
DROP TABLE IF EXISTS frete;
DROP TABLE IF EXISTS alerta;
DROP TABLE IF EXISTS rastreamento;
DROP TABLE IF EXISTS viagem;
DROP TABLE IF EXISTS rota_entrega;
DROP TABLE IF EXISTS rota;
DROP TABLE IF EXISTS entrega_produto;
DROP TABLE IF EXISTS entrega;
DROP TABLE IF EXISTS movimentacao_estoque;
DROP TABLE IF EXISTS localizacao_estoque;
DROP TABLE IF EXISTS estoque;
DROP TABLE IF EXISTS produto;
DROP TABLE IF EXISTS armazem;
DROP TABLE IF EXISTS cliente;
DROP TABLE IF EXISTS veiculo;
DROP TABLE IF EXISTS motorista;
DROP TABLE IF EXISTS transportadora;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS endereco;

-- -------------------------------------------------------------
--  endereco
-- -------------------------------------------------------------
CREATE TABLE endereco (
    id_endereco INT AUTO_INCREMENT PRIMARY KEY,
    cep         VARCHAR(9),
    logradouro  VARCHAR(255),
    numero      VARCHAR(20),
    complemento VARCHAR(100),
    bairro      VARCHAR(100),
    cidade      VARCHAR(100),
    estado      CHAR(2)
);

-- -------------------------------------------------------------
--  usuario
-- -------------------------------------------------------------
CREATE TABLE usuario (
    id_usuario   INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    senha        VARCHAR(255)  NOT NULL,
    perfil       ENUM('ADMIN', 'GERENTE', 'OPERADOR') NOT NULL,
    status       ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    trocar_senha TINYINT(1)    DEFAULT 0,
    data_cadastro DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
--  transportadora
-- -------------------------------------------------------------
CREATE TABLE transportadora (
    id_transportadora INT AUTO_INCREMENT PRIMARY KEY,
    cnpj              VARCHAR(18)  NOT NULL UNIQUE,
    razao_social      VARCHAR(150) NOT NULL,
    nome_fantasia     VARCHAR(150) NOT NULL,
    telefone          VARCHAR(20),
    email             VARCHAR(100),
    id_endereco       INT NULL,
    status            ENUM('ATIVA', 'INATIVA') DEFAULT 'ATIVA',
    data_cadastro     DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transportadora_endereco
        FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

-- -------------------------------------------------------------
--  motorista
-- -------------------------------------------------------------
CREATE TABLE motorista (
    id_motorista      INT AUTO_INCREMENT PRIMARY KEY,
    id_transportadora INT          NOT NULL,
    nome              VARCHAR(150) NOT NULL,
    cpf               VARCHAR(14)  UNIQUE,
    cnh               VARCHAR(20)  UNIQUE NOT NULL,
    categoria_cnh     VARCHAR(5),
    validade_cnh      DATE,
    telefone          VARCHAR(20),
    status            ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    data_cadastro     DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_motorista_transportadora
        FOREIGN KEY (id_transportadora) REFERENCES transportadora(id_transportadora)
);

-- -------------------------------------------------------------
--  veiculo
-- -------------------------------------------------------------
CREATE TABLE veiculo (
    id_veiculo        INT AUTO_INCREMENT PRIMARY KEY,
    id_transportadora INT         NOT NULL,
    placa             VARCHAR(10) UNIQUE NOT NULL,
    modelo            VARCHAR(100),
    tipo_veiculo      VARCHAR(50),
    capacidade_carga  DECIMAL(10,2),
    status            ENUM('DISPONIVEL', 'EM_VIAGEM', 'MANUTENCAO') DEFAULT 'DISPONIVEL',
    data_cadastro     DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_veiculo_transportadora
        FOREIGN KEY (id_transportadora) REFERENCES transportadora(id_transportadora)
);

-- -------------------------------------------------------------
--  cliente
-- -------------------------------------------------------------
CREATE TABLE cliente (
    id_cliente    INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(150) NOT NULL,
    cpf_cnpj      VARCHAR(18),
    telefone      VARCHAR(20),
    id_endereco   INT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cliente_endereco
        FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

-- -------------------------------------------------------------
--  armazem
-- -------------------------------------------------------------
CREATE TABLE armazem (
    id_armazem    INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    id_endereco   INT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_armazem_endereco
        FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

-- -------------------------------------------------------------
--  produto
-- -------------------------------------------------------------
CREATE TABLE produto (
    id_produto    INT AUTO_INCREMENT PRIMARY KEY,
    descricao     VARCHAR(200) NOT NULL,
    peso          DECIMAL(10,2),
    volume        DECIMAL(10,2),
    validade      DATE,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------
--  estoque  (controle por armazém)
--  UNIQUE(id_produto, id_armazem): um registro por produto/armazém
--  id_armazem NULL = estoque global sem armazém definido
-- -------------------------------------------------------------
CREATE TABLE estoque (
    id_estoque INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    id_armazem INT NULL,
    quantidade  INT DEFAULT 0,
    UNIQUE KEY uq_estoque_produto_armazem (id_produto, id_armazem),
    CONSTRAINT fk_estoque_produto
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto),
    CONSTRAINT fk_estoque_armazem
        FOREIGN KEY (id_armazem) REFERENCES armazem(id_armazem)
);

-- -------------------------------------------------------------
--  localizacao_estoque
-- -------------------------------------------------------------
CREATE TABLE localizacao_estoque (
    id_localizacao INT AUTO_INCREMENT PRIMARY KEY,
    id_produto     INT NOT NULL,
    id_armazem     INT NOT NULL,
    corredor       VARCHAR(20),
    prateleira     VARCHAR(20),
    nivel          VARCHAR(20),
    quantidade     INT DEFAULT 0,
    CONSTRAINT fk_localizacao_produto
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto),
    CONSTRAINT fk_localizacao_armazem
        FOREIGN KEY (id_armazem) REFERENCES armazem(id_armazem)
);

-- -------------------------------------------------------------
--  movimentacao_estoque
--  id_armazem NULL = movimentação sem armazém específico
-- -------------------------------------------------------------
CREATE TABLE movimentacao_estoque (
    id_movimentacao   INT AUTO_INCREMENT PRIMARY KEY,
    id_produto        INT NOT NULL,
    id_usuario        INT NOT NULL,
    id_armazem        INT NULL,
    tipo_movimentacao ENUM('ENTRADA', 'SAIDA') NOT NULL,
    quantidade        INT NOT NULL,
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimentacao_produto
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto),
    CONSTRAINT fk_movimentacao_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    CONSTRAINT fk_movimentacao_armazem
        FOREIGN KEY (id_armazem) REFERENCES armazem(id_armazem)
);

-- -------------------------------------------------------------
--  entrega
--  id_armazem = armazém de origem para desconto de estoque
-- -------------------------------------------------------------
CREATE TABLE entrega (
    id_entrega     INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente     INT NOT NULL,
    id_armazem     INT NULL,
    data_prevista  DATE,
    data_realizada DATE,
    peso_total     DECIMAL(10,2),
    volume_total   DECIMAL(10,2),
    status         ENUM('PENDENTE', 'EM_TRANSITO', 'ENTREGUE', 'ATRASADA') DEFAULT 'PENDENTE',
    CONSTRAINT fk_entrega_cliente
        FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    CONSTRAINT fk_entrega_armazem
        FOREIGN KEY (id_armazem) REFERENCES armazem(id_armazem)
);

-- -------------------------------------------------------------
--  entrega_produto
-- -------------------------------------------------------------
CREATE TABLE entrega_produto (
    id_entrega INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    PRIMARY KEY (id_entrega, id_produto),
    CONSTRAINT fk_entrega_produto_entrega
        FOREIGN KEY (id_entrega) REFERENCES entrega(id_entrega),
    CONSTRAINT fk_entrega_produto_produto
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
);

-- -------------------------------------------------------------
--  rota
-- -------------------------------------------------------------
CREATE TABLE rota (
    id_rota      INT AUTO_INCREMENT PRIMARY KEY,
    id_veiculo   INT NOT NULL,
    id_motorista INT NOT NULL,
    distancia    DECIMAL(10,2),
    status       ENUM('PLANEJADA', 'EM_ANDAMENTO', 'FINALIZADA') DEFAULT 'PLANEJADA',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rota_veiculo
        FOREIGN KEY (id_veiculo) REFERENCES veiculo(id_veiculo),
    CONSTRAINT fk_rota_motorista
        FOREIGN KEY (id_motorista) REFERENCES motorista(id_motorista)
);

-- -------------------------------------------------------------
--  rota_entrega
-- -------------------------------------------------------------
CREATE TABLE rota_entrega (
    id_rota    INT NOT NULL,
    id_entrega INT NOT NULL,
    PRIMARY KEY (id_rota, id_entrega),
    CONSTRAINT fk_rota_entrega_rota
        FOREIGN KEY (id_rota) REFERENCES rota(id_rota),
    CONSTRAINT fk_rota_entrega_entrega
        FOREIGN KEY (id_entrega) REFERENCES entrega(id_entrega)
);

-- -------------------------------------------------------------
--  viagem
-- -------------------------------------------------------------
CREATE TABLE viagem (
    id_viagem             INT AUTO_INCREMENT PRIMARY KEY,
    id_rota               INT NOT NULL,
    data_saida            DATETIME,
    data_chegada_prevista DATETIME,
    data_chegada_real     DATETIME,
    status                ENUM('INICIADA', 'EM_TRANSITO', 'CONCLUIDA', 'CANCELADA') DEFAULT 'INICIADA',
    CONSTRAINT fk_viagem_rota
        FOREIGN KEY (id_rota) REFERENCES rota(id_rota)
);

-- -------------------------------------------------------------
--  rastreamento
-- -------------------------------------------------------------
CREATE TABLE rastreamento (
    id_rastreamento INT AUTO_INCREMENT PRIMARY KEY,
    id_viagem       INT NOT NULL,
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    data_hora       DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rastreamento_viagem
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem)
);

-- -------------------------------------------------------------
--  alerta
-- -------------------------------------------------------------
CREATE TABLE alerta (
    id_alerta   INT AUTO_INCREMENT PRIMARY KEY,
    id_viagem   INT NOT NULL,
    tipo_alerta ENUM('ATRASO', 'DESVIO_ROTA', 'PARADA_NAO_PROGRAMADA'),
    descricao   TEXT,
    data_hora   DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alerta_viagem
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem)
);

-- -------------------------------------------------------------
--  frete
-- -------------------------------------------------------------
CREATE TABLE frete (
    id_frete           INT AUTO_INCREMENT PRIMARY KEY,
    id_viagem          INT NOT NULL UNIQUE,
    id_transportadora  INT NOT NULL,
    valor              DECIMAL(12,2),
    custo_operacional  DECIMAL(12,2),
    nota_fiscal        VARCHAR(50),
    data_emissao       DATE,
    CONSTRAINT fk_frete_viagem
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem),
    CONSTRAINT fk_frete_transportadora
        FOREIGN KEY (id_transportadora) REFERENCES transportadora(id_transportadora)
);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
--  Dados iniciais
-- =============================================================

-- Administrador padrão  |  email: admin@sistema.com  |  senha: admin123
-- Hash gerado com password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuario (nome, email, senha, perfil, status, trocar_senha) VALUES (
    'Administrador',
    'admin@sistema.com',
    '$2y$10$g1f6S1cOwl9EpEdlUHBYC.MAG0cyl3zH7YIPwAWxuuK77wnGTln8y',
    'ADMIN',
    'ATIVO',
    0
);

-- =============================================================
--  Dados de exemplo
-- =============================================================

-- -------------------------------------------------------------
--  Endereços
--  1-3:  sedes das transportadoras
--  4-7:  armazéns / centros de distribuição
--  8-15: clientes
-- -------------------------------------------------------------
INSERT INTO endereco (cep, logradouro, numero, cidade, estado) VALUES
-- transportadoras
('90010-000', 'Av. Farrapos',          '1200', 'Porto Alegre',    'RS'),  -- 1
('01310-100', 'Av. Paulista',          '2500', 'São Paulo',       'SP'),  -- 2
('66010-000', 'Av. Presidente Vargas', '800',  'Belém',           'PA'),  -- 3
-- armazéns
('04794-000', 'Rod. dos Imigrantes',   'km 4', 'São Paulo',       'SP'),  -- 4
('91020-000', 'Av. Assis Brasil',      '3500', 'Porto Alegre',    'RS'),  -- 5
('71218-000', 'SCIA Quadra 14',        's/n',  'Brasília',        'DF'),  -- 6
('81200-000', 'Rod. BR-116',           'km 98','Curitiba',        'PR'),  -- 7
-- clientes
('30130-010', 'Av. do Contorno',       '500',  'Belo Horizonte',  'MG'),  -- 8
('20040-020', 'Av. Rio Branco',        '156',  'Rio de Janeiro',  'RJ'),  -- 9
('40020-010', 'Av. Sete de Setembro',  '1000', 'Salvador',        'BA'),  -- 10
('60135-000', 'Av. Beira Mar',         '3000', 'Fortaleza',       'CE'),  -- 11
('80010-100', 'Rua XV de Novembro',    '700',  'Curitiba',        'PR'),  -- 12
('50010-010', 'Av. Boa Viagem',        '450',  'Recife',          'PE'),  -- 13
('74010-010', 'Av. Goiás',             '1100', 'Goiânia',         'GO'),  -- 14
('88010-000', 'Av. Beira-Mar Norte',   '220',  'Florianópolis',   'SC');  -- 15

-- -------------------------------------------------------------
--  Transportadoras
-- -------------------------------------------------------------
INSERT INTO transportadora (cnpj, razao_social, nome_fantasia, telefone, email, id_endereco, status) VALUES
('12.345.678/0001-90', 'TransLog Sul Transportes Ltda',     'TransLog Sul',  '(51) 3210-4000', 'contato@translogsul.com.br',   1, 'ATIVA'),
('23.456.789/0001-01', 'Rota Certa Logística e Transporte', 'Rota Certa',    '(11) 3900-5500', 'operacoes@rotacerta.com.br',   2, 'ATIVA'),
('34.567.890/0001-12', 'NorteFrete Soluções Logísticas SA', 'NorteFrete',    '(91) 3222-8800', 'frete@nortefrete.com.br',      3, 'ATIVA');

-- -------------------------------------------------------------
--  Motoristas  (2 por transportadora)
-- -------------------------------------------------------------
INSERT INTO motorista (id_transportadora, nome, cpf, cnh, categoria_cnh, validade_cnh, telefone, status) VALUES
-- TransLog Sul (id_transportadora = 1)
(1, 'Carlos Eduardo Souza',  '111.222.333-44', 'RS1234567', 'E', '2027-08-15', '(51) 99801-1111', 'ATIVO'),
(1, 'Fernanda Lima Pereira', '222.333.444-55', 'RS2345678', 'C', '2026-03-20', '(51) 98702-2222', 'ATIVO'),
-- Rota Certa (id_transportadora = 2)
(2, 'Ricardo Alves Mendes',  '333.444.555-66', 'SP3456789', 'E', '2028-11-10', '(11) 97603-3333', 'ATIVO'),
(2, 'Ana Paula Rodrigues',   '444.555.666-77', 'SP4567890', 'D', '2027-05-30', '(11) 96504-4444', 'ATIVO'),
-- NorteFrete (id_transportadora = 3)
(3, 'José Raimundo Costa',   '555.666.777-88', 'PA5678901', 'E', '2026-09-25', '(91) 95405-5555', 'ATIVO'),
(3, 'Mariana Oliveira Neto', '666.777.888-99', 'PA6789012', 'C', '2028-01-14', '(91) 94306-6666', 'ATIVO');

-- -------------------------------------------------------------
--  Veículos  (2 por transportadora)
-- -------------------------------------------------------------
INSERT INTO veiculo (id_transportadora, placa, modelo, tipo_veiculo, capacidade_carga, status) VALUES
-- TransLog Sul
(1, 'RSA-1A01', 'Volvo FH 540',      'Bitrem',         42000.00, 'DISPONIVEL'),
(1, 'RSB-2B02', 'Scania R450',       'Carreta Baú',    28000.00, 'DISPONIVEL'),
-- Rota Certa
(2, 'SPC-3C03', 'Mercedes Actros',   'Carreta Graneleira', 35000.00, 'DISPONIVEL'),
(2, 'SPD-4D04', 'Volkswagen Meteor', 'Truck Baú',      15000.00, 'DISPONIVEL'),
-- NorteFrete
(3, 'PAE-5E05', 'Iveco Stralis',     'Carreta Frigorífica', 22000.00, 'DISPONIVEL'),
(3, 'PAF-6F06', 'Ford Cargo 2429',   'Truck Carga Seca',    12000.00, 'DISPONIVEL');

-- -------------------------------------------------------------
--  Clientes
-- -------------------------------------------------------------
INSERT INTO cliente (nome, cpf_cnpj, telefone, id_endereco) VALUES
('Grupo Mineração Horizonte',     '10.111.222/0001-01', '(31) 3300-1000', 8),
('Distribuidora Carioca Ltda',    '20.222.333/0001-02', '(21) 2500-2000', 9),
('Atacadão Nordeste SA',          '30.333.444/0001-03', '(71) 3100-3000', 10),
('Redes Supermercados Fortaleza', '40.444.555/0001-04', '(85) 3400-4000', 11),
('Construtora Paraná Ltda',       '50.555.666/0001-05', '(41) 3200-5000', 12),
('Farmácias Pernambuco SA',       '60.666.777/0001-06', '(81) 3300-6000', 13),
('Agro Centro-Oeste Ltda',        '70.777.888/0001-07', '(62) 3500-7000', 14),
('Eletrônicos do Sul ME',         '80.888.999/0001-08', '(48) 3600-8000', 15);

-- -------------------------------------------------------------
--  Armazéns / Centros de Distribuição
-- -------------------------------------------------------------
INSERT INTO armazem (nome, id_endereco) VALUES
('CD São Paulo',    4),
('CD Porto Alegre', 5),
('CD Brasília',     6),
('CD Curitiba',     7);

-- -------------------------------------------------------------
--  Produtos
-- -------------------------------------------------------------
INSERT INTO produto (descricao, peso, volume, validade) VALUES
('Notebook Dell Inspiron 15',        2.50,  0.01, NULL),          -- 1
('Monitor LG UltraWide 29"',         4.80,  0.03, NULL),          -- 2
('Teclado Mecânico Redragon',        1.20,  0.01, NULL),          -- 3
('Mouse Gamer Logitech G502',        0.20,  0.01, NULL),          -- 4
('Cabo HDMI 2.1 — 5m',              0.30,  0.01, NULL),          -- 5
('Arroz Branco Tipo 1 — 5kg',       5.00,  0.01, '2026-12-31'),  -- 6
('Feijão Preto — 1kg',              1.00,  0.01, '2026-09-30'),  -- 7
('Azeite de Oliva Extra Virgem 500ml', 0.60, 0.01, '2027-03-15'), -- 8
('Capacete de Segurança CA-31097',   0.40,  0.02, NULL),          -- 9
('Luva Nitrílica Descartável cx/100', 0.30, 0.01, '2028-06-01'), -- 10
('Parafuso Sextavado M10 cx/500',    5.00,  0.01, NULL),          -- 11
('Caixa de Papelão 60x40x40 (un)',   0.50,  0.10, NULL);          -- 12

-- -------------------------------------------------------------
--  Estoque (por produto e por armazém)
--  Alguns itens abaixo de 10 unidades para acionar alerta crítico
-- -------------------------------------------------------------
INSERT INTO estoque (id_produto, id_armazem, quantidade) VALUES
-- CD São Paulo (id_armazem = 1)
(1,  1, 45),   -- Notebook
(2,  1, 30),   -- Monitor
(3,  1, 80),   -- Teclado
(4,  1, 120),  -- Mouse
(5,  1, 200),  -- Cabo HDMI
(6,  1, 500),  -- Arroz
(9,  1, 60),   -- Capacete
(10, 1, 7),    -- Luva (crítico)
(12, 1, 300),  -- Caixa papelão
-- CD Porto Alegre (id_armazem = 2)
(1,  2, 20),   -- Notebook
(2,  2, 15),   -- Monitor
(6,  2, 300),  -- Arroz
(7,  2, 250),  -- Feijão
(8,  2, 180),  -- Azeite
(11, 2, 4),    -- Parafuso (crítico)
(12, 2, 150),  -- Caixa papelão
-- CD Brasília (id_armazem = 3)
(3,  3, 50),   -- Teclado
(4,  3, 90),   -- Mouse
(5,  3, 110),  -- Cabo HDMI
(7,  3, 180),  -- Feijão
(9,  3, 35),   -- Capacete
(10, 3, 85),   -- Luva
(11, 3, 220),  -- Parafuso
-- CD Curitiba (id_armazem = 4)
(1,  4, 8),    -- Notebook (crítico)
(2,  4, 12),   -- Monitor
(6,  4, 400),  -- Arroz
(8,  4, 95),   -- Azeite
(9,  4, 5),    -- Capacete (crítico)
(12, 4, 500);  -- Caixa papelão
