<?php
/*
 * Arquivo: dao/DashboardDao.php
 * Finalidade: Data Access Object (DAO) para o Dashboard principal (index).
 * Centraliza todas as queries SQL responsáveis pelos indicadores resumidos
 * exibidos na página inicial após o login:
 *  - Contagens de entregas por status (pendente, em trânsito, atrasada)
 *  - Viagens ativas no momento
 *  - Alertas gerados nos últimos 7 dias
 *  - Veículos disponíveis para operação
 *  - Total de frete faturado no mês atual
 *  - Quantidade de motoristas ativos
 * Recebe a conexão PDO via construtor — sem instanciar conexão própria.
 */

class DashboardDao
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
     * INDICADORES DE ENTREGAS
     * ===================================================================== */

    /**
     * Conta as entregas com status 'PENDENTE' (aguardando saída).
     *
     * @return int Total de entregas pendentes
     */
    public function entregasPendentes(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM entrega WHERE status = 'PENDENTE'"
        )->fetchColumn();
    }

    /**
     * Conta as entregas com status 'EM_TRANSITO' (a caminho do destino).
     *
     * @return int Total de entregas em trânsito
     */
    public function entregasEmTransito(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM entrega WHERE status = 'EM_TRANSITO'"
        )->fetchColumn();
    }

    /**
     * Conta as entregas com status 'ATRASADA' (passaram da data prevista).
     *
     * @return int Total de entregas atrasadas
     */
    public function entregasAtrasadas(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM entrega WHERE status = 'ATRASADA'"
        )->fetchColumn();
    }

    /* =====================================================================
     * INDICADORES OPERACIONAIS
     * ===================================================================== */

    /**
     * Conta as viagens ativas (INICIADA ou EM_TRANSITO) no momento.
     *
     * @return int Total de viagens ativas
     */
    public function viagensAtivas(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM viagem WHERE status IN ('INICIADA', 'EM_TRANSITO')"
        )->fetchColumn();
    }

    /**
     * Conta alertas registrados nos últimos 7 dias.
     * Exibe apenas alertas recentes para evitar ruído de dados desatualizados.
     *
     * @return int Total de alertas recentes
     */
    public function alertasAtivos(): int
    {
        return (int)$this->pdo->query("
            SELECT
              (SELECT COUNT(*) FROM entrega
               WHERE data_prevista < CURDATE() AND status NOT IN ('ENTREGUE'))
            + (SELECT COUNT(*) FROM viagem
               WHERE data_chegada_prevista < NOW() AND status NOT IN ('CONCLUIDA','CANCELADA'))
            + (SELECT COUNT(*) FROM alerta WHERE tipo_alerta IN ('DESVIO_ROTA','PARADA_NAO_PROGRAMADA'))
            + (SELECT COUNT(*) FROM estoque WHERE quantidade < 10)
        ")->fetchColumn();
    }

    /**
     * Conta veículos com status 'DISPONIVEL', aptos para nova operação.
     *
     * @return int Total de veículos disponíveis
     */
    public function veiculosDisponiveis(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM veiculo WHERE status = 'DISPONIVEL'"
        )->fetchColumn();
    }

    /* =====================================================================
     * INDICADORES FINANCEIROS
     * ===================================================================== */

    /**
     * Retorna a soma dos valores dos fretes emitidos no mês e ano correntes.
     * COALESCE garante retorno de 0 caso não haja fretes no período.
     *
     * @return float Total faturado em fretes no mês atual
     */
    public function freteMesAtual(): float
    {
        return (float)$this->pdo->query(
            "SELECT COALESCE(SUM(valor), 0)
             FROM frete
             WHERE MONTH(data_emissao) = MONTH(CURDATE())
               AND YEAR(data_emissao)  = YEAR(CURDATE())"
        )->fetchColumn();
    }

    /* =====================================================================
     * INDICADORES DE RECURSOS HUMANOS
     * ===================================================================== */

    /**
     * Conta motoristas com status 'ATIVO', disponíveis para alocação em rotas.
     *
     * @return int Total de motoristas ativos
     */
    public function motoristasAtivos(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM motorista WHERE status = 'ATIVO'"
        )->fetchColumn();
    }
}
