<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Instalar Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card shadow border-0 p-4" style="max-width:600px;width:100%">
    <h4 class="fw-bold mb-4"><i class="bi bi-database-fill-gear"></i> Instalação do Sistema</h4>
<?php
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS gestao_logistica CHARACTER SET utf8 COLLATE utf8_general_ci");
    $pdo->exec("USE gestao_logistica");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tabelas = ['alerta','rastreamento','viagem','rota_entrega','rota','entrega_produto','entrega',
                'movimentacao_estoque','localizacao_estoque','estoque','produto','armazem','cliente',
                'veiculo','motorista','transportadora','usuario'];
    foreach ($tabelas as $t) {
        $pdo->exec("DROP TABLE IF EXISTS $t");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<p class='text-success'><strong>&#10003;</strong> Banco de dados criado.</p>";

    $pdo->exec("CREATE TABLE usuario (
        id_usuario INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        perfil ENUM('ADMIN','GERENTE','OPERADOR') NOT NULL,
        status ENUM('ATIVO','INATIVO') DEFAULT 'ATIVO',
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE transportadora (
        id_transportadora INT AUTO_INCREMENT PRIMARY KEY,
        cnpj VARCHAR(18) NOT NULL UNIQUE,
        razao_social VARCHAR(150) NOT NULL,
        nome_fantasia VARCHAR(150) NOT NULL,
        telefone VARCHAR(20),
        email VARCHAR(100),
        endereco VARCHAR(255),
        status ENUM('ATIVA','INATIVA') DEFAULT 'ATIVA',
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE motorista (
        id_motorista INT AUTO_INCREMENT PRIMARY KEY,
        id_transportadora INT NOT NULL,
        nome VARCHAR(150) NOT NULL,
        cpf VARCHAR(14) UNIQUE,
        cnh VARCHAR(20) UNIQUE NOT NULL,
        categoria_cnh VARCHAR(5),
        validade_cnh DATE,
        telefone VARCHAR(20),
        status ENUM('ATIVO','INATIVO') DEFAULT 'ATIVO',
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_transportadora) REFERENCES transportadora(id_transportadora)
    )");

    $pdo->exec("CREATE TABLE veiculo (
        id_veiculo INT AUTO_INCREMENT PRIMARY KEY,
        id_transportadora INT NOT NULL,
        placa VARCHAR(10) UNIQUE NOT NULL,
        modelo VARCHAR(100),
        tipo_veiculo VARCHAR(50),
        capacidade_carga DECIMAL(10,2),
        status ENUM('DISPONIVEL','EM_VIAGEM','MANUTENCAO') DEFAULT 'DISPONIVEL',
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_transportadora) REFERENCES transportadora(id_transportadora)
    )");

    $pdo->exec("CREATE TABLE cliente (
        id_cliente INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(150) NOT NULL,
        cpf_cnpj VARCHAR(18),
        telefone VARCHAR(20),
        endereco VARCHAR(255),
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE armazem (
        id_armazem INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        endereco VARCHAR(255),
        cidade VARCHAR(100),
        estado CHAR(2),
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE produto (
        id_produto INT AUTO_INCREMENT PRIMARY KEY,
        descricao VARCHAR(200) NOT NULL,
        peso DECIMAL(10,2),
        volume DECIMAL(10,2),
        validade DATE,
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE estoque (
        id_estoque INT AUTO_INCREMENT PRIMARY KEY,
        id_produto INT NOT NULL UNIQUE,
        quantidade INT DEFAULT 0,
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    )");

    $pdo->exec("CREATE TABLE localizacao_estoque (
        id_localizacao INT AUTO_INCREMENT PRIMARY KEY,
        id_produto INT NOT NULL,
        id_armazem INT NOT NULL,
        corredor VARCHAR(20),
        prateleira VARCHAR(20),
        nivel VARCHAR(20),
        quantidade INT DEFAULT 0,
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto),
        FOREIGN KEY (id_armazem) REFERENCES armazem(id_armazem)
    )");

    $pdo->exec("CREATE TABLE movimentacao_estoque (
        id_movimentacao INT AUTO_INCREMENT PRIMARY KEY,
        id_produto INT NOT NULL,
        id_usuario INT NOT NULL,
        tipo_movimentacao ENUM('ENTRADA','SAIDA') NOT NULL,
        quantidade INT NOT NULL,
        data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto),
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
    )");

    $pdo->exec("CREATE TABLE entrega (
        id_entrega INT AUTO_INCREMENT PRIMARY KEY,
        id_cliente INT NOT NULL,
        data_prevista DATE,
        data_realizada DATE,
        peso_total DECIMAL(10,2),
        volume_total DECIMAL(10,2),
        status ENUM('PENDENTE','EM_TRANSITO','ENTREGUE','ATRASADA') DEFAULT 'PENDENTE',
        FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente)
    )");

    $pdo->exec("CREATE TABLE entrega_produto (
        id_entrega INT NOT NULL,
        id_produto INT NOT NULL,
        quantidade INT NOT NULL,
        PRIMARY KEY (id_entrega, id_produto),
        FOREIGN KEY (id_entrega) REFERENCES entrega(id_entrega),
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    )");

    $pdo->exec("CREATE TABLE rota (
        id_rota INT AUTO_INCREMENT PRIMARY KEY,
        id_veiculo INT NOT NULL,
        id_motorista INT NOT NULL,
        distancia DECIMAL(10,2),
        status ENUM('PLANEJADA','EM_ANDAMENTO','FINALIZADA') DEFAULT 'PLANEJADA',
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_veiculo) REFERENCES veiculo(id_veiculo),
        FOREIGN KEY (id_motorista) REFERENCES motorista(id_motorista)
    )");

    $pdo->exec("CREATE TABLE rota_entrega (
        id_rota INT NOT NULL,
        id_entrega INT NOT NULL,
        PRIMARY KEY (id_rota, id_entrega),
        FOREIGN KEY (id_rota) REFERENCES rota(id_rota),
        FOREIGN KEY (id_entrega) REFERENCES entrega(id_entrega)
    )");

    $pdo->exec("CREATE TABLE viagem (
        id_viagem INT AUTO_INCREMENT PRIMARY KEY,
        id_rota INT NOT NULL,
        data_saida DATETIME,
        data_chegada_prevista DATETIME,
        data_chegada_real DATETIME,
        status ENUM('INICIADA','EM_TRANSITO','CONCLUIDA','CANCELADA') DEFAULT 'INICIADA',
        FOREIGN KEY (id_rota) REFERENCES rota(id_rota)
    )");

    $pdo->exec("CREATE TABLE rastreamento (
        id_rastreamento INT AUTO_INCREMENT PRIMARY KEY,
        id_viagem INT NOT NULL,
        latitude DECIMAL(10,7),
        longitude DECIMAL(10,7),
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem)
    )");

    $pdo->exec("CREATE TABLE alerta (
        id_alerta INT AUTO_INCREMENT PRIMARY KEY,
        id_viagem INT NOT NULL,
        tipo_alerta ENUM('ATRASO','DESVIO_ROTA','PARADA_NAO_PROGRAMADA'),
        descricao TEXT,
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem)
    )");

    $pdo->exec("CREATE TABLE frete (
        id_frete INT AUTO_INCREMENT PRIMARY KEY,
        id_viagem INT NOT NULL UNIQUE,
        id_transportadora INT NOT NULL,
        valor DECIMAL(12,2),
        custo_operacional DECIMAL(12,2),
        nota_fiscal VARCHAR(50),
        data_emissao DATE,
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem),
        FOREIGN KEY (id_transportadora) REFERENCES transportadora(id_transportadora)
    )");

    echo "<p class='text-success'><strong>&#10003;</strong> Todas as tabelas criadas.</p>";

    $senha = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO usuario (nome, email, senha, perfil) VALUES (?, ?, ?, 'ADMIN')")
        ->execute(['Administrador', 'admin@sistema.com', $senha]);

    echo "<p class='text-success'><strong>&#10003;</strong> Usuário administrador criado.</p>";

    echo "<div class='alert alert-success mt-3'>
        <h5>&#10003; Instalação concluída com sucesso!</h5>
        <p class='mb-1'><strong>Usuário:</strong> admin@sistema.com</p>
        <p class='mb-3'><strong>Senha:</strong> admin123</p>
        <a href='index.php' class='btn btn-success'>Ir para o Sistema &rarr;</a>
    </div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
}
?>
</div>
</body>
</html>
