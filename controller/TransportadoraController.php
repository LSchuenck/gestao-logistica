<?php
/**
 * Controller: TransportadoraController
 * Trata as requisições GET/POST da página de gerenciamento de transportadoras.
 * Delega toda persistência ao TransportadoraDao e ao EnderecoDao.
 * Define as variáveis necessárias para a view e faz o include da mesma.
 */
class TransportadoraController {

    public function __construct(
        private TransportadoraDao $dao,
        private EnderecoDao $enderecoDao
    ) {}

    /**
     * Ponto de entrada único — processa a requisição e inclui a view.
     */
    public function processar(): void {
        $erro = "";

        // --- EXCLUSÃO DE TRANSPORTADORA ---
        // Acionada via GET ?excluir=ID
        if (isset($_GET['excluir'])) {
            try {
                $id          = (int) $_GET['excluir'];
                $idEndereco  = $this->dao->buscarIdEndereco($id);

                // Remove transportadora e endereço em transação atômica no DAO
                $this->dao->excluirComEndereco($id, $idEndereco);

                salvarMensagem('success', 'Transportadora removida com sucesso!');
                header('Location: transportadoras.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Não é possível excluir: existem motoristas ou veículos vinculados a esta transportadora.');
                header('Location: transportadoras.php');
                exit;
            }
        }

        // --- ALTERNAR STATUS (ATIVA <-> INATIVA) ---
        // Acionada via GET ?toggle=ID
        if (isset($_GET['toggle'])) {
            $this->dao->alternarStatus((int) $_GET['toggle']);

            header('Location: transportadoras.php');
            exit;
        }

        // --- CADASTRO DE NOVA TRANSPORTADORA ---
        // Acionada via POST ao enviar o formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $edicao = ($_POST['acao'] ?? '') === 'editar';
                if ($edicao) {
                    $id = (int) $_POST['id_transportadora'];
                    $idEndereco = $this->dao->buscarIdEndereco($id);
                    $this->dao->atualizar($id, [
                        'cnpj'          => $_POST['cnpj'],
                        'razao_social'  => $_POST['razao_social'],
                        'nome_fantasia' => $_POST['nome_fantasia'],
                        'telefone'      => $_POST['telefone'],
                        'email'         => $_POST['email'],
                    ]);
                    if ($idEndereco) {
                        $this->enderecoDao->atualizar($idEndereco, $_POST);
                    }
                } else {
                    $idEndereco = $this->enderecoDao->inserir($_POST);
                    $this->dao->inserir([
                        'cnpj'          => $_POST['cnpj'],
                        'razao_social'  => $_POST['razao_social'],
                        'nome_fantasia' => $_POST['nome_fantasia'],
                        'telefone'      => $_POST['telefone'],
                        'email'         => $_POST['email'],
                        'id_endereco'   => $idEndereco,
                    ]);
                }
                salvarMensagem('success', $edicao ? 'Transportadora atualizada com sucesso!' : 'Transportadora cadastrada com sucesso!');
                header('Location: transportadoras.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao salvar: CNPJ já registrado ou dados inválidos.');
                header('Location: transportadoras.php');
                exit;
            }
        }

        // --- CONSULTAS PARA A VIEW ---

        // Lista completa de transportadoras com endereço (LEFT JOIN no DAO)
        $lista  = $this->dao->listar();
        $total  = count($lista);
        $ativas = $this->dao->contarAtivas();

        // Siglas dos estados brasileiros para o select do formulário de endereço
        $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
                    'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

        // Renderiza a view — não deve ser alterada
        include 'views/transportadoras.php';
    }
}
