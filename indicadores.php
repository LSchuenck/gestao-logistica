<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicadores Logísticos - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>

<div class="container mb-5">

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded mb-2 d-inline-block"><i class="bi bi-cash-stack fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Receita Total</span>
                <h4 class="fw-black text-primary mb-0 mt-1" style="font-size:1.1rem">R$ <?= number_format($total_frete,0,',','.') ?></h4>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="bg-danger bg-opacity-10 text-danger p-2 rounded mb-2 d-inline-block"><i class="bi bi-graph-down-arrow fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Custo Total</span>
                <h4 class="fw-black text-danger mb-0 mt-1" style="font-size:1.1rem">R$ <?= number_format($total_custo,0,',','.') ?></h4>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="bg-success bg-opacity-10 text-success p-2 rounded mb-2 d-inline-block"><i class="bi bi-check2-circle fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Entregas no Prazo</span>
                <h4 class="fw-black text-success mb-0 mt-1"><?= number_format($taxa_prazo,1,',','.') ?>%</h4>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="bg-warning bg-opacity-10 text-warning p-2 rounded mb-2 d-inline-block"><i class="bi bi-truck fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Taxa Conclusão</span>
                <h4 class="fw-black text-warning mb-0 mt-1"><?= number_format($taxa_conclusao,1,',','.') ?>%</h4>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="bg-dark bg-opacity-10 text-dark p-2 rounded mb-2 d-inline-block"><i class="bi bi-signpost-split fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">KM Percorridos</span>
                <h4 class="fw-black text-dark mb-0 mt-1"><?= number_format($km_total,0,',','.') ?></h4>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card shadow-sm h-100 p-3 bg-white">
                <div class="bg-danger bg-opacity-10 text-danger p-2 rounded mb-2 d-inline-block"><i class="bi bi-exclamation-triangle fs-4"></i></div>
                <span class="text-muted small text-uppercase fw-bold">Alertas Ativos</span>
                <h4 class="fw-black <?= $total_alertas>0?'text-danger':'text-success' ?> mb-0 mt-1"><?= $total_alertas ?></h4>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="fw-bold mb-3"><i class="bi bi-pie-chart text-primary"></i> Distribuição de Entregas por Status</div>
                <div style="height:260px"><canvas id="chartEntregas"></canvas></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm bg-white p-3 h-100">
                <div class="fw-bold mb-3"><i class="bi bi-bar-chart text-success"></i> Receita vs Custo por Transportadora</div>
                <div style="height:260px"><canvas id="chartTransp"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm bg-white p-3 mb-4">
        <div class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-trophy text-warning"></i> Desempenho por Transportadora</div>
        <?php if(empty($ranking)): ?>
        <p class="text-center text-muted py-3">Nenhum dado disponível. Cadastre transportadoras, viagens e fretes para ver os indicadores.</p>
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
                <?php foreach($ranking as $r):
                    $margem_linha = $r['receita'] > 0 ? (($r['receita'] - $r['custo']) / $r['receita']) * 100 : 0;
                    $cor_bar = 'bg-danger';
                    if ($margem_linha >= 20) $cor_bar = 'bg-warning';
                    if ($margem_linha >= 40) $cor_bar = 'bg-success';
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['nome_fantasia']) ?></strong></td>
                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $r['total_fretes'] ?></span></td>
                    <td class="text-end fw-bold text-primary">R$ <?= number_format($r['receita'],2,',','.') ?></td>
                    <td class="text-end text-danger">R$ <?= number_format($r['custo'],2,',','.') ?></td>
                    <td class="text-end fw-bold <?= $margem_linha>=30?'text-success':'text-warning' ?>"><?= number_format($margem_linha,1,',','.') ?>%</td>
                    <td style="min-width:120px">
                        <div class="d-flex align-items-center gap-2">
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

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-box-arrow-right"></i> Entregas</div>
                <div class="d-flex justify-content-between"><span class="small">Total:</span><strong><?= $total_entregas ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Entregues:</span><strong class="text-success"><?= $dist_entregas['ENTREGUE'] ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Pendentes:</span><strong class="text-warning"><?= $dist_entregas['PENDENTE'] ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Em Trânsito:</span><strong class="text-primary"><?= $dist_entregas['EM_TRANSITO'] ?? 0 ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Atrasadas:</span><strong class="text-danger"><?= $dist_entregas['ATRASADA'] ?? 0 ?></strong></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-broadcast"></i> Viagens</div>
                <div class="d-flex justify-content-between"><span class="small">Total:</span><strong><?= $total_viagens ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Concluídas:</span><strong class="text-success"><?= $viag_concluidas ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Em Andamento:</span><strong class="text-primary"><?= $pdo->query("SELECT COUNT(*) FROM viagem WHERE status IN ('INICIADA','EM_TRANSITO')")->fetchColumn() ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Canceladas:</span><strong class="text-secondary"><?= $pdo->query("SELECT COUNT(*) FROM viagem WHERE status='CANCELADA'")->fetchColumn() ?></strong></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 bg-white">
                <div class="fw-bold mb-2 small text-uppercase text-muted"><i class="bi bi-receipt"></i> Faturamento</div>
                <div class="d-flex justify-content-between"><span class="small">Fretes emitidos:</span><strong><?= $total_fretes ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Receita bruta:</span><strong class="text-primary">R$ <?= number_format($total_frete,2,',','.') ?></strong></div>
                <div class="d-flex justify-content-between"><span class="small">Custo op.:</span><strong class="text-danger">R$ <?= number_format($total_custo,2,',','.') ?></strong></div>
                <div class="d-flex justify-content-between border-top mt-2 pt-2">
                    <span class="small fw-bold">Lucro bruto:</span>
                    <strong class="<?= ($total_frete-$total_custo)>=0?'text-success':'text-danger' ?>">R$ <?= number_format($total_frete-$total_custo,2,',','.') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctxEntregas = document.getElementById('chartEntregas').getContext('2d');
new Chart(ctxEntregas, {
    type: 'doughnut',
    data: {
        labels: ['Pendente','Em Trânsito','Entregue','Atrasada'],
        datasets: [{
            data: [
                <?= $dist_entregas['PENDENTE'] ?? 0 ?>,
                <?= $dist_entregas['EM_TRANSITO'] ?? 0 ?>,
                <?= $dist_entregas['ENTREGUE'] ?? 0 ?>,
                <?= $dist_entregas['ATRASADA'] ?? 0 ?>
            ],
            backgroundColor: ['#ffc107','#0d6efd','#198754','#dc3545'],
            borderWidth: 2, hoverOffset: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    }
});

const ctxTransp = document.getElementById('chartTransp').getContext('2d');
new Chart(ctxTransp, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_grafico) ?>,
        datasets: [
            {
                label: 'Receita (R$)',
                data: <?= json_encode($dados_receita) ?>,
                backgroundColor: 'rgba(13,110,253,0.7)',
                borderColor: '#0d6efd', borderWidth: 1
            },
            {
                label: 'Custo Op. (R$)',
                data: <?= json_encode($dados_custo) ?>,
                backgroundColor: 'rgba(220,53,69,0.6)',
                borderColor: '#dc3545', borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$' + v.toLocaleString('pt-BR') } } }
    }
});
</script>
</body></html>
