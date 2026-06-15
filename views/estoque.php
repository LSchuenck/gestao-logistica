<?php
/**
 * View: Estoque
 *
 * Exibe o painel de gestão de estoque do sistema.
 * Apresenta KPIs (volume total, SKUs cadastrados, estoque crítico e
 * produtos vencidos), formulário para registrar movimentações (entrada
 * ou saída), inventário por produto com detalhe por armazém expansível
 * e histórico das últimas 20 movimentações registradas.
 *
 * Variáveis esperadas do controller:
 * - $erro                (string) Mensagem de erro, se houver
 * - $sucesso             (string) Mensagem de sucesso, se houver
 * - $total_itens         (int)    Soma total de unidades em estoque
 * - $criticos            (int)    Produtos com estoque abaixo de 10 unidades
 * - $vencidos            (int)    Produtos com validade vencida
 * - $hoje                (string) Data atual (Y-m-d) para comparação de validade
 * - $produtos            (array)  Lista de produtos com qtd_estoque e validade
 * - $armazens            (array)  Lista de armazéns para o select do formulário
 * - $estoque_por_armazem (array)  Array indexado por id_produto com saldo por armazém
 * - $historico           (array)  Últimas 20 movimentações registradas
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - Gestão Logística</title>
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

    <!-- Bloco PHP: exibe alerta de erro se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3"><i class="bi bi-x-circle-fill fs-5"></i> <?= $erro ?></div>
    <?php endif; ?>

    <!-- Bloco PHP: exibe alerta de sucesso após movimentação bem-sucedida -->
    <?php if($sucesso): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3"><i class="bi bi-check-circle-fill fs-5"></i> <?= $sucesso ?></div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI DE ESTOQUE ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Volume total de unidades em estoque -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Volume Total</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= number_format($total_itens ?? 0,0,',','.') ?> <span class="fs-6 fw-normal text-muted">un</span></h3>
        </div></div>
        <!-- KPI: Total de SKUs (produtos distintos) cadastrados -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">SKUs Cadastrados</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= count($produtos ?? []) ?></h3>
        </div></div>
        <!-- KPI: Produtos com estoque crítico (vermelho se > 0, verde se 0) -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Estoque Crítico</span>
            <h3 class="fw-black <?= $criticos>0?'text-danger':'text-success' ?> m-0 mt-1"><?= $criticos ?></h3>
        </div></div>
        <!-- KPI: Produtos com validade vencida (vermelho se > 0, verde se 0) -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Produtos Vencidos</span>
            <h3 class="fw-black <?= $vencidos>0?'text-danger':'text-success' ?> m-0 mt-1"><?= $vencidos ?></h3>
        </div></div>
    </div>

    <div class="row g-4 mb-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE MOVIMENTAÇÃO ===== -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2">
                    <i class="bi bi-arrow-left-right text-success"></i> Registrar Movimentação
                </div>

                <!-- Formulário POST para registrar entrada ou saída de estoque -->
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="movimentacao">

                    <!-- Select de produto com quantidade atual exibida entre parênteses -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Produto</label>
                        <select name="id_produto" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista produtos com saldo atual em estoque -->
                            <?php foreach($produtos ?? [] as $p): ?>
                            <option value="<?= $p['id_produto'] ?>">
                                <?= htmlspecialchars($p['descricao']) ?> (<?= number_format($p['qtd_estoque'],0,',','.') ?> un)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Select de armazém onde a movimentação será registrada -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Armazém</label>
                        <select name="id_armazem_mov" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista armazéns disponíveis -->
                            <?php foreach($armazens ?? [] as $a): ?>
                            <option value="<?= $a['id_armazem'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Select do tipo de movimentação (ENTRADA ou SAÍDA) -->
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Tipo</label>
                        <select name="tipo_movimentacao" class="form-select form-select-sm" required>
                            <option value="ENTRADA">⬆ ENTRADA</option>
                            <option value="SAIDA">⬇ SAÍDA</option>
                        </select>
                    </div>

                    <!-- Campo de quantidade a movimentar (mínimo 1) -->
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Quantidade</label>
                        <input type="number" name="quantidade" min="1" class="form-control form-control-sm" required>
                    </div>

                    <!-- Botão de confirmar a movimentação -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-check-circle-fill me-1"></i>Confirmar Movimentação
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: INVENTÁRIO E HISTÓRICO ===== -->
        <div class="col-lg-7">

            <!-- Tabela de inventário por produto com detalhe expansível por armazém -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2">
                    <i class="bi bi-grid-3x3 text-primary"></i> Inventário por Produto
                    <span class="text-muted fw-normal small ms-1">— clique para ver por armazém</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Total</th>
                                <th>Validade</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Loop PHP: itera sobre cada produto para montar a linha e o detalhe por armazém -->
                        <?php foreach($produtos ?? [] as $p):
                            /* Verifica se o produto está vencido comparando com a data atual */
                            $vencido   = !empty($p['validade']) && $p['validade'] < ($hoje ?? date('Y-m-d'));
                            /* Marca como crítico se o estoque for menor que 10 unidades */
                            $critico   = $p['qtd_estoque'] < 10;
                            /* Obtém o detalhamento do estoque deste produto por armazém */
                            $porArmaz  = $estoque_por_armazem[$p['id_produto']] ?? [];
                        ?>
                            <!-- Linha clicável que expande/colapsa o detalhe por armazém -->
                            <tr class="row-h" style="cursor:pointer"
                                data-bs-toggle="collapse" data-bs-target="#armazem-<?= $p['id_produto'] ?>">
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($p['descricao']) ?></span>
                                    <!-- Badge de alerta de estoque crítico -->
                                    <?php if($critico): ?><span class="badge bg-danger ms-1" style="font-size:9px">CRÍTICO</span><?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <!-- Badge com total em estoque (vermelho se crítico, verde se ok) -->
                                    <span class="badge rounded-pill <?= $critico?'bg-danger':'bg-success' ?>">
                                        <?= number_format($p['qtd_estoque'],0,',','.') ?> un
                                    </span>
                                </td>
                                <td>
                                    <!-- Badge de validade (vermelho se vencida, cinza se válida) -->
                                    <?php if(!empty($p['validade'])): ?>
                                        <span class="badge <?= $vencido?'bg-danger':'bg-light text-dark border' ?>" style="font-size:10px">
                                            <?= date('d/m/Y', strtotime($p['validade'])) ?>
                                        </span>
                                    <?php else: ?><small class="text-muted">—</small><?php endif; ?>
                                </td>
                            </tr>
                            <!-- Linha expansível com detalhe do estoque por armazém -->
                            <tr class="collapse" id="armazem-<?= $p['id_produto'] ?>">
                                <td colspan="3" class="bg-light py-2 px-4">
                                    <?php if(empty($porArmaz)): ?>
                                    <span class="text-muted fst-italic small">Nenhuma entrada por armazém registrada.</span>
                                    <?php else: ?>
                                    <!-- Tabela interna com o saldo por armazém -->
                                    <table class="table table-sm mb-0" style="font-size:.82rem">
                                        <thead><tr>
                                            <th class="fw-semibold text-muted">Armazém</th>
                                            <th class="text-end fw-semibold text-muted">Quantidade</th>
                                        </tr></thead>
                                        <tbody>
                                        <!-- Loop PHP: lista o saldo de cada armazém para este produto -->
                                        <?php foreach($porArmaz as $pa): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pa['armazem_nome']) ?></td>
                                            <td class="text-end fw-bold"><?= number_format($pa['quantidade'],0,',','.') ?> un</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabela de histórico das últimas 20 movimentações -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-secondary"></i> Últimas 20 Movimentações</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr><th>Produto</th><th>Armazém</th><th class="text-center">Tipo</th><th class="text-center">Qtd</th><th>Data/Hora</th></tr>
                        </thead>
                        <tbody>
                        <!-- Bloco PHP: exibe mensagem se não há movimentações -->
                        <?php if(empty($historico)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma movimentação registrada.</td></tr>
                        <!-- Loop PHP: itera sobre as últimas 20 movimentações -->
                        <?php else: foreach($historico as $h): ?>
                            <tr>
                                <!-- Coluna: descrição do produto -->
                                <td><small><?= htmlspecialchars($h['descricao']) ?></small></td>
                                <!-- Coluna: nome do armazém onde ocorreu a movimentação -->
                                <td><small class="text-muted"><?= $h['armazem_nome'] ? htmlspecialchars($h['armazem_nome']) : '—' ?></small></td>
                                <!-- Coluna: badge do tipo (verde=ENTRADA, vermelho=SAIDA) -->
                                <td class="text-center">
                                    <span class="badge <?= $h['tipo_movimentacao']=='ENTRADA'?'bg-success':'bg-danger' ?>">
                                        <?= $h['tipo_movimentacao'] == 'ENTRADA' ? '⬆' : '⬇' ?> <?= $h['tipo_movimentacao'] ?>
                                    </span>
                                </td>
                                <!-- Coluna: quantidade movimentada em negrito -->
                                <td class="text-center fw-bold"><?= number_format($h['quantidade'],0,',','.') ?></td>
                                <!-- Coluna: data e hora da movimentação formatadas -->
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['data_movimentacao'])) ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap JS para funcionalidades interativas (colapso) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
