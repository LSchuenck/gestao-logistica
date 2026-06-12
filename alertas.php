<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">
    <?php if($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-x-circle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <?php if($id_viagem_filtro): ?>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-filter-circle-fill"></i>
        Exibindo alertas da Viagem #<?= str_pad($id_viagem_filtro,4,'0',STR_PAD_LEFT) ?>
        <a href="alertas.php" class="ms-auto btn btn-sm btn-outline-secondary">Ver todos</a>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total de Alertas</span>
            <h3 class="fw-black text-danger m-0 mt-1"><?= $total ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Atrasos</span>
            <h3 class="fw-black text-danger m-0 mt-1"><?= $atrasos ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Desvios de Rota</span>
            <h3 class="fw-black text-warning m-0 mt-1"><?= $desvios ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Paradas N. Prog.</span>
            <h3 class="fw-black text-secondary m-0 mt-1"><?= $paradas ?></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-danger w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formAlerta">
                <i class="bi bi-exclamation-triangle-fill"></i> Registrar Alerta
            </button>
            <div class="collapse mb-4" id="formAlerta">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-bell-fill text-danger"></i> Novo Alerta</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Viagem em Andamento</label>
                            <select name="id_viagem" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($viagens_ativas as $v): ?>
                                <option value="<?= $v['id_viagem'] ?>" <?= $id_viagem_filtro==$v['id_viagem']?'selected':'' ?>>
                                    #<?= str_pad($v['id_viagem'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($v['motorista']) ?> (<?= htmlspecialchars($v['placa']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Tipo do Alerta</label>
                            <select name="tipo_alerta" class="form-select form-select-sm" required>
                                <option value="ATRASO">⏰ Atraso na Entrega</option>
                                <option value="DESVIO_ROTA">🗺️ Desvio de Rota</option>
                                <option value="PARADA_NAO_PROGRAMADA">🛑 Parada Não Programada</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Descrição do Ocorrido</label>
                            <textarea name="descricao" class="form-control form-control-sm" rows="3" placeholder="Descreva o ocorrido..." required></textarea>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-bell-fill"></i> Registrar Alerta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card border-0 bg-dark text-white p-3 rounded-3 shadow-sm">
                <h6 class="small fw-bold text-danger mb-2"><i class="bi bi-shield-exclamation"></i> Alertas Automáticos</h6>
                <p class="m-0 text-muted" style="font-size:11px">
                    Alertas de <strong class="text-warning">ATRASO</strong> são gerados automaticamente quando a data prevista de chegada é ultrapassada.<br><br>
                    <strong class="text-warning">DESVIO DE ROTA</strong> e <strong class="text-warning">PARADA NÃO PROGRAMADA</strong> podem ser registrados manualmente pela operação.
                </p>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-bell text-danger"></i> Histórico de Alertas
                </div>
                <?php if(empty($lista)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle text-success fs-1"></i><br>
                    <strong class="d-block mt-2">Nenhum alerta registrado</strong>
                    <small>Todas as viagens estão operando normalmente.</small>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach($lista as $a):
                        $tipo_config = match($a['tipo_alerta']) {
                            'ATRASO'                  => ['bg-danger', 'bi-clock-history', 'Atraso'],
                            'DESVIO_ROTA'             => ['bg-warning text-dark', 'bi-map-fill', 'Desvio de Rota'],
                            'PARADA_NAO_PROGRAMADA'   => ['bg-secondary', 'bi-octagon-fill', 'Parada N. Prog.'],
                            default                   => ['bg-dark', 'bi-exclamation', '—']
                        };
                    ?>
                    <div class="list-group-item list-group-item-action row-h border-0 py-3 px-0">
                        <div class="d-flex align-items-start gap-3">
                            <div class="badge <?= $tipo_config[0] ?> p-2 rounded-3 fs-5">
                                <i class="bi <?= $tipo_config[1] ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge <?= $tipo_config[0] ?> me-2"><?= $tipo_config[2] ?></span>
                                        <strong>Viagem #<?= str_pad($a['id_viagem'],4,'0',STR_PAD_LEFT) ?></strong>
                                        <small class="text-muted ms-2"><?= htmlspecialchars($a['motorista']) ?> | <?= htmlspecialchars($a['placa']) ?></small>
                                    </div>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></small>
                                </div>
                                <p class="mb-0 mt-1 small text-muted"><?= htmlspecialchars($a['descricao']) ?></p>
                            </div>
                            <a href="?excluir=<?= $a['id_alerta'] ?>" class="btn btn-sm btn-outline-danger px-2"
                               onclick="return confirm('Remover este alerta?')" title="Resolver / Remover">
                                <i class="bi bi-check2"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
