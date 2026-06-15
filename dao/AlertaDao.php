<?php
/*
 * Arquivo: dao/AlertaDao.php
 * Finalidade: Data Access Object (DAO) para o módulo de Alertas.
 * Centraliza todas as queries SQL responsáveis por identificar as três
 * categorias de alertas do sistema:
 *  1. ATRASO  — Entregas com data prevista vencida e ainda não entregues
 *  2. VIAGEM  — Viagens em andamento com prazo de chegada ultrapassado
 *  3. ESTOQUE — Produtos com saldo de estoque abaixo do mínimo (< 10 unidades)
 * Recebe a conexão PDO via construtor — sem instanciar conexão própria.
 */

class AlertaDao
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
     * CONSULTAS DE LEITURA POR CATEGORIA DE ALERTA
     * ===================================================================== */

    /**
     * Busca todas as entregas cuja data prevista já passou e cujo status
     * ainda não é 'ENTREGUE'. Calcula os dias de atraso com DATEDIFF.
     * Inclui dados do cliente e cidade para contextualizar o alerta.
     *
     * @return array Lista de entregas atrasadas como arrays associativos
     */
    public function buscarEntregasAtrasadas(): array
    {
        return $this->pdo->query(
            "SELECT e.id_entrega, e.data_prevista, e.status,
                c.nome AS cliente, en.cidade, en.estado,
                DATEDIFF(CURDATE(), e.data_prevista) AS dias_atraso
             FROM entrega e
             JOIN cliente c     ON e.id_cliente  = c.id_cliente
             LEFT JOIN endereco en ON c.id_endereco = en.id_endereco
             WHERE e.data_prevista < CURDATE()
               AND e.status NOT IN ('ENTREGUE')
             ORDER BY dias_atraso DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca viagens ativas (não concluídas e não canceladas) cuja data/hora
     * prevista de chegada já foi ultrapassada.
     * Usa TIMESTAMPDIFF para calcular as horas de atraso com precisão.
     * Inclui motorista e placa para identificação rápida.
     *
     * @return array Lista de viagens atrasadas como arrays associativos
     */
    public function buscarViagensAtrasadas(): array
    {
        return $this->pdo->query(
            "SELECT vi.id_viagem, vi.data_chegada_prevista, vi.status,
                m.nome AS motorista, ve.placa,
                TIMESTAMPDIFF(HOUR, vi.data_chegada_prevista, NOW()) AS horas_atraso
             FROM viagem vi
             JOIN rota r      ON vi.id_rota      = r.id_rota
             JOIN motorista m ON r.id_motorista  = m.id_motorista
             JOIN veiculo ve  ON r.id_veiculo    = ve.id_veiculo
             WHERE vi.data_chegada_prevista < NOW()
               AND vi.status NOT IN ('CONCLUIDA', 'CANCELADA')
             ORDER BY horas_atraso DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca registros persistidos na tabela `alerta` com tipo DESVIO_ROTA,
     * trazendo dados do motorista e veículo para contextualização.
     * Gerados pelo OperacaoDao::registrarDesvioRota() via simulação na tela de Operações.
     *
     * @return array Lista de desvios registrados, do mais recente para o mais antigo
     */
    public function buscarDesviosRota(): array
    {
        return $this->pdo->query(
            "SELECT a.id_alerta, a.id_viagem, a.descricao, a.data_hora,
                    m.nome AS motorista, ve.placa
             FROM alerta a
             JOIN viagem vi   ON a.id_viagem    = vi.id_viagem
             JOIN rota r      ON vi.id_rota      = r.id_rota
             JOIN motorista m ON r.id_motorista  = m.id_motorista
             JOIN veiculo ve  ON r.id_veiculo    = ve.id_veiculo
             WHERE a.tipo_alerta = 'DESVIO_ROTA'
             ORDER BY a.data_hora DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca paradas não programadas persistidas na tabela `alerta`.
     * Geradas pelo OperacaoDao::registrarParada() via modal na tela de Operações.
     *
     * @return array Lista de paradas registradas, da mais recente para a mais antiga
     */
    public function buscarParadasNaoProgramadas(): array
    {
        return $this->pdo->query(
            "SELECT a.id_alerta, a.id_viagem, a.descricao, a.data_hora,
                    m.nome AS motorista, ve.placa
             FROM alerta a
             JOIN viagem vi   ON a.id_viagem    = vi.id_viagem
             JOIN rota r      ON vi.id_rota      = r.id_rota
             JOIN motorista m ON r.id_motorista  = m.id_motorista
             JOIN veiculo ve  ON r.id_veiculo    = ve.id_veiculo
             WHERE a.tipo_alerta = 'PARADA_NAO_PROGRAMADA'
             ORDER BY a.data_hora DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca produtos cujo saldo total em estoque é menor que 10 unidades.
     * Usa GROUP BY + HAVING para agregar múltiplos registros de estoque do
     * mesmo produto (caso distribuído em diferentes armazéns).
     * Ordena do mais crítico (menor quantidade) para o menos crítico.
     *
     * @return array Lista de produtos com estoque crítico
     */
    public function buscarEstoqueCritico(): array
    {
        return $this->pdo->query(
            "SELECT p.descricao, e.quantidade AS qtd, a.nome AS armazem
             FROM estoque e
             JOIN produto p  ON e.id_produto = p.id_produto
             JOIN armazem a  ON e.id_armazem  = a.id_armazem
             WHERE e.quantidade < 10
             ORDER BY e.quantidade ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
