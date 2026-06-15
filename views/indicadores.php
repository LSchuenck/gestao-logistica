<?php
/**
 * View: Indicadores Logísticos
 *
 * Exibe o painel de indicadores e KPIs do sistema de gestão logística.
 * Apresenta 6 KPIs principais (receita, custo, taxa de prazo, taxa de conclusão,
 * km percorridos e alertas ativos), dois gráficos Chart.js (rosca de distribuição
 * de entregas por status e barras de receita vs custo por transportadora),
 * tabela de ranking de desempenho por transportadora com barra de eficiência,
 * e três cards resumidos (entregas, viagens e faturamento).
 *
 * Variáveis esperadas do controller:
 * - $total_frete      (float)  Receita total de fretes emitidos
 * - $total_custo      (float)  Custo operacional total dos fretes
 * - $taxa_prazo       (float)  Percentual de entregas realizadas no prazo
 * - $taxa_conclusao   (float)  Percentual de rotas concluídas
 * - $km_total         (float)  Total de quilômetros percorridos em viagens
 * - $total_alertas    (int)    Quantidade de alertas ativos no momento
 * - $dist_entregas    (array)  Distribuição de entregas por status (PENDENTE, EM_TRANSITO, ENTREGUE, ATRASADA)
 * - $ranking          (array)  Desempenho por transportadora (receita, custo, total_fretes)
 * - $labels_grafico   (array)  Nomes das transportadoras para o eixo X do gráfico de barras
 * - $dados_receita    (array)  Receita por transportadora (mesmo índice que $labels_grafico)
 * - $dados_custo      (array)  Custo por transportadora (mesmo índice que $labels_grafico)
 * - $total_entregas   (int)    Total geral de entregas cadastradas
 * - $total_viagens    (int)    Total geral de viagens cadastradas
 * - $viag_concluidas  (int)    Viagens com status CONCLUIDA
 * - $viag_andamento   (int)    Viagens com status EM_TRANSITO
 * - $viag_canceladas  (int)    Viagens com status CANCELADA
 * - $total_fretes     (int)    Total de fretes emitidos
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores Logísticos - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js para renderização dos gráficos de rosca e barras -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<!-- Renderiza a barra de navegação superior do sistema -->
<?php renderNavbar(); ?>

<div class="container mb-5">

    <!-- ===== CARDS DE KPI PRINCIPAIS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Receita total de fretes emitidos -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="kpi-icon-neutral p-2 rounded mb-2 d-inline-block"><i class="bi bi-cash-stack fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Receita Total</span>
                <h4 class="fw-black text-dark mb-0 mt-1" style="font-size:1.1rem">R$ <?= number_format($total_frete ?? 0,0,',','.') ?></h4>
            </div>
        </div>
        <!-- KPI: Custo operacional total dos fretes -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="kpi-icon-neutral p-2 rounded mb-2 d-inline-block"><i class="bi bi-graph-down-arrow fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Custo Total</span>
                <h4 class="fw-black text-dark mb-0 mt-1" style="font-size:1.1rem">R$ <?= number_format($total_custo ?? 0,0,',','.') ?></h4>
            </div>
        </div>
        <!-- KPI: Taxa de entregas realizadas dentro do prazo previsto -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="kpi-icon-neutral p-2 rounded mb-2 d-inline-block"><i class="bi bi-check2-circle fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Entregas no Prazo</span>
                <h4 class="fw-black text-dark mb-0 mt-1"><?= number_format($taxa_prazo ?? 0,1,',','.') ?>%</h4>
            </div>
        </div>
        <!-- KPI: Taxa de conclusão de rotas/operações planejadas -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="kpi-icon-neutral p-2 rounded mb-2 d-inline-block"><i class="bi bi-truck fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Taxa Conclusão</span>
                <h4 class="fw-black text-dark mb-0 mt-1"><?= number_format($taxa_conclusao ?? 0,1,',','.') ?>%</h4>
            </div>
        </div>
        <!-- KPI: Total de quilômetros percorridos em todas as viagens -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="kpi-icon-neutral p-2 rounded mb-2 d-inline-block"><i class="bi bi-signpost-split fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">KM Percorridos</span>
                <h4 class="fw-black text-dark mb-0 mt-1"><?= number_format($km_total ?? 0,0,',','.') ?></h4>
            </div>
        </div>
        <!-- KPI: Total de alertas ativos -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="kpi-icon-neutral p-2 rounded mb-2 d-inline-block"><i class="bi bi-exclamation-triangle fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Alertas Ativos</span>
                <h4 class="fw-black text-dark mb-0 mt-1"><?= $total_alertas ?? 0 ?></h4>
            </div>
        </div>
    </div>

    <!-- ===== LINHA DE GRÁFICOS ===== -->
    <div class="row g-4 mb-4">
        <!-- Gráfico de rosca: distribuição de entregas por status (Chart.js) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="fw-bold mb-3"><i class="bi bi-pie-chart text-primary"></i> Distribuição de Entregas por Status</div>
                <!-- Canvas do gráfico de rosca (inicializado no script abaixo) -->
                <div style="height:260px"><canvas id="chartEntregas"></canvas></div>
            </div>
        </div>
        <!-- Gráfico de barras agrupadas: receita vs custo por transportadora (Chart.js) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="fw-bold mb-3"><i class="bi bi-bar-chart text-success"></i> Receita vs Custo por Transportadora</div>
                <!-- Canvas do gráfico de barras (inicializado no script abaixo) -->
                <div style="height:260px"><canvas id="chartTransp"></canvas></div>
            </div>
        </div>
    </div>

    <!-- ===== TABELA DE DESEMPENHO POR TRANSPORTADORA (RANKING) ===== -->
    <div class="card border-0 shadow-sm bg-white p-3 mb-4">
        <div class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-trophy text-warning"></i> Desempenho por Transportadora</div>
        <!-- Bloco PHP: exibe mensagem de instrução se não há dados no ranking -->
        <?php if(empty($ranking)): ?>
        <p class="text-center text-muted py-3">Nenhum dado disponível. Cadastre transportadoras, viagens e fretes para ver os indicadores.</p>
        <!-- Tabela de ranking: itera sobre cada transportadora com fretes registrados -->
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transportadora</th>
                        <th class="text-center">Fretes</th>
                        <th class="text-end">Receita</th>
                        <th class="text-end">Custo</th>
                        <th class="text-end">Margem</th>
                        <th>Eficiência</th>
                    </tr>
                </thead>
                <tbody>
                <!-- Loop PHP: itera sobre o ranking de transportadoras -->
                <?php foreach($ranking as $r):
                    /* Calcula a margem percentual desta transportadora */
                    $margem_linha = $r['receita'] > 0 ? (($r['receita'] - $r['custo']) / $r['receita']) * 100 : 0;
                    /* Cor da barra de eficiência: vermelho < 20%, amarelo 20–40%, verde >= 40% */
                    $cor_bar = 'bg-danger';
                    if ($margem_linha >= 20) $cor_bar = 'bg-warning';
                    if ($margem_linha >= 40) $cor_bar = 'bg-success';
                ?>
                <tr>
                    <!-- Coluna: nome fantasia da transportadora em negrito -->
                    <td><strong><?= htmlspecialchars($r['nome_fantasia']) ?></strong></td>
                    <!-- Coluna: total de fretes emitidos pela transportadora -->
                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $r['total_fretes'] ?></span></td>
                    <!-- Coluna: receita total formatada em R$ destacada em azul -->
                    <td class="text-end fw-bold text-primary">R$ <?= number_format($r['receita'],2,',','.') ?></td>
                    <!-- Coluna: custo total formatado em R$ destacado em vermelho -->
                    <td class="text-end text-danger">R$ <?= number_format($r['custo'],2,',','.') ?></td>
                    <!-- Coluna: margem percentual (verde se >= 30%, amarelo se < 30%) -->
                    <td class="text-end fw-bold <?= $margem_linha>=30?'text-success':'text-warning' ?>"><?= number_format($margem_linha,1,',','.') ?>%</td>
                    <!-- Coluna: barra de progresso de eficiência com a margem percentual -->
                    <td style="min-width:120px">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Barra de progresso com largura proporcional à margem (máx. 100%) -->
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar <?= $cor_bar ?>" style="width:<?= min(100,$margem_linha) ?>%"></div>
                            </div>
                            <small class="text-muted fw-bold"><?= number_format($margem_linha,0) ?>%</small>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== CARDS RESUMIDOS DE ENTREGAS, VIAGENS E FATURAMENTO ===== -->
    <div class="row g-3">
        <!-- Card resumido de entregas por status -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-box-arrow-right"></i> Entregas</div>
                <div class="d-flex justify-content-between"><span class="small">Total:</span><strong><?= $total_entregas ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Entregues:</span><strong class="text-success"><?= $dist_entregas['ENTREGUE'] ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Pendentes:</span><strong class="text-warning"><?= $dist_entregas['PENDENTE'] ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Em Trânsito:</span><strong class="text-primary"><?= $dist_entregas['EM_TRANSITO'] ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Atrasadas:</span><strong class="text-danger"><?= $dist_entregas['ATRASADA'] ?? 0 ?></strong></div>
            </div>
        </div>
        <!-- Card resumido de viagens por status -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-broadcast"></i> Viagens</div>
                <div class="d-flex justify-content-between"><span class="small">Total:</span><strong><?= $total_viagens ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Concluídas:</span><strong class="text-success"><?= $viag_concluidas ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Em Andamento:</span><strong class="text-primary"><?= $viag_andamento ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Canceladas:</span><strong class="text-secondary"><?= $viag_canceladas ?? 0 ?></strong></div>
            </div>
        </div>
        <!-- Card resumido de faturamento com lucro bruto calculado -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-receipt"></i> Faturamento</div>
                <div class="d-flex justify-content-between"><span class="small">Fretes emitidos:</span><strong><?= $total_fretes ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Receita bruta:</span><strong class="text-primary">R$ <?= number_format($total_frete ?? 0,2,',','.') ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Custo op.:</span><strong class="text-danger">R$ <?= number_format($total_custo ?? 0,2,',','.') ?></strong></div>
                <!-- Linha separadora com o lucro bruto (verde se positivo, vermelho se negativo) -->
                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                    <span class="small fw-bold">Lucro bruto:</span>
                    <strong class="<?= (($total_frete??0)-($total_custo??0))>=0?'text-success':'text-danger' ?>">R$ <?= number_format(($total_frete??0)-($total_custo??0),2,',','.') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ===== GRÁFICO DE ROSCA: DISTRIBUIÇÃO DE ENTREGAS POR STATUS ===== */
const ctxEntregas = document.getElementById('chartEntregas').getContext('2d');
new Chart(ctxEntregas, {
    type: 'doughnut', // Tipo rosca para mostrar proporções de status
    data: {
        labels: ['Pendente','Em Trânsito','Entregue','Atrasada'],
        datasets: [{
            /* Dados injetados pelo PHP com as contagens por status */
            data: [
                <?= $dist_entregas['PENDENTE'] ?? 0 ?>,
                <?= $dist_entregas['EM_TRANSITO'] ?? 0 ?>,
                <?= $dist_entregas['ENTREGUE'] ?? 0 ?>,
                <?= $dist_entregas['ATRASADA'] ?? 0 ?>
            ],
            /* Cores correspondentes: amarelo=Pendente, azul=Em Trânsito, verde=Entregue, vermelho=Atrasada */
            backgroundColor: ['#ffc107','#0d6efd','#198754','#dc3545'],
            borderWidth: 2, hoverOffset: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    }
});

/* ===== GRÁFICO DE BARRAS AGRUPADAS: RECEITA VS CUSTO POR TRANSPORTADORA ===== */
const ctxTransp = document.getElementById('chartTransp').getContext('2d');
new Chart(ctxTransp, {
    type: 'bar', // Tipo barras para comparar receita e custo por transportadora
    data: {
        /* Rótulos do eixo X: nomes das transportadoras injetados pelo PHP */
        labels: <?= json_encode($labels_grafico ?? []) ?>,
        datasets: [
            {
                label: 'Receita (R$)',
                /* Dados de receita por transportadora injetados pelo PHP */
                data: <?= json_encode($dados_receita ?? []) ?>,
                backgroundColor: 'rgba(13,110,253,0.7)', // Azul Bootstrap
                borderColor: '#0d6efd', borderWidth: 1
            },
            {
                label: 'Custo Op. (R$)',
                /* Dados de custo operacional por transportadora injetados pelo PHP */
                data: <?= json_encode($dados_custo ?? []) ?>,
                backgroundColor: 'rgba(220,53,69,0.6)', // Vermelho Bootstrap
                borderColor: '#dc3545', borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            y: {
                beginAtZero: true,
                /* Formata os valores do eixo Y com prefixo R$ e separador de milhar pt-BR */
                ticks: { callback: v => 'R$' + v.toLocaleString('pt-BR') }
            }
        }
    }
});
</script>
</body></html>
