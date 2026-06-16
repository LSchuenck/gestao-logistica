<?php
/**
 * Controller: EstoqueController
 *
 * Intermediário entre o entry point (estoque.php) e as camadas de DAO e View.
 * Interpreta as requisições HTTP (POST), delega as operações ao EstoqueDao e
 * MovimentacaoDao, define as variáveis necessárias para a view e inclui
 * views/estoque.php.
 *
 * Ações tratadas:
 *  - POST acao=movimentacao → registra entrada ou saída de estoque com validação de saldo
 *  - (padrão)               → carrega dados e renderiza a view
 */
class EstoqueController
{
    /** @var EstoqueDao DAO de estoque injetado pelo entry point */
    private EstoqueDao $estoqueDao;

    /** @var MovimentacaoDao DAO de movimentações injetado pelo entry point */
    private MovimentacaoDao $movimentacaoDao;

    /** @var int ID do usuário logado, capturado no entry point da sessão */
    private int $idUsuario;

    /**
     * Recebe os DAOs e o ID do usuário por injeção de dependência.
     *
     * @param EstoqueDao     $estoqueDao      Instância do DAO de estoque
     * @param MovimentacaoDao $movimentacaoDao Instância do DAO de movimentações
     * @param int             $idUsuario       ID do usuário autenticado na sessão
     */
    public function __construct(
        EstoqueDao      $estoqueDao,
        MovimentacaoDao $movimentacaoDao,
        int             $idUsuario
    ) {
        $this->estoqueDao      = $estoqueDao;
        $this->movimentacaoDao = $movimentacaoDao;
        $this->idUsuario       = $idUsuario;
    }

    /**
     * Ponto de entrada principal do controller.
     * Processa a requisição POST de movimentação e carrega os dados para a view.
     *
     * @return void
     */
    public function processar(): void
    {
        $erro    = '';
        $sucesso = '';

        // -----------------------------------------------------------------
        // AÇÃO: REGISTRAR MOVIMENTAÇÃO DE ESTOQUE (POST acao=movimentacao)
        // Pode ser ENTRADA (aumenta saldo) ou SAÍDA (reduz saldo).
        // Usa transação para garantir que o saldo e o histórico sejam gravados
        // de forma atômica — evita inconsistências em caso de falha parcial.
        // -----------------------------------------------------------------
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'movimentacao') {
            try {
                // Sanitização dos dados recebidos do formulário
                $idProduto = (int)$_POST['id_produto'];
                $idArmazem = (int)$_POST['id_armazem_mov'];
                $tipo      = $_POST['tipo_movimentacao']; // 'ENTRADA' ou 'SAIDA'
                $qtd       = (int)$_POST['quantidade'];

                // Validação básica: quantidade deve ser positiva
                if ($qtd <= 0) {
                    throw new Exception('Quantidade deve ser maior que zero.');
                }

                // Inicia transação para garantir consistência entre saldo e histórico
                $this->estoqueDao->iniciarTransacao();

                // Consulta o saldo atual do produto no armazém informado
                $saldoAtual = $this->estoqueDao->consultarSaldo($idProduto, $idArmazem);

                // Impede saída além do saldo disponível — evita estoque negativo
                if ($tipo === 'SAIDA' && $qtd > $saldoAtual) {
                    throw new Exception(
                        "Saldo insuficiente neste armazém. Disponível: {$saldoAtual} unidades."
                    );
                }

                // Calcula o delta: positivo para ENTRADA, negativo para SAÍDA
                $delta = ($tipo === 'ENTRADA') ? $qtd : -$qtd;

                // Aplica o delta no saldo (INSERT ... ON DUPLICATE KEY UPDATE)
                $this->estoqueDao->aplicarDelta($idProduto, $idArmazem, $delta);

                // Registra a movimentação no histórico para rastreabilidade
                $this->movimentacaoDao->registrar($idProduto, $this->idUsuario, $idArmazem, $tipo, $qtd);

                $this->estoqueDao->confirmarTransacao(); // Confirma as duas operações juntas
                $sucesso = 'Movimentação registrada com sucesso!';
            } catch (Exception $e) {
                // Reverte tudo se qualquer etapa falhar
                $this->estoqueDao->reverterTransacao();
                $erro = $e->getMessage(); // Exibe a mensagem de erro para o usuário
            }
        }

        // -----------------------------------------------------------------
        // CARREGAMENTO DE DADOS PARA A VIEW
        // Todas as variáveis abaixo são esperadas por views/estoque.php.
        // -----------------------------------------------------------------

        // Lista consolidada de produtos com total em estoque (soma de todos os armazéns)
        $produtos = $this->estoqueDao->listarProdutosComEstoque();

        // Detalhe do estoque por armazém indexado por id_produto
        $estoque_por_armazem = $this->estoqueDao->listarEstoquePorArmazem();

        // Lista de armazéns para o select do formulário de movimentação
        $armazens = $this->estoqueDao->listarArmazens();

        // Histórico das últimas 20 movimentações
        $historico = $this->movimentacaoDao->listarHistorico(20);

        // -----------------------------------------------------------------
        // CÁLCULO DE INDICADORES RÁPIDOS
        // Exibidos nos cards de resumo no topo da página.
        // -----------------------------------------------------------------

        // Soma total de unidades em estoque em todos os armazéns e produtos
        $total_itens = array_sum(array_column($produtos, 'qtd_estoque'));

        $hoje     = date('Y-m-d'); // Data atual para comparação com datas de validade
        $vencidos = 0;             // Contador de produtos com validade expirada
        $criticos = 0;             // Contador de produtos com estoque abaixo de 10 unidades (nível crítico)

        foreach ($produtos as $p) {
            // Produto vencido: possui data de validade preenchida e ela já passou
            if (!empty($p['validade']) && $p['validade'] < $hoje) {
                $vencidos++;
            }
            // Produto crítico: estoque total menor que 10 unidades (limiar de alerta)
            if ($p['qtd_estoque'] < 10) {
                $criticos++;
            }
        }

        // Delega a renderização HTML para a view dedicada
        include 'views/estoque.php';
    }
}
