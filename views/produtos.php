<?php
/**
 * View: Produtos
 *
 * Exibe o painel de gestão de produtos (SKUs) do sistema.
 * Apresenta KPIs (total de SKUs, estoque crítico e vencidos),
 * formulário colapsável para cadastro de novos produtos,
 * cartão informativo sobre o fluxo de estoque e tabela com
 * os produtos cadastrados, indicando quantidade em estoque e validade.
 *
 * Variáveis esperadas do controller:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de SKUs cadastrados
 * - $criticos(int)    Produtos com estoque abaixo de 10 unidades
 * - $vencidos(int)    Produtos com data de validade vencida
 * - $hoje    (string) Data atual no formato Y-m-d para comparação de validade
 * - $lista   (array)  Lista de produtos com qtd_estoque e validade
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<!-- Renderiza a barra de navegação superior do sistema -->
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">

    <!-- Bloco PHP: exibe alerta de aviso se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI DE PRODUTOS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de SKUs (produtos distintos) cadastrados -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total de SKUs</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Produtos com estoque crítico (menos de 10 unidades) -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Estoque Crítico (&lt;10)</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $criticos ?></h3>
        </div></div>
        <!-- KPI: Produtos com data de validade vencida -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Vencidos</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $vencidos ?></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO E CARTÃO INFORMATIVO ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de cadastro (colapso Bootstrap) -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formProd">
                <i class="bi bi-file-earmark-plus-fill"></i> Cadastrar Produto
            </button>

            <!-- Formulário colapsável de cadastro de produto -->
            <div class="collapse mb-4" id="formProd">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box"></i> Dados do Produto</h6>

                    <!-- Formulário POST com dados do produto -->
                    <form method="POST" class="row g-3">

                        <!-- Campo de descrição/nome do produto -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Descrição</label>
                            <input type="text" name="descricao" class="form-control form-control-sm" placeholder="Ex: Bobina de Aço 500mm" required>
                        </div>

                        <!-- Campo de peso unitário em kilogramas (opcional) -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Peso (kg)</label>
                            <input type="number" step="0.01" name="peso" class="form-control form-control-sm">
                        </div>

                        <!-- Campo de volume unitário em metros cúbicos (opcional) -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Volume (m³)</label>
                            <input type="number" step="0.01" name="volume" class="form-control form-control-sm">
                        </div>

                        <!-- Campo de data de validade do produto (opcional) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Validade <span class="text-muted fw-normal">(Opcional)</span></label>
                            <input type="date" name="validade" class="form-control form-control-sm">
                        </div>

                        <!-- Botão de submissão do formulário -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-info text-white btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cartão informativo sobre o fluxo de estoque -->
            <div class="card border-0 bg-dark text-white p-3 rounded-3 shadow-sm">
                <h6 class="small fw-bold text-info mb-2"><i class="bi bi-info-circle"></i> Fluxo de Estoque</h6>
                <p class="m-0 text-info" style="font-size:11px">Ao cadastrar um produto, o estoque é iniciado com quantidade zero. Use a página <strong>Estoque</strong> para registrar entradas e saídas de mercadorias.</p>
                <!-- Link de atalho para a página de estoque -->
                <a href="estoque.php" class="btn btn-outline-info btn-sm mt-2">Ir para Estoque &rarr;</a>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE PRODUTOS ===== -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-box text-info"></i> Produtos Cadastrados
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Peso / Volume</th>
                                <th class="text-center">Estoque</th>
                                <th>Validade</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Bloco PHP: exibe mensagem se não há produtos cadastrados -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum produto cadastrado.</td></tr>
                        <!-- Loop PHP: itera sobre cada produto e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $p):
                            /* Verifica se o produto está vencido comparando com a data atual */
                            $vencido  = !empty($p['validade']) && $p['validade'] < ($hoje ?? date('Y-m-d'));
                            /* Marca como crítico se o estoque for menor que 10 unidades */
                            $critico  = $p['qtd_estoque'] < 10;
                        ?>
                            <tr class="row-h">
                                <!-- Coluna: descrição do produto e ID em destaque secundário -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($p['descricao']) ?></strong>
                                    <small class="text-muted">#<?= $p['id_produto'] ?></small>
                                </td>
                                <!-- Coluna: peso e volume unitários formatados -->
                                <td class="text-center small">
                                    <?= $p['peso'] ? number_format($p['peso'],2,',','.').' kg' : '—' ?><br>
                                    <?= $p['volume'] ? number_format($p['volume'],2,',','.').' m³' : '—' ?>
                                </td>
                                <!-- Coluna: quantidade em estoque com badge (vermelho se crítico, verde se ok) -->
                                <td class="text-center">
                                    <span class="badge px-3 py-1 rounded-pill <?= $critico ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $p['qtd_estoque'] ?> un
                                    </span>
                                </td>
                                <!-- Coluna: data de validade com badge (vermelho se vencida, cinza se válida) -->
                                <td>
                                    <?php if(!empty($p['validade'])): ?>
                                        <span class="badge <?= $vencido ? 'bg-danger' : 'bg-light text-dark border' ?>">
                                            <?= date('d/m/Y', strtotime($p['validade'])) ?>
                                        </span>
                                    <?php else: ?><span class="text-muted small">Sem validade</span><?php endif; ?>
                                </td>
                                <!-- Coluna: botão de exclusão com confirmação JavaScript -->
                                <td class="text-center">
                                    <a href="?excluir=<?= $p['id_produto'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir este produto?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
