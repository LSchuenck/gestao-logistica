<?php
/*
 * Arquivo: controller/FreteController.php
 * Finalidade: Controller do módulo de Fretes.
 * Responsável por processar todas as requisições GET/POST relacionadas
 * a fretes, delegar operações ao FreteDao e preparar as variáveis
 * que serão utilizadas pela view views/frete.php.
 *
 * Ações tratadas:
 *  - GET ?nf=<id>       → renderiza a DANFE completa (HTML de impressão) e termina
 *  - GET ?excluir=<id>  → exclui o frete e redireciona
 *  - POST               → cadastra novo frete e redireciona
 *  - (padrão)           → carrega dados para a listagem
 *
 * Nota: a geração da DANFE está mantida inline neste controller (não em uma
 * view separada) porque ela possui seu próprio layout HTML completo com CSS de
 * impressão, terminando com exit — tal como estava no arquivo original.
 */

class FreteController
{
    /** @var FreteDao DAO responsável pelo acesso aos dados de fretes */
    private FreteDao $dao;

    /**
     * Construtor: injeta o DAO de fretes.
     *
     * @param FreteDao $dao Instância do DAO de fretes
     */
    public function __construct(FreteDao $dao)
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
        /* -----------------------------------------------------------------
         * AÇÃO GET: Visualizar Nota Fiscal (DANFE)
         * Renderiza uma página HTML independente com layout de impressão.
         * O bloco termina com exit para não executar o restante do controller.
         * Mantido inline pois possui CSS e estrutura HTML de impressão próprios.
         * ----------------------------------------------------------------- */
        if (isset($_GET['nf']) && !empty($_GET['nf'])) {
            $this->renderizarDanfe((int)$_GET['nf']);
            exit; // Encerra após renderizar a NF; o restante não é processado
        }

        $erro = "";

        /* -----------------------------------------------------------------
         * AÇÃO GET: Excluir frete
         * Remove o registro do frete. Caso exista FK protegendo o registro,
         * o catch captura o erro e exibe uma mensagem amigável.
         * ----------------------------------------------------------------- */
        if (isset($_GET['excluir'])) {
            try {
                $this->dao->excluir((int)$_GET['excluir']);
                salvarMensagem('success', 'Frete removido com sucesso!');
                header("Location: frete.php");
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao excluir frete.');
                header("Location: frete.php");
                exit;
            }
        }

        /* -----------------------------------------------------------------
         * AÇÃO POST: Cadastrar novo frete
         * A constraint UNIQUE em id_viagem impede que a mesma viagem tenha
         * dois fretes — o catch trata a exceção de violação de unicidade.
         * ----------------------------------------------------------------- */
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Usa a data atual como padrão se o campo estiver vazio
                $dataEmissao = !empty($_POST['data_emissao'])
                    ? $_POST['data_emissao']
                    : date('Y-m-d');

                $this->dao->inserir(
                    (int)$_POST['id_viagem'],
                    (int)$_POST['id_transportadora'],
                    (float)$_POST['valor'],
                    (float)($_POST['custo_operacional'] ?? 0),
                    $_POST['nota_fiscal'] ?? '',
                    $dataEmissao
                );
                salvarMensagem('success', 'Frete registrado com sucesso!');
                header("Location: frete.php");
                exit;
            } catch (Exception $e) {
                salvarMensagem('danger', 'Erro ao registrar frete: viagem já possui frete ou dados inválidos.');
                header("Location: frete.php");
                exit;
            }
        }

        /* -----------------------------------------------------------------
         * CARREGAMENTO DE DADOS PARA A VIEW
         * ----------------------------------------------------------------- */

        // Viagens que ainda não possuem frete, com dados para a calculadora JS
        // (já inclui id_transportadora e nome derivados da viagem)
        $viagens_sem_frete = $this->dao->listarViagensSemFrete();

        // Lista completa de fretes para a tabela
        $lista = $this->dao->listarTodos();

        // Indicadores financeiros para os cards de KPI
        $total_fretes = $this->dao->contarTotal();
        $total_valor  = $this->dao->somarValores();
        $total_custo  = $this->dao->somarCustos();

        // Margem de contribuição percentual: (Receita - Custo) / Receita * 100
        $margem = $total_valor > 0
            ? (($total_valor - $total_custo) / $total_valor) * 100
            : 0;

        // Delega a renderização HTML para a view dedicada
        include 'views/frete.php';
    }

    /**
     * Renderiza a DANFE (Documento Auxiliar de Nota Fiscal Eletrônica)
     * como uma página HTML completa com CSS de impressão e termina a
     * execução. Mantido no controller pois não usa variáveis da listagem.
     *
     * @param int $idFrete ID do frete para gerar a DANFE
     * @return void
     */
    private function renderizarDanfe(int $idFrete): void
    {
        // Busca todos os dados necessários para compor a DANFE
        $frete = $this->dao->buscarParaDanfe($idFrete);

        // Interrompe com mensagem de erro se o frete não existir no banco
        if (!$frete) {
            die("<div class='container mt-5 alert alert-danger'>Frete não encontrado!</div>");
        }

        // Número da NF com zeros à esquerda para exibição padronizada (ex.: 000042)
        $numero_nf = str_pad($frete['id_frete'], 6, "0", STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nota Fiscal de Frete #<?= $numero_nf ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<!-- Barra de ações visível apenas na tela (oculta na impressão via CSS .no-print) -->
<div class="container my-4 no-print">
    <div class="d-flex justify-content-between align-items-center bg-dark p-3 rounded text-white">
        <div>
            <a href="frete.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-arrow-left"></i> Voltar</a>
            <span class="fw-bold">DANFE — Documento Auxiliar de Nota Fiscal</span>
        </div>
        <button onclick="window.print()" class="btn btn-success fw-bold"><i class="bi bi-printer-fill"></i> Imprimir NF</button>
    </div>
</div>
<!-- Corpo do DANFE — layout inspirado no padrão NF-e brasileiro -->
<div class="container bg-white p-4 shadow-sm border rounded mb-5" style="max-width:900px;color:#000">
    <!-- Cabeçalho: identificação da transportadora e número da NF -->
    <div class="row g-0 border border-dark mb-2">
        <div class="col-md-5 p-2 border-end border-dark text-center d-flex flex-column justify-content-center">
            <h4 class="fw-bold m-0"><?= htmlspecialchars($frete['transportadora']) ?></h4>
            <span class="small text-muted">Transportes e Soluções Logísticas</span>
            <span class="small">CNPJ: <?= htmlspecialchars($frete['cnpj']) ?></span>
        </div>
        <div class="col-md-3 border-end border-dark text-center p-2 d-flex flex-column justify-content-center">
            <span class="fw-bold d-block fs-5">DANFE</span>
            <span class="small d-block text-muted" style="font-size:9px">Documento Auxiliar da Nota Fiscal Eletrônica</span>
            <div class="mt-2 text-start px-2" style="font-size:11px">
                <strong>NF Nº:</strong> <?= $numero_nf ?><br>
                <strong>NF do Frete:</strong> <?= htmlspecialchars($frete['nota_fiscal'] ?? '—') ?>
            </div>
        </div>
        <!-- Código de barras simulado para fins acadêmicos -->
        <div class="col-md-4 p-2">
            <span class="danfe-title">Código de Barras</span>
            <div class="barcode mb-1"></div>
            <!-- Número fictício: prefixo fixo + mês/ano atual + ID do frete com 20 dígitos -->
            <span style="font-size:9px;font-family:monospace"><?= "3126" . date('my') . str_pad($frete['id_frete'], 20, "0", STR_PAD_LEFT) ?></span>
        </div>
    </div>

    <!-- Seção: natureza da operação, data de emissão e status da viagem -->
    <div class="row g-0 border border-dark mb-2">
        <div class="col-md-6 danfe-box border-end border-dark">
            <span class="danfe-title">Natureza da Operação</span>
            <span class="fw-bold">PRESTAÇÃO DE SERVIÇO DE TRANSPORTE DE CARGA</span>
        </div>
        <div class="col-md-3 danfe-box border-end border-dark">
            <span class="danfe-title">Data de Emissão</span>
            <!-- Se não houver data de emissão cadastrada, usa a data atual -->
            <span class="fw-bold"><?= $frete['data_emissao'] ? date('d/m/Y', strtotime($frete['data_emissao'])) : date('d/m/Y') ?></span>
        </div>
        <div class="col-md-3 danfe-box">
            <span class="danfe-title">Status da Viagem</span>
            <span class="fw-bold"><?= $frete['viagem_status'] ?></span>
        </div>
    </div>

    <!-- Seção: dados do motorista e veículo vinculados à rota da viagem -->
    <h6 class="fw-bold text-uppercase mt-3 mb-1" style="font-size:12px">Dados do Motorista e Veículo</h6>
    <div class="row g-0 border border-dark mb-2">
        <div class="col-md-4 danfe-box border-end border-dark">
            <span class="danfe-title">Nome do Motorista</span>
            <span class="fw-bold"><?= htmlspecialchars($frete['motorista']) ?></span>
        </div>
        <div class="col-md-3 danfe-box border-end border-dark">
            <span class="danfe-title">CNH</span>
            <span class="fw-bold"><?= htmlspecialchars($frete['cnh']) ?></span>
        </div>
        <div class="col-md-3 danfe-box border-end border-dark">
            <span class="danfe-title">Tipo de Veículo</span>
            <span class="fw-bold"><?= htmlspecialchars($frete['tipo_veiculo']) ?></span>
        </div>
        <div class="col-md-2 danfe-box">
            <span class="danfe-title">Placa</span>
            <span class="fw-bold"><?= htmlspecialchars($frete['placa']) ?></span>
        </div>
    </div>

    <!-- Seção: cálculo do frete com base tributária de ICMS -->
    <h6 class="fw-bold text-uppercase mt-3 mb-1" style="font-size:12px">Cálculo do Frete</h6>
    <div class="row g-0 border border-dark mb-4">
        <div class="col-md-3 danfe-box border-end border-dark">
            <span class="danfe-title">Custo Operacional</span>
            <span>R$ <?= number_format($frete['custo_operacional'] ?? 0, 2, ',', '.') ?></span>
        </div>
        <!-- Base de cálculo do ICMS: 80% do valor total do frete (regra fiscal simplificada) -->
        <div class="col-md-3 danfe-box border-end border-dark">
            <span class="danfe-title">Base ICMS (80%)</span>
            <span>R$ <?= number_format($frete['valor'] * 0.8, 2, ',', '.') ?></span>
        </div>
        <!-- ICMS calculado à alíquota de 12% sobre a base de cálculo -->
        <div class="col-md-3 danfe-box border-end border-dark">
            <span class="danfe-title">ICMS (12%)</span>
            <span>R$ <?= number_format(($frete['valor'] * 0.8) * 0.12, 2, ',', '.') ?></span>
        </div>
        <!-- Valor total bruto do frete conforme cadastrado -->
        <div class="col-md-3 danfe-box bg-light">
            <span class="danfe-title text-primary">VALOR TOTAL DO FRETE</span>
            <span class="danfe-val text-primary">R$ <?= number_format($frete['valor'] ?? 0, 2, ',', '.') ?></span>
        </div>
    </div>
    <!-- Rodapé informativo obrigatório em documentos acadêmicos -->
    <div class="border-top pt-2 text-center text-muted" style="font-size:10px">
        DOCUMENTO EMITIDO PARA FINS ACADÊMICOS — GESTÃO LOGÍSTICA <?= date('Y') ?>
    </div>
</div>
</body>
</html>
<?php
    } // fim renderizarDanfe
}
