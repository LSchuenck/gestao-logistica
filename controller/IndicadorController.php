<?php
/*
 * Arquivo: controller/IndicadorController.php
 * Finalidade: Controller do módulo de Indicadores de Desempenho (KPIs).
 * Responsável por solicitar os dados ao IndicadorDao, calcular métricas
 * derivadas (taxas percentuais, margens) e preparar todas as variáveis
 * necessárias para a view views/indicadores.php.
 *
 * Este módulo é somente leitura — não há ações POST ou DELETE.
 */

class IndicadorController
{
    /** @var IndicadorDao DAO responsável pelo acesso aos dados de indicadores */
    private IndicadorDao $dao;

    /**
     * Construtor: injeta o DAO de indicadores.
     *
     * @param IndicadorDao $dao Instância do DAO de indicadores
     */
    public function __construct(IndicadorDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Ponto de entrada principal do controller.
     * Coleta todos os KPIs do DAO, calcula métricas derivadas e carrega a view.
     *
     * @return void
     */
    public function processar(): void
    {
        /* -----------------------------------------------------------------
         * KPI 1: FATURAMENTO E CUSTOS
         * ----------------------------------------------------------------- */
        $total_frete  = $this->dao->totalFrete();
        $total_custo  = $this->dao->totalCusto();
        $total_fretes = $this->dao->totalFretes();

        /* -----------------------------------------------------------------
         * KPI 2: TAXA DE ENTREGAS NO PRAZO
         * Calcula o percentual de entregas realizadas dentro do prazo previsto.
         * Evita divisão por zero quando não há entregas cadastradas.
         * ----------------------------------------------------------------- */
        $total_entregas    = $this->dao->totalEntregas();
        $entregas_no_prazo = $this->dao->entregasNoPrazo();
        $taxa_prazo        = $total_entregas > 0
            ? ($entregas_no_prazo / $total_entregas) * 100
            : 0;
        $entregas_atrasadas = $this->dao->entregasAtrasadas();

        /* -----------------------------------------------------------------
         * KPI 3: VIAGENS
         * Calcula o percentual de viagens concluídas em relação ao total.
         * ----------------------------------------------------------------- */
        $total_viagens   = $this->dao->totalViagens();
        $viag_concluidas = $this->dao->viagensConcluidas();
        $viag_andamento  = $this->dao->viagensEmAndamento();
        $viag_canceladas = $this->dao->viagensCanceladas();
        $taxa_conclusao  = $total_viagens > 0
            ? ($viag_concluidas / $total_viagens) * 100
            : 0;

        /* -----------------------------------------------------------------
         * KPI 4: TOTAL DE ALERTAS (visão histórica da tabela alerta)
         * ----------------------------------------------------------------- */
        $total_alertas = $this->dao->totalAlertas();

        /* -----------------------------------------------------------------
         * KPI 5: RANKING DE EFICIÊNCIA POR TRANSPORTADORA
         * ----------------------------------------------------------------- */
        $ranking = $this->dao->rankingTransportadoras();

        /* -----------------------------------------------------------------
         * KPI 6: DISTÂNCIA TOTAL PERCORRIDA
         * ----------------------------------------------------------------- */
        $km_total = $this->dao->kmTotal();

        /* -----------------------------------------------------------------
         * DADOS PARA GRÁFICOS
         * Prepara arrays formatados para o Chart.js da view:
         *  - labels_grafico: nomes das transportadoras (eixo X)
         *  - dados_receita:  array de receitas por transportadora
         *  - dados_custo:    array de custos por transportadora
         * Usa valores padrão para evitar erros JS quando não há dados.
         * ----------------------------------------------------------------- */
        $labels_grafico = array_column($ranking, 'nome_fantasia') ?: ['Sem dados'];
        $dados_receita  = array_map(fn($r) => (float)$r['receita'], $ranking) ?: [0];
        $dados_custo    = array_map(fn($r) => (float)$r['custo'],   $ranking) ?: [0];

        /* -----------------------------------------------------------------
         * DISTRIBUIÇÃO DE ENTREGAS POR STATUS (para gráfico de rosca)
         * ----------------------------------------------------------------- */
        $dist_entregas = $this->dao->distribuicaoEntregas();

        // Carrega a view de indicadores com todas as variáveis calculadas
        include 'views/indicadores.php';
    }
}
