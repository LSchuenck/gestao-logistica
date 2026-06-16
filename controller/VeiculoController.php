<?php
/**
 * Controller: VeiculoController
 * Trata as requisições GET/POST da página de gerenciamento de veículos.
 * Delega toda persistência ao VeiculoDao.
 * Define as variáveis necessárias para a view e faz o include da mesma.
 */
class VeiculoController {

    /** Status válidos para um veículo — usados como whitelist na atualização */
    private const STATUSES_VALIDOS = ['DISPONIVEL', 'EM_VIAGEM', 'MANUTENCAO'];

    public function __construct(private VeiculoDao $dao) {}

    /**
     * Ponto de entrada único — processa a requisição e inclui a view.
     */
    public function processar(): void {
        $erro = "";

        // --- EXCLUSÃO DE VEÍCULO ---
        // Acionada via GET ?excluir=ID
        if (isset($_GET['excluir'])) {
            try {
                $this->dao->excluir((int) $_GET['excluir']);
                salvarMensagem('success', 'Veículo removido com sucesso!');
                header('Location: veiculos.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Não é possível excluir: este veículo possui rotas ou viagens vinculadas.');
                header('Location: veiculos.php');
                exit;
            }
        }

        // --- ALTERAÇÃO DE STATUS DO VEÍCULO ---
        // Acionada via GET ?status=NOVO_STATUS&id=ID
        if (isset($_GET['status'])) {
            $novo = $_GET['status'];

            // Valida o status via whitelist para evitar valores inválidos no banco
            if (in_array($novo, self::STATUSES_VALIDOS, true)) {
                $this->dao->atualizarStatus((int) $_GET['id'], $novo);
            }

            header('Location: veiculos.php');
            exit;
        }

        // --- CADASTRO DE NOVO VEÍCULO ---
        // Acionada via POST ao enviar o formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dados = [
                    'id_transportadora' => $_POST['id_transportadora'],
                    'placa'             => $_POST['placa'],
                    'modelo'            => $_POST['modelo'],
                    'tipo_veiculo'      => $_POST['tipo_veiculo'],
                    'capacidade_carga'  => $_POST['capacidade_carga'],
                ];
                $edicao = ($_POST['acao'] ?? '') === 'editar';
                if ($edicao) {
                    $this->dao->atualizar((int) $_POST['id_veiculo'], $dados);
                } else {
                    $this->dao->inserir($dados);
                }
                salvarMensagem('success', $edicao ? 'Veículo atualizado com sucesso!' : 'Veículo cadastrado com sucesso!');
                header('Location: veiculos.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao salvar: placa já registrada ou dados inválidos.');
                header('Location: veiculos.php');
                exit;
            }
        }

        // --- CONSULTAS PARA A VIEW ---

        // Transportadoras ativas para popular o select do formulário de cadastro
        $transp = $this->dao->listarTransportadorasAtivas();

        // Lista completa de veículos com nome fantasia da transportadora
        $lista  = $this->dao->listar();
        $total  = count($lista);

        // Calcula métricas de frota percorrendo a lista uma única vez
        $disponiveis = 0;
        $em_viagem   = 0;
        $manutencao  = 0;
        $cap_total   = 0;

        foreach ($lista as $v) {
            match ($v['status']) {
                'DISPONIVEL' => $disponiveis++,
                'EM_VIAGEM'  => $em_viagem++,
                default      => $manutencao++,
            };
            $cap_total += $v['capacidade_carga'];
        }

        // Renderiza a view — não deve ser alterada
        include 'views/veiculos.php';
    }
}
