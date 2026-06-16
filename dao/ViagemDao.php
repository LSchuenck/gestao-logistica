<?php
/*
 * Arquivo: dao/ViagemDao.php
 * Finalidade: Data Access Object (DAO) para o módulo de Viagens.
 * Centraliza todas as queries SQL relacionadas à tabela `viagem` e suas
 * tabelas dependentes (rota, motorista, veiculo, entrega, rastreamento,
 * alerta, frete).
 * Recebe a conexão PDO via construtor — sem instanciar conexão própria.
 */

class ViagemDao
{
    /** @var PDO Instância da conexão com o banco de dados */
    private PDO $pdo;

    /**
     * Construtor: injeta a dependência PDO.
     *
     * @param PDO $pdo Conexão com o banco de dados
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* =====================================================================
     * CONSULTAS DE LEITURA
     * ===================================================================== */

    /**
     * Retorna todas as viagens com dados de rota, motorista e veículo,
     * ordenadas pela viagem mais recente primeiro.
     *
     * @return array Lista de viagens como arrays associativos
     */
    public function listarTodas(): array
    {
        return $this->pdo->query(
            "SELECT vi.*, r.distancia,
                m.nome AS motorista, ve.placa, ve.tipo_veiculo
             FROM viagem vi
             JOIN rota r       ON vi.id_rota       = r.id_rota
             JOIN motorista m  ON r.id_motorista   = m.id_motorista
             JOIN veiculo ve   ON r.id_veiculo     = ve.id_veiculo
             ORDER BY vi.id_viagem DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna as rotas disponíveis para associação a uma nova viagem.
     * Exclui rotas já FINALIZADAS, pois enceraram seu ciclo.
     *
     * @return array Lista de rotas disponíveis
     */
    public function listarRotasDisponiveis(): array
    {
        return $this->pdo->query(
            "SELECT r.id_rota, m.nome AS motorista, ve.placa
             FROM rota r
             JOIN motorista m ON r.id_motorista = m.id_motorista
             JOIN veiculo ve  ON r.id_veiculo   = ve.id_veiculo
             WHERE r.status != 'FINALIZADA'
             ORDER BY r.id_rota DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca o último ponto de rastreamento GPS de uma viagem específica.
     *
     * @param int $idViagem ID da viagem
     * @return array|false Registro do último ponto GPS ou false se não houver
     */
    public function buscarUltimoRastreamento(int $idViagem)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM rastreamento
             WHERE id_viagem = ?
             ORDER BY data_hora DESC
             LIMIT 1"
        );
        $stmt->execute([$idViagem]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Conta as viagens por status para exibição nos cards de KPI.
     *
     * @param string|array $status Status ou array de status para filtrar
     * @return int Total de viagens no(s) status informado(s)
     */
    public function contarPorStatus($status): int
    {
        if (is_array($status)) {
            // Monta placeholders dinâmicos para o IN (ex.: ?,?)
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM viagem WHERE status IN ($placeholders)"
            );
            $stmt->execute($status);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM viagem WHERE status = ?"
            );
            $stmt->execute([$status]);
        }
        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca o id_rota de uma viagem pelo seu ID.
     *
     * @param int $idViagem ID da viagem
     * @return int|false ID da rota ou false se não encontrado
     */
    public function buscarIdRota(int $idViagem)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_rota FROM viagem WHERE id_viagem = ?"
        );
        $stmt->execute([$idViagem]);
        return $stmt->fetchColumn();
    }

    /* =====================================================================
     * OPERAÇÕES DE ESCRITA — VIAGEM
     * ===================================================================== */

    /**
     * Insere uma nova viagem no banco de dados e atualiza em cascata:
     * - Rota → EM_ANDAMENTO
     * - Entregas PENDENTES da rota → EM_TRANSITO
     * Toda a operação é executada dentro de uma transação.
     *
     * @param int    $idRota              ID da rota vinculada
     * @param string $dataSaida           Data/hora de saída
     * @param string $dataChegadaPrevista Data/hora prevista de chegada
     * @return void
     * @throws Exception Em caso de falha durante a transação
     */
    public function inserir(int $idRota, string $dataSaida, string $dataChegadaPrevista): void
    {
        $this->pdo->beginTransaction();
        try {
            // Insere o registro da viagem com as datas informadas pelo formulário
            $this->pdo->prepare(
                "INSERT INTO viagem (id_rota, data_saida, data_chegada_prevista)
                 VALUES (?, ?, ?)"
            )->execute([$idRota, $dataSaida, $dataChegadaPrevista]);

            // Marca a rota como EM_ANDAMENTO indicando que a viagem foi iniciada
            $this->pdo->prepare(
                "UPDATE rota SET status = 'EM_ANDAMENTO' WHERE id_rota = ?"
            )->execute([$idRota]);

            // Transiciona apenas as entregas PENDENTES da rota para EM_TRANSITO;
            // entregas já em outros status não são afetadas
            $this->pdo->prepare(
                "UPDATE entrega SET status = 'EM_TRANSITO'
                 WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                   AND status = 'PENDENTE'"
            )->execute([$idRota]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Altera o status de uma viagem com os efeitos em cascata necessários.
     * Toda a operação é executada dentro de uma transação para garantir atomicidade.
     *
     * Regras de cascata:
     * - CONCLUIDA: rota → FINALIZADA; entregas da rota → ENTREGUE
     * - CANCELADA:  rota → PLANEJADA; entregas EM_TRANSITO → PENDENTE
     * - Outros:    apenas atualiza o status da viagem
     *
     * @param int    $idViagem   ID da viagem
     * @param int    $idRota     ID da rota associada
     * @param string $novoStatus Novo status desejado
     * @return void
     */
    public function atualizarStatus(int $idViagem, int $idRota, string $novoStatus): void
    {
        $this->pdo->beginTransaction();

        if ($novoStatus === 'CONCLUIDA') {
            // Registra a conclusão com o timestamp atual como chegada real
            $this->pdo->prepare(
                "UPDATE viagem SET status = 'CONCLUIDA', data_chegada_real = NOW()
                 WHERE id_viagem = ?"
            )->execute([$idViagem]);

            // Finaliza a rota para que não possa ser reiniciada sem intervenção
            $this->pdo->prepare(
                "UPDATE rota SET status = 'FINALIZADA' WHERE id_rota = ?"
            )->execute([$idRota]);

            // Marca todas as entregas da rota como ENTREGUE com a data de hoje
            $this->pdo->prepare(
                "UPDATE entrega SET status = 'ENTREGUE', data_realizada = CURDATE()
                 WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)"
            )->execute([$idRota]);

        } elseif ($novoStatus === 'CANCELADA') {
            // Cancela a viagem e remove a data de chegada real (não houve chegada)
            $this->pdo->prepare(
                "UPDATE viagem SET status = 'CANCELADA', data_chegada_real = NULL
                 WHERE id_viagem = ?"
            )->execute([$idViagem]);

            // Reverte a rota para PLANEJADA para que possa ser reativada futuramente
            $this->pdo->prepare(
                "UPDATE rota SET status = 'PLANEJADA' WHERE id_rota = ?"
            )->execute([$idRota]);

            // Reverte apenas as entregas EM_TRANSITO para PENDENTE;
            // entregas já ENTREGUE ou CANCELADA não são afetadas
            $this->pdo->prepare(
                "UPDATE entrega SET status = 'PENDENTE'
                 WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                   AND status = 'EM_TRANSITO'"
            )->execute([$idRota]);

        } else {
            // Transições intermediárias (INICIADA, EM_TRANSITO) alteram apenas a viagem
            $this->pdo->prepare(
                "UPDATE viagem SET status = ? WHERE id_viagem = ?"
            )->execute([$novoStatus, $idViagem]);
        }

        $this->pdo->commit();
    }

    /**
     * Exclui uma viagem e todos os seus dados dependentes em cascata,
     * respeitando a ordem de dependências de chave estrangeira:
     * alertas → rastreamentos → fretes → viagem.
     *
     * @param int $idViagem ID da viagem a ser excluída
     * @return void
     * @throws Exception Em caso de falha durante a exclusão
     */
    public function excluir(int $idViagem): void
    {
        // Remove alertas gerados durante a viagem
        $this->pdo->prepare(
            "DELETE FROM alerta WHERE id_viagem = ?"
        )->execute([$idViagem]);

        // Remove pontos de rastreamento GPS registrados durante a viagem
        $this->pdo->prepare(
            "DELETE FROM rastreamento WHERE id_viagem = ?"
        )->execute([$idViagem]);

        // Remove fretes/notas fiscais vinculados à viagem
        $this->pdo->prepare(
            "DELETE FROM frete WHERE id_viagem = ?"
        )->execute([$idViagem]);

        // Remove o registro principal da viagem
        $this->pdo->prepare(
            "DELETE FROM viagem WHERE id_viagem = ?"
        )->execute([$idViagem]);
    }

    /* =====================================================================
     * OPERAÇÕES DE ESCRITA — RASTREAMENTO
     * ===================================================================== */

    /**
     * Registra um ponto de rastreamento GPS para uma viagem.
     * A data/hora é gerada automaticamente pelo banco (NOW()).
     *
     * @param int   $idViagem  ID da viagem
     * @param float $latitude  Coordenada de latitude
     * @param float $longitude Coordenada de longitude
     * @return void
     * @throws Exception Em caso de falha ao inserir
     */
    public function registrarRastreamento(int $idViagem, float $latitude, float $longitude): void
    {
        $this->pdo->prepare(
            "INSERT INTO rastreamento (id_viagem, latitude, longitude)
             VALUES (?, ?, ?)"
        )->execute([$idViagem, $latitude, $longitude]);
    }
}
