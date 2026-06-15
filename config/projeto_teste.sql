CREATE DATABASE gestao_logistica;
USE gestao_logistica;

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

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('ADMIN', 'GERENTE', 'OPERADOR') NOT NULL,
    status ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transportadora (
    id_transportadora INT AUTO_INCREMENT PRIMARY KEY,
    cnpj VARCHAR(18) NOT NULL UNIQUE,
    razao_social VARCHAR(150) NOT NULL,
    nome_fantasia VARCHAR(150) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    id_endereco INT NULL,
    status ENUM('ATIVA', 'INATIVA') DEFAULT 'ATIVA',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transportadora_endereco FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

CREATE TABLE motorista (
    id_motorista INT AUTO_INCREMENT PRIMARY KEY,
    id_transportadora INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    cnh VARCHAR(20) UNIQUE NOT NULL,
    categoria_cnh VARCHAR(5),
    validade_cnh DATE,
    telefone VARCHAR(20),
    status ENUM('ATIVO', 'INATIVO') DEFAULT 'ATIVO',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_motorista_transportadora
        FOREIGN KEY (id_transportadora)
        REFERENCES transportadora(id_transportadora)
);

CREATE TABLE veiculo (
    id_veiculo INT AUTO_INCREMENT PRIMARY KEY,
    id_transportadora INT NOT NULL,
    placa VARCHAR(10) UNIQUE NOT NULL,
    modelo VARCHAR(100),
    tipo_veiculo VARCHAR(50),
    capacidade_carga DECIMAL(10,2),
    status ENUM(
        'DISPONIVEL',
        'EM_VIAGEM',
        'MANUTENCAO'
    ) DEFAULT 'DISPONIVEL',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_veiculo_transportadora
        FOREIGN KEY (id_transportadora)
        REFERENCES transportadora(id_transportadora)
);

CREATE TABLE cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf_cnpj VARCHAR(18),
    telefone VARCHAR(20),
    id_endereco INT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cliente_endereco FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

CREATE TABLE armazem (
    id_armazem INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    id_endereco INT NULL,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_armazem_endereco FOREIGN KEY (id_endereco) REFERENCES endereco(id_endereco)
);

CREATE TABLE produto (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(200) NOT NULL,
    peso DECIMAL(10,2),
    volume DECIMAL(10,2),
    validade DATE,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE estoque (
    id_estoque INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL UNIQUE,
    quantidade INT DEFAULT 0,

    CONSTRAINT fk_estoque_produto
        FOREIGN KEY (id_produto)
        REFERENCES produto(id_produto)
);

CREATE TABLE localizacao_estoque (
    id_localizacao INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    id_armazem INT NOT NULL,
    corredor VARCHAR(20),
    prateleira VARCHAR(20),
    nivel VARCHAR(20),
    quantidade INT DEFAULT 0,

    CONSTRAINT fk_localizacao_produto
        FOREIGN KEY (id_produto)
        REFERENCES produto(id_produto),

    CONSTRAINT fk_localizacao_armazem
        FOREIGN KEY (id_armazem)
        REFERENCES armazem(id_armazem)
);

CREATE TABLE movimentacao_estoque (
    id_movimentacao INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo_movimentacao ENUM('ENTRADA', 'SAIDA') NOT NULL,
    quantidade INT NOT NULL,
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_movimentacao_produto
        FOREIGN KEY (id_produto)
        REFERENCES produto(id_produto),

    CONSTRAINT fk_movimentacao_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
);

CREATE TABLE entrega (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    data_prevista DATE,
    data_realizada DATE,
    peso_total DECIMAL(10,2),
    volume_total DECIMAL(10,2),
    status ENUM(
        'PENDENTE',
        'EM_TRANSITO',
        'ENTREGUE',
        'ATRASADA'
    ) DEFAULT 'PENDENTE',

    CONSTRAINT fk_entrega_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES cliente(id_cliente)
);

CREATE TABLE entrega_produto (
    id_entrega INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,

    PRIMARY KEY (id_entrega, id_produto),

    CONSTRAINT fk_entrega_produto_entrega
        FOREIGN KEY (id_entrega)
        REFERENCES entrega(id_entrega),

    CONSTRAINT fk_entrega_produto_produto
        FOREIGN KEY (id_produto)
        REFERENCES produto(id_produto)
);

CREATE TABLE rota (
    id_rota INT AUTO_INCREMENT PRIMARY KEY,
    id_veiculo INT NOT NULL,
    id_motorista INT NOT NULL,
    distancia DECIMAL(10,2),
    status ENUM(
        'PLANEJADA',
        'EM_ANDAMENTO',
        'FINALIZADA'
    ) DEFAULT 'PLANEJADA',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_rota_veiculo
        FOREIGN KEY (id_veiculo)
        REFERENCES veiculo(id_veiculo),

    CONSTRAINT fk_rota_motorista
        FOREIGN KEY (id_motorista)
        REFERENCES motorista(id_motorista)
);

CREATE TABLE rota_entrega (
    id_rota INT NOT NULL,
    id_entrega INT NOT NULL,

    PRIMARY KEY (id_rota, id_entrega),

    CONSTRAINT fk_rota_entrega_rota
        FOREIGN KEY (id_rota)
        REFERENCES rota(id_rota),

    CONSTRAINT fk_rota_entrega_entrega
        FOREIGN KEY (id_entrega)
        REFERENCES entrega(id_entrega)
);

CREATE TABLE viagem (
    id_viagem INT AUTO_INCREMENT PRIMARY KEY,
    id_rota INT NOT NULL,
    data_saida DATETIME,
    data_chegada_prevista DATETIME,
    data_chegada_real DATETIME,
    status ENUM(
        'INICIADA',
        'EM_TRANSITO',
        'CONCLUIDA',
        'CANCELADA'
    ) DEFAULT 'INICIADA',

    CONSTRAINT fk_viagem_rota
        FOREIGN KEY (id_rota)
        REFERENCES rota(id_rota)
);

CREATE TABLE rastreamento (
    id_rastreamento INT AUTO_INCREMENT PRIMARY KEY,
    id_viagem INT NOT NULL,
    latitude DECIMAL(10,7),
    longitude DECIMAL(10,7),
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_rastreamento_viagem
        FOREIGN KEY (id_viagem)
        REFERENCES viagem(id_viagem)
);

CREATE TABLE alerta (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    id_viagem INT NOT NULL,
    tipo_alerta ENUM(
        'ATRASO',
        'DESVIO_ROTA',
        'PARADA_NAO_PROGRAMADA'
    ),
    descricao TEXT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_alerta_viagem
        FOREIGN KEY (id_viagem)
        REFERENCES viagem(id_viagem)
);

CREATE TABLE frete (
    id_frete INT AUTO_INCREMENT PRIMARY KEY,
    id_viagem INT NOT NULL UNIQUE,
    id_transportadora INT NOT NULL,
    valor DECIMAL(12,2),
    custo_operacional DECIMAL(12,2),
    nota_fiscal VARCHAR(50),
    data_emissao DATE,

    CONSTRAINT fk_frete_viagem
        FOREIGN KEY (id_viagem)
        REFERENCES viagem(id_viagem),

    CONSTRAINT fk_frete_transportadora
        FOREIGN KEY (id_transportadora)
        REFERENCES transportadora(id_transportadora)
);
