<?php
/*
 * Arquivo: dao/IndicadorDao.php
 * Finalidade: Data Access Object (DAO) para o módulo de Indicadores.
 * Centraliza todas as queries SQL responsáveis por calcular os KPIs e
 * dados para gráficos do painel de indicadores de desempenho logístico.
 *
 * KPIs calculados:
 *  - KPI 1: Faturamento total, custo operacional e número de fretes
 *  - KPI 2: Taxa de entregas no prazo e quantidade de entregas atrasadas
 *  - KPI 3: Status das viagens e taxa de conclusão
 *  - KPI 4: Total de alertas registrados
 *  - KPI 5: Ranking de eficiência por transportadora
 *  - KPI 6: Distância total percorrida em viagens concluídas
 *  - Gráficos: receita/custo por transportadora e distribuição de entregas
 *
 * Recebe a conexão PDO via construtor — sem instanciar conexão própria.
 */

class IndicadorDao
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
     * KPI 1: FATURAMENTO E CUSTOS
     * ===================================================================== */

    /**
     * Retorna a soma de todos os valores de frete (receita bruta total).
     * COALESCE garante retorno de 0 quando não há registros.
     *
     * @return float Receita bruta total
     */
    public function totalFrete(): float
    {
        return (float)$this->pdo->query(
            "SELECT COALESCE(SUM(valor), 0) FROM frete"
        )->fetchColumn();
    }

    /**
     * Retorna a soma de todos os custos operacionais dos fretes.
     *
     * @return float Custo operacional total
     */
    public function totalCusto(): float
    {
        return (float)$this->pdo->query(
            "SELECT COALESCE(SUM(custo_operacional), 0) FROM frete"
        )->fetchColumn();
    }

    /**
     * Retorna o total de fretes emitidos.
     *
     * @return int Número de fretes
     */
    public function totalFretes(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM frete")->fetchColumn();
    }

    /* =====================================================================
     * KPI 2: TAXA DE ENTREGAS NO PRAZO
     * ===================================================================== */

    /**
     * Retorna o total de entregas cadastradas.
     *
     * @return int Total de entregas
     */
    public function totalEntregas(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM entrega")->fetchColumn();
    }

    /**
     * Retorna a quantidade de entregas realizadas dentro do prazo.
     * Uma entrega é "no prazo" quando status = 'ENTREGUE' e
     * data_realizada é NULL ou anterior/igual à data_prevista.
     *
     * @return int Quantidade de entregas no prazo
     */
    public function entregasNoPrazo(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM entrega
             WHERE status = 'ENTREGUE'
               AND (data_realizada IS NULL OR data_realizada <= data_prevista)"
        )->fetchColumn();
    }

    /**
     * Retorna a quantidade de entregas marcadas com status 'ATRASADA'.
     *
     * @return int Quantidade de entregas atrasadas
     */
    public function entregasAtrasadas(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM entrega WHERE status = 'ATRASADA'"
        )->fetchColumn();
    }

    /* =====================================================================
     * KPI 3: VIAGENS
     * ===================================================================== */

    /**
     * Retorna o total de viagens cadastradas.
     *
     * @return int Total de viagens
     */
    public function totalViagens(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM viagem")->fetchColumn();
    }

    /**
     * Retorna a quantidade de viagens com status 'CONCLUIDA'.
     *
     * @return int Viagens concluídas
     */
    public function viagensConcluidas(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM viagem WHERE status = 'CONCLUIDA'"
        )->fetchColumn();
    }

    /**
     * Retorna a quantidade de viagens em andamento (INICIADA ou EM_TRANSITO).
     *
     * @return int Viagens em andamento
     */
    public function viagensEmAndamento(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM viagem WHERE status IN ('INICIADA', 'EM_TRANSITO')"
        )->fetchColumn();
    }

    /**
     * Retorna a quantidade de viagens com status 'CANCELADA'.
     *
     * @return int Viagens canceladas
     */
    public function viagensCanceladas(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM viagem WHERE status = 'CANCELADA'"
        )->fetchColumn();
    }

    /* =====================================================================
     * KPI 4: TOTAL DE ALERTAS
     * ===================================================================== */

    /**
     * Retorna o total de alertas registrados na tabela 'alerta'.
     * Representa uma visão histórica (diferente da página alertas.php
     * que mostra alertas ativos calculados em tempo real).
     *
     * @return int Total de alertas
     */
    public function totalAlertas(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM alerta")->fetchColumn();
    }

    /* =====================================================================
     * KPI 5: RANKING DE EFICIÊNCIA POR TRANSPORTADORA
     * ===================================================================== */

    /**
     * Retorna o ranking de eficiência das transportadoras com fretes ou
     * viagens concluídas. Para cada uma calcula:
     *  - total_fretes: quantidade de fretes emitidos
     *  - receita:      soma dos valores cobrados
     *  - custo:        soma dos custos operacionais
     *  - viag_ok:      viagens concluídas (via subquery correlacionada)
     * Ordenado por receita decrescente.
     *
     * @return array Ranking de transportadoras
     */
    public function rankingTransportadoras(): array
    {
        return $this->pdo->query(
            "SELECT t.nome_fantasia,
                COUNT(f.id_frete) AS total_fretes,
                COALESCE(SUM(f.valor), 0) AS receita,
                COALESCE(SUM(f.custo_operacional), 0) AS custo,
                (SELECT COUNT(*)
                 FROM viagem vi
                 JOIN rota r ON vi.id_rota = r.id_rota
                 JOIN motorista m ON r.id_motorista = m.id_motorista
                 WHERE m.id_transportadora = t.id_transportadora
                   AND vi.status = 'CONCLUIDA') AS viag_ok
             FROM transportadora t
             LEFT JOIN frete f ON f.id_transportadora = t.id_transportadora
             GROUP BY t.id_transportadora, t.nome_fantasia
             HAVING total_fretes > 0 OR viag_ok > 0
             ORDER BY receita DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================================
     * KPI 6: DISTÂNCIA TOTAL PERCORRIDA
     * ===================================================================== */

    /**
     * Retorna a soma das distâncias de rotas usadas em viagens CONCLUIDAS.
     * Representa o total de km rodados pelo sistema de logística.
     *
     * @return float Total de quilômetros percorridos
     */
    public function kmTotal(): float
    {
        return (float)$this->pdo->query(
            "SELECT COALESCE(SUM(r.distancia), 0)
             FROM viagem vi
             JOIN rota r ON vi.id_rota = r.id_rota
             WHERE vi.status = 'CONCLUIDA'"
        )->fetchColumn();
    }

    /* =====================================================================
     * DADOS PARA GRÁFICOS
     * ===================================================================== */

    /**
     * Retorna a distribuição de entregas agrupadas por status como array
     * associativo [status => total], ideal para gráfico de rosca (doughnut).
     * PDO::FETCH_KEY_PAIR retorna diretamente pares [status => count].
     *
     * @return array Distribuição de entregas por status
     */
    public function distribuicaoEntregas(): array
    {
        return $this->pdo->query(
            "SELECT status, COUNT(*) AS total FROM entrega GROUP BY status"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
