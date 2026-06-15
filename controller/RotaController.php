<?php
/**
 * Controller: RotaController
 * Processa as requisições HTTP do módulo de Rotas.
 *
 * Responsabilidades:
 *  - Tratar ações GET (excluir rota, alterar status)
 *  - Tratar ações POST (criar nova rota, vincular entrega à rota)
 *  - Consultar o DAO para obter os dados necessários
 *  - Definir as variáveis esperadas pela view views/rotas.php
 *  - Incluir a view ao final do processamento
 *
 * Variáveis definidas para a view:
 *  - $erro              (string) Mensagem de erro para exibição, se houver
 *  - $planejadas        (int)    Quantidade de rotas com status PLANEJADA
 *  - $em_andamento      (int)    Quantidade de rotas com status EM_ANDAMENTO
 *  - $finalizadas       (int)    Quantidade de rotas com status FINALIZADA
 *  - $motoristas        (array)  Lista de motoristas ativos para o select
 *  - $veiculos          (array)  Lista de veículos disponíveis para o select
 *  - $entregas          (array)  Entregas pendentes sem rota vinculada
 *  - $lista             (array)  Lista completa de rotas com motorista e veículo
 *  - $entregas_por_rota (array)  Array indexado por id_rota com as entregas de cada rota
 */

require_once __DIR__ . '/../dao/RotaDao.php';

class RotaController {

    /** @var RotaDao Instância do DAO responsável pelas queries de rota */
    private RotaDao $dao;

    /**
     * Construtor: recebe o DAO injetado externamente.
     *
     * @param RotaDao $dao DAO já instanciado com a conexão PDO
     */
    public function __construct(RotaDao $dao) {
        $this->dao = $dao;
    }

    /**
     * Ponto de entrada principal do controller.
     * Delega o processamento conforme o método HTTP e os parâmetros recebidos,
     * carrega os dados para a view e a inclui ao final.
     *
     * @return void
     */
    public function handle(): void {
        // Variável de erro inicializada vazia; será preenchida se alguma operação falhar
        $erro = "";

        /* =================================================================
         * AÇÃO GET: Excluir rota
         * Disparada quando ?excluir=<id_rota> está presente na URL.
         * ================================================================= */
        if (isset($_GET['excluir'])) {
            try {
                $this->dao->excluir((int) $_GET['excluir']);
                header("Location: rotas.php");
                exit;
            } catch (Exception $e) {
                // FK violation: existe viagem vinculada à rota
                $erro = "Não é possível excluir: existe viagem vinculada a esta rota.";
            }
        }

        /* =================================================================
         * AÇÃO GET: Alterar status da rota
         * Disparada quando ?status=<STATUS>&id=<id_rota> estão na URL.
         * Valida o status antes de persistir para evitar valores inválidos.
         * ================================================================= */
        if (isset($_GET['status'], $_GET['id'])) {
            // Valores de status permitidos para uma rota
            $statusValidos = ['PLANEJADA', 'EM_ANDAMENTO', 'FINALIZADA'];

            if (in_array($_GET['status'], $statusValidos)) {
                $this->dao->atualizarStatus($_GET['status'], (int) $_GET['id']);
            }

            header("Location: rotas.php");
            exit;
        }

        /* =================================================================
         * AÇÃO POST: Processamento de formulários
         * ================================================================= */
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

            /* -------------------------------------------------------------
             * SUB-AÇÃO: Criar nova rota
             * Insere rota associando veículo, motorista e distância.
             * ------------------------------------------------------------- */
            if ($_POST['acao'] === 'nova_rota') {
                try {
                    $distancia = isset($_POST['distancia']) && $_POST['distancia'] !== ''
                        ? (float) $_POST['distancia']
                        : null;

                    $this->dao->inserir(
                        (int) $_POST['id_veiculo'],
                        (int) $_POST['id_motorista'],
                        $distancia
                    );

                    header("Location: rotas.php");
                    exit;
                } catch (Exception $e) {
                    $erro = "Erro ao criar rota.";
                }
            }

            /* -------------------------------------------------------------
             * SUB-AÇÃO: Vincular entrega a uma rota existente
             * Cria o vínculo na tabela rota_entrega.
             * A constraint UNIQUE impede duplicatas; a exceção é tratada abaixo.
             * ------------------------------------------------------------- */
            if ($_POST['acao'] === 'add_entrega') {
                try {
                    $this->dao->vincularEntrega(
                        (int) $_POST['id_rota'],
                        (int) $_POST['id_entrega']
                    );

                    header("Location: rotas.php");
                    exit;
                } catch (Exception $e) {
                    // Pode ocorrer se a entrega já estiver vinculada a esta ou outra rota
                    $erro = "Entrega já vinculada ou erro ao adicionar.";
                }
            }
        }

        /* =================================================================
         * CARREGAMENTO DE DADOS PARA A VIEW
         * ================================================================= */

        // Lista completa de rotas com motorista, veículo e contagem de entregas
        $lista = $this->dao->listarTodas();

        // Dados para os selects dos formulários
        $motoristas = $this->dao->listarMotoristas();
        $veiculos   = $this->dao->listarVeiculosDisponiveis();
        $entregas   = $this->dao->listarEntregasPendentes();

        // Monta o array de entregas indexado por id_rota para acesso direto na view
        $entregas_por_rota = [];
        foreach ($this->dao->listarEntregasPorRota() as $row) {
            $entregas_por_rota[$row['id_rota']][] = $row;
        }

        // KPIs de status para os cards do painel
        $planejadas   = $this->dao->contarPlanejadas();
        $em_andamento = $this->dao->contarEmAndamento();
        $finalizadas  = $this->dao->contarFinalizadas();

        // Inclui a view passando todas as variáveis definidas acima
        include __DIR__ . '/../views/rotas.php';
    }
}
