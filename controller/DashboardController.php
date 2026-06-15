<?php
/*
 * Arquivo: controller/DashboardController.php
 * Finalidade: Controller do Dashboard principal (index).
 * Responsável por solicitar todos os indicadores ao DashboardDao,
 * verificar flags de redirecionamento e preparar as variáveis
 * que serão utilizadas pela view views/index.php.
 *
 * Este módulo é somente leitura — não há ações POST ou DELETE.
 */

class DashboardController
{
    /** @var DashboardDao DAO responsável pelo acesso aos dados do dashboard */
    private DashboardDao $dao;

    /**
     * Construtor: injeta o DAO do dashboard.
     *
     * @param DashboardDao $dao Instância do DAO do dashboard
     */
    public function __construct(DashboardDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Ponto de entrada principal do controller.
     * Verifica flag de acesso negado, coleta todos os indicadores do DAO
     * e carrega a view com as variáveis preparadas.
     *
     * @return void
     */
    public function processar(): void
    {
        /* -----------------------------------------------------------------
         * Flag de acesso negado
         * Quando o usuário tenta acessar uma página sem permissão, é
         * redirecionado para index.php?acesso=negado. A view usa essa
         * variável para exibir um alerta de aviso ao usuário.
         * ----------------------------------------------------------------- */
        $acesso_negado = isset($_GET['acesso']) && $_GET['acesso'] === 'negado';

        /* -----------------------------------------------------------------
         * INDICADORES DE ENTREGAS
         * Contagens por status para os cards da seção "Entregas".
         * ----------------------------------------------------------------- */
        $entregas_pendentes = $this->dao->entregasPendentes();
        $entregas_transito  = $this->dao->entregasEmTransito();
        $entregas_atrasadas = $this->dao->entregasAtrasadas();

        /* -----------------------------------------------------------------
         * INDICADORES OPERACIONAIS
         * Dados para os cards da seção "Operação".
         * ----------------------------------------------------------------- */
        $viagens_ativas      = $this->dao->viagensAtivas();
        $alertas_recentes    = $this->dao->alertasAtivos();
        $veiculos_disponiveis = $this->dao->veiculosDisponiveis();

        /* -----------------------------------------------------------------
         * INDICADORES FINANCEIROS
         * ----------------------------------------------------------------- */
        $frete_mes = $this->dao->freteMesAtual();

        /* -----------------------------------------------------------------
         * INDICADORES DE RECURSOS HUMANOS
         * Não exibido em card dedicado mas disponível para a view usar.
         * ----------------------------------------------------------------- */
        $motoristas_ativos = $this->dao->motoristasAtivos();

        // Carrega a view do dashboard com todas as variáveis preparadas
        include 'views/index.php';
    }
}
