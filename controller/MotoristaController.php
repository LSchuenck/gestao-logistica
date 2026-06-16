<?php
/**
 * Controller: MotoristaController
 * Trata as requisições GET/POST da página de gerenciamento de motoristas.
 * Delega toda persistência ao MotoristaDao.
 * Define as variáveis necessárias para a view e faz o include da mesma.
 */
class MotoristaController {

    public function __construct(private MotoristaDao $dao) {}

    /**
     * Ponto de entrada único — processa a requisição e inclui a view.
     */
    public function processar(): void {
        $erro = "";

        // --- EXCLUSÃO DE MOTORISTA ---
        // Acionada via GET ?excluir=ID
        if (isset($_GET['excluir'])) {
            try {
                $this->dao->excluir((int) $_GET['excluir']);
                salvarMensagem('success', 'Motorista removido com sucesso!');
                header('Location: motoristas.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Não é possível excluir: este motorista possui rotas vinculadas no sistema.');
                header('Location: motoristas.php');
                exit;
            }
        }

        // --- CADASTRO DE NOVO MOTORISTA ---
        // Acionada via POST ao enviar o formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $validade = !empty($_POST['validade_cnh']) ? $_POST['validade_cnh'] : null;
                $dados = [
                    'id_transportadora' => $_POST['id_transportadora'],
                    'nome'              => $_POST['nome'],
                    'cpf'               => $_POST['cpf'],
                    'cnh'               => $_POST['cnh'],
                    'categoria_cnh'     => $_POST['categoria_cnh'],
                    'validade_cnh'      => $validade,
                    'telefone'          => $_POST['telefone'],
                ];
                $edicao = ($_POST['acao'] ?? '') === 'editar';
                if ($edicao) {
                    $this->dao->atualizar((int) $_POST['id_motorista'], $dados);
                } else {
                    $this->dao->inserir($dados);
                }
                salvarMensagem('success', $edicao ? 'Motorista atualizado com sucesso!' : 'Motorista cadastrado com sucesso!');
                header('Location: motoristas.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao salvar: CPF ou CNH já registrado, ou dados inválidos.');
                header('Location: motoristas.php');
                exit;
            }
        }

        // --- CONSULTAS PARA A VIEW ---

        // Transportadoras ativas para popular o select do formulário de cadastro
        $transp = $this->dao->listarTransportadorasAtivas();

        // Lista completa de motoristas com nome fantasia da transportadora
        $lista  = $this->dao->listar();
        $total  = count($lista);
        $ativos = $this->dao->contarAtivos();

        // Data atual para comparação de validade da CNH na view
        $hoje     = date('Y-m-d');

        // Conta motoristas com CNH vencida
        $vencidos = 0;
        foreach ($lista as $m) {
            if (!empty($m['validade_cnh']) && $m['validade_cnh'] < $hoje) {
                $vencidos++;
            }
        }

        // Renderiza a view — não deve ser alterada
        include 'views/motoristas.php';
    }
}
