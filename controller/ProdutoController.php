<?php
/**
 * Controller: ProdutoController
 *
 * Responsável por tratar as requisições GET e POST da página de produtos,
 * coordenar as operações com o ProdutoDao e preparar as variáveis necessárias
 * para a view views/produtos.php.
 *
 * Variáveis disponibilizadas para a view:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de SKUs cadastrados
 * - $criticos(int)    Produtos com estoque abaixo de 10 unidades
 * - $vencidos(int)    Produtos com data de validade vencida
 * - $hoje    (string) Data atual no formato Y-m-d para comparação de validade na view
 * - $lista   (array)  Produtos com qtd_estoque e validade
 */
class ProdutoController
{
    /** @var ProdutoDao DAO responsável pela persistência de produtos */
    private ProdutoDao $produtoDao;

    /**
     * Recebe o DAO via injeção de dependência.
     *
     * @param ProdutoDao $produtoDao Instância do DAO de produtos
     */
    public function __construct(ProdutoDao $produtoDao)
    {
        $this->produtoDao = $produtoDao;
    }

    /**
     * Ponto de entrada do controller.
     *
     * Processa a requisição atual (exclusão via GET ou cadastro via POST),
     * calcula os indicadores de alerta (vencidos e críticos),
     * prepara as variáveis da view e inclui o template HTML.
     *
     * @return void
     */
    public function processar(): void
    {
        $erro = "";

        // ---------------------------------------------------------------
        // OPERAÇÃO: EXCLUSÃO DE PRODUTO (GET ?excluir=id)
        // Exclusão em cascata manual: localizacao_estoque → movimentacao_estoque
        // → estoque → produto. FK com entregas impedirá a exclusão.
        // ---------------------------------------------------------------
        if (isset($_GET['excluir'])) {
            try {
                $this->produtoDao->excluir((int) $_GET['excluir']);
                salvarMensagem('success', 'Produto removido com sucesso!');
                header("Location: produtos.php");
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Não é possível excluir: produto vinculado a entregas ou movimentações.');
                header("Location: produtos.php");
                exit;
            }
        }

        // ---------------------------------------------------------------
        // OPERAÇÃO: CADASTRO DE NOVO PRODUTO (POST)
        // Insere produto e estoque inicial (quantidade zero) em transação.
        // ---------------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dados = [
                    'descricao' => $_POST['descricao'] ?? '',
                    'peso'      => !empty($_POST['peso'])    ? $_POST['peso']    : null,
                    'volume'    => !empty($_POST['volume'])  ? $_POST['volume']  : null,
                    // Validade armazenada como NULL quando não informada pelo usuário
                    'validade'  => !empty($_POST['validade']) ? $_POST['validade'] : null,
                ];
                $this->produtoDao->inserir($dados);
                salvarMensagem('success', 'Produto cadastrado com sucesso!');
                header("Location: produtos.php");
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao cadastrar produto.');
                header("Location: produtos.php");
                exit;
            }
        }

        // ---------------------------------------------------------------
        // CONSULTAS E CÁLCULO DE INDICADORES PARA A VIEW
        // ---------------------------------------------------------------

        // Lista de produtos com saldo de estoque atual
        $lista = $this->produtoDao->listarTodos();

        // Total de SKUs cadastrados para exibir no KPI
        $total = count($lista);

        // Data atual para comparação com a validade dos produtos
        $hoje = date('Y-m-d');

        // Contadores de alertas exibidos nos KPIs da view
        $vencidos = 0; // Produtos com data de validade expirada
        $criticos = 0; // Produtos com menos de 10 unidades em estoque

        foreach ($lista as $p) {
            if (!empty($p['validade']) && $p['validade'] < $hoje) {
                $vencidos++;
            }
            if ($p['qtd_estoque'] < 10) {
                $criticos++;
            }
        }

        // Carrega a view que utiliza $erro, $total, $vencidos, $criticos, $hoje e $lista
        include 'views/produtos.php';
    }
}
