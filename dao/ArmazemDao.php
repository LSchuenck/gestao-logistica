<?php
/**
 * DAO: ArmazemDao
 *
 * Responsável por toda a persistência da entidade Armazém no banco de dados.
 * Centraliza as queries SQL de listagem, inserção e exclusão de armazéns,
 * incluindo métricas de estoque calculadas por subqueries.
 */
class ArmazemDao
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
     * Retorna todos os armazéns com seus endereços e métricas de estoque.
     *
     * Inclui duas métricas calculadas por subquery:
     * - total_skus:  quantidade de produtos distintos armazenados
     * - total_itens: soma total de unidades em estoque no armazém
     *
     * @return array Lista de armazéns como arrays associativos
     */
    public function listarTodos(): array
    {
        $sql = "
            SELECT a.*,
                   e.cep, e.logradouro, e.numero, e.complemento,
                   e.bairro, e.cidade, e.estado,
                   (SELECT COUNT(DISTINCT id_produto)
                      FROM localizacao_estoque l
                     WHERE l.id_armazem = a.id_armazem) AS total_skus,
                   (SELECT COALESCE(SUM(quantidade), 0)
                      FROM localizacao_estoque l
                     WHERE l.id_armazem = a.id_armazem) AS total_itens
            FROM armazem a
            LEFT JOIN endereco e ON a.id_endereco = e.id_endereco
            ORDER BY a.id_armazem DESC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca o id_endereco vinculado a um armazém.
     *
     * Necessário para excluir o endereço órfão após remover o armazém.
     *
     * @param int $idArmazem ID do armazém
     * @return int|false ID do endereço ou false se não houver
     */
    public function buscarIdEndereco(int $idArmazem): int|false
    {
        $stmt = $this->pdo->prepare("SELECT id_endereco FROM armazem WHERE id_armazem = ?");
        $stmt->execute([$idArmazem]);
        return $stmt->fetchColumn();
    }

    public function buscarPorId(int $idArmazem): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, e.cep, e.logradouro, e.numero, e.complemento,
                   e.bairro, e.cidade, e.estado
            FROM armazem a
            LEFT JOIN endereco e ON a.id_endereco = e.id_endereco
            WHERE a.id_armazem = ?
        ");
        $stmt->execute([$idArmazem]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizar(int $idArmazem, string $nome, array $dadosEndereco): void
    {
        $idEndereco = $this->buscarIdEndereco($idArmazem);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE armazem SET nome = ? WHERE id_armazem = ?")
                      ->execute([$nome, $idArmazem]);
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
     * Insere um novo armazém e seu endereço em uma única transação.
     *
     * Passo 1: insere o endereço e captura o ID gerado.
     * Passo 2: insere o armazém referenciando o endereço criado.
     * Faz rollback automático em caso de falha.
     *
     * @param string $nome         Nome do armazém ou centro de distribuição
     * @param array  $dadosEndereco Campos: cep, logradouro, numero, complemento, bairro, cidade, estado
     * @return void
     * @throws Exception Em caso de falha na inserção
     */
    public function inserir(string $nome, array $dadosEndereco): void
    {
        $this->pdo->beginTransaction();
        try {
            // Passo 1: insere o endereço do armazém
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

            // Passo 2: insere o armazém vinculado ao endereço recém-criado
            $this->pdo->prepare("INSERT INTO armazem (nome, id_endereco) VALUES (?, ?)")
                      ->execute([$nome, $idEndereco]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Exclui um armazém e seu endereço vinculado em uma única transação.
     *
     * Se o armazém possuir produtos vinculados via localizacao_estoque,
     * a FK impedirá a exclusão e a exceção será relançada para o controller.
     *
     * @param int $idArmazem ID do armazém a ser excluído
     * @return void
     * @throws Exception Em caso de violação de chave estrangeira
     */
    public function excluir(int $idArmazem): void
    {
        // Busca o endereço vinculado antes da transação
        $idEndereco = $this->buscarIdEndereco($idArmazem);

        $this->pdo->beginTransaction();
        try {
            // Remove o armazém pelo ID
            $this->pdo->prepare("DELETE FROM armazem WHERE id_armazem = ?")->execute([$idArmazem]);

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
