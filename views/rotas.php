<?php
/**
 * View: Rotas
 *
 * Exibe o painel de planejamento e gestão de rotas do sistema.
 * Apresenta KPIs de rotas (planejadas, em andamento, finalizadas),
 * formulário para criar nova rota, formulário para vincular entregas
 * à rota, painel de entregas aguardando rota e tabela de rotas com
 * detalhe expansível das entregas vinculadas.
 *
 * Variáveis esperadas do controller:
 * - $erro               (string) Mensagem de erro, se houver
 * - $planejadas         (int)    Rotas com status PLANEJADA
 * - $em_andamento       (int)    Rotas com status EM_ANDAMENTO
 * - $finalizadas        (int)    Rotas com status FINALIZADA
 * - $motoristas         (array)  Lista de motoristas para o select
 * - $veiculos           (array)  Lista de veículos disponíveis para o select
 * - $entregas           (array)  Entregas pendentes sem rota vinculada
 * - $lista              (array)  Lista de rotas cadastradas
 * - $entregas_por_rota  (array)  Array indexado por id_rota com as entregas de cada rota
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotas - Gestão Logística</title>
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

    <!-- ===== CARDS DE KPI DE ROTAS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Rotas no status PLANEJADA -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Planejadas</span>
            <h3 class="fw-black text-warning m-0 mt-1"><?= $planejadas ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Rotas em andamento -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Andamento</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= $em_andamento ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Rotas finalizadas -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Finalizadas</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $finalizadas ?? 0 ?></h3>
        </div></div>
    </div>

    <div class="row g-4 mb-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIOS E PAINEL DE PENDÊNCIAS ===== -->
        <div class="col-lg-4">

            <!-- Botão que abre/fecha o formulário de nova rota -->
            <button class="btn btn-primary w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formRota">
                <i class="bi bi-plus-circle-fill"></i> Planejar Nova Rota
            </button>

            <!-- Formulário colapsável para criar nova rota -->
            <div class="collapse mb-3" id="formRota">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-map"></i> Dados da Rota</h6>

                    <!-- Formulário POST para criação de nova rota -->
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="nova_rota">

                        <!-- Select de motorista responsável pela rota -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Motorista</label>
                            <select name="id_motorista" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <!-- Loop PHP: lista motoristas disponíveis com nome e transportadora -->
                                <?php foreach($motoristas ?? [] as $m): ?>
                                <option value="<?= $m['id_motorista'] ?>"><?= htmlspecialchars($m['nome']) ?> (<?= htmlspecialchars($m['nome_fantasia']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Select de veículo (apenas os disponíveis) para a rota -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Veículo (Disponíveis)</label>
                            <select name="id_veiculo" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <!-- Loop PHP: lista veículos disponíveis com placa e tipo -->
                                <?php foreach($veiculos ?? [] as $v): ?>
                                <option value="<?= $v['id_veiculo'] ?>"><?= htmlspecialchars($v['placa']) ?> — <?= htmlspecialchars($v['tipo_veiculo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campo de distância total estimada em km -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Distância Total Estimada (km)</label>
                            <input type="number" step="0.1" name="distancia" class="form-control form-control-sm" placeholder="0.0">
                        </div>

                        <!-- Botão de submissão -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Criar Rota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Formulário para vincular uma entrega pendente a uma rota existente -->
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-3">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-arrow-right"></i> Vincular Entrega à Rota</h6>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="add_entrega">

                    <!-- Select de rota destino (apenas rotas não finalizadas) -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Rota</label>
                        <select name="id_rota" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista apenas rotas não finalizadas -->
                            <?php foreach($lista ?? [] as $r): if($r['status'] != 'FINALIZADA'): ?>
                            <option value="<?= $r['id_rota'] ?>">#<?= str_pad($r['id_rota'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($r['motorista']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <!-- Select de entrega pendente para vincular à rota -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Entrega (Pendentes)</label>
                        <select name="id_entrega" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista entregas pendentes sem rota -->
                            <?php foreach($entregas ?? [] as $e): ?>
                            <option value="<?= $e['id_entrega'] ?>">#<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($e['cliente']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botão de submissão para vincular entrega à rota -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-link-45deg"></i> Vincular
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bloco PHP: exibe painel de entregas aguardando rota somente se houver pendências -->
            <?php if(!empty($entregas)): ?>
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
                <div class="small fw-bold text-muted text-uppercase mb-2">
                    <i class="bi bi-clock-history text-warning"></i> Entregas Aguardando Rota
                    <!-- Badge com quantidade de entregas pendentes -->
                    <span class="badge bg-warning text-dark ms-1"><?= count($entregas) ?></span>
                </div>
                <!-- Loop PHP: lista cada entrega pendente em um item visual -->
                <div class="d-flex flex-column gap-1">
                    <?php foreach($entregas as $e): ?>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <span class="font-monospace small text-muted">#<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?></span>
                        <span class="small fw-bold"><?= htmlspecialchars($e['cliente']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE ROTAS ===== -->
        <div class="col-lg-8">
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
                        <!-- Bloco PHP: exibe mensagem se não há rotas cadastradas -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma rota planejada.</td></tr>
                        <!-- Loop PHP: itera sobre cada rota e renderiza a linha principal e a linha expansível -->
                        <?php else: foreach($lista as $r):
                            /* Define o badge de status com cor correspondente */
                            $badge = match($r['status']) {
                                'PLANEJADA'    => 'bg-warning text-dark',
                                'EM_ANDAMENTO' => 'bg-primary',
                                'FINALIZADA'   => 'bg-success',
                                default        => 'bg-secondary'
                            };
                            /* Obtém as entregas vinculadas a esta rota específica */
                            $rotaEntregas = $entregas_por_rota[$r['id_rota']] ?? [];
                        ?>
                            <!-- Linha principal da rota -->
                            <tr class="row-h">
                                <!-- Coluna: número da rota com zero-padding -->
                                <td><strong class="font-monospace">#<?= str_pad($r['id_rota'],4,'0',STR_PAD_LEFT) ?></strong></td>
                                <!-- Coluna: nome do motorista e dados do veículo -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($r['motorista']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($r['placa']) ?> — <?= htmlspecialchars($r['tipo_veiculo']) ?></small>
                                </td>
                                <!-- Coluna: distância total estimada em km -->
                                <td class="text-center">
                                    <?= $r['distancia'] ? number_format($r['distancia'],1,',','.').' km' : '—' ?>
                                </td>
                                <!-- Coluna: badge clicável que expande/colapsa o detalhe das entregas -->
                                <td class="text-center">
                                    <?php if($r['total_entregas'] > 0): ?>
                                    <a class="badge bg-secondary rounded-pill text-decoration-none"
                                       data-bs-toggle="collapse"
                                       href="#ent-<?= $r['id_rota'] ?>"
                                       style="cursor:pointer"
                                       title="Ver entregas">
                                        <?= $r['total_entregas'] ?> <i class="bi bi-chevron-down" style="font-size:.65rem"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="badge bg-light text-muted border">0</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: dropdown para alternar o status da rota via GET -->
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
                                <!-- Coluna: botões de iniciar viagem e excluir a rota -->
                                <td class="text-center d-flex gap-1 justify-content-center">
                                    <!-- Botão que redireciona para a tela de viagens com a rota pré-selecionada -->
                                    <a href="viagens.php?rota=<?= $r['id_rota'] ?>" class="btn btn-sm btn-outline-primary px-2" title="Iniciar Viagem">
                                        <i class="bi bi-broadcast"></i>
                                    </a>
                                    <!-- Botão de exclusão com confirmação JavaScript -->
                                    <a href="?excluir=<?= $r['id_rota'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir esta rota?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Linha expansível com detalhe das entregas vinculadas à rota -->
                            <?php if(!empty($rotaEntregas)): ?>
                            <tr class="p-0 border-0">
                                <td colspan="6" class="p-0 border-0">
                                    <div class="collapse" id="ent-<?= $r['id_rota'] ?>">
                                        <div class="bg-light px-4 py-2 border-bottom">
                                            <!-- Tabela interna com as entregas da rota -->
                                            <table class="table table-sm mb-0" style="font-size:.82rem">
                                                <thead>
                                                    <tr>
                                                        <th class="text-muted fw-normal">Nº Entrega</th>
                                                        <th class="text-muted fw-normal">Cliente</th>
                                                        <th class="text-muted fw-normal">Data Prevista</th>
                                                        <th class="text-muted fw-normal">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <!-- Loop PHP: itera sobre as entregas desta rota -->
                                                <?php foreach($rotaEntregas as $re):
                                                    /* Define o badge de status da entrega com cor correspondente */
                                                    $eb = match($re['status']) {
                                                        'PENDENTE'    => 'bg-warning text-dark',
                                                        'EM_TRANSITO' => 'bg-primary',
                                                        'ENTREGUE'    => 'bg-success',
                                                        'ATRASADA'    => 'bg-danger',
                                                        default       => 'bg-secondary'
                                                    };
                                                ?>
                                                <tr>
                                                    <td class="font-monospace">#<?= str_pad($re['id_entrega'],4,'0',STR_PAD_LEFT) ?></td>
                                                    <td><?= htmlspecialchars($re['cliente']) ?></td>
                                                    <td><?= $re['data_prevista'] ? date('d/m/Y', strtotime($re['data_prevista'])) : '—' ?></td>
                                                    <td><span class="badge <?= $eb ?>"><?= $re['status'] ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap JS para funcionalidades interativas (colapso, dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
