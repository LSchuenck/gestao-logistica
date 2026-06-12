<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viagens - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css"/>
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
            <span class="text-muted small text-uppercase fw-bold">Em Trânsito</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= $em_transito ?> <span class="badge bg-primary pulse ms-1" style="font-size:10px">LIVE</span></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Concluídas</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $concluidas ?></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Canceladas</span>
            <h3 class="fw-black text-secondary m-0 mt-1"><?= $canceladas ?></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <button class="btn btn-dark w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formViagem">
                <i class="bi bi-play-circle-fill text-warning"></i> Iniciar Nova Viagem
            </button>
            <div class="collapse mb-3" id="formViagem">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-broadcast"></i> Dados da Viagem</h6>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="nova_viagem">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Rota</label>
                            <select name="id_rota" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($rotas_disponiveis as $r): ?>
                                <option value="<?= $r['id_rota'] ?>">#<?= str_pad($r['id_rota'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($r['motorista']) ?> (<?= htmlspecialchars($r['placa']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Data/Hora de Saída</label>
                            <input type="datetime-local" name="data_saida" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Previsão de Chegada</label>
                            <input type="datetime-local" name="data_chegada_prevista" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-play-fill"></i> Iniciar Viagem
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-3">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-crosshair"></i> Registrar Posição GPS</h6>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="rastreamento">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Viagem</label>
                        <select name="id_viagem" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($lista as $vi): if($vi['status'] == 'EM_TRANSITO' || $vi['status'] == 'INICIADA'): ?>
                            <option value="<?= $vi['id_viagem'] ?>">#<?= str_pad($vi['id_viagem'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($vi['motorista']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" class="form-control form-control-sm" placeholder="-21.1306" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" class="form-control form-control-sm" placeholder="-42.3662" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-geo-alt-fill"></i> Registrar Coordenada
                        </button>
                    </div>
                </form>
            </div>

            <div class="row g-2">
                <div class="col-6"><div class="card border-0 shadow-sm p-3 bg-white rounded-3 text-center">
                    <span class="text-muted small text-uppercase fw-bold">Última Lat</span>
                    <h6 class="fw-bold text-primary m-0 mt-1"><?= $ultimo_rastr ? number_format($ultimo_rastr['latitude'],4) : '—' ?></h6>
                </div></div>
                <div class="col-6"><div class="card border-0 shadow-sm p-3 bg-white rounded-3 text-center">
                    <span class="text-muted small text-uppercase fw-bold">Última Lng</span>
                    <h6 class="fw-bold text-primary m-0 mt-1"><?= $ultimo_rastr ? number_format($ultimo_rastr['longitude'],4) : '—' ?></h6>
                </div></div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-map text-warning"></i> Rastreamento em Tempo Real</span>
                    <?php if($ultimo_rastr): ?>
                    <span class="badge bg-success pulse"><i class="bi bi-circle-fill"></i> GPS Online</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Sem sinal</span>
                    <?php endif; ?>
                </div>
                <div id="mapaViagem"></div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-list-check text-warning"></i> Histórico de Viagens
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Viagem</th>
                                <th>Motorista / Veículo</th>
                                <th>Saída</th>
                                <th>Previsão</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma viagem registrada.</td></tr>
                        <?php else: foreach($lista as $vi):
                            $badge = match($vi['status']) {
                                'INICIADA'    => 'bg-warning text-dark',
                                'EM_TRANSITO' => 'bg-primary',
                                'CONCLUIDA'   => 'bg-success',
                                'CANCELADA'   => 'bg-secondary',
                                default       => 'bg-secondary'
                            };
                        ?>
                            <tr class="row-h">
                                <td><strong class="font-monospace">#<?= str_pad($vi['id_viagem'],4,'0',STR_PAD_LEFT) ?></strong><br>
                                <small class="text-muted">Rota #<?= $vi['id_rota'] ?></small></td>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($vi['motorista']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($vi['placa']) ?> — <?= htmlspecialchars($vi['tipo_veiculo']) ?></small>
                                </td>
                                <td><small><?= $vi['data_saida'] ? date('d/m/Y H:i', strtotime($vi['data_saida'])) : '—' ?></small></td>
                                <td><small><?= $vi['data_chegada_prevista'] ? date('d/m/Y H:i', strtotime($vi['data_chegada_prevista'])) : '—' ?></small></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="badge <?= $badge ?> border-0 dropdown-toggle" data-bs-toggle="dropdown" style="cursor:pointer">
                                            <?= $vi['status'] ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item small" href="?status=INICIADA&id=<?= $vi['id_viagem'] ?>">Iniciada</a></li>
                                            <li><a class="dropdown-item small" href="?status=EM_TRANSITO&id=<?= $vi['id_viagem'] ?>">Em Trânsito</a></li>
                                            <li><a class="dropdown-item small" href="?status=CONCLUIDA&id=<?= $vi['id_viagem'] ?>">Concluída</a></li>
                                            <li><a class="dropdown-item small" href="?status=CANCELADA&id=<?= $vi['id_viagem'] ?>">Cancelada</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="alertas.php?viagem=<?= $vi['id_viagem'] ?>" class="btn btn-sm btn-outline-warning px-2 me-1" title="Ver Alertas">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </a>
                                    <a href="?excluir=<?= $vi['id_viagem'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir esta viagem?')">
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
const map = L.map('mapaViagem').setView([-15.7801,-47.9292], 5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(map);

<?php if($ultimo_rastr): ?>
const lat = <?= $ultimo_rastr['latitude'] ?>;
const lng = <?= $ultimo_rastr['longitude'] ?>;
map.setView([lat, lng], 8);
L.marker([lat, lng])
    .addTo(map)
    .bindPopup('<b>&#128652; Última Posição Registrada</b><br>Viagem #<?= $ultima_viagem['id_viagem'] ?><br><?= htmlspecialchars($ultima_viagem['motorista'] ?? '') ?>')
    .openPopup();
L.circle([lat, lng], {radius: 5000, color:'#0d6efd', fillOpacity:0.1}).addTo(map);
<?php else: ?>
const cidades = [
    {n:"São Paulo",lat:-23.5505,lng:-46.6333},
    {n:"Rio de Janeiro",lat:-22.9068,lng:-43.1729},
    {n:"Belo Horizonte",lat:-19.9173,lng:-43.9345},
    {n:"Brasília",lat:-15.7801,lng:-47.9292},
    {n:"Salvador",lat:-12.9777,lng:-38.5016},
];
cidades.forEach(c => L.circleMarker([c.lat,c.lng],{radius:5,fillColor:'#0d6efd',color:'#fff',weight:2,fillOpacity:0.8}).bindPopup(c.n).addTo(map));
<?php endif; ?>
</script>
</body></html>
