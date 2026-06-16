<?php
/**
 * Controller: OperacaoController
 *
 * Trata todas as requisições GET e POST do módulo de Operações,
 * delegando as queries ao OperacaoDao e preparando as variáveis
 * necessárias para a view em views/operacoes.php.
 *
 * Ações GET tratadas:
 *   ?update_dist   — Atualiza distância calculada pelo mapa
 *   ?excluir_rota  — Exclui rota e vínculos de entregas
 *   ?remover_entrega + ?id_rota — Remove entrega de uma rota
 *   ?status_viagem + ?id_viagem — Altera status da viagem (CONCLUIDA, CANCELADA, EM_TRANSITO, INICIADA)
 *
 * Ações POST tratadas ($_POST['acao']):
 *   nova_rota      — Cria nova rota com veículo, motorista e entregas opcionais
 *   add_entrega    — Adiciona entrega a uma rota planejada existente
 *   iniciar_viagem — Inicia viagem: cria viagem e transiciona status da rota e entregas
 *
 * Variáveis definidas para a view:
 *   $erro               (string) Mensagem de erro, se houver
 *   $transportadoras    (array)  Lista de transportadoras ativas com endereço
 *   $motoristas         (array)  Todos os motoristas ativos com transportadora
 *   $veiculos           (array)  Todos os veículos disponíveis
 *   $entregas_pendentes (array)  Entregas PENDENTE sem rota vinculada
 *   $lista              (array)  Lista de operações (rotas) com dados completos
 *   $entregas_por_rota  (array)  Array indexado por id_rota com as entregas vinculadas
 *   $planejadas         (int)    Quantidade de rotas PLANEJADA
 *   $em_andamento       (int)    Quantidade de rotas EM_ANDAMENTO
 *   $finalizadas        (int)    Quantidade de rotas FINALIZADA
 */
class OperacaoController {

    public function __construct(private OperacaoDao $dao) {}

    /**
     * Ponto de entrada do controller.
     * Processa GET e POST, depois carrega os dados para a view.
     */
    public function processar(): void {
        $this->_processarGet();
        $this->_processarPost();
        $this->_carregarView();
    }

    // =========================================================================
    // PROCESSAMENTO DE REQUISIÇÕES GET
    // =========================================================================

    /**
     * Trata todas as ações GET da página de operações.
     * Cada ação realiza sua operação e redireciona ao final (PRG pattern).
     */
    private function _processarGet(): void {

        /* -----------------------------------------------------------------
         * Atualizar distância calculada pelo mapa
         * Parâmetros: ?update_dist=1&dist=<distancia>&id_rota=<id>
         * ----------------------------------------------------------------- */
        if (isset($_GET['update_dist'])) {
            $this->dao->atualizarDistancia(
                floatval($_GET['dist']),
                (int) $_GET['id_rota']
            );
            header("Location: operacoes.php"); exit;
        }

        /* -----------------------------------------------------------------
         * Excluir rota
         * Parâmetros: ?excluir_rota=<id_rota>
         * Só é permitido excluir rotas PLANEJADAS sem viagem associada;
         * se houver viagem vinculada, o banco lança exceção (FK).
         * ----------------------------------------------------------------- */
        if (isset($_GET['excluir_rota'])) {
            try {
                $this->dao->excluirRota((int) $_GET['excluir_rota']);
                salvarMensagem('success', 'Operação removida com sucesso!');
                header("Location: operacoes.php"); exit;
            } catch (Exception) {
                salvarMensagem('danger', 'Não é possível excluir: existe viagem vinculada a esta operação.');
                header("Location: operacoes.php"); exit;
            }
        }

        /* -----------------------------------------------------------------
         * Remover entrega de uma rota
         * Parâmetros: ?remover_entrega=<id_entrega>&id_rota=<id_rota>
         * ----------------------------------------------------------------- */
        if (isset($_GET['remover_entrega'], $_GET['id_rota'])) {
            $this->dao->removerEntrega(
                (int) $_GET['id_rota'],
                (int) $_GET['remover_entrega']
            );
            header("Location: operacoes.php"); exit;
        }

        /* -----------------------------------------------------------------
         * Alterar status da viagem
         * Parâmetros: ?status_viagem=<STATUS>&id_viagem=<id>
         * Casos: CONCLUIDA, CANCELADA, INICIADA, EM_TRANSITO
         * ----------------------------------------------------------------- */
        if (isset($_GET['status_viagem'], $_GET['id_viagem'])) {
            // Lista de status válidos para evitar injeção de valores arbitrários
            $validos = ['INICIADA', 'EM_TRANSITO', 'CONCLUIDA', 'CANCELADA'];

            if (in_array($_GET['status_viagem'], $validos)) {
                $idViagem   = (int) $_GET['id_viagem'];
                $novoStatus = $_GET['status_viagem'];

                try {
                    if ($novoStatus === 'CONCLUIDA') {
                        // Busca o id_rota para as operações em cascata
                        $idRota    = $this->dao->buscarIdRotaPorViagem($idViagem);
                        $idUsuario = (int) $_SESSION['usuario']['id'];
                        $this->dao->concluirViagem($idViagem, $idRota, $idUsuario);

                    } elseif ($novoStatus === 'CANCELADA') {
                        // Busca o id_rota para as operações em cascata
                        $idRota    = $this->dao->buscarIdRotaPorViagem($idViagem);
                        $idUsuario = (int) $_SESSION['usuario']['id'];
                        $this->dao->cancelarViagem($idViagem, $idRota, $idUsuario);

                    } else {
                        // INICIADA ou EM_TRANSITO: apenas atualiza o status da viagem
                        $this->dao->atualizarStatusViagem($idViagem, $novoStatus);
                    }
                } catch (Exception) {
                    salvarMensagem('danger', 'Erro ao atualizar status da viagem.');
                }
            }

            header("Location: operacoes.php"); exit;
        }
    }

    // =========================================================================
    // PROCESSAMENTO DE REQUISIÇÕES POST
    // =========================================================================

    /**
     * Trata todas as ações POST do módulo de operações.
     * Cada sub-ação é identificada por $_POST['acao'].
     */
    private function _processarPost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['acao'])) {
            return;
        }

        /* -----------------------------------------------------------------
         * Criar nova rota com veículo, motorista e entregas opcionais.
         * Valida o peso total das entregas contra a capacidade do veículo
         * antes de persistir — rejeita e exibe mensagem se exceder.
         * ----------------------------------------------------------------- */
        if ($_POST['acao'] === 'nova_rota') {
            try {
                $idVeiculo  = (int) $_POST['id_veiculo'];
                $idEntregas = (!empty($_POST['id_entregas']) && is_array($_POST['id_entregas']))
                    ? $_POST['id_entregas']
                    : [];

                // Valida peso total das entregas selecionadas contra capacidade_carga do veículo
                if (!empty($idEntregas)) {
                    $capacidade = $this->dao->buscarCapacidadeVeiculo($idVeiculo);
                    if ($capacidade !== null && $capacidade > 0) {
                        $pesoTotal = $this->dao->somarPesoEntregas($idEntregas);
                        if ($pesoTotal > $capacidade) {
                            salvarMensagem('danger', sprintf(
                                'Peso total das entregas (%s kg) ultrapassa a capacidade do veículo (%s kg). Remova entregas ou escolha outro veículo.',
                                number_format($pesoTotal, 0, ',', '.'),
                                number_format($capacidade, 0, ',', '.')
                            ));
                            header("Location: operacoes.php"); exit;
                        }
                    }
                }

                $this->dao->criarRota(
                    $idVeiculo,
                    (int) $_POST['id_motorista'],
                    isset($_POST['distancia']) && $_POST['distancia'] !== ''
                        ? floatval($_POST['distancia'])
                        : null,
                    $idEntregas
                );

                salvarMensagem('success', 'Operação criada com sucesso!');
                header("Location: operacoes.php"); exit;
            } catch (Exception) {
                salvarMensagem('danger', 'Erro ao criar operação.');
                header("Location: operacoes.php"); exit;
            }
        }

        /* -----------------------------------------------------------------
         * Adicionar entrega a uma rota existente
         * ----------------------------------------------------------------- */
        if ($_POST['acao'] === 'add_entrega') {
            try {
                $this->dao->adicionarEntrega(
                    (int) $_POST['id_rota'],
                    (int) $_POST['id_entrega']
                );
                salvarMensagem('success', 'Entrega adicionada à operação com sucesso!');
                header("Location: operacoes.php"); exit;
            } catch (Exception) {
                salvarMensagem('danger', 'Entrega já vinculada ou não disponível.');
                header("Location: operacoes.php"); exit;
            }
        }

        /* -----------------------------------------------------------------
         * Registrar desvio de rota (simulação de nova origem em viagem ativa)
         * Atualiza distância na rota e cria alerta DESVIO_ROTA.
         * ----------------------------------------------------------------- */
        if ($_POST['acao'] === 'simular_desvio') {
            try {
                $this->dao->registrarDesvioRota(
                    (int)    $_POST['id_rota'],
                    (int)    $_POST['id_viagem'],
                    (string) $_POST['nova_origem_nome'],
                    floatval($_POST['nova_distancia'])
                );
                salvarMensagem('success', 'Desvio de rota registrado com sucesso!');
                header("Location: operacoes.php"); exit;
            } catch (Exception) {
                salvarMensagem('danger', 'Erro ao registrar desvio de rota.');
                header("Location: operacoes.php"); exit;
            }
        }

        /* -----------------------------------------------------------------
         * Registrar parada não programada durante viagem em andamento
         * ----------------------------------------------------------------- */
        if ($_POST['acao'] === 'registrar_parada') {
            try {
                $this->dao->registrarParada(
                    (int)    $_POST['id_viagem'],
                    (string) $_POST['local'],
                    (string) $_POST['motivo']
                );
                salvarMensagem('success', 'Parada registrada com sucesso!');
                header("Location: operacoes.php"); exit;
            } catch (Exception) {
                salvarMensagem('danger', 'Erro ao registrar parada.');
                header("Location: operacoes.php"); exit;
            }
        }

        /* -----------------------------------------------------------------
         * Iniciar viagem: cria viagem e transiciona status da rota e entregas
         * ----------------------------------------------------------------- */
        if ($_POST['acao'] === 'iniciar_viagem') {
            try {
                $this->dao->iniciarViagem(
                    (int)    $_POST['id_rota'],
                    (string) $_POST['data_saida'],
                    (string) $_POST['data_chegada_prevista'],
                    (int)    $_SESSION['usuario']['id']
                );
                salvarMensagem('success', 'Viagem iniciada com sucesso!');
                header("Location: operacoes.php"); exit;
            } catch (Exception) {
                salvarMensagem('danger', 'Erro ao iniciar viagem.');
                header("Location: operacoes.php"); exit;
            }
        }
    }

    // =========================================================================
    // CARREGAMENTO DE DADOS E INCLUSÃO DA VIEW
    // =========================================================================

    /**
     * Carrega todos os dados necessários para a view e inclui o arquivo de template.
     * As variáveis são extraídas no escopo do include para ficarem disponíveis na view.
     */
    private function _carregarView(): void {
        // Flash messages são exibidas pelo exibirNavegacao(); $erro mantido vazio para compatibilidade com a view
        $erro = '';

        // Dados para os selects e painel lateral de entregas pendentes
        $transportadoras    = $this->dao->listarTransportadoras();
        $motoristas         = $this->dao->listarMotoristas();
        $veiculos           = $this->dao->listarVeiculos();
        $entregas_pendentes = $this->dao->listarEntregasPendentes();

        // Lista principal de operações/rotas
        $lista = $this->dao->listarOperacoes();

        // Agrupa as entregas por id_rota para a sub-tabela colapsável de cada operação
        $entregas_por_rota = [];
        foreach ($this->dao->listarEntregasPorRota() as $row) {
            $entregas_por_rota[$row['id_rota']][] = $row;
        }

        // Contadores de KPI para os cards do topo da página
        $contadores   = $this->dao->contarPorStatus();
        $planejadas   = $contadores['planejadas'];
        $em_andamento = $contadores['em_andamento'];
        $finalizadas  = $contadores['finalizadas'];

        // Inclui a view; todas as variáveis definidas acima ficam disponíveis no template
        include 'views/operacoes.php';
    }
}
