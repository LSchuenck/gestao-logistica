<?php
/**
 * DAO: OperacaoDao
 *
 * Concentra todas as queries SQL do módulo de Operações.
 * Gerencia rotas, vínculos de entregas, viagens e o ciclo completo
 * de desconto de estoque ao concluir uma viagem.
 *
 * Fluxo de status:
 *   Rota:    PLANEJADA → EM_ANDAMENTO → FINALIZADA  (ou volta a PLANEJADA se cancelada)
 *   Viagem:  INICIADA  → EM_TRANSITO  → CONCLUIDA   (ou CANCELADA)
 *   Entrega: PENDENTE  → EM_TRANSITO  → ENTREGUE    (ou volta a PENDENTE se cancelada)
 */
class OperacaoDao {

    public function __construct(private PDO $pdo) {}

    // =========================================================================
    // CONSULTAS DE LISTAGEM / LEITURA
    // =========================================================================

    /**
     * Retorna todas as transportadoras ativas com endereço.
     * Usadas nos selects do formulário de nova operação.
     *
     * @return array Lista de transportadoras com id, nome_fantasia, cidade, estado
     */
    public function listarTransportadoras(): array {
        return $this->pdo->query("
            SELECT t.id_transportadora, t.nome_fantasia, en.cidade, en.estado
            FROM transportadora t
            LEFT JOIN endereco en ON t.id_endereco = en.id_endereco
            WHERE t.status = 'ATIVA'
            ORDER BY t.nome_fantasia
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os motoristas ativos com o nome da transportadora.
     * Serializado como JSON no data-attr da view para filtragem dinâmica por transportadora.
     *
     * @return array Lista de motoristas com id, nome, id_transportadora, nome_fantasia
     */
    public function listarMotoristas(): array {
        return $this->pdo->query("
            SELECT m.id_motorista, m.nome, m.id_transportadora, t.nome_fantasia
            FROM motorista m
            JOIN transportadora t ON m.id_transportadora = t.id_transportadora
            WHERE m.status = 'ATIVO'
            ORDER BY t.nome_fantasia, m.nome
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos os veículos disponíveis com dados de capacidade e transportadora.
     * Serializado como JSON no data-attr da view para filtragem dinâmica por transportadora.
     *
     * @return array Lista de veículos com id, placa, tipo_veiculo, capacidade_carga, id_transportadora
     */
    public function listarVeiculos(): array {
        return $this->pdo->query("
            SELECT v.id_veiculo, v.placa, v.tipo_veiculo, v.capacidade_carga, v.id_transportadora
            FROM veiculo v
            WHERE v.status = 'DISPONIVEL'
            ORDER BY v.placa
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna as entregas com status PENDENTE sem rota vinculada.
     * Inclui dados do cliente destinatário e do armazém de origem (pode ser nulo).
     * Ordenadas pela data prevista ascendente para priorizar as mais urgentes.
     *
     * @return array Lista de entregas pendentes sem rota
     */
    public function listarEntregasPendentes(): array {
        return $this->pdo->query("
            SELECT e.id_entrega, c.nome AS cliente, e.data_prevista,
                en.cidade, en.estado,
                a.id_armazem, a.nome AS armazem_nome,
                en_a.cidade AS armazem_cidade, en_a.estado AS armazem_estado
            FROM entrega e
            JOIN cliente c ON e.id_cliente = c.id_cliente
            LEFT JOIN endereco en   ON c.id_endereco   = en.id_endereco
            LEFT JOIN armazem a     ON e.id_armazem     = a.id_armazem
            LEFT JOIN endereco en_a ON a.id_endereco   = en_a.id_endereco
            WHERE e.status = 'PENDENTE'
            ORDER BY e.data_prevista ASC, e.id_entrega DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta principal de operações (rotas) com dados completos:
     * motorista, veículo, transportadora, viagem mais recente e contagem de entregas.
     *
     * A ordenação prioriza: EM_ANDAMENTO → PLANEJADA → FINALIZADA,
     * garantindo que as operações mais urgentes apareçam no topo.
     *
     * @return array Lista de operações para a tela principal
     */
    public function listarOperacoes(): array {
        return $this->pdo->query("
            SELECT r.*,
                m.nome AS motorista, ve.placa, ve.tipo_veiculo, ve.capacidade_carga,
                t.nome_fantasia AS transportadora_nome,
                en_t.cidade AS transp_cidade, en_t.estado AS transp_estado,
                vi.id_viagem, vi.status AS status_viagem,
                vi.data_saida, vi.data_chegada_prevista, vi.data_chegada_real,
                (SELECT COUNT(*) FROM rota_entrega re WHERE re.id_rota = r.id_rota) AS total_entregas
            FROM rota r
            JOIN motorista m      ON r.id_motorista       = m.id_motorista
            JOIN veiculo ve       ON r.id_veiculo         = ve.id_veiculo
            JOIN transportadora t ON m.id_transportadora  = t.id_transportadora
            LEFT JOIN endereco en_t ON t.id_endereco      = en_t.id_endereco
            LEFT JOIN viagem vi ON vi.id_rota = r.id_rota
                AND vi.id_viagem = (SELECT MAX(v2.id_viagem) FROM viagem v2 WHERE v2.id_rota = r.id_rota)
            ORDER BY
                FIELD(r.status, 'EM_ANDAMENTO', 'PLANEJADA', 'FINALIZADA') ASC,
                r.id_rota DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as entregas vinculadas a rotas, com endereço do cliente e do armazém.
     * O resultado é um array indexado por id_rota (agrupamento feito no Controller).
     * Utilizado na view para renderizar a sub-tabela colapsável de cada operação.
     *
     * @return array Linhas com id_rota, dados da entrega, cliente e armazém
     */
    public function listarEntregasPorRota(): array {
        return $this->pdo->query("
            SELECT re.id_rota, e.id_entrega, e.status, e.data_prevista, c.nome AS cliente,
                en.logradouro, en.numero, en.bairro, en.cidade, en.estado,
                a.id_armazem, a.nome AS armazem_nome,
                en_a.cidade AS armazem_cidade, en_a.estado AS armazem_estado
            FROM rota_entrega re
            JOIN entrega e  ON re.id_entrega  = e.id_entrega
            JOIN cliente c  ON e.id_cliente   = c.id_cliente
            LEFT JOIN endereco en   ON c.id_endereco  = en.id_endereco
            LEFT JOIN armazem a     ON e.id_armazem    = a.id_armazem
            LEFT JOIN endereco en_a ON a.id_endereco  = en_a.id_endereco
            ORDER BY e.data_prevista ASC, e.id_entrega ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna os contadores de rotas por status para os KPI cards do topo da página.
     *
     * @return array Associativo com chaves: planejadas, em_andamento, finalizadas
     */
    public function contarPorStatus(): array {
        return [
            'planejadas'   => (int) $this->pdo->query("SELECT COUNT(*) FROM rota WHERE status = 'PLANEJADA'")->fetchColumn(),
            'em_andamento' => (int) $this->pdo->query("SELECT COUNT(*) FROM rota WHERE status = 'EM_ANDAMENTO'")->fetchColumn(),
            'finalizadas'  => (int) $this->pdo->query("SELECT COUNT(*) FROM rota WHERE status = 'FINALIZADA'")->fetchColumn(),
        ];
    }

    /**
     * Busca o id_rota associado a uma viagem específica.
     * Necessário para as operações em cascata sobre rota e entregas ao mudar o status da viagem.
     *
     * @param int $idViagem ID da viagem
     * @return int|false ID da rota ou false se não encontrada
     */
    public function buscarIdRotaPorViagem(int $idViagem): int|false {
        $stmt = $this->pdo->prepare("SELECT id_rota FROM viagem WHERE id_viagem = ?");
        $stmt->execute([$idViagem]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : false;
    }

    /**
     * Busca os IDs das entregas vinculadas a uma rota que estejam EM_TRANSITO.
     * O filtro por status é crítico para evitar duplo desconto de estoque caso
     * a viagem seja marcada como concluída mais de uma vez por algum erro de UI.
     *
     * @param int $idRota ID da rota
     * @return array Lista de IDs de entregas EM_TRANSITO
     */
    public function buscarEntregasEmTransito(int $idRota): array {
        $stmt = $this->pdo->prepare("
            SELECT id_entrega FROM entrega
            WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
              AND status = 'EM_TRANSITO'
        ");
        $stmt->execute([$idRota]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // =========================================================================
    // OPERAÇÕES DE ROTA
    // =========================================================================

    /**
     * Cria uma nova rota com veículo, motorista e distância.
     * Usa transação para garantir atomicidade entre a inserção da rota e os vínculos de entregas.
     * INSERT IGNORE nos vínculos garante idempotência caso alguma entrega já esteja vinculada.
     *
     * @param int        $idVeiculo   ID do veículo
     * @param int        $idMotorista ID do motorista
     * @param float|null $distancia   Distância estimada em km (pode ser nula)
     * @param array      $idEntregas  Array de IDs de entregas a vincular (opcional)
     * @return int ID da rota criada
     * @throws Exception Se ocorrer erro na inserção
     */
    public function criarRota(int $idVeiculo, int $idMotorista, ?float $distancia, array $idEntregas = []): int {
        $this->pdo->beginTransaction();

        try {
            // Insere a rota principal
            $this->pdo->prepare("INSERT INTO rota (id_veiculo, id_motorista, distancia) VALUES (?, ?, ?)")
                ->execute([$idVeiculo, $idMotorista, $distancia]);

            $idRota = (int) $this->pdo->lastInsertId();

            // Vincula as entregas selecionadas à nova rota (INSERT IGNORE = idempotente)
            if (!empty($idEntregas)) {
                $stmtLink = $this->pdo->prepare("INSERT IGNORE INTO rota_entrega (id_rota, id_entrega) VALUES (?, ?)");
                foreach ($idEntregas as $idEnt) {
                    $stmtLink->execute([$idRota, (int) $idEnt]);
                }
            }

            $this->pdo->commit();
            return $idRota;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Exclui uma rota e seus vínculos de entregas.
     * Os vínculos em rota_entrega são removidos primeiro para respeitar a FK.
     * Se houver viagem vinculada, o banco lança exceção (FK) que deve ser tratada no Controller.
     *
     * @param int $idRota ID da rota a excluir
     * @throws Exception Se houver viagem vinculada ou outro erro de banco
     */
    public function excluirRota(int $idRota): void {
        // Remove os vínculos antes de excluir a rota (restrição de chave estrangeira)
        $this->pdo->prepare("DELETE FROM rota_entrega WHERE id_rota = ?")->execute([$idRota]);
        $this->pdo->prepare("DELETE FROM rota WHERE id_rota = ?")->execute([$idRota]);
    }

    /**
     * Atualiza a distância calculada pelo mapa para uma rota específica.
     *
     * @param float $distancia Distância em km calculada pelo frontend (Leaflet)
     * @param int   $idRota    ID da rota a atualizar
     */
    public function atualizarDistancia(float $distancia, int $idRota): void {
        $this->pdo->prepare("UPDATE rota SET distancia = ? WHERE id_rota = ?")
            ->execute([$distancia, $idRota]);
    }

    /**
     * Registra um desvio de rota: atualiza a distância na rota e insere
     * um alerta do tipo DESVIO_ROTA vinculado à viagem em andamento.
     * Executado em transação para garantir atomicidade.
     *
     * @param int    $idRota        ID da rota a atualizar
     * @param int    $idViagem      ID da viagem em andamento (FK do alerta)
     * @param string $novaOrigem    Nome/localidade da nova posição de origem
     * @param float  $novaDistancia Nova distância calculada em km
     */
    public function registrarDesvioRota(int $idRota, int $idViagem, string $novaOrigem, float $novaDistancia): void {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE rota SET distancia = ? WHERE id_rota = ?")
                ->execute([$novaDistancia, $idRota]);

            $descricao = "Desvio de rota registrado: nova posição em '{$novaOrigem}'. "
                       . "Nova distância calculada: " . number_format($novaDistancia, 1, '.', '') . " km.";
            $this->pdo->prepare(
                "INSERT INTO alerta (id_viagem, tipo_alerta, descricao) VALUES (?, 'DESVIO_ROTA', ?)"
            )->execute([$idViagem, $descricao]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Registra uma parada não programada da viagem na tabela alerta.
     *
     * @param int    $idViagem ID da viagem em andamento
     * @param string $local    Localização/descrição da parada
     * @param string $motivo   Motivo da parada (Abastecimento, Problema Mecânico, etc.)
     */
    public function registrarParada(int $idViagem, string $local, string $motivo): void {
        $descricao = "Parada em '{$local}' — Motivo: {$motivo}.";
        $this->pdo->prepare(
            "INSERT INTO alerta (id_viagem, tipo_alerta, descricao) VALUES (?, 'PARADA_NAO_PROGRAMADA', ?)"
        )->execute([$idViagem, $descricao]);
    }

    // =========================================================================
    // OPERAÇÕES DE VÍNCULO DE ENTREGAS
    // =========================================================================

    /**
     * Vincula uma entrega a uma rota existente.
     * Lança exceção se a entrega já estiver vinculada ou não disponível.
     *
     * @param int $idRota    ID da rota destino
     * @param int $idEntrega ID da entrega a vincular
     * @throws Exception Se a entrega já estiver vinculada
     */
    public function adicionarEntrega(int $idRota, int $idEntrega): void {
        $this->pdo->prepare("INSERT INTO rota_entrega (id_rota, id_entrega) VALUES (?, ?)")
            ->execute([$idRota, $idEntrega]);
    }

    /**
     * Remove o vínculo entre uma entrega e uma rota sem excluir a entrega.
     * A entrega volta a ficar disponível para ser adicionada a outra rota.
     *
     * @param int $idRota    ID da rota
     * @param int $idEntrega ID da entrega a desvincular
     */
    public function removerEntrega(int $idRota, int $idEntrega): void {
        $this->pdo->prepare("DELETE FROM rota_entrega WHERE id_rota = ? AND id_entrega = ?")
            ->execute([$idRota, $idEntrega]);
    }

    // =========================================================================
    // OPERAÇÕES DE VIAGEM
    // =========================================================================

    /**
     * Inicia uma viagem para uma rota planejada.
     * Cria o registro de viagem e dispara as transições de status em cascata:
     *   - Rota:     PLANEJADA  → EM_ANDAMENTO
     *   - Entregas: PENDENTE   → EM_TRANSITO  (apenas as PENDENTES desta rota)
     *   - Estoque:  desconto imediato ao sair do armazém (SAIDA registrada)
     * Encapsulado em transação para garantir consistência entre todas as operações.
     *
     * @param int    $idRota              ID da rota a iniciar
     * @param string $dataSaida           Data/hora de saída (formato datetime)
     * @param string $dataChegadaPrevista Previsão de chegada (formato datetime)
     * @param int    $idUsuario           ID do usuário logado (para registrar movimentação)
     * @throws Exception Se ocorrer erro na transação
     */
    public function iniciarViagem(int $idRota, string $dataSaida, string $dataChegadaPrevista, int $idUsuario): void {
        $this->pdo->beginTransaction();

        try {
            // Cria o registro de viagem com as datas informadas
            $this->pdo->prepare("INSERT INTO viagem (id_rota, data_saida, data_chegada_prevista) VALUES (?, ?, ?)")
                ->execute([$idRota, $dataSaida, $dataChegadaPrevista]);

            // Transiciona a rota para EM_ANDAMENTO
            $this->pdo->prepare("UPDATE rota SET status = 'EM_ANDAMENTO' WHERE id_rota = ?")
                ->execute([$idRota]);

            // Busca IDs das entregas PENDENTES antes de transicioná-las (para desconto de estoque)
            $stmtIds = $this->pdo->prepare("
                SELECT id_entrega FROM entrega
                WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                  AND status = 'PENDENTE'
            ");
            $stmtIds->execute([$idRota]);
            $idsEntregas = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

            // Transiciona as entregas PENDENTES para EM_TRANSITO
            $this->pdo->prepare("
                UPDATE entrega SET status = 'EM_TRANSITO'
                WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                  AND status = 'PENDENTE'
            ")->execute([$idRota]);

            // Desconta o estoque imediatamente — o produto saiu do armazém com o caminhão
            if (!empty($idsEntregas)) {
                $this->_descontarEstoque($idsEntregas, $idUsuario);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Conclui uma viagem e aplica os efeitos de status em cascata:
     *   1. A viagem recebe status CONCLUIDA e a data/hora real de chegada (NOW())
     *   2. A rota associada é marcada como FINALIZADA
     *   3. Entregas EM_TRANSITO da rota são marcadas como ENTREGUE com data de hoje
     *
     * O estoque NÃO é descontado aqui — o desconto ocorre ao iniciar a viagem,
     * refletindo que os produtos saem do armazém no momento da partida.
     * Encapsulado em transação para garantir atomicidade.
     *
     * @param int $idViagem  ID da viagem a concluir
     * @param int $idRota    ID da rota vinculada à viagem
     * @param int $idUsuario ID do usuário logado (mantido para compatibilidade)
     * @throws Exception Se ocorrer erro na transação
     */
    public function concluirViagem(int $idViagem, int $idRota, int $idUsuario): void {
        $this->pdo->beginTransaction();

        try {
            // 1. Marca a viagem como CONCLUIDA registrando o momento real de chegada
            $this->pdo->prepare("UPDATE viagem SET status = 'CONCLUIDA', data_chegada_real = NOW() WHERE id_viagem = ?")
                ->execute([$idViagem]);

            // 2. Finaliza a rota associada à viagem concluída
            $this->pdo->prepare("UPDATE rota SET status = 'FINALIZADA' WHERE id_rota = ?")
                ->execute([$idRota]);

            // 3. Marca as entregas EM_TRANSITO como ENTREGUE com a data de realização de hoje
            $this->pdo->prepare("
                UPDATE entrega SET status = 'ENTREGUE', data_realizada = CURDATE()
                WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                  AND status = 'EM_TRANSITO'
            ")->execute([$idRota]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cancela uma viagem e reverte todos os efeitos em cascata:
     *   1. A viagem é marcada como CANCELADA e data_chegada_real é limpa (NULL)
     *   2. A rota volta para PLANEJADA (pode ser reiniciada no futuro)
     *   3. Entregas EM_TRANSITO desta rota voltam para PENDENTE
     *   4. O estoque descontado ao iniciar é devolvido (ENTRADA registrada)
     *
     * Encapsulado em transação para garantir atomicidade.
     *
     * @param int $idViagem  ID da viagem a cancelar
     * @param int $idRota    ID da rota vinculada à viagem
     * @param int $idUsuario ID do usuário logado (para registrar movimentação de estorno)
     * @throws Exception Se ocorrer erro na transação
     */
    public function cancelarViagem(int $idViagem, int $idRota, int $idUsuario): void {
        $this->pdo->beginTransaction();

        try {
            // Busca IDs das entregas EM_TRANSITO antes de revertê-las (para estorno de estoque)
            $stmtIds = $this->pdo->prepare("
                SELECT id_entrega FROM entrega
                WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                  AND status = 'EM_TRANSITO'
            ");
            $stmtIds->execute([$idRota]);
            $idsEntregas = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

            // Cancela a viagem e limpa a data de chegada real (ainda não ocorreu)
            $this->pdo->prepare("UPDATE viagem SET status = 'CANCELADA', data_chegada_real = NULL WHERE id_viagem = ?")
                ->execute([$idViagem]);

            // Reverte a rota para PLANEJADA para que possa ser reiniciada futuramente
            $this->pdo->prepare("UPDATE rota SET status = 'PLANEJADA' WHERE id_rota = ?")
                ->execute([$idRota]);

            // Reverte as entregas EM_TRANSITO para PENDENTE
            $this->pdo->prepare("
                UPDATE entrega SET status = 'PENDENTE'
                WHERE id_entrega IN (SELECT id_entrega FROM rota_entrega WHERE id_rota = ?)
                  AND status = 'EM_TRANSITO'
            ")->execute([$idRota]);

            // Estorna o estoque descontado ao iniciar a viagem
            if (!empty($idsEntregas)) {
                $this->_estornarEstoque($idsEntregas, $idUsuario);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza apenas o status de uma viagem (para transições intermediárias).
     * Utilizado para os status INICIADA e EM_TRANSITO, sem alterar rota, entregas ou estoque.
     *
     * @param int    $idViagem  ID da viagem
     * @param string $novoStatus Novo status da viagem (INICIADA ou EM_TRANSITO)
     */
    public function atualizarStatusViagem(int $idViagem, string $novoStatus): void {
        $this->pdo->prepare("UPDATE viagem SET status = ? WHERE id_viagem = ?")
            ->execute([$novoStatus, $idViagem]);
    }

    // =========================================================================
    // MÉTODOS PRIVADOS / INTERNOS
    // =========================================================================

    /**
     * Desconta o estoque e registra movimentações de SAIDA para uma lista de entregas.
     *
     * Lógica de armazém (algoritmo crítico — não alterar sem revisão):
     *   - Se a entrega possui id_armazem definido:
     *       Desconta diretamente naquele armazém via GREATEST(0, quantidade - ?),
     *       garantindo que o saldo nunca fique negativo.
     *       Registra 1 movimentação de SAIDA com o id_armazem.
     *   - Se a entrega NÃO possui id_armazem (entrega sem origem definida):
     *       Percorre os armazéns com estoque > 0 em ordem decrescente de quantidade,
     *       descontando proporcionalmente até zerar o total necessário (desconto distribuído).
     *       Trata corretamente armazéns com id_armazem IS NULL (registro genérico).
     *       Registra 1 movimentação de SAIDA sem id_armazem específico.
     *
     * IMPORTANTE: Este método deve ser chamado dentro de uma transação ativa.
     *
     * @param array $idsEntregas Lista de IDs de entregas a processar
     * @param int   $idUsuario   ID do usuário logado para registrar a movimentação
     */
    /**
     * Estorna o estoque das entregas ao cancelar uma viagem.
     * Operação inversa de _descontarEstoque: devolve as quantidades ao armazém de origem.
     *
     * - Com armazém definido: soma diretamente no armazém da entrega.
     * - Sem armazém definido: devolve ao armazém com maior estoque desse produto.
     *
     * IMPORTANTE: deve ser chamado dentro de uma transação ativa.
     *
     * @param array $idsEntregas Lista de IDs de entregas a estornar
     * @param int   $idUsuario   ID do usuário logado para registrar a movimentação
     */
    private function _estornarEstoque(array $idsEntregas, int $idUsuario): void {
        $stmtProd = $this->pdo->prepare("SELECT id_produto, quantidade FROM entrega_produto WHERE id_entrega = ?");
        $stmtArm  = $this->pdo->prepare("SELECT id_armazem FROM entrega WHERE id_entrega = ?");

        foreach ($idsEntregas as $idEnt) {
            $stmtArm->execute([$idEnt]);
            $idArmazemEnt = $stmtArm->fetchColumn() ?: null;

            $stmtProd->execute([$idEnt]);

            foreach ($stmtProd->fetchAll(PDO::FETCH_ASSOC) as $p) {
                if ($idArmazemEnt) {
                    // Devolve ao armazém de origem da entrega
                    $this->pdo->prepare("
                        UPDATE estoque SET quantidade = quantidade + ?
                        WHERE id_produto = ? AND id_armazem = ?
                    ")->execute([$p['quantidade'], $p['id_produto'], $idArmazemEnt]);

                    $this->pdo->prepare("
                        INSERT INTO movimentacao_estoque
                            (id_produto, id_usuario, id_armazem, tipo_movimentacao, quantidade)
                        VALUES (?, ?, ?, 'ENTRADA', ?)
                    ")->execute([$p['id_produto'], $idUsuario, $idArmazemEnt, $p['quantidade']]);

                } else {
                    // Sem armazém definido: devolve ao armazém com maior estoque desse produto
                    $stmtAlvo = $this->pdo->prepare("
                        SELECT id_armazem FROM estoque
                        WHERE id_produto = ?
                        ORDER BY quantidade DESC LIMIT 1
                    ");
                    $stmtAlvo->execute([$p['id_produto']]);
                    $idArmazemAlvo = $stmtAlvo->fetchColumn();

                    if ($idArmazemAlvo !== false) {
                        $this->pdo->prepare("
                            UPDATE estoque SET quantidade = quantidade + ?
                            WHERE id_produto = ? AND id_armazem = ?
                        ")->execute([$p['quantidade'], $p['id_produto'], $idArmazemAlvo]);
                    }

                    $this->pdo->prepare("
                        INSERT INTO movimentacao_estoque
                            (id_produto, id_usuario, tipo_movimentacao, quantidade)
                        VALUES (?, ?, 'ENTRADA', ?)
                    ")->execute([$p['id_produto'], $idUsuario, $p['quantidade']]);
                }
            }
        }
    }

    private function _descontarEstoque(array $idsEntregas, int $idUsuario): void {
        // Prepared statements reutilizáveis para otimizar as queries dentro dos loops
        $stmtProd = $this->pdo->prepare("SELECT id_produto, quantidade FROM entrega_produto WHERE id_entrega = ?");
        $stmtArm  = $this->pdo->prepare("SELECT id_armazem FROM entrega WHERE id_entrega = ?");

        foreach ($idsEntregas as $idEnt) {
            // Obtém o armazém de origem desta entrega específica
            $stmtArm->execute([$idEnt]);
            $idArmazemEnt = $stmtArm->fetchColumn() ?: null;

            // Obtém todos os produtos e suas quantidades desta entrega
            $stmtProd->execute([$idEnt]);

            foreach ($stmtProd->fetchAll(PDO::FETCH_ASSOC) as $p) {
                if ($idArmazemEnt) {
                    /* -------------------------------------------------------
                     * Armazém definido: desconta diretamente no armazém
                     * da entrega. GREATEST(0, quantidade - ?) garante que
                     * o saldo nunca fique negativo no banco de dados.
                     * ------------------------------------------------------- */
                    $this->pdo->prepare("
                        UPDATE estoque
                        SET quantidade = GREATEST(0, quantidade - ?)
                        WHERE id_produto = ? AND id_armazem = ?
                    ")->execute([$p['quantidade'], $p['id_produto'], $idArmazemEnt]);

                    // Registra a movimentação de SAIDA com armazém definido
                    $this->pdo->prepare("
                        INSERT INTO movimentacao_estoque
                            (id_produto, id_usuario, id_armazem, tipo_movimentacao, quantidade)
                        VALUES (?, ?, ?, 'SAIDA', ?)
                    ")->execute([$p['id_produto'], $idUsuario, $idArmazemEnt, $p['quantidade']]);

                } else {
                    /* -------------------------------------------------------
                     * Sem armazém definido: distribui o desconto entre os
                     * armazéns com estoque disponível, priorizando os com
                     * maior quantidade (ordem DESC). Desconta proporcionalmente
                     * até atingir o total necessário (algoritmo distribuído).
                     * ------------------------------------------------------- */
                    $wRows = $this->pdo->prepare("
                        SELECT id_armazem, quantidade
                        FROM estoque
                        WHERE id_produto = ? AND quantidade > 0
                        ORDER BY quantidade DESC
                    ");
                    $wRows->execute([$p['id_produto']]);

                    // Quantidade restante a descontar do estoque total
                    $remaining = (int) $p['quantidade'];

                    foreach ($wRows->fetchAll(PDO::FETCH_ASSOC) as $w) {
                        // Para quando o desconto total já foi realizado
                        if ($remaining <= 0) break;

                        // Desconta o menor valor entre o que falta e o disponível no armazém
                        $deduct = min($remaining, (int) $w['quantidade']);

                        if ($w['id_armazem'] !== null) {
                            // Armazém com ID definido: atualiza pela chave primária composta
                            $this->pdo->prepare("
                                UPDATE estoque
                                SET quantidade = quantidade - ?
                                WHERE id_produto = ? AND id_armazem = ?
                            ")->execute([$deduct, $p['id_produto'], $w['id_armazem']]);
                        } else {
                            // Armazém sem ID (registro genérico): usa IS NULL na condição
                            $this->pdo->prepare("
                                UPDATE estoque
                                SET quantidade = GREATEST(0, quantidade - ?)
                                WHERE id_produto = ? AND id_armazem IS NULL
                            ")->execute([$deduct, $p['id_produto']]);
                        }

                        $remaining -= $deduct;
                    }

                    // Registra a movimentação de SAIDA total sem armazém específico
                    $this->pdo->prepare("
                        INSERT INTO movimentacao_estoque
                            (id_produto, id_usuario, tipo_movimentacao, quantidade)
                        VALUES (?, ?, 'SAIDA', ?)
                    ")->execute([$p['id_produto'], $idUsuario, $p['quantidade']]);
                }
            }
        }
    }
}
