<?php
/**
 * Controller: EntregaController
 *
 * Intermediário entre o entry point (entregas.php) e as camadas de DAO e View.
 * Interpreta as requisições HTTP (GET e POST), delega as operações ao EntregaDao,
 * define as variáveis necessárias para a view e inclui views/entregas.php.
 *
 * Ações tratadas:
 *  - GET ?excluir={id}              → exclui entrega e redireciona
 *  - GET ?status={STATUS}&id={id}   → atualiza status (com desconto de estoque se ENTREGUE)
 *  - POST acao=nova_entrega_completa → cadastra nova entrega com produtos vinculados
 *  - POST acao=atualizar_armazem    → altera o armazém de origem da entrega
 *  - POST acao=add_produto          → vincula produto avulso a uma entrega
 *  - (padrão)                       → carrega dados e renderiza a view
 */
class EntregaController
{
    /** @var EntregaDao DAO de entregas injetado pelo entry point */
    private EntregaDao $dao;

    /**
     * Recebe o DAO por injeção de dependência.
     *
     * @param EntregaDao $dao Instância do DAO de entregas
     */
    public function __construct(EntregaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Ponto de entrada principal do controller.
     * Roteia a requisição para o método adequado e, ao final, carrega a view.
     *
     * @return void
     */
    public function processar(): void
    {
        $erro    = '';
        $sucesso = '';

        // -----------------------------------------------------------------
        // AJAX: produtos disponíveis em um armazém específico
        // GET ?ajax=produtos_armazem&id={id_armazem}
        // Retorna JSON para preencher o select de produtos dinamicamente.
        // -----------------------------------------------------------------
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'produtos_armazem') {
            header('Content-Type: application/json; charset=utf-8');
            $idArmazem = (int)($_GET['id'] ?? 0);
            echo json_encode($idArmazem ? $this->dao->listarProdutosPorArmazem($idArmazem) : []);
            exit;
        }

        // -----------------------------------------------------------------
        // AÇÃO: EXCLUIR ENTREGA (GET ?excluir={id})
        // -----------------------------------------------------------------
        if (isset($_GET['excluir'])) {
            try {
                $this->dao->excluir((int)$_GET['excluir']);
                salvarMensagem('success', 'Entrega removida com sucesso!');
                header('Location: entregas.php');
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Não é possível excluir esta entrega.');
                header('Location: entregas.php');
                exit;
            }
        }

        // -----------------------------------------------------------------
        // AÇÃO: ATUALIZAR STATUS (GET ?status={STATUS}&id={id})
        // -----------------------------------------------------------------
        if (isset($_GET['status'], $_GET['id'])) {
            // Lista de statuses permitidos — qualquer outro valor é ignorado por segurança
            $statusesValidos = ['PENDENTE', 'EM_TRANSITO', 'ENTREGUE', 'ATRASADA'];
            if (in_array($_GET['status'], $statusesValidos)) {
                $idUsuario = (int)$_SESSION['usuario']['id']; // Usuário logado para movimentações
                $this->dao->atualizarStatus(
                    (int)$_GET['id'],
                    $_GET['status'],
                    $idUsuario
                );
            }
            // Redireciona após qualquer atualização de status para evitar reenvio da URL
            header('Location: entregas.php');
            exit;
        }

        // -----------------------------------------------------------------
        // AÇÕES VIA POST
        // -----------------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

            // --------------------------------------------------------------
            // AÇÃO: NOVA_ENTREGA_COMPLETA
            // Cadastra uma entrega com cliente, armazém, data prevista, peso,
            // volume e lista de produtos de uma só vez.
            // --------------------------------------------------------------
            if ($_POST['acao'] === 'nova_entrega_completa') {
                try {
                    $this->dao->cadastrarCompleta(
                        (int)$_POST['id_cliente'],
                        !empty($_POST['id_armazem'])   ? (int)$_POST['id_armazem']         : null,
                        $_POST['data_prevista'],
                        !empty($_POST['peso_total'])   ? floatval($_POST['peso_total'])     : null,
                        !empty($_POST['volume_total']) ? floatval($_POST['volume_total'])   : null,
                        is_array($_POST['produtos'] ?? null) ? $_POST['produtos'] : []
                    );
                    salvarMensagem('success', 'Entrega cadastrada com sucesso!');
                    header('Location: entregas.php');
                    exit;
                } catch (Exception $e) {
                    salvarMensagem('danger', 'Erro ao cadastrar entrega.');
                    header('Location: entregas.php');
                    exit;
                }
            }

            // --------------------------------------------------------------
            // AÇÃO: ATUALIZAR_ARMAZEM
            // Permite corrigir ou definir o armazém de origem de uma entrega
            // já existente sem alterar os demais campos.
            // --------------------------------------------------------------
            if ($_POST['acao'] === 'atualizar_armazem') {
                $this->dao->atualizarArmazem(
                    (int)$_POST['id_entrega'],
                    !empty($_POST['id_armazem']) ? (int)$_POST['id_armazem'] : null
                );
                header('Location: entregas.php');
                exit;
            }

            // --------------------------------------------------------------
            // AÇÃO: ADD_PRODUTO
            // Adiciona um produto avulso a uma entrega já cadastrada.
            // --------------------------------------------------------------
            if ($_POST['acao'] === 'add_produto') {
                try {
                    $this->dao->adicionarProduto(
                        (int)$_POST['id_entrega'],
                        (int)$_POST['id_produto'],
                        (int)$_POST['quantidade']
                    );
                    salvarMensagem('success', 'Produto adicionado à entrega com sucesso!');
                    header('Location: entregas.php');
                    exit;
                } catch (Exception $e) {
                    salvarMensagem('danger', 'Produto já adicionado ou erro ao vincular.');
                    header('Location: entregas.php');
                    exit;
                }
            }
        }

        // -----------------------------------------------------------------
        // CARREGAMENTO DE DADOS PARA A VIEW
        // Todas as variáveis abaixo são esperadas por views/entregas.php.
        // -----------------------------------------------------------------

        // Listas para preencher os selects dos formulários
        $clientes = $this->dao->listarClientes();
        $produtos  = $this->dao->listarProdutos();
        $armazens  = $this->dao->listarArmazens();

        // Lista completa de entregas com dados de cliente, armazém e contagem de itens
        $lista = $this->dao->listarTodas();

        // Contadores por status para os cards de KPI no topo da página
        $contadores   = $this->dao->contarPorStatus();
        $pendentes    = $contadores['pendentes'];
        $em_transito  = $contadores['em_transito'];
        $entregues    = $contadores['entregues'];
        $atrasadas    = $contadores['atrasadas'];

        // Produtos de cada entrega indexados por id_entrega para acesso O(1) na view
        $produtos_por_entrega = $this->dao->listarProdutosPorEntrega();

        // Delega a renderização HTML para a view dedicada
        include 'views/entregas.php';
    }
}
