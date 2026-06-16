<?php
/**
 * View: Operações
 *
 * Exibe o painel de gerenciamento de operações logísticas (rotas e viagens integradas).
 * Apresenta KPIs (planejadas, em andamento, finalizadas, aguardando rota), formulário
 * para planejar nova operação com seleção dinâmica de motorista/veículo por transportadora,
 * formulário para vincular entrega pendente a uma operação planejada, painel de entregas
 * sem rota e cards de cada operação com mapa Leaflet do trajeto e sub-tabela de entregas.
 *
 * Variáveis esperadas do controller:
 * - $erro                (string) Mensagem de erro, se houver
 * - $transportadoras     (array)  Lista de transportadoras com motoristas e veículos
 * - $motoristas          (array)  Todos os motoristas (serializado como JSON no data-attr)
 * - $veiculos            (array)  Todos os veículos disponíveis (serializado como JSON)
 * - $entregas_pendentes  (array)  Entregas sem rota vinculada
 * - $lista               (array)  Lista de operações (rotas) cadastradas
 * - $entregas_por_rota   (array)  Array indexado por id_rota com as entregas vinculadas
 * - $planejadas          (int)    Quantidade de operações com status PLANEJADA
 * - $em_andamento        (int)    Quantidade de operações com status EM_ANDAMENTO
 * - $finalizadas         (int)    Quantidade de operações com status FINALIZADA
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operações - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS para exibição do mapa de trajeto de cada operação -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body class="bg-light">
<!-- Renderiza a barra de navegação superior do sistema -->
<?php exibirNavegacao(); ?>
<div class="container-fluid px-4 mb-5">

    <!-- Bloco PHP: exibe alerta de aviso se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI DE OPERAÇÕES ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Operações planejadas (pendentes de iniciar) -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <span class="text-muted small text-uppercase fw-bold">Planejadas</span>
                <h3 class="fw-black text-dark m-0 mt-1"><?= $planejadas ?></h3>
            </div>
        </div>
        <!-- KPI: Operações atualmente em andamento -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <span class="text-muted small text-uppercase fw-bold">Em Andamento</span>
                <h3 class="fw-black text-dark m-0 mt-1"><?= $em_andamento ?></h3>
            </div>
        </div>
        <!-- KPI: Operações já finalizadas -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <span class="text-muted small text-uppercase fw-bold">Finalizadas</span>
                <h3 class="fw-black text-dark m-0 mt-1"><?= $finalizadas ?></h3>
            </div>
        </div>
        <!-- KPI: Entregas pendentes sem operação vinculada -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100">
                <span class="text-muted small text-uppercase fw-bold">Aguardando Rota</span>
                <h3 class="fw-black text-dark m-0 mt-1"><?= count($entregas_pendentes) ?></h3>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">

        <!-- ===== PAINEL ESQUERDO: FORMULÁRIOS E PAINEL DE ENTREGAS PENDENTES ===== -->
        <div class="col-lg-4">

            <!-- Botão que abre/fecha o formulário de nova operação -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm"
                    type="button" data-bs-toggle="collapse" data-bs-target="#formNovaOp">
                <i class="bi bi-plus-circle-fill"></i> Nova Operação
            </button>

            <!-- Formulário colapsável para planejar nova operação logística -->
            <div class="collapse mb-3" id="formNovaOp">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-truck me-1"></i>Planejar Operação</h6>

                    <!-- Formulário POST para criar nova rota/operação -->
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="nova_rota">

                        <!-- Select de transportadora (filtra motoristas/veículos via JS) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Transportadora</label>
                            <select id="sel-transportadora" class="form-select form-select-sm">
                                <option value="">Selecione a transportadora...</option>
                                <!-- Loop PHP: lista as transportadoras disponíveis -->
                                <?php foreach($transportadoras as $t): ?>
                                <option value="<?= $t['id_transportadora'] ?>">
                                    <?= htmlspecialchars($t['nome_fantasia']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Select de motorista (habilitado via JS após seleção da transportadora) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Motorista</label>
                            <select id="sel-motorista" name="id_motorista" class="form-select form-select-sm" required disabled>
                                <option value="">Selecione a transportadora primeiro</option>
                            </select>
                        </div>

                        <!-- Select de veículo disponível (habilitado via JS após seleção da transportadora) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Veículo (Disponíveis)</label>
                            <select id="sel-veiculo" name="id_veiculo" class="form-select form-select-sm" required disabled>
                                <option value="">Selecione a transportadora primeiro</option>
                            </select>
                        </div>

                        <!-- Seção de seleção de entregas (exibida via JS após selecionar transportadora) -->
                        <div class="col-12" id="entregas-form-section" style="display:none">
                            <label class="small fw-bold text-muted">Entregas a incluir</label>
                            <!-- Lista dinâmica com checkboxes das entregas pendentes -->
                            <div id="lista-entregas-form"
                                 class="border rounded p-2 overflow-auto"
                                 style="max-height:130px;font-size:.82rem;background:#fff">
                            </div>
                            <!-- Botão que calcula o trajeto e km da rota no mapa do formulário -->
                            <button type="button" id="btn-calc-form"
                                    onclick="calcularTrajetoForm()"
                                    class="btn btn-outline-secondary btn-sm w-100 mt-2">
                                <i class="bi bi-map me-1"></i>Calcular km da Rota
                            </button>
                            <!-- Mapa do trajeto calculado no formulário (exibido após calcular) -->
                            <div id="mapa-form-container" style="display:none" class="mt-2">
                                <div id="mapa-form" style="height:220px;border-radius:8px"></div>
                                <div id="mapa-form-info"></div>
                            </div>
                        </div>

                        <!-- Campo manual de distância estimada (ou preenchido pelo cálculo do mapa) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Distância Estimada (km)</label>
                            <input type="number" step="0.1" min="0" name="distancia" id="input-distancia-form"
                                   class="form-control form-control-sm" placeholder="Calculável pelo mapa acima">
                        </div>

                        <!-- Aviso de excesso de carga (exibido dinamicamente pelo script.js) -->
                        <div id="aviso-peso" class="alert alert-danger py-2 small mb-0" style="display:none"></div>

                        <!-- Botão de submissão para criar a operação -->
                        <div class="col-12">
                            <button type="submit" id="btn-criar-op" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle me-1"></i>Criar Operação
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card para vincular entrega pendente a uma operação planejada -->
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-3">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-link-45deg me-1"></i>Adicionar Entrega</h6>
                <?php
                /* Filtra apenas as operações com status PLANEJADA para receber novas entregas */
                $rotas_planejadas = array_values(array_filter($lista, fn($op) => $op['status'] === 'PLANEJADA'));
                ?>
                <!-- Bloco PHP: exibe mensagem se não há operações planejadas -->
                <?php if(empty($rotas_planejadas)): ?>
                <p class="text-muted small mb-0 fst-italic">Nenhuma operação planejada disponível.</p>
                <!-- Bloco PHP: exibe mensagem se não há entregas pendentes para vincular -->
                <?php elseif(empty($entregas_pendentes)): ?>
                <p class="text-muted small mb-0 fst-italic">Nenhuma entrega pendente sem rota.</p>
                <!-- Formulário POST para vincular entrega a uma operação planejada -->
                <?php else: ?>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="add_entrega">

                    <!-- Select de operação planejada disponível -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Operação</label>
                        <select name="id_rota" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista operações planejadas com motorista -->
                            <?php foreach($rotas_planejadas as $op): ?>
                            <option value="<?= $op['id_rota'] ?>">
                                #<?= str_pad($op['id_rota'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($op['motorista']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Select de entrega pendente a ser vinculada -->
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Entrega</label>
                        <select name="id_entrega" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <!-- Loop PHP: lista entregas pendentes com data prevista -->
                            <?php foreach($entregas_pendentes as $e): ?>
                            <option value="<?= $e['id_entrega'] ?>">
                                #<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($e['cliente']) ?>
                                <?= $e['data_prevista'] ? ' · '.date('d/m/Y', strtotime($e['data_prevista'])) : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botão de submissão para adicionar entrega à operação selecionada -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-plus-lg me-1"></i>Adicionar à Operação
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <!-- Painel lateral com a lista de entregas aguardando rota (se houver) -->
            <?php if(!empty($entregas_pendentes)): ?>
            <div class="card border-0 shadow-sm p-3 rounded-4 border-start border-3 border-danger">
                <div class="small fw-bold text-muted text-uppercase mb-2">
                    <i class="bi bi-clock-history text-danger me-1"></i> Aguardando Rota
                    <span class="badge bg-danger ms-1"><?= count($entregas_pendentes) ?></span>
                </div>
                <!-- Loop PHP: lista cada entrega pendente com número, cliente e data prevista -->
                <div class="d-flex flex-column gap-1">
                    <?php foreach($entregas_pendentes as $e): ?>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <span class="font-monospace text-muted" style="font-size:.78rem">
                            #<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?>
                        </span>
                        <span class="small fw-bold flex-grow-1"><?= htmlspecialchars($e['cliente']) ?></span>
                        <!-- Badge com a data prevista da entrega (se definida) -->
                        <?php if($e['data_prevista']): ?>
                        <span class="badge bg-light text-muted border" style="font-size:.7rem">
                            <?= date('d/m', strtotime($e['data_prevista'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ===== COLUNA DIREITA: CARDS DE CADA OPERAÇÃO ===== -->
        <div class="col-lg-8">
            <!-- Bloco PHP: exibe mensagem se não há operações cadastradas -->
            <?php if(empty($lista)): ?>
            <div class="card border-0 shadow-sm p-5 text-center text-muted rounded-4">
                <i class="bi bi-truck fs-1 d-block mb-2 opacity-25"></i>
                Nenhuma operação cadastrada. Crie uma nova operação ao lado.
            </div>
            <!-- Loop PHP: itera sobre cada operação e renderiza seu card -->
            <?php else: foreach($lista as $op):
                $statusRota = $op['status'];

                /* Define label, badge, borda e fundo do cabeçalho conforme o status da operação */
                if ($statusRota === 'EM_ANDAMENTO') {
                    $labelStatus = 'EM ANDAMENTO';
                    $badgeClass  = 'bg-primary';
                    $borderLeft  = 'border-primary';
                    $headerBg    = 'bg-primary bg-opacity-10';
                } elseif ($statusRota === 'FINALIZADA') {
                    $labelStatus = 'CONCLUÍDA';
                    $badgeClass  = 'bg-success';
                    $borderLeft  = 'border-success';
                    $headerBg    = 'bg-success bg-opacity-10';
                } else {
                    $labelStatus = 'PLANEJADA';
                    $badgeClass  = 'bg-warning text-dark';
                    $borderLeft  = 'border-warning';
                    $headerBg    = 'bg-warning bg-opacity-10';
                }

                /* Obtém as entregas vinculadas a esta operação */
                $rotaEntregas = $entregas_por_rota[$op['id_rota']] ?? [];
            ?>
            <!-- Card individual de operação com cor da borda esquerda por status -->
            <div class="card border-0 border-start border-3 <?= $borderLeft ?> shadow-sm rounded-4 mb-3 op-card">

                <!-- Cabeçalho do card com número, badge de status e botão de exclusão (se planejada) -->
                <div class="<?= $headerBg ?> px-4 py-3 rounded-top-4 d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <!-- Número formatado da operação (ex: #0001) -->
                        <strong class="font-monospace">#<?= str_pad($op['id_rota'],4,'0',STR_PAD_LEFT) ?></strong>
                        <!-- Badge com o status da operação -->
                        <span class="badge <?= $badgeClass ?> rounded-pill"><?= $labelStatus ?></span>
                        <!-- Exibe o número da viagem vinculada (se houver) -->
                        <?php if($op['id_viagem']): ?>
                        <span class="text-muted small">
                            <i class="bi bi-broadcast me-1"></i>Viagem #<?= str_pad($op['id_viagem'],4,'0',STR_PAD_LEFT) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <!-- Botão de exclusão (disponível apenas para operações planejadas) -->
                    <?php if($statusRota === 'PLANEJADA'): ?>
                    <a href="?excluir_rota=<?= $op['id_rota'] ?>"
                       class="btn btn-sm btn-outline-danger border-0 px-2"
                       onclick="return confirm('Excluir esta operação?')"
                       title="Excluir operação">
                        <i class="bi bi-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="px-4 py-3">

                    <!-- Bloco de informações do motorista e veículo da operação -->
                    <div class="row g-2 mb-3">
                        <!-- Info do motorista com avatar circular e ícone de pessoa -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                     style="width:36px;height:36px;flex-shrink:0">
                                    <i class="bi bi-person-fill text-secondary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small"><?= htmlspecialchars($op['motorista']) ?></div>
                                    <div class="text-muted" style="font-size:.73rem">Motorista</div>
                                </div>
                            </div>
                        </div>
                        <!-- Info do veículo com placa, tipo e distância estimada -->
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                     style="width:36px;height:36px;flex-shrink:0">
                                    <i class="bi bi-truck text-secondary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small"><?= htmlspecialchars($op['placa']) ?> · <?= htmlspecialchars($op['tipo_veiculo']) ?></div>
                                    <div class="text-muted" style="font-size:.73rem">
                                        Veículo<?= $op['distancia'] ? ' · <strong>'.number_format($op['distancia'],1,',','.').' km</strong>' : '' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloco de entregas vinculadas à operação com opção de detalhar -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-muted text-uppercase">
                                <i class="bi bi-boxes me-1"></i>Entregas
                                <span class="badge bg-secondary rounded-pill ms-1"><?= $op['total_entregas'] ?></span>
                            </span>
                            <!-- Link para colapsar/expandir a sub-tabela de entregas -->
                            <?php if($op['total_entregas'] > 0): ?>
                            <a href="#ent-<?= $op['id_rota'] ?>" data-bs-toggle="collapse"
                               class="text-muted small text-decoration-none">
                                <i class="bi bi-chevron-down" style="font-size:.75rem"></i> detalhar
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Sub-tabela expansível com as entregas desta operação -->
                        <?php if(!empty($rotaEntregas)): ?>
                        <div class="collapse" id="ent-<?= $op['id_rota'] ?>">
                            <div class="rounded-3 overflow-hidden border">
                                <table class="table table-sm mb-0" style="font-size:.82rem">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Cliente</th>
                                            <th>Previsto</th>
                                            <th class="text-center pe-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Loop PHP: itera sobre cada entrega da operação -->
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
                                        <!-- Coluna: número da entrega em fonte monoespaçada -->
                                        <td class="ps-3 font-monospace text-muted" style="font-size:.78rem">
                                            <?= str_pad($re['id_entrega'],4,'0',STR_PAD_LEFT) ?>
                                        </td>
                                        <!-- Coluna: nome do cliente destinatário -->
                                        <td><?= htmlspecialchars($re['cliente']) ?></td>
                                        <!-- Coluna: data prevista de entrega -->
                                        <td><?= $re['data_prevista'] ? date('d/m/Y', strtotime($re['data_prevista'])) : '—' ?></td>
                                        <!-- Coluna: badge com status atual da entrega -->
                                        <td class="text-center pe-3">
                                            <span class="badge <?= $eb ?>"><?= str_replace('_',' ',$re['status']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php
                        $origemArmazem = null;
                        foreach ($rotaEntregas as $re) {
                            if (!empty($re['armazem_nome']) && !empty($re['armazem_cidade'])) {
                                $origemArmazem = $re;
                                break;
                            }
                        }
                        $mapaOrigem = $origemArmazem ? [
                            'nome'   => $origemArmazem['armazem_nome'],
                            'cidade' => $origemArmazem['armazem_cidade'],
                            'estado' => $origemArmazem['armazem_estado'] ?? '',
                        ] : [
                            'nome'   => $op['transportadora_nome'] ?? '',
                            'cidade' => $op['transp_cidade'] ?? '',
                            'estado' => $op['transp_estado'] ?? '',
                        ];
                        ?>
                        <!-- JSON embutido com dados do mapa desta operação (origem + paradas) -->
                        <script type="application/json" id="mapa-data-<?= $op['id_rota'] ?>">
                        <?= json_encode([
                            'origem'   => $mapaOrigem,
                            'entregas' => array_map(fn($e) => [
                                'id'      => $e['id_entrega'],
                                'cliente' => $e['cliente'],
                                'cidade'  => $e['cidade'] ?? '',
                                'estado'  => $e['estado'] ?? '',
                            ], $rotaEntregas),
                        ], JSON_HEX_TAG | JSON_HEX_AMP) ?>
                        </script>

                        <!-- Botão e contêiner do mapa Leaflet do trajeto desta operação -->
                        <div class="mt-2">
                            <!-- Botão que aciona o cálculo e exibição do trajeto no mapa -->
                            <button type="button" id="btn-mapa-<?= $op['id_rota'] ?>"
                                    class="btn btn-outline-secondary btn-sm"
                                    onclick="calcularTrajeto(<?= $op['id_rota'] ?>)">
                                <i class="bi bi-map me-1"></i>Ver Trajeto / Calcular km
                            </button>
                            <!-- Contêiner do mapa (oculto até clicar em "Ver Trajeto") -->
                            <div id="mapa-container-<?= $op['id_rota'] ?>" style="display:none" class="mt-2">
                                <div id="mapa-<?= $op['id_rota'] ?>" class="mapa-trajeto"></div>
                                <!-- Área de informações do trajeto (distância calculada pelo Leaflet) -->
                                <div id="mapa-info-<?= $op['id_rota'] ?>"></div>
                            </div>
                        </div>

                        <!-- Mensagem informativa quando a operação está planejada mas sem entregas -->
                        <?php elseif($statusRota === 'PLANEJADA'): ?>
                        <p class="text-muted small fst-italic mb-0">
                            <i class="bi bi-info-circle me-1"></i>Nenhuma entrega vinculada. Use o painel ao lado para adicionar.
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Bloco com datas da viagem vinculada (saída, previsão e chegada real) -->
                    <?php if($op['id_viagem']): ?>
                    <div class="d-flex flex-wrap gap-4 p-3 rounded-3 bg-light mb-3">
                        <div>
                            <div class="text-muted fw-bold" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Saída</div>
                            <div class="small fw-bold">
                                <?= $op['data_saida'] ? date('d/m/Y H:i', strtotime($op['data_saida'])) : '—' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted fw-bold" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Prev. Chegada</div>
                            <div class="small fw-bold">
                                <?= $op['data_chegada_prevista'] ? date('d/m/Y H:i', strtotime($op['data_chegada_prevista'])) : '—' ?>
                            </div>
                        </div>
                        <!-- Exibe a chegada real somente se a viagem já foi concluída -->
                        <?php if($op['data_chegada_real']): ?>
                        <div>
                            <div class="text-success fw-bold" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px">Chegada Real</div>
                            <div class="small fw-bold text-success">
                                <i class="bi bi-check-circle-fill me-1"></i><?= date('d/m/Y H:i', strtotime($op['data_chegada_real'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Área de ações por status da operação -->
                    <?php if($statusRota === 'PLANEJADA'): ?>
                        <!-- Operação PLANEJADA com entregas: exibe botão de iniciar viagem -->
                        <?php if($op['total_entregas'] > 0): ?>
                        <button class="btn btn-primary btn-sm fw-bold w-100 py-2" type="button"
                                data-bs-toggle="collapse" data-bs-target="#form-viagem-<?= $op['id_rota'] ?>">
                            <i class="bi bi-broadcast me-1"></i>Iniciar Viagem
                        </button>
                        <!-- Formulário colapsável para confirmar a saída da viagem -->
                        <div class="collapse mt-3" id="form-viagem-<?= $op['id_rota'] ?>">
                            <form method="POST" class="p-3 bg-light rounded-3">
                                <input type="hidden" name="acao" value="iniciar_viagem">
                                <input type="hidden" name="id_rota" value="<?= $op['id_rota'] ?>">
                                <div class="row g-2">
                                    <!-- Campos de data/hora de saída e previsão de chegada -->
                                    <div class="col-sm-6">
                                        <label class="small fw-bold text-muted">Data/Hora de Saída</label>
                                        <input type="datetime-local" name="data_saida"
                                               class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small fw-bold text-muted">Previsão de Chegada</label>
                                        <input type="datetime-local" name="data_chegada_prevista"
                                               class="form-control form-control-sm" required>
                                    </div>
                                    <!-- Botões de confirmar saída e cancelar -->
                                    <div class="col-12 mt-1 d-flex gap-2">
                                        <button type="submit" class="btn btn-success btn-sm fw-bold px-4">
                                            <i class="bi bi-check-circle me-1"></i>Confirmar Saída
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm px-3"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#form-viagem-<?= $op['id_rota'] ?>">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- Operação PLANEJADA sem entregas: exibe alerta para vincular entregas -->
                        <?php else: ?>
                        <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Adicione ao menos uma entrega para iniciar a viagem.
                        </div>
                        <?php endif; ?>

                    <!-- Operação EM ANDAMENTO: botões de concluir, cancelar e simular desvio -->
                    <?php elseif($statusRota === 'EM_ANDAMENTO'): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Botão de simulação de desvio de rota: abre modal com mapa interativo -->
                        <button type="button"
                                class="btn btn-outline-warning btn-sm px-3 py-2"
                                data-bs-toggle="modal" data-bs-target="#modalDesvio"
                                data-id-rota="<?= $op['id_rota'] ?>"
                                data-id-viagem="<?= $op['id_viagem'] ?>"
                                title="Simular nova posição de origem e recalcular distância">
                            <i class="bi bi-geo-alt me-1"></i>Simular Desvio
                        </button>
                        <!-- Botão de registro de parada não programada -->
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm px-3 py-2"
                                data-bs-toggle="modal" data-bs-target="#modalParada"
                                data-id-viagem="<?= $op['id_viagem'] ?>"
                                data-id-rota="<?= $op['id_rota'] ?>"
                                title="Registrar parada não programada">
                            <i class="bi bi-pause-circle me-1"></i>Registrar Parada
                        </button>
                        <!-- Botão de conclusão da viagem (marca todas as entregas como ENTREGUE) -->
                        <a href="?status_viagem=CONCLUIDA&id_viagem=<?= $op['id_viagem'] ?>"
                           class="btn btn-success btn-sm fw-bold flex-grow-1 py-2"
                           onclick="return confirm('Confirmar conclusão? Todas as entregas serão marcadas como ENTREGUE.')">
                            <i class="bi bi-check-circle-fill me-1"></i>Concluir Viagem
                        </a>
                        <!-- Botão de cancelamento da viagem (entregas voltam para PENDENTE) -->
                        <a href="?status_viagem=CANCELADA&id_viagem=<?= $op['id_viagem'] ?>"
                           class="btn btn-outline-danger btn-sm px-3 py-2"
                           onclick="return confirm('Cancelar esta viagem? As entregas voltarão para PENDENTE.')">
                            <i class="bi bi-x-circle me-1"></i>Cancelar
                        </a>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Data attributes com motoristas, veículos, transportadoras e entregas em JSON (lidos por assets/js/operacoes.js) -->
<div id="app-data"
     data-motoristas="<?= htmlspecialchars(json_encode($motoristas), ENT_QUOTES) ?>"
     data-veiculos="<?= htmlspecialchars(json_encode($veiculos), ENT_QUOTES) ?>"
     data-transportadoras="<?= htmlspecialchars(json_encode($transportadoras), ENT_QUOTES) ?>"
     data-entregas-pendentes="<?= htmlspecialchars(json_encode($entregas_pendentes), ENT_QUOTES) ?>"
     hidden></div>

<!-- Modal: Simular Desvio de Rota -->
<div class="modal fade" id="modalDesvio" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold">
          <i class="bi bi-geo-alt-fill text-warning me-1"></i> Simular Desvio de Rota
          <small class="text-muted fw-normal ms-2" id="desvio-label-op"></small>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="p-3 border-bottom bg-warning bg-opacity-10">
          <p class="small text-muted mb-1">
            <i class="bi bi-cursor-fill me-1"></i>
            <strong>Clique no mapa</strong> para definir a nova posição atual do veículo.
            O sistema calculará a distância até os destinos e registrará um alerta <span class="badge bg-warning text-dark">DESVIO_ROTA</span>.
          </p>
          <div id="desvio-info" class="mt-1"></div>
        </div>
        <div id="mapa-desvio" style="height:450px;"></div>
      </div>
      <div class="modal-footer py-2">
        <form method="POST" id="form-desvio" class="d-flex gap-2 align-items-center w-100 justify-content-end flex-wrap">
          <input type="hidden" name="acao"            value="simular_desvio">
          <input type="hidden" name="id_rota"         id="desvio-id-rota">
          <input type="hidden" name="id_viagem"       id="desvio-id-viagem">
          <input type="hidden" name="nova_origem_nome" id="desvio-origem-nome">
          <input type="hidden" name="nova_distancia"  id="desvio-nova-distancia">
          <span id="desvio-resumo" class="text-muted small me-auto"></span>
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-warning fw-bold" id="btn-confirmar-desvio" disabled>
            <i class="bi bi-check-circle me-1"></i>Confirmar Desvio
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Registrar Parada Não Programada -->
<div class="modal fade" id="modalParada" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold">
          <i class="bi bi-pause-circle-fill text-secondary me-1"></i> Registrar Parada Não Programada
          <small class="text-muted fw-normal ms-2" id="parada-label-op"></small>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="acao"      value="registrar_parada">
        <input type="hidden" name="id_viagem" id="parada-id-viagem">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Local da Parada</label>
            <input type="text" name="local" class="form-control form-control-sm"
                   placeholder="Ex: Posto BR km 342, Rodovia SP-330" required maxlength="150">
          </div>
          <div class="mb-1">
            <label class="form-label small fw-bold text-muted">Motivo</label>
            <select name="motivo" class="form-select form-select-sm" required>
              <option value="">Selecione...</option>
              <option value="Abastecimento">Abastecimento</option>
              <option value="Problema Mecânico">Problema Mecânico</option>
              <option value="Descanso Obrigatório">Descanso Obrigatório</option>
              <option value="Fiscalização">Fiscalização</option>
              <option value="Outro">Outro</option>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-dark fw-bold">
            <i class="bi bi-check-circle me-1"></i>Registrar Parada
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS para renderização dos mapas de trajeto -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Script principal do sistema (filtro de motorista/veículo por transportadora, calcularTrajeto) -->
<script src="assets/js/script.js"></script>
<!-- Lógica do modal "Simular Desvio de Rota" -->
<script src="assets/js/operacoes.js"></script>
</body>
</html>
