<?php
/**
 * View: Viagens
 *
 * Exibe o painel de gestão e rastreamento de viagens do sistema.
 * Apresenta KPIs (em trânsito, concluídas, canceladas), formulário para
 * iniciar nova viagem, formulário para registrar posição GPS, mapa Leaflet
 * com a última coordenada registrada (ou marcadores de cidades) e tabela
 * de histórico de viagens com dropdown de status.
 *
 * Variáveis esperadas do controller:
 * - $erro                  (string) Mensagem de erro, se houver
 * - $em_transito           (int)    Viagens com status EM_TRANSITO
 * - $concluidas            (int)    Viagens com status CONCLUIDA
 * - $canceladas            (int)    Viagens com status CANCELADA
 * - $rotas_disponiveis     (array)  Rotas disponíveis para iniciar viagem
 * - $lista                 (array)  Lista de viagens cadastradas
 * - $ultimo_rastr          (array)  Última coordenada GPS registrada (latitude/longitude)
 * - $ultima_viagem         (array)  Dados da viagem com a última coordenada GPS
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viagens - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS para exibição do mapa interativo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"/>
    <!-- Leaflet Routing Machine CSS para traçar rotas no mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css"/>
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

    <!-- ===== CARDS DE KPI DE VIAGENS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Viagens atualmente em trânsito com indicador LIVE -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Trânsito</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= $em_transito ?? 0 ?> <span class="badge bg-primary pulse ms-1" style="font-size:10px">LIVE</span></h3>
        </div></div>
        <!-- KPI: Total de viagens concluídas -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Concluídas</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $concluidas ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Total de viagens canceladas -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Canceladas</span>
            <h3 class="fw-black text-secondary m-0 mt-1"><?= $canceladas ?? 0 ?></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIOS DE VIAGEM E GPS ===== -->
        <div class="col-lg-4">

            <!-- Botão que abre/fecha o formulário de nova viagem -->
            <button class="btn btn-dark w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formViagem">
                <i class="bi bi-play-circle-fill text-warning"></i> Iniciar Nova Viagem
            </button>

            <!-- Formulário colapsável para iniciar uma nova viagem -->
            <div class="collapse mb-3" id="formViagem">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-broadcast"></i> Dados da Viagem</h6>

                    <!-- Formulário POST para iniciar nova viagem -->
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="nova_viagem">

                        <!-- Select de rota disponível para a viagem -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Rota</label>
                            <select name="id_rota" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <!-- Loop PHP: lista rotas disponíveis com motorista e placa -->
                                <?php foreach($rotas_disponiveis ?? [] as $r): ?>
                                <option value="<?= $r['id_rota'] ?>">#<?= str_pad($r['id_rota'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($r['motorista']) ?> (<?= htmlspecialchars($r['placa']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campo de data e hora de saída do veículo -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Data/Hora de Saída</label>
                            <input type="datetime-local" name="data_saida" class="form-control form-control-sm" required>
                        </div>

                        <!-- Campo de previsão de chegada ao destino -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Previsão de Chegada</label>
                            <input type="datetime-local" name="data_chegada_prevista" class="form-control form-control-sm" required>
                        </div>

                        <!-- Botão de submissão para iniciar a viagem -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-play-fill"></i> Iniciar Viagem
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Formulário para registrar manualmente uma posição GPS de uma viagem ativa -->
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-3">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-crosshair"></i> Registrar Posição GPS</h6>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="rastreamento">

                    <!-- Select de viagem ativa (EM_TRANSITO ou INICIADA) para registrar GPS -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Viagem</label>
                        <select name="id_viagem" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista apenas viagens em andamento -->
                            <?php foreach($lista ?? [] as $vi): if($vi['status'] == 'EM_TRANSITO' || $vi['status'] == 'INICIADA'): ?>
                            <option value="<?= $vi['id_viagem'] ?>">#<?= str_pad($vi['id_viagem'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($vi['motorista']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <!-- Campos de latitude e longitude para registro de posição -->
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" class="form-control form-control-sm" placeholder="-21.1306" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" class="form-control form-control-sm" placeholder="-42.3662" required>
                    </div>

                    <!-- Botão de submissão para registrar a coordenada GPS -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-geo-alt-fill"></i> Registrar Coordenada
                        </button>
                    </div>
                </form>
            </div>

            <!-- Cards informativos com a última posição GPS registrada -->
            <div class="row g-2">
                <!-- Card com a última latitude registrada -->
                <div class="col-6"><div class="card border-0 shadow-sm p-3 bg-white rounded-3 text-center">
                    <span class="text-muted small text-uppercase fw-bold">Última Lat</span>
                    <h6 class="fw-bold text-primary m-0 mt-1"><?= $ultimo_rastr ? number_format($ultimo_rastr['latitude'],4) : '—' ?></h6>
                </div></div>
                <!-- Card com a última longitude registrada -->
                <div class="col-6"><div class="card border-0 shadow-sm p-3 bg-white rounded-3 text-center">
                    <span class="text-muted small text-uppercase fw-bold">Última Lng</span>
                    <h6 class="fw-bold text-primary m-0 mt-1"><?= $ultimo_rastr ? number_format($ultimo_rastr['longitude'],4) : '—' ?></h6>
                </div></div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: MAPA E TABELA DE VIAGENS ===== -->
        <div class="col-lg-8">

            <!-- Card do mapa de rastreamento em tempo real -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark"><i class="bi bi-map text-warning"></i> Rastreamento em Tempo Real</span>
                    <!-- Badge de status do GPS (online se houver coordenada, offline caso contrário) -->
                    <?php if($ultimo_rastr): ?>
                    <span class="badge bg-success pulse"><i class="bi bi-circle-fill"></i> GPS Online</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Sem sinal</span>
                    <?php endif; ?>
                </div>
                <!-- Contêiner do mapa Leaflet (inicializado via JavaScript abaixo) -->
                <div id="mapaViagem"></div>
            </div>

            <!-- Card da tabela de histórico de viagens -->
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
                        <!-- Bloco PHP: exibe mensagem se não há viagens registradas -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma viagem registrada.</td></tr>
                        <!-- Loop PHP: itera sobre cada viagem e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $vi):
                            /* Define o badge de status da viagem com cor correspondente */
                            $badge = match($vi['status']) {
                                'INICIADA'    => 'bg-warning text-dark',
                                'EM_TRANSITO' => 'bg-primary',
                                'CONCLUIDA'   => 'bg-success',
                                'CANCELADA'   => 'bg-secondary',
                                default       => 'bg-secondary'
                            };
                        ?>
                            <tr class="row-h">
                                <!-- Coluna: número da viagem e rota vinculada -->
                                <td><strong class="font-monospace">#<?= str_pad($vi['id_viagem'],4,'0',STR_PAD_LEFT) ?></strong><br>
                                <small class="text-muted">Rota #<?= $vi['id_rota'] ?></small></td>
                                <!-- Coluna: motorista e dados do veículo -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($vi['motorista']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($vi['placa']) ?> — <?= htmlspecialchars($vi['tipo_veiculo']) ?></small>
                                </td>
                                <!-- Coluna: data/hora de saída formatada -->
                                <td><small><?= $vi['data_saida'] ? date('d/m/Y H:i', strtotime($vi['data_saida'])) : '—' ?></small></td>
                                <!-- Coluna: previsão de chegada e chegada real (se disponível) -->
                                <td>
                                    <small><?= $vi['data_chegada_prevista'] ? date('d/m/Y H:i', strtotime($vi['data_chegada_prevista'])) : '—' ?></small>
                                    <?php if(!empty($vi['data_chegada_real'])): ?>
                                    <br><small class="text-success"><i class="bi bi-check-circle-fill"></i> <?= date('d/m/Y H:i', strtotime($vi['data_chegada_real'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: dropdown para alternar o status da viagem via GET -->
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
                                <!-- Coluna: botões de ver alertas e excluir viagem -->
                                <td class="text-center">
                                    <!-- Botão que redireciona para os alertas desta viagem -->
                                    <a href="alertas.php?viagem=<?= $vi['id_viagem'] ?>" class="btn btn-sm btn-outline-warning px-2 me-1" title="Ver Alertas">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </a>
                                    <!-- Botão de exclusão com confirmação JavaScript -->
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

<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS para renderização do mapa interativo -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ===== INICIALIZAÇÃO DO MAPA LEAFLET ===== */

/* Cria o mapa centralizado no Brasil (Brasília) com zoom 5 */
const map = L.map('mapaViagem').setView([-15.7801,-47.9292], 5);

/* Adiciona o tile layer do OpenStreetMap como fundo do mapa */
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(map);

<?php if($ultimo_rastr): ?>
/* Bloco PHP: se houver última posição GPS registrada, centraliza o mapa nela */
const lat = <?= $ultimo_rastr['latitude'] ?>;
const lng = <?= $ultimo_rastr['longitude'] ?>;

/* Reposiciona o mapa para a última coordenada com zoom 8 */
map.setView([lat, lng], 8);

/* Adiciona marcador na última posição com popup de informações da viagem */
L.marker([lat, lng])
    .addTo(map)
    .bindPopup('<b>&#128652; Última Posição Registrada</b><br>Viagem #<?= $ultima_viagem['id_viagem'] ?? [] ?><br><?= htmlspecialchars($ultima_viagem['motorista'] ?? '') ?>')
    .openPopup();

/* Adiciona círculo de área ao redor da última posição (raio de 5km) */
L.circle([lat, lng], {radius: 5000, color:'#0d6efd', fillOpacity:0.1}).addTo(map);

<?php else: ?>
/* Bloco PHP: sem posição GPS — exibe marcadores das principais cidades do Brasil */
const cidades = [
    {n:"São Paulo",lat:-23.5505,lng:-46.6333},
    {n:"Rio de Janeiro",lat:-22.9068,lng:-43.1729},
    {n:"Belo Horizonte",lat:-19.9173,lng:-43.9345},
    {n:"Brasília",lat:-15.7801,lng:-47.9292},
    {n:"Salvador",lat:-12.9777,lng:-38.5016},
];
/* Adiciona marcadores circulares azuis para cada cidade de referência */
cidades.forEach(c => L.circleMarker([c.lat,c.lng],{radius:5,fillColor:'#0d6efd',color:'#fff',weight:2,fillOpacity:0.8}).bindPopup(c.n).addTo(map));
<?php endif; ?>
</script>
</body></html>
