<?php
/**
 * DAO: EntregaDao
 *
 * Concentra todas as operações de banco de dados relacionadas ao módulo de Entregas.
 * Cada método encapsula uma ou mais queries SQL, mantendo a lógica de persistência
 * separada do controller e da view.
 *
 * Responsabilidades:
 *  - CRUD de entregas e vínculos com produtos
 *  - Atualização de status com desconto automático de estoque (lógica por armazém ou distribuída)
 *  - Atualização do armazém de origem
 *  - Consultas para alimentar a view (lista, contadores de status, produtos por entrega)
 */
class EntregaDao
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
    // OPERAÇÕES DE ESCRITA
    // -------------------------------------------------------------------------

    /**
     * Exclui uma entrega e todos os seus vínculos dependentes.
     * Remove primeiro entrega_produto e rota_entrega (chaves estrangeiras)
     * e só então remove o registro principal em entrega.
     *
     * @param  int  $idEntrega ID da entrega a excluir
     * @return void
     * @throws Exception Em caso de erro de banco (ex.: FK residual)
     */
    public function excluir(int $idEntrega): void
    {
        // Remove a relação entre esta entrega e as rotas vinculadas
        $this->pdo->prepare("DELETE FROM rota_entrega WHERE id_entrega = ?")
            ->execute([$idEntrega]);

        // Remove os produtos associados a esta entrega
        $this->pdo->prepare("DELETE FROM entrega_produto WHERE id_entrega = ?")
            ->execute([$idEntrega]);

        // Remove o registro principal da entrega
        $this->pdo->prepare("DELETE FROM entrega WHERE id_entrega = ?")
            ->execute([$idEntrega]);
    }

    /**
     * Cadastra uma nova entrega completa com seus produtos vinculados.
     * Usa transação para garantir atomicidade: se a inserção de algum produto
     * falhar, toda a operação é revertida (rollback).
     *
     * @param  int         $idCliente   ID do cliente destinatário
     * @param  int|null    $idArmazem   ID do armazém de origem (opcional)
     * @param  string      $dataPrevista Data prevista no formato Y-m-d
     * @param  float|null  $pesoTotal   Peso total da carga em kg (opcional)
     * @param  float|null  $volumeTotal Volume total da carga em m³ (opcional)
     * @param  array       $produtos    Array de ['id_produto' => int, 'quantidade' => int]
     * @return int ID da entrega criada
     * @throws Exception Em caso de falha em qualquer etapa da transação
     */
    public function cadastrarCompleta(
        int     $idCliente,
        ?int    $idArmazem,
        string  $dataPrevista,
        ?float  $pesoTotal,
        ?float  $volumeTotal,
        array   $produtos
    ): int {
        $this->pdo->beginTransaction(); // Inicia a transação para garantir atomicidade

        try {
            // Insere o cabeçalho da entrega; armazém, peso e volume são opcionais
            $this->pdo->prepare(
                "INSERT INTO entrega (id_cliente, id_armazem, data_prevista, peso_total, volume_total)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$idCliente, $idArmazem, $dataPrevista, $pesoTotal, $volumeTotal]);

            $idEntrega = (int)$this->pdo->lastInsertId(); // ID da entrega recém-criada

            // Insere os produtos vinculados, ignorando linhas com produto ou quantidade inválidos
            if (!empty($produtos)) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO entrega_produto (id_entrega, id_produto, quantidade) VALUES (?, ?, ?)"
                );
                foreach ($produtos as $p) {
                    if (!empty($p['id_produto']) && (int)($p['quantidade'] ?? 0) > 0) {
                        $stmt->execute([$idEntrega, (int)$p['id_produto'], (int)$p['quantidade']]);
                    }
                }
            }

            $this->pdo->commit(); // Confirma todas as inserções
            return $idEntrega;
        } catch (Exception $e) {
            // Reverte tudo se qualquer etapa falhar
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Atualiza o status de uma entrega e, se o novo status for ENTREGUE,
     * executa o desconto automático do estoque.
     *
     * O desconto de estoque só ocorre se o status anterior NÃO era ENTREGUE,
     * evitando desconto duplo caso o usuário salve o mesmo status por engano.
     *
     * Algoritmo de desconto:
     *  - Se a entrega possui armazém de origem: desconta diretamente desse armazém.
     *  - Se não possui armazém: distribui o desconto entre armazéns em ordem
     *    decrescente de quantidade (maior saldo primeiro) até zerar o necessário.
     *
     * @param  int    $idEntrega  ID da entrega a atualizar
     * @param  string $novoStatus Novo status (PENDENTE, EM_TRANSITO, ENTREGUE, ATRASADA)
     * @param  int    $idUsuario  ID do usuário logado (para registrar movimentações)
     * @return void
     */
    public function atualizarStatus(int $idEntrega, string $novoStatus, int $idUsuario): void
    {
        // Consulta o status atual para evitar duplo desconto de estoque
        $stmtStatus = $this->pdo->prepare("SELECT status FROM entrega WHERE id_entrega = ?");
        $stmtStatus->execute([$idEntrega]);
        $statusAnterior = $stmtStatus->fetchColumn();

        // Registra a data de realização apenas quando a entrega é concluída
        $dataRealizada = ($novoStatus === 'ENTREGUE') ? date('Y-m-d') : null;

        $this->pdo->prepare(
            "UPDATE entrega SET status = ?, data_realizada = ? WHERE id_entrega = ?"
        )->execute([$novoStatus, $dataRealizada, $idEntrega]);

        // Desconta estoque ao marcar ENTREGUE (só se não estava ENTREGUE antes)
        // A verificação do statusAnterior impede desconto duplo caso o usuário
        // salve o mesmo status novamente por engano.
        if ($novoStatus === 'ENTREGUE' && $statusAnterior !== 'ENTREGUE') {
            $this->descontarEstoque($idEntrega, $idUsuario);
        }
    }

    /**
     * Executa o desconto de estoque para todos os produtos de uma entrega.
     * É chamado internamente por atualizarStatus() quando o status passa para ENTREGUE.
     *
     * @param  int $idEntrega ID da entrega cujos produtos serão descontados
     * @param  int $idUsuario ID do usuário responsável (para histórico de movimentação)
     * @return void
     */
    private function descontarEstoque(int $idEntrega, int $idUsuario): void
    {
        // Obtém o armazém de origem definido na entrega (pode ser null)
        $stmtArm = $this->pdo->prepare("SELECT id_armazem FROM entrega WHERE id_entrega = ?");
        $stmtArm->execute([$idEntrega]);
        $idArmazemEntrega = $stmtArm->fetchColumn() ?: null;

        // Busca todos os produtos e quantidades vinculados a esta entrega
        $stmtProds = $this->pdo->prepare(
            "SELECT id_produto, quantidade FROM entrega_produto WHERE id_entrega = ?"
        );
        $stmtProds->execute([$idEntrega]);

        foreach ($stmtProds->fetchAll(PDO::FETCH_ASSOC) as $p) {
            if ($idArmazemEntrega) {
                /* Caso 1: Entrega possui armazém de origem definido.
                 * Desconta diretamente do estoque daquele armazém.
                 * GREATEST(0, ...) impede que a quantidade fique negativa. */
                $this->pdo->prepare(
                    "UPDATE estoque
                     SET quantidade = GREATEST(0, quantidade - ?)
                     WHERE id_produto = ? AND id_armazem = ?"
                )->execute([$p['quantidade'], $p['id_produto'], $idArmazemEntrega]);

                // Registra a movimentação de saída no histórico
                $this->pdo->prepare(
                    "INSERT INTO movimentacao_estoque
                         (id_produto, id_usuario, id_armazem, tipo_movimentacao, quantidade)
                     VALUES (?, ?, ?, 'SAIDA', ?)"
                )->execute([$p['id_produto'], $idUsuario, $idArmazemEntrega, $p['quantidade']]);
            } else {
                /* Caso 2: Entrega sem armazém de origem definido.
                 * Desconta os armazéns em ordem decrescente de quantidade
                 * (maior saldo primeiro) até zerar a quantidade necessária.
                 * Essa lógica distribui o desconto entre múltiplos armazéns
                 * quando nenhum armazém específico foi associado à entrega. */
                $stmtArmaz = $this->pdo->prepare(
                    "SELECT id_armazem, quantidade
                     FROM estoque
                     WHERE id_produto = ? AND quantidade > 0
                     ORDER BY quantidade DESC"
                );
                $stmtArmaz->execute([$p['id_produto']]);

                $remaining = (int)$p['quantidade']; // Saldo ainda a ser descontado

                foreach ($stmtArmaz->fetchAll(PDO::FETCH_ASSOC) as $w) {
                    if ($remaining <= 0) break; // Toda a quantidade já foi descontada

                    $deduct = min($remaining, (int)$w['quantidade']); // Desconta no máximo o disponível

                    if ($w['id_armazem'] !== null) {
                        // Armazém com ID definido: atualiza normalmente
                        $this->pdo->prepare(
                            "UPDATE estoque
                             SET quantidade = quantidade - ?
                             WHERE id_produto = ? AND id_armazem = ?"
                        )->execute([$deduct, $p['id_produto'], $w['id_armazem']]);
                    } else {
                        // Registro de estoque sem armazém (estoque "flutuante"): usa GREATEST para segurança
                        $this->pdo->prepare(
                            "UPDATE estoque
                             SET quantidade = GREATEST(0, quantidade - ?)
                             WHERE id_produto = ? AND id_armazem IS NULL"
                        )->execute([$deduct, $p['id_produto']]);
                    }

                    $remaining -= $deduct; // Atualiza o saldo restante a descontar
                }

                // Registra a movimentação total de saída (sem armazém específico)
                $this->pdo->prepare(
                    "INSERT INTO movimentacao_estoque
                         (id_produto, id_usuario, tipo_movimentacao, quantidade)
                     VALUES (?, ?, 'SAIDA', ?)"
                )->execute([$p['id_produto'], $idUsuario, $p['quantidade']]);
            }
        }
    }

    /**
     * Atualiza o armazém de origem de uma entrega já cadastrada.
     * Permite definir ou corrigir o armazém sem alterar os demais campos.
     *
     * @param  int      $idEntrega ID da entrega a atualizar
     * @param  int|null $idArmazem Novo ID do armazém (null para remover vínculo)
     * @return void
     */
    public function atualizarArmazem(int $idEntrega, ?int $idArmazem): void
    {
        $this->pdo->prepare(
            "UPDATE entrega SET id_armazem = ? WHERE id_entrega = ?"
        )->execute([$idArmazem, $idEntrega]);
    }

    /**
     * Adiciona um produto avulso a uma entrega já cadastrada.
     * A constraint UNIQUE em (id_entrega, id_produto) no banco impede duplicidade.
     *
     * @param  int $idEntrega  ID da entrega de destino
     * @param  int $idProduto  ID do produto a vincular
     * @param  int $quantidade Quantidade do produto a adicionar
     * @return void
     * @throws Exception Em caso de produto já vinculado (chave duplicada) ou outro erro
     */
    public function adicionarProduto(int $idEntrega, int $idProduto, int $quantidade): void
    {
        $this->pdo->prepare(
            "INSERT INTO entrega_produto (id_entrega, id_produto, quantidade) VALUES (?, ?, ?)"
        )->execute([$idEntrega, $idProduto, $quantidade]);
    }

    // -------------------------------------------------------------------------
    // CONSULTAS PARA A VIEW
    // -------------------------------------------------------------------------

    /**
     * Retorna a lista completa de clientes ordenada por nome.
     * Usada para popular o select de cliente no formulário de nova entrega.
     *
     * @return array Lista de clientes (id_cliente, nome, ...)
     */
    public function listarClientes(): array
    {
        return $this->pdo->query(
            "SELECT * FROM cliente ORDER BY nome"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a lista de produtos com id, descrição, peso e volume.
     * Usada para popular o select de produtos e calcular totais de peso/volume via JS.
     *
     * @return array Lista de produtos (id_produto, descricao, peso, volume)
     */
    public function listarProdutos(): array
    {
        return $this->pdo->query(
            "SELECT id_produto, descricao, peso, volume FROM produto ORDER BY descricao"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a lista de armazéns com cidade e estado do endereço vinculado.
     * Usada nos selects de armazém de origem (formulário e edição rápida).
     *
     * @return array Lista de armazéns (id_armazem, nome, cidade, estado)
     */
    public function listarArmazens(): array
    {
        return $this->pdo->query(
            "SELECT a.id_armazem, a.nome, en.cidade, en.estado
             FROM armazem a
             LEFT JOIN endereco en ON a.id_endereco = en.id_endereco
             ORDER BY a.nome"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna apenas os produtos que têm estoque disponível (quantidade > 0)
     * no armazém informado, incluindo a quantidade disponível.
     * Usada pelo endpoint AJAX para filtrar o select de produtos após
     * o usuário selecionar o armazém de origem de uma nova entrega.
     *
     * @param  int   $idArmazem ID do armazém selecionado
     * @return array Lista de produtos com quantidade_disponivel
     */
    public function listarProdutosPorArmazem(int $idArmazem): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.id_produto, p.descricao, p.peso, p.volume,
                    COALESCE(e.quantidade, 0) AS quantidade_disponivel
             FROM produto p
             JOIN estoque e ON p.id_produto = e.id_produto
             WHERE e.id_armazem = ? AND e.quantidade > 0
             ORDER BY p.descricao"
        );
        $stmt->execute([$idArmazem]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as entregas cadastradas com dados do cliente, armazém
     * e contagem de itens vinculados, ordenadas da mais recente para a mais antiga.
     *
     * @return array Lista de entregas enriquecida com dados relacionados
     */
    public function listarTodas(): array
    {
        return $this->pdo->query("
            SELECT
                e.*,
                c.nome AS cliente_nome,
                a.nome AS armazem_nome,
                en_a.cidade AS armazem_cidade,
                en_a.estado AS armazem_estado,
                (SELECT COUNT(*)
                   FROM entrega_produto ep
                  WHERE ep.id_entrega = e.id_entrega) AS total_itens
            FROM entrega e
            JOIN  cliente c    ON e.id_cliente = c.id_cliente
            LEFT JOIN armazem a    ON e.id_armazem = a.id_armazem
            LEFT JOIN endereco en_a ON a.id_endereco = en_a.id_endereco
            ORDER BY e.id_entrega DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a contagem de entregas por status.
     * Alimenta os cards de KPI no topo da view.
     *
     * @return array Mapa ['pendentes' => int, 'em_transito' => int, 'entregues' => int, 'atrasadas' => int]
     */
    public function contarPorStatus(): array
    {
        return [
            'pendentes'   => (int)$this->pdo->query("SELECT COUNT(*) FROM entrega WHERE status='PENDENTE'")->fetchColumn(),
            'em_transito' => (int)$this->pdo->query("SELECT COUNT(*) FROM entrega WHERE status='EM_TRANSITO'")->fetchColumn(),
            'entregues'   => (int)$this->pdo->query("SELECT COUNT(*) FROM entrega WHERE status='ENTREGUE'")->fetchColumn(),
            'atrasadas'   => (int)$this->pdo->query("SELECT COUNT(*) FROM entrega WHERE status='ATRASADA'")->fetchColumn(),
        ];
    }

    /**
     * Retorna todos os produtos vinculados a todas as entregas, agrupados em
     * um array indexado por id_entrega para acesso O(1) na view.
     *
     * @return array Array indexado: [id_entrega => [['descricao', 'quantidade', 'peso', 'volume'], ...]]
     */
    public function listarProdutosPorEntrega(): array
    {
        $rows = $this->pdo->query("
            SELECT ep.id_entrega, ep.quantidade, p.descricao, p.peso, p.volume
            FROM entrega_produto ep
            JOIN produto p ON ep.id_produto = p.id_produto
            ORDER BY ep.id_entrega, p.descricao
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Indexa os produtos pelo id_entrega para acesso direto na view
        $resultado = [];
        foreach ($rows as $row) {
            $resultado[$row['id_entrega']][] = $row;
        }
        return $resultado;
    }
}
