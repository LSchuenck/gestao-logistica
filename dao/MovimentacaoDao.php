<?php
/**
 * DAO: MovimentacaoDao
 *
 * Concentra todas as operações de banco de dados relacionadas ao histórico
 * de movimentações de estoque (tabela movimentacao_estoque).
 *
 * Responsabilidades:
 *  - Registrar novas movimentações de entrada e saída
 *  - Consultar o histórico das últimas movimentações para a view
 */
class MovimentacaoDao
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

    /**
     * Registra uma movimentação de estoque no histórico.
     * O id_armazem é opcional: pode ser null quando a movimentação não está
     * associada a um armazém específico (ex.: desconto distribuído entre armazéns).
     *
     * @param  int      $idProduto  ID do produto movimentado
     * @param  int      $idUsuario  ID do usuário responsável pela movimentação
     * @param  int|null $idArmazem  ID do armazém (null para movimentação sem armazém definido)
     * @param  string   $tipo       Tipo da movimentação: 'ENTRADA' ou 'SAIDA'
     * @param  int      $quantidade Quantidade movimentada (sempre positiva)
     * @return void
     */
    public function registrar(
        int     $idProduto,
        int     $idUsuario,
        ?int    $idArmazem,
        string  $tipo,
        int     $quantidade
    ): void {
        $this->pdo->prepare(
            "INSERT INTO movimentacao_estoque
                 (id_produto, id_usuario, id_armazem, tipo_movimentacao, quantidade)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$idProduto, $idUsuario, $idArmazem, $tipo, $quantidade]);
    }

    /**
     * Retorna o histórico das últimas movimentações de estoque, com detalhes
     * do produto, usuário responsável e armazém envolvido.
     * Limitar a 20 registros evita carregamento excessivo na página.
     *
     * @param  int $limite Número máximo de registros a retornar (padrão: 20)
     * @return array Lista de movimentações ordenada da mais recente para a mais antiga
     */
    public function listarHistorico(int $limite = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                m.*,
                p.descricao,
                u.nome AS usuario_nome,
                a.nome AS armazem_nome
            FROM movimentacao_estoque m
            JOIN  produto  p ON m.id_produto  = p.id_produto
            JOIN  usuario  u ON m.id_usuario  = u.id_usuario
            LEFT JOIN armazem  a ON m.id_armazem  = a.id_armazem
            ORDER BY m.data_movimentacao DESC
            LIMIT ?
        ");
        // PDO requer bind para LIMIT quando ATTR_EMULATE_PREPARES está desativado
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
