<?php
/*
 * Arquivo: controller/ViagemController.php
 * Finalidade: Controller do módulo de Viagens.
 * Responsável por processar todas as requisições GET/POST relacionadas
 * a viagens, delegar operações ao ViagemDao e preparar as variáveis
 * que serão utilizadas pela view views/viagens.php.
 *
 * Ações tratadas:
 *  - GET ?excluir=<id>              → excluir viagem em cascata
 *  - GET ?status=<STATUS>&id=<id>   → alterar status com cascata em rota/entregas
 *  - POST acao=nova_viagem          → criar nova viagem a partir de uma rota
 *  - POST acao=rastreamento         → registrar coordenada GPS
 *  - (padrão)                       → carregar dados para a listagem
 */

class ViagemController
{
    /** @var ViagemDao DAO responsável pelo acesso aos dados de viagens */
    private ViagemDao $dao;

    /**
     * Construtor: injeta o DAO de viagens.
     *
     * @param ViagemDao $dao Instância do DAO de viagens
     */
    public function __construct(ViagemDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Ponto de entrada principal do controller.
     * Roteia a requisição para o método correto com base nos parâmetros
     * GET/POST presentes e, ao final, carrega a view com os dados.
     *
     * @return void
     */
    public function processar(): void
    {
        $erro = "";

        /* -----------------------------------------------------------------
         * AÇÃO GET: Excluir viagem
         * Remove a viagem e todos os seus dados dependentes (alertas,
         * rastreamentos, fretes) em cascata, depois redireciona.
         * ----------------------------------------------------------------- */
        if (isset($_GET['excluir'])) {
            try {
                $this->dao->excluir((int)$_GET['excluir']);
                header("Location: viagens.php");
                exit;
            } catch (Exception $e) {
                $erro = "Erro ao excluir viagem.";
            }
        }

        /* -----------------------------------------------------------------
         * AÇÃO GET: Alterar status da viagem
         * Válida o status recebido e delega a transição ao DAO, que aplica
         * as atualizações em cascata dentro de uma transação.
         * ----------------------------------------------------------------- */
        if (isset($_GET['status']) && isset($_GET['id'])) {
            // Lista de status válidos para evitar injeção de valores arbitrários
            $validos = ['INICIADA', 'EM_TRANSITO', 'CONCLUIDA', 'CANCELADA'];

            if (in_array($_GET['status'], $validos)) {
                $idViagem   = (int)$_GET['id'];
                $novoStatus = $_GET['status'];

                // Busca o id_rota para repassar ao DAO (necessário para cascata)
                $idRota = $this->dao->buscarIdRota($idViagem);

                if ($idRota !== false) {
                    $this->dao->atualizarStatus($idViagem, (int)$idRota, $novoStatus);
                }
            }

            header("Location: viagens.php");
            exit;
        }

        /* -----------------------------------------------------------------
         * AÇÕES POST: Processamento de formulários
         * ----------------------------------------------------------------- */
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

            /* -------------------------------------------------------------
             * SUB-AÇÃO: Criar nova viagem a partir de uma rota
             * Valida os campos obrigatórios e delega a criação ao DAO,
             * que executa tudo dentro de uma transação.
             * ------------------------------------------------------------- */
            if ($_POST['acao'] === 'nova_viagem') {
                try {
                    $this->dao->inserir(
                        (int)$_POST['id_rota'],
                        $_POST['data_saida'],
                        $_POST['data_chegada_prevista']
                    );
                    header("Location: viagens.php");
                    exit;
                } catch (Exception $e) {
                    $erro = "Erro ao iniciar viagem.";
                }
            }

            /* -------------------------------------------------------------
             * SUB-AÇÃO: Registrar ponto de rastreamento GPS
             * Armazena a coordenada associada à viagem selecionada.
             * ------------------------------------------------------------- */
            if ($_POST['acao'] === 'rastreamento') {
                try {
                    $this->dao->registrarRastreamento(
                        (int)$_POST['id_viagem'],
                        (float)$_POST['latitude'],
                        (float)$_POST['longitude']
                    );
                    header("Location: viagens.php");
                    exit;
                } catch (Exception $e) {
                    $erro = "Erro ao registrar coordenada.";
                }
            }
        }

        /* -----------------------------------------------------------------
         * CARREGAMENTO DE DADOS PARA A VIEW
         * Busca todos os dados necessários para renderizar a página de
         * listagem e os cards de KPI.
         * ----------------------------------------------------------------- */

        // Lista completa de viagens com motorista e veículo para a tabela
        $lista = $this->dao->listarTodas();

        // Viagem mais recente para exibição do mapa de rastreamento
        $ultima_viagem = !empty($lista) ? $lista[0] : null;

        // Último ponto GPS da viagem mais recente (para o mapa Leaflet)
        $ultimo_rastr = null;
        if ($ultima_viagem) {
            $resultado = $this->dao->buscarUltimoRastreamento((int)$ultima_viagem['id_viagem']);
            $ultimo_rastr = $resultado ?: null;
        }

        // Rotas disponíveis para criação de nova viagem (select do formulário)
        $rotas_disponiveis = $this->dao->listarRotasDisponiveis();

        // Contadores de status para os cards de KPI no topo da página
        $em_transito = $this->dao->contarPorStatus(['INICIADA', 'EM_TRANSITO']);
        $concluidas  = $this->dao->contarPorStatus('CONCLUIDA');
        $canceladas  = $this->dao->contarPorStatus('CANCELADA');

        // Carrega a view de viagens com todas as variáveis preparadas acima
        include 'views/viagens.php';
    }
}
