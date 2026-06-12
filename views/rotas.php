<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotas - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Planejadas</span>
            <h3 class="fw-black text-warning m-0 mt-1"><?= $planejadas ?></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Andamento</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= $em_andamento ?></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Finalizadas</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $finalizadas ?></h3>
        </div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <button class="btn btn-primary w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formRota">
                <i class="bi bi-plus-circle-fill"></i> Planejar Nova Rota
            </button>
            <div class="collapse mb-3" id="formRota">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-map"></i> Dados da Rota</h6>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="nova_rota">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Motorista</label>
                            <select name="id_motorista" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($motoristas as $m): ?>
                                <option value="<?= $m['id_motorista'] ?>"><?= htmlspecialchars($m['nome']) ?> (<?= htmlspecialchars($m['nome_fantasia']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Veículo (Disponíveis)</label>
                            <select name="id_veiculo" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($veiculos as $v): ?>
                                <option value="<?= $v['id_veiculo'] ?>"><?= htmlspecialchars($v['placa']) ?> — <?= htmlspecialchars($v['tipo_veiculo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Distância Total Estimada (km)</label>
                            <input type="number" step="0.1" name="distancia" class="form-control form-control-sm" placeholder="0.0">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Criar Rota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-3">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-arrow-right"></i> Vincular Entrega à Rota</h6>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="add_entrega">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Rota</label>
                        <select name="id_rota" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($lista as $r): if($r['status'] != 'FINALIZADA'): ?>
                            <option value="<?= $r['id_rota'] ?>">#<?= str_pad($r['id_rota'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($r['motorista']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Entrega (Pendentes)</label>
                        <select name="id_entrega" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($entregas as $e): ?>
                            <option value="<?= $e['id_entrega'] ?>">#<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($e['cliente']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-link-45deg"></i> Vincular
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="fw-bold text-dark mb-2"><i class="bi bi-map text-primary"></i> Mapa de Referência</div>
                <div id="mapaRotas"></div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-list-check text-primary"></i> Rotas Planejadas
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rota</th>
                                <th>Motorista / Veículo</th>
                                <th class="text-center">Distância</th>
                                <th class="text-center">Entregas</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma rota planejada.</td></tr>
                        <?php else: foreach($lista as $r):
                            $badge = match($r['status']) {
                                'PLANEJADA'    => 'bg-warning text-dark',
                                'EM_ANDAMENTO' => 'bg-primary',
                                'FINALIZADA'   => 'bg-success',
                                default        => 'bg-secondary'
                            };
                        ?>
                            <tr class="row-h">
                                <td><strong class="font-monospace">#<?= str_pad($r['id_rota'],4,'0',STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($r['motorista']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($r['placa']) ?> — <?= htmlspecialchars($r['tipo_veiculo']) ?></small>
                                </td>
                                <td class="text-center">
                                    <?= $r['distancia'] ? number_format($r['distancia'],1,',','.').' km' : '—' ?>
                                </td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $r['total_entregas'] ?></span></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="badge <?= $badge ?> border-0 dropdown-toggle" data-bs-toggle="dropdown" style="cursor:pointer">
                                            <?= str_replace('_',' ',$r['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item small" href="?status=PLANEJADA&id=<?= $r['id_rota'] ?>">Planejada</a></li>
                                            <li><a class="dropdown-item small" href="?status=EM_ANDAMENTO&id=<?= $r['id_rota'] ?>">Em Andamento</a></li>
                                            <li><a class="dropdown-item small" href="?status=FINALIZADA&id=<?= $r['id_rota'] ?>">Finalizada</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="viagens.php?rota=<?= $r['id_rota'] ?>" class="btn btn-sm btn-outline-primary px-2 me-1" title="Iniciar Viagem">
                                        <i class="bi bi-broadcast"></i>
                                    </a>
                                    <a href="?excluir=<?= $r['id_rota'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir esta rota?')">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('mapaRotas').setView([-15.7801, -47.9292], 5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);
const estados = [
    {nome:"São Paulo",lat:-23.5505,lng:-46.6333},
    {nome:"Rio de Janeiro",lat:-22.9068,lng:-43.1729},
    {nome:"Belo Horizonte",lat:-19.9173,lng:-43.9345},
    {nome:"Brasília",lat:-15.7801,lng:-47.9292},
    {nome:"Salvador",lat:-12.9777,lng:-38.5016},
    {nome:"Curitiba",lat:-25.4372,lng:-49.2699},
    {nome:"Manaus",lat:-3.1190,lng:-60.0217},
];
estados.forEach(c => {
    L.circleMarker([c.lat,c.lng],{radius:6,fillColor:'#0d6efd',color:'#fff',weight:2,fillOpacity:0.9})
        .bindPopup(`<b>${c.nome}</b>`)
        .addTo(map);
});
</script>
</body></html>
