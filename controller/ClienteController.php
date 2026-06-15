<?php
/**
 * Controller: ClienteController
 *
 * Responsável por tratar as requisições GET e POST da página de clientes,
 * coordenar as operações com o ClienteDao e preparar as variáveis necessárias
 * para a view views/clientes.php.
 *
 * Variáveis disponibilizadas para a view:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de clientes cadastrados
 * - $estados (array)  Lista de siglas de UF para o select de estado
 * - $lista   (array)  Clientes com endereço e total_entregas
 */
class ClienteController
{
    /** @var ClienteDao DAO responsável pela persistência de clientes */
    private ClienteDao $clienteDao;

    /**
     * Recebe o DAO via injeção de dependência.
     *
     * @param ClienteDao $clienteDao Instância do DAO de clientes
     */
    public function __construct(ClienteDao $clienteDao)
    {
        $this->clienteDao = $clienteDao;
    }

    /**
     * Ponto de entrada do controller.
     *
     * Processa a requisição atual (exclusão via GET ou cadastro via POST),
     * prepara as variáveis da view e inclui o template HTML.
     *
     * @return void
     */
    public function executar(): void
    {
        $erro = "";

        // ---------------------------------------------------------------
        // OPERAÇÃO: EXCLUSÃO DE CLIENTE (GET ?excluir=id)
        // Remove o cliente e seu endereço vinculado em transação atômica.
        // Em caso de FK com entregas, exibe mensagem de erro sem redirecionar.
        // ---------------------------------------------------------------
        if (isset($_GET['excluir'])) {
            try {
                $this->clienteDao->excluir((int) $_GET['excluir']);
                // Redireciona para evitar reenvio acidental ao recarregar a página (PRG)
                header("Location: clientes.php");
                exit;
            } catch (Exception $e) {
                $erro = "Não é possível excluir: existem entregas vinculadas a este cliente.";
            }
        }

        // ---------------------------------------------------------------
        // OPERAÇÃO: CADASTRO DE NOVO CLIENTE (POST)
        // Insere endereço e cliente em transação. Redireciona ao concluir.
        // ---------------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $dadosCliente = [
                    'nome'     => $_POST['nome']     ?? '',
                    'cpf_cnpj' => $_POST['cpf_cnpj'] ?? null,
                    'telefone' => $_POST['telefone'] ?? null,
                ];
                $dadosEndereco = [
                    'cep'         => $_POST['cep']         ?? '',
                    'logradouro'  => $_POST['logradouro']  ?? '',
                    'numero'      => $_POST['numero']      ?? '',
                    'complemento' => $_POST['complemento'] ?? '',
                    'bairro'      => $_POST['bairro']      ?? '',
                    'cidade'      => $_POST['cidade']      ?? '',
                    'estado'      => $_POST['estado']      ?? '',
                ];
                if (($_POST['acao'] ?? '') === 'editar') {
                    $this->clienteDao->atualizar((int) $_POST['id_cliente'], $dadosCliente, $dadosEndereco);
                } else {
                    $this->clienteDao->inserir($dadosCliente, $dadosEndereco);
                }
                header("Location: clientes.php");
                exit;
            } catch (Exception $e) {
                $erro = "Erro ao salvar cliente.";
            }
        }

        // ---------------------------------------------------------------
        // CONSULTAS PARA A VIEW
        // ---------------------------------------------------------------

        // Lista de clientes com endereço e total de entregas por cliente
        $lista = $this->clienteDao->listarTodos();

        // Total de clientes cadastrados para exibir no KPI
        $total = count($lista);

        // Siglas dos estados brasileiros para o select do formulário de cadastro
        $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
                    'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC',
                    'SP','SE','TO'];

        // Carrega a view que utiliza $erro, $total, $estados e $lista
        include 'views/clientes.php';
    }
}
