<?php
/**
 * DAO: ProdutoDao
 *
 * Responsável por toda a persistência da entidade Produto no banco de dados.
 * Centraliza as queries SQL de listagem, inserção e exclusão de produtos,
 * incluindo o gerenciamento do estoque inicial e a exclusão em cascata manual.
 */
class ProdutoDao
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
     * Retorna todos os produtos com seu saldo de estoque atual.
     *
     * Usa LEFT JOIN para incluir produtos que, por algum motivo,
     * não possuam registro na tabela estoque (COALESCE retorna 0 nesses casos).
     *
     * @return array Lista de produtos como arrays associativos
     */
    public function listarTodos(): array
    {
        $sql = "
            SELECT p.*, COALESCE(e.quantidade, 0) AS qtd_estoque
            FROM produto p
            LEFT JOIN estoque e ON p.id_produto = e.id_produto
            ORDER BY p.id_produto DESC
        ";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insere um novo produto e cria seu registro de estoque inicial em uma transação.
     *
     * Passo 1: insere o produto com seus atributos principais.
     * Passo 2: cria o saldo de estoque zerado para o novo produto,
     *          garantindo que consultas de estoque nunca retornem NULL.
     *
     * @param array $dados Campos: descricao, peso (nullable), volume (nullable), validade (nullable)
     * @return void
     * @throws Exception Em caso de falha na inserção
     */
    public function inserir(array $dados): void
    {
        $this->pdo->beginTransaction();
        try {
            // Passo 1: insere o produto com seus atributos
            $sql = "INSERT INTO produto (descricao, peso, volume, validade) VALUES (?, ?, ?, ?)";
            $this->pdo->prepare($sql)->execute([
                $dados['descricao'],
                $dados['peso'],
                $dados['volume'],
                $dados['validade'], // NULL se não informado
            ]);
            $idProduto = $this->pdo->lastInsertId();

            // Passo 2: cria o saldo de estoque zerado para o novo produto
            $this->pdo->prepare("INSERT INTO estoque (id_produto, quantidade) VALUES (?, 0)")
                      ->execute([$idProduto]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Exclui um produto e todos os seus dados relacionados em uma única transação.
     *
     * A exclusão é feita em cascata manual na seguinte ordem para respeitar as FKs:
     *  1. localizacao_estoque  — onde o produto está armazenado nos armazéns
     *  2. movimentacao_estoque — histórico de entradas e saídas do produto
     *  3. estoque              — saldo atual do produto
     *  4. produto              — registro principal
     *
     * Se o produto estiver vinculado a entregas, a FK impedirá a exclusão
     * e a exceção será relançada para o controller tratar.
     *
     * @param int $idProduto ID do produto a ser excluído
     * @return void
     * @throws Exception Em caso de violação de chave estrangeira com entregas
     */
    public function excluir(int $idProduto): void
    {
        $this->pdo->beginTransaction();
        try {
            // Remove as localizações de estoque do produto nos armazéns
            $this->pdo->prepare("DELETE FROM localizacao_estoque WHERE id_produto = ?")->execute([$idProduto]);

            // Remove o histórico de movimentações de estoque
            $this->pdo->prepare("DELETE FROM movimentacao_estoque WHERE id_produto = ?")->execute([$idProduto]);

            // Remove o saldo de estoque do produto
            $this->pdo->prepare("DELETE FROM estoque WHERE id_produto = ?")->execute([$idProduto]);

            // Remove o registro principal do produto
            $this->pdo->prepare("DELETE FROM produto WHERE id_produto = ?")->execute([$idProduto]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
