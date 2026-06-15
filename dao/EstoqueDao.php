<?php
/**
 * DAO: EstoqueDao
 *
 * Concentra todas as operações de banco de dados relacionadas ao saldo de estoque.
 * Utiliza ON DUPLICATE KEY UPDATE para upsert atômico do saldo por produto/armazém,
 * garantindo que não haja quantidade negativa com GREATEST(0, ...).
 *
 * Responsabilidades:
 *  - Consulta do saldo atual de um produto em um armazém
 *  - Atualização (upsert) do saldo via ON DUPLICATE KEY UPDATE
 *  - Gerenciamento de transações (iniciar, confirmar, reverter)
 *  - Consultas para alimentar a view (inventário consolidado, detalhe por armazém)
 */
class EstoqueDao
{
    /** @var PDO Instância PDO injetada pelo controller */
    private PDO $pdo;

    /**
     * Recebe a conexão PDO por injeção de dependência.
     *
     * @param PDO $pdo Conexão ativa com o banco de dados
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // -------------------------------------------------------------------------
    // GERENCIAMENTO DE TRANSAÇÕES
    // -------------------------------------------------------------------------

    /**
     * Inicia uma transação PDO para garantir atomicidade entre
     * a atualização do saldo e o registro no histórico de movimentações.
     *
     * @return void
     */
    public function iniciarTransacao(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Confirma a transação em andamento, tornando permanentes todas as
     * operações realizadas desde o início.
     *
     * @return void
     */
    public function confirmarTransacao(): void
    {
        $this->pdo->commit();
    }

    /**
     * Reverte a transação em andamento, desfazendo todas as operações
     * realizadas desde o início. Verifica se há transação ativa antes de
     * tentar o rollback para evitar erros em fluxos de exceção.
     *
     * @return void
     */
    public function reverterTransacao(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Consulta o saldo atual de um produto em um armazém específico.
     * Retorna 0 caso não exista registro na tabela estoque para essa combinação.
     *
     * @param  int $idProduto  ID do produto
     * @param  int $idArmazem  ID do armazém
     * @return int Quantidade disponível (>= 0)
     */
    public function consultarSaldo(int $idProduto, int $idArmazem): int
    {
        // COALESCE retorna 0 caso não exista registro de estoque ainda
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(quantidade, 0) FROM estoque WHERE id_produto = ? AND id_armazem = ?"
        );
        $stmt->execute([$idProduto, $idArmazem]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Atualiza o saldo de um produto em um armazém aplicando um delta.
     * Usa ON DUPLICATE KEY UPDATE para inserir o registro se ainda não existir
     * ou incrementar/decrementar caso já exista.
     * GREATEST(0, ...) impede que a quantidade fique negativa mesmo em
     * situações de concorrência.
     *
     * @param  int $idProduto ID do produto
     * @param  int $idArmazem ID do armazém
     * @param  int $delta     Valor a somar ao saldo atual (positivo=entrada, negativo=saída)
     * @return void
     */
    public function aplicarDelta(int $idProduto, int $idArmazem, int $delta): void
    {
        /* ON DUPLICATE KEY UPDATE: se já existir registro para
         * (id_produto, id_armazem), apenas incrementa/decrementa.
         * Caso contrário, insere um novo registro com o delta.
         * GREATEST(0, ...) impede que a quantidade fique negativa
         * mesmo em situações de concorrência. */
        $this->pdo->prepare("
            INSERT INTO estoque (id_produto, id_armazem, quantidade) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantidade = GREATEST(0, quantidade + ?)
        ")->execute([$idProduto, $idArmazem, $delta, $delta]);
    }

    /**
     * Retorna a lista consolidada de produtos com a soma do estoque em todos os armazéns.
     * LEFT JOIN garante que produtos sem nenhum estoque também apareçam com quantidade 0,
     * evitando que fiquem "invisíveis" na tela.
     *
     * @return array Lista de produtos com qtd_estoque e demais campos da tabela produto
     */
    public function listarProdutosComEstoque(): array
    {
        return $this->pdo->query("
            SELECT p.*, COALESCE(SUM(e.quantidade), 0) AS qtd_estoque
            FROM produto p
            LEFT JOIN estoque e ON p.id_produto = e.id_produto
            GROUP BY p.id_produto
            ORDER BY p.descricao
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o estoque de todos os produtos agrupado por armazém,
     * indexado por id_produto para acesso O(1) na view.
     * Filtra apenas registros com quantidade > 0 para não poluir a interface.
     *
     * @return array Array indexado: [id_produto => [['armazem_nome', 'quantidade'], ...]]
     */
    public function listarEstoquePorArmazem(): array
    {
        $rows = $this->pdo->query("
            SELECT e.id_produto, e.quantidade, a.nome AS armazem_nome
            FROM estoque e
            JOIN armazem a ON e.id_armazem = a.id_armazem
            WHERE e.quantidade > 0
            ORDER BY e.id_produto, a.nome
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Indexa por id_produto para acesso direto na view
        $resultado = [];
        foreach ($rows as $row) {
            $resultado[$row['id_produto']][] = $row;
        }
        return $resultado;
    }

    /**
     * Retorna a lista de armazéns disponíveis para preencher o select do formulário.
     *
     * @return array Lista de armazéns (id_armazem, nome, ...)
     */
    public function listarArmazens(): array
    {
        return $this->pdo->query(
            "SELECT * FROM armazem ORDER BY nome"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
