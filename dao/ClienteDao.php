<?php
/**
 * DAO: ClienteDao
 *
 * Responsável por toda a persistência da entidade Cliente no banco de dados.
 * Centraliza as queries SQL de listagem, inserção e exclusão de clientes,
 * garantindo que a lógica de acesso a dados fique separada do controller.
 */
class ClienteDao
{
    /** @var PDO Instância de conexão com o banco de dados */
    private PDO $pdo;

    /**
     * Recebe a conexão PDO via injeção de dependência.
     *
     * @param PDO $pdo Conexão ativa com o banco de dados
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna todos os clientes com seus endereços e total de entregas.
     *
     * Usa LEFT JOIN para incluir clientes sem endereço cadastrado.
     * A subquery conta as entregas vinculadas a cada cliente.
     *
     * @return array Lista de clientes como arrays associativos
     */
    public function listarTodos(): array
    {
        $sql = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM entrega e WHERE e.id_cliente = c.id_cliente) AS total_entregas,
                   en.cep, en.logradouro, en.numero, en.complemento,
                   en.bairro, en.cidade, en.estado
            FROM cliente c
            LEFT JOIN endereco en ON c.id_endereco = en.id_endereco
            ORDER BY c.id_cliente DESC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca o id_endereco vinculado a um cliente.
     *
     * Necessário para excluir o endereço órfão após remover o cliente.
     *
     * @param int $idCliente ID do cliente
     * @return int|false ID do endereço ou false se não houver
     */
    public function buscarIdEndereco(int $idCliente): int|false
    {
        $stmt = $this->pdo->prepare("SELECT id_endereco FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$idCliente]);
        return $stmt->fetchColumn();
    }

    public function buscarPorId(int $idCliente): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, e.cep, e.logradouro, e.numero, e.complemento,
                   e.bairro, e.cidade, e.estado
            FROM cliente c
            LEFT JOIN endereco e ON c.id_endereco = e.id_endereco
            WHERE c.id_cliente = ?
        ");
        $stmt->execute([$idCliente]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizar(int $idCliente, array $dadosCliente, array $dadosEndereco): void
    {
        $idEndereco = $this->buscarIdEndereco($idCliente);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE cliente SET nome=?, cpf_cnpj=?, telefone=? WHERE id_cliente=?")
                      ->execute([
                          $dadosCliente['nome'],
                          $dadosCliente['cpf_cnpj'] ?: null,
                          $dadosCliente['telefone'] ?: null,
                          $idCliente,
                      ]);
            if ($idEndereco) {
                $this->pdo->prepare(
                    "UPDATE endereco SET cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, estado=? WHERE id_endereco=?"
                )->execute([
                    $dadosEndereco['cep'], $dadosEndereco['logradouro'], $dadosEndereco['numero'],
                    $dadosEndereco['complemento'], $dadosEndereco['bairro'], $dadosEndereco['cidade'],
                    strtoupper($dadosEndereco['estado']), $idEndereco,
                ]);
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Insere um novo cliente e seu endereço em uma única transação.
     *
     * Passo 1: insere o endereço e captura o ID gerado.
     * Passo 2: insere o cliente referenciando o endereço criado.
     * Faz rollback automático em caso de falha (ex.: CPF/CNPJ duplicado).
     *
     * @param array $dadosCliente  Campos: nome, cpf_cnpj, telefone
     * @param array $dadosEndereco Campos: cep, logradouro, numero, complemento, bairro, cidade, estado
     * @return void
     * @throws Exception Em caso de falha na inserção
     */
    public function inserir(array $dadosCliente, array $dadosEndereco): void
    {
        $this->pdo->beginTransaction();
        try {
            // Passo 1: insere o endereço do cliente
            $sqlEnd = "INSERT INTO endereco (cep, logradouro, numero, complemento, bairro, cidade, estado)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->pdo->prepare($sqlEnd)->execute([
                $dadosEndereco['cep'],
                $dadosEndereco['logradouro'],
                $dadosEndereco['numero'],
                $dadosEndereco['complemento'],
                $dadosEndereco['bairro'],
                $dadosEndereco['cidade'],
                strtoupper($dadosEndereco['estado']), // Garante sigla em maiúsculas (ex.: "sp" -> "SP")
            ]);
            $idEndereco = $this->pdo->lastInsertId();

            // Passo 2: insere o cliente vinculado ao endereço recém-criado
            $sqlCli = "INSERT INTO cliente (nome, cpf_cnpj, telefone, id_endereco) VALUES (?, ?, ?, ?)";
            $this->pdo->prepare($sqlCli)->execute([
                $dadosCliente['nome'],
                $dadosCliente['cpf_cnpj'],
                $dadosCliente['telefone'],
                $idEndereco,
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Exclui um cliente e seu endereço vinculado em uma única transação.
     *
     * Busca o id_endereco antes de iniciar a transação para poder excluí-lo
     * depois de remover o cliente (necessário pela ordem das FKs).
     * Se o cliente possuir entregas vinculadas, a FK impedirá a exclusão
     * e a exceção será relançada para o controller tratar.
     *
     * @param int $idCliente ID do cliente a ser excluído
     * @return void
     * @throws Exception Em caso de violação de chave estrangeira
     */
    public function excluir(int $idCliente): void
    {
        // Busca o endereço vinculado antes da transação para não perder o ID após o DELETE
        $idEndereco = $this->buscarIdEndereco($idCliente);

        $this->pdo->beginTransaction();
        try {
            // Remove o cliente pelo ID
            $this->pdo->prepare("DELETE FROM cliente WHERE id_cliente = ?")->execute([$idCliente]);

            // Remove o endereço vinculado, se existia
            if ($idEndereco) {
                $this->pdo->prepare("DELETE FROM endereco WHERE id_endereco = ?")->execute([$idEndereco]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
