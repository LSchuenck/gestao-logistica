<?php
/**
 * DAO: RotaDao
 * Responsável por todas as operações SQL relacionadas à entidade Rota.
 * Recebe a instância PDO no construtor e encapsula cada query em um método dedicado.
 */
class RotaDao {

    /** @var PDO Instância de conexão com o banco de dados */
    private PDO $pdo;

    /**
     * Construtor: recebe a conexão PDO injetada externamente.
     *
     * @param PDO $pdo Conexão ativa com o banco de dados
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // =========================================================================
    // CONSULTAS DE LISTAGEM
    // =========================================================================

    /**
     * Retorna todas as rotas com dados do motorista, veículo e contagem de entregas.
     * Ordenadas pelo id_rota decrescente (mais recentes primeiro).
     *
     * @return array Linhas com colunas: id_rota, id_veiculo, id_motorista, distancia,
     *               status, motorista, placa, tipo_veiculo, total_entregas
     */
    public function listarTodas(): array {
        $sql = "SELECT r.*, m.nome AS motorista, ve.placa, ve.tipo_veiculo,
                    (SELECT COUNT(*) FROM rota_entrega re WHERE re.id_rota = r.id_rota) AS total_entregas
                FROM rota r
                JOIN motorista m  ON r.id_motorista = m.id_motorista
                JOIN veiculo  ve  ON r.id_veiculo   = ve.id_veiculo
                ORDER BY r.id_rota DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os motoristas ativos com o nome de sua transportadora.
     * Utilizado para popular o select do formulário de nova rota.
     *
     * @return array Linhas com colunas: id_motorista, nome, nome_fantasia
     */
    public function listarMotoristas(): array {
        $sql = "SELECT m.id_motorista, m.nome, t.nome_fantasia
                FROM motorista m
                JOIN transportadora t ON m.id_transportadora = t.id_transportadora
                WHERE m.status = 'ATIVO'
                ORDER BY m.nome";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os veículos com status DISPONIVEL.
     * Utilizado para popular o select do formulário de nova rota.
     *
     * @return array Linhas com colunas da tabela veiculo
     */
    public function listarVeiculosDisponiveis(): array {
        return $this->pdo
            ->query("SELECT * FROM veiculo WHERE status = 'DISPONIVEL' ORDER BY placa")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as entregas com status PENDENTE.
     * Utilizado para popular o select de entregas disponíveis para vinculação.
     *
     * @return array Linhas com colunas: id_entrega, status, cliente
     */
    public function listarEntregasPendentes(): array {
        $sql = "SELECT e.id_entrega, e.status, c.nome AS cliente
                FROM entrega e
                JOIN cliente c ON e.id_cliente = c.id_cliente
                WHERE e.status = 'PENDENTE'
                ORDER BY e.id_entrega DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as entregas vinculadas às rotas, agrupáveis por id_rota.
     * Inclui dados de cliente e data prevista para exibição no detalhe expansível.
     *
     * @return array Linhas com colunas: id_rota, id_entrega, status, data_prevista, cliente
     */
    public function listarEntregasPorRota(): array {
        $sql = "SELECT re.id_rota, e.id_entrega, e.status, e.data_prevista, c.nome AS cliente
                FROM rota_entrega re
                JOIN entrega e  ON re.id_entrega  = e.id_entrega
                JOIN cliente c  ON e.id_cliente   = c.id_cliente
                ORDER BY e.id_entrega";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // CONTADORES DE STATUS (KPIs)
    // =========================================================================

    /**
     * Retorna a quantidade de rotas com status PLANEJADA.
     *
     * @return int Total de rotas planejadas
     */
    public function contarPlanejadas(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM rota WHERE status = 'PLANEJADA'")
            ->fetchColumn();
    }

    /**
     * Retorna a quantidade de rotas com status EM_ANDAMENTO.
     *
     * @return int Total de rotas em andamento
     */
    public function contarEmAndamento(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM rota WHERE status = 'EM_ANDAMENTO'")
            ->fetchColumn();
    }

    /**
     * Retorna a quantidade de rotas com status FINALIZADA.
     *
     * @return int Total de rotas finalizadas
     */
    public function contarFinalizadas(): int {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM rota WHERE status = 'FINALIZADA'")
            ->fetchColumn();
    }

    // =========================================================================
    // OPERAÇÕES DE ESCRITA
    // =========================================================================

    /**
     * Insere uma nova rota no banco de dados.
     *
     * @param int        $idVeiculo   ID do veículo selecionado
     * @param int        $idMotorista ID do motorista responsável
     * @param float|null $distancia   Distância estimada em km (opcional)
     * @return void
     */
    public function inserir(int $idVeiculo, int $idMotorista, ?float $distancia): void {
        $sql = "INSERT INTO rota (id_veiculo, id_motorista, distancia) VALUES (?, ?, ?)";
        $this->pdo->prepare($sql)->execute([$idVeiculo, $idMotorista, $distancia]);
    }

    /**
     * Vincula uma entrega a uma rota existente na tabela rota_entrega.
     * A constraint UNIQUE da tabela impede duplicatas — uma exceção PDO será
     * lançada caso a entrega já esteja vinculada.
     *
     * @param int $idRota    ID da rota de destino
     * @param int $idEntrega ID da entrega a vincular
     * @return void
     */
    public function vincularEntrega(int $idRota, int $idEntrega): void {
        $this->pdo
            ->prepare("INSERT INTO rota_entrega (id_rota, id_entrega) VALUES (?, ?)")
            ->execute([$idRota, $idEntrega]);
    }

    /**
     * Atualiza o status de uma rota.
     * Somente valores permitidos devem ser passados — a validação fica no Controller.
     *
     * @param string $status   Novo status: PLANEJADA | EM_ANDAMENTO | FINALIZADA
     * @param int    $idRota   ID da rota a ser atualizada
     * @return void
     */
    public function atualizarStatus(string $status, int $idRota): void {
        $this->pdo
            ->prepare("UPDATE rota SET status = ? WHERE id_rota = ?")
            ->execute([$status, $idRota]);
    }

    /**
     * Remove todos os vínculos de entregas de uma rota e depois exclui a rota.
     * A remoção prévia dos vínculos é obrigatória para respeitar as constraints
     * de chave estrangeira da tabela rota_entrega.
     * Se houver viagem vinculada à rota, o banco lançará uma exceção PDO (FK),
     * que deve ser capturada no Controller.
     *
     * @param int $idRota ID da rota a ser excluída
     * @return void
     */
    public function excluir(int $idRota): void {
        // Remove primeiro os vínculos de entregas para respeitar as FKs
        $this->pdo
            ->prepare("DELETE FROM rota_entrega WHERE id_rota = ?")
            ->execute([$idRota]);

        // Exclui o registro principal da rota
        $this->pdo
            ->prepare("DELETE FROM rota WHERE id_rota = ?")
            ->execute([$idRota]);
    }
}
