<?php
/*
 * Arquivo: instalar.php
 * Finalidade: Script de instalação do sistema de Gestão Logística.
 * Cria o banco de dados, todas as tabelas necessárias e um usuário administrador padrão.
 * Deve ser executado apenas uma vez, na configuração inicial do ambiente.
 *
 * ATENÇÃO: Após a instalação, remova ou restrinja o acesso a este arquivo em produção,
 * pois ele APAGA e recria todas as tabelas do banco de dados ao ser executado novamente.
 *
 * Credenciais do administrador padrão criadas por este script:
 *   Usuário: admin@sistema.com
 *   Senha:   admin123
 */

// Habilita exibição de todos os erros PHP para facilitar a depuração durante a instalação
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
    /*
     * Conecta ao MySQL sem especificar banco de dados (ainda não existe),
     * apenas para poder criar o banco "gestao_logistica" a seguir.
     */
    $pdo = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Cria o banco de dados caso ainda não exista, com charset UTF-8 para suporte a acentos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gestao_logistica CHARACTER SET utf8 COLLATE utf8_general_ci");

    // Seleciona o banco recém-criado para as próximas operações
    $pdo->exec("USE gestao_logistica");

    /*
     * Desativa temporariamente a verificação de chaves estrangeiras para permitir
     * o DROP das tabelas na ordem correta, sem erros de constraint de integridade referencial.
     */
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    /*
     * Lista de todas as tabelas do sistema na ordem correta para remoção.
     * As tabelas dependentes (com chaves estrangeiras) vêm antes das tabelas pai,
     * embora FOREIGN_KEY_CHECKS esteja desativado neste momento.
     */
    $tabelas = ['alerta','rastreamento','viagem','rota_entrega','rota','entrega_produto','entrega',
                'movimentacao_estoque','localizacao_estoque','estoque','produto','armazem','cliente',
                'veiculo','motorista','transportadora','usuario'];

    // Remove todas as tabelas existentes para garantir instalação limpa (sem conflitos de schema)
    foreach ($tabelas as $t) {
        $pdo->exec("DROP TABLE IF EXISTS $t");
    }

    // Reativa a verificação de chaves estrangeiras após o DROP das tabelas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<p class='text-success'><strong>&#10003;</strong> Banco de dados criado.</p>";

    /*
     * Criação da tabela de usuários do sistema.
     * - perfil: define o nível de acesso (ADMIN, GERENTE, OPERADOR)
     * - status: permite desativar contas sem excluí-las
     * - trocar_senha: flag para forçar troca de senha no primeiro acesso (não presente no schema inicial,
     *   mas adicionada via migration; documentada aqui para referência)
     */
    $pdo->exec("CREATE TABLE usuario (
        id_usuario INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        perfil ENUM('ADMIN','GERENTE','OPERADOR') NOT NULL,
        status ENUM('ATIVO','INATIVO') DEFAULT 'ATIVO',
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    /*
     * Tabela de transportadoras parceiras.
     * Relacionada com motoristas e veículos (um-para-muitos).
     * data_atualizacao é atualizada automaticamente pelo MySQL a cada modificação no registro.
     */
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

    /*
     * Tabela de motoristas vinculados às transportadoras.
     * - cnh: obrigatória e única por motorista
     * - validade_cnh: usada para alertas de vencimento de habilitação
     */
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

    /*
     * Tabela de veículos da frota.
     * - status controla a disponibilidade do veículo para novas rotas
     * - capacidade_carga é usada no planejamento logístico de entregas
     */
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

    // Tabela de clientes destinatários das entregas
    $pdo->exec("CREATE TABLE cliente (
        id_cliente INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(150) NOT NULL,
        cpf_cnpj VARCHAR(18),
        telefone VARCHAR(20),
        endereco VARCHAR(255),
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    /*
     * Tabela de armazéns/depósitos do sistema.
     * Relacionada com a localização física dos produtos no estoque.
     */
    $pdo->exec("CREATE TABLE armazem (
        id_armazem INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        endereco VARCHAR(255),
        cidade VARCHAR(100),
        estado CHAR(2),
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    /*
     * Tabela de produtos cadastrados no sistema.
     * - peso e volume: usados no cálculo de capacidade de carga das entregas
     * - validade: usada para alertas de produtos próximos do vencimento
     */
    $pdo->exec("CREATE TABLE produto (
        id_produto INT AUTO_INCREMENT PRIMARY KEY,
        descricao VARCHAR(200) NOT NULL,
        peso DECIMAL(10,2),
        volume DECIMAL(10,2),
        validade DATE,
        data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    /*
     * Tabela de estoque geral por produto.
     * Relação 1:1 com produto (UNIQUE em id_produto).
     * Armazena a quantidade total consolidada do produto em todo o sistema.
     */
    $pdo->exec("CREATE TABLE estoque (
        id_estoque INT AUTO_INCREMENT PRIMARY KEY,
        id_produto INT NOT NULL UNIQUE,
        quantidade INT DEFAULT 0,
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    )");

    /*
     * Tabela de localização física dos produtos dentro dos armazéns.
     * Permite rastrear em qual corredor, prateleira e nível cada produto está armazenado.
     * Um produto pode estar em múltiplos armazéns simultaneamente.
     */
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

    /*
     * Tabela de movimentações de estoque (entradas e saídas).
     * Registra o histórico de todas as movimentações para auditoria e rastreabilidade.
     * - id_usuario: registra quem realizou a movimentação
     */
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

    /*
     * Tabela de entregas a serem realizadas para os clientes.
     * - status: controla o ciclo de vida da entrega (PENDENTE -> EM_TRANSITO -> ENTREGUE/ATRASADA)
     * - data_prevista vs data_realizada: base para o cálculo de pontualidade nos indicadores
     */
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

    /*
     * Tabela de relacionamento N:N entre entregas e produtos.
     * Uma entrega pode conter vários produtos e um produto pode estar em várias entregas.
     * Chave primária composta por id_entrega + id_produto garante que não haja duplicatas.
     */
    $pdo->exec("CREATE TABLE entrega_produto (
        id_entrega INT NOT NULL,
        id_produto INT NOT NULL,
        quantidade INT NOT NULL,
        PRIMARY KEY (id_entrega, id_produto),
        FOREIGN KEY (id_entrega) REFERENCES entrega(id_entrega),
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
    )");

    /*
     * Tabela de rotas de entrega planejadas.
     * Uma rota associa um veículo e um motorista para executar um conjunto de entregas.
     * - distancia: quilometragem total planejada para a rota
     */
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

    /*
     * Tabela de relacionamento N:N entre rotas e entregas.
     * Permite que uma rota contenha múltiplas entregas (otimização de rotas).
     * Chave primária composta evita duplicatas.
     */
    $pdo->exec("CREATE TABLE rota_entrega (
        id_rota INT NOT NULL,
        id_entrega INT NOT NULL,
        PRIMARY KEY (id_rota, id_entrega),
        FOREIGN KEY (id_rota) REFERENCES rota(id_rota),
        FOREIGN KEY (id_entrega) REFERENCES entrega(id_entrega)
    )");

    /*
     * Tabela de viagens realizadas (execução das rotas).
     * - data_chegada_prevista vs data_chegada_real: base para cálculo de atrasos
     * - status: ciclo de vida completo da viagem (INICIADA -> EM_TRANSITO -> CONCLUIDA/CANCELADA)
     */
    $pdo->exec("CREATE TABLE viagem (
        id_viagem INT AUTO_INCREMENT PRIMARY KEY,
        id_rota INT NOT NULL,
        data_saida DATETIME,
        data_chegada_prevista DATETIME,
        data_chegada_real DATETIME,
        status ENUM('INICIADA','EM_TRANSITO','CONCLUIDA','CANCELADA') DEFAULT 'INICIADA',
        FOREIGN KEY (id_rota) REFERENCES rota(id_rota)
    )");

    /*
     * Tabela de rastreamento GPS das viagens.
     * Armazena pontos de localização com coordenadas geográficas ao longo do tempo.
     * Usado para monitoramento em tempo real e análise de trajetos.
     */
    $pdo->exec("CREATE TABLE rastreamento (
        id_rastreamento INT AUTO_INCREMENT PRIMARY KEY,
        id_viagem INT NOT NULL,
        latitude DECIMAL(10,7),
        longitude DECIMAL(10,7),
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem)
    )");

    /*
     * Tabela de alertas gerados durante as viagens.
     * Registra ocorrências como atrasos, desvios de rota e paradas não programadas,
     * permitindo gestão e acompanhamento de incidentes operacionais.
     */
    $pdo->exec("CREATE TABLE alerta (
        id_alerta INT AUTO_INCREMENT PRIMARY KEY,
        id_viagem INT NOT NULL,
        tipo_alerta ENUM('ATRASO','DESVIO_ROTA','PARADA_NAO_PROGRAMADA'),
        descricao TEXT,
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_viagem) REFERENCES viagem(id_viagem)
    )");

    /*
     * Tabela de fretes e notas fiscais vinculados às viagens.
     * - valor: valor total cobrado pela transportadora
     * - custo_operacional: custo interno (combustível, pedágio, etc.)
     * - nota_fiscal: número da NF para controle financeiro e fiscal
     * Relação 1:1 com viagem (UNIQUE em id_viagem).
     */
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

    /*
     * Cria o usuário administrador padrão do sistema.
     * A senha "admin123" é hasheada com bcrypt antes de ser armazenada.
     * IMPORTANTE: O administrador deve alterar esta senha no primeiro acesso.
     */
    $senha = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO usuario (nome, email, senha, perfil) VALUES (?, ?, ?, 'ADMIN')")
        ->execute(['Administrador', 'admin@sistema.com', $senha]);

    echo "<p class='text-success'><strong>&#10003;</strong> Usuário administrador criado.</p>";

    // Exibe resumo final da instalação com as credenciais de acesso inicial
    echo "<div class='alert alert-success mt-3'>
        <h5>&#10003; Instalação concluída com sucesso!</h5>
        <p class='mb-1'><strong>Usuário:</strong> admin@sistema.com</p>
        <p class='mb-3'><strong>Senha:</strong> admin123</p>
        <a href='index.php' class='btn btn-success'>Ir para o Sistema &rarr;</a>
    </div>";

} catch (Exception $e) {
    // Exibe detalhes do erro caso algo dê errado durante a instalação
    echo "<div class='alert alert-danger'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
}
?>
</div>
</body>
</html>
