<?php
/**
 * Controller: ArmazemController
 *
 * Responsável por tratar as requisições GET e POST da página de armazéns,
 * coordenar as operações com o ArmazemDao e preparar as variáveis necessárias
 * para a view views/armazens.php.
 *
 * Variáveis disponibilizadas para a view:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de armazéns ativos
 * - $estados (array)  Lista de siglas de UF para o select de estado
 * - $lista   (array)  Armazéns com endereço, total_skus e total_itens
 */
class ArmazemController
{
    /** @var ArmazemDao DAO responsável pela persistência de armazéns */
    private ArmazemDao $armazemDao;

    /**
     * Recebe o DAO via injeção de dependência.
     *
     * @param ArmazemDao $armazemDao Instância do DAO de armazéns
     */
    public function __construct(ArmazemDao $armazemDao)
    {
        $this->armazemDao = $armazemDao;
    }

    /**
     * Ponto de entrada do controller.
     *
     * Processa a requisição atual (exclusão via GET ou cadastro via POST),
     * prepara as variáveis da view e inclui o template HTML.
     *
     * @return void
     */
    public function processar(): void
    {
        $erro = "";

        // ---------------------------------------------------------------
        // OPERAÇÃO: EXCLUSÃO DE ARMAZÉM (GET ?excluir=id)
        // Remove o armazém e seu endereço vinculado em transação atômica.
        // Se houver produtos vinculados ao armazém, a FK impedirá a exclusão.
        // ---------------------------------------------------------------
        if (isset($_GET['excluir'])) {
            try {
                $this->armazemDao->excluir((int) $_GET['excluir']);
                salvarMensagem('success', 'Armazém removido com sucesso!');
                header("Location: armazens.php");
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Não é possível excluir: existem produtos localizados neste armazém.');
                header("Location: armazens.php");
                exit;
            }
        }

        // ---------------------------------------------------------------
        // OPERAÇÃO: CADASTRO DE NOVO ARMAZÉM (POST)
        // Insere endereço e armazém em transação. Redireciona ao concluir.
        // ---------------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $nome = $_POST['nome'] ?? '';
                $dadosEndereco = [
                    'cep'         => $_POST['cep']         ?? '',
                    'logradouro'  => $_POST['logradouro']  ?? '',
                    'numero'      => $_POST['numero']      ?? '',
                    'complemento' => $_POST['complemento'] ?? '',
                    'bairro'      => $_POST['bairro']      ?? '',
                    'cidade'      => $_POST['cidade']      ?? '',
                    'estado'      => $_POST['estado']      ?? '',
                ];
                $edicao = ($_POST['acao'] ?? '') === 'editar';
                if ($edicao) {
                    $this->armazemDao->atualizar((int) $_POST['id_armazem'], $nome, $dadosEndereco);
                } else {
                    $this->armazemDao->inserir($nome, $dadosEndereco);
                }
                salvarMensagem('success', $edicao ? 'Armazém atualizado com sucesso!' : 'Armazém cadastrado com sucesso!');
                header("Location: armazens.php");
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao salvar armazém.');
                header("Location: armazens.php");
                exit;
            }
        }

        // ---------------------------------------------------------------
        // CONSULTAS PARA A VIEW
        // ---------------------------------------------------------------

        // Lista de armazéns com endereço, total de SKUs e total de itens em estoque
        $lista = $this->armazemDao->listarTodos();

        // Total de armazéns cadastrados para exibir no KPI
        $total = count($lista);

        // Siglas dos estados brasileiros para o select do formulário de cadastro
        $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
                    'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC',
                    'SP','SE','TO'];

        // Carrega a view que utiliza $erro, $total, $estados e $lista
        include 'views/armazens.php';
    }
}
