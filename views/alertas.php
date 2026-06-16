<?php
/**
 * View: Alertas
 *
 * Exibe a central de alertas gerados automaticamente pelo sistema.
 * Apresenta KPIs (total de alertas, entregas atrasadas, viagens sem
 * retorno e estoque crítico), botões de filtro rápido por tipo de
 * alerta e a lista de alertas com ícones e cores correspondentes ao tipo.
 *
 * Variáveis esperadas do controller:
 * - $alertas        (array) Lista de alertas com tipo, titulo, descricao e ref
 * - $total_atrasos  (int)   Quantidade de alertas do tipo ATRASO
 * - $total_viagens  (int)   Quantidade de alertas do tipo VIAGEM
 * - $total_estoque  (int)   Quantidade de alertas do tipo ESTOQUE
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<!-- Renderiza a barra de navegação superior do sistema -->
<?php exibirNavegacao(); ?>
<div class="container-fluid px-4 mb-5">

    <!-- ===== CARDS DE KPI DE ALERTAS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total geral de alertas (vermelho se > 0, verde se 0) -->
        <div class="col-6 col-md"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total de Alertas</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= count($alertas) ?></h3>
        </div></div>
        <!-- KPI: Alertas de entregas atrasadas -->
        <div class="col-6 col-md"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Entregas Atrasadas</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_atrasos ?></h3>
        </div></div>
        <!-- KPI: Alertas de viagens em trânsito sem retorno previsto -->
        <div class="col-6 col-md"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Viagens Sem Retorno</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_viagens ?></h3>
        </div></div>
        <!-- KPI: Alertas de estoque crítico -->
        <div class="col-6 col-md"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Estoque Crítico</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_estoque ?></h3>
        </div></div>
        <!-- KPI: Desvios de rota registrados manualmente -->
        <div class="col-6 col-md"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Desvios de Rota</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_desvios ?></h3>
        </div></div>
        <!-- KPI: Paradas não programadas -->
        <div class="col-6 col-md"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Paradas</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_paradas ?></h3>
        </div></div>
    </div>

    <!-- Bloco PHP: exibe botões de filtro rápido somente se houver alertas -->
    <?php if(!empty($alertas)): ?>
    <!-- Botões de filtro rápido para exibir alertas por tipo via JavaScript -->
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <span class="small text-muted fw-bold">Filtrar:</span>
        <!-- Botão "Todos" - ativo por padrão -->
        <button class="btn btn-sm btn-outline-secondary active" onclick="filtrarAlertas('TODOS', this)">Todos (<?= count($alertas) ?>)</button>
        <!-- Botão de filtro por alertas de atraso (visível apenas se houver atrasos) -->
        <?php if($total_atrasos > 0): ?>
        <button class="btn btn-sm btn-outline-danger" onclick="filtrarAlertas('ATRASO', this)">
            <i class="bi bi-clock-history me-1"></i>Entregas (<?= $total_atrasos ?>)
        </button>
        <?php endif; ?>
        <!-- Botão de filtro por alertas de viagem (visível apenas se houver viagens em aberto) -->
        <?php if($total_viagens > 0): ?>
        <button class="btn btn-sm btn-outline-warning" onclick="filtrarAlertas('VIAGEM', this)">
            <i class="bi bi-truck me-1"></i>Viagens (<?= $total_viagens ?>)
        </button>
        <?php endif; ?>
        <!-- Botão de filtro por alertas de estoque (visível apenas se houver alertas de estoque) -->
        <?php if($total_estoque > 0): ?>
        <button class="btn btn-sm btn-outline-secondary" onclick="filtrarAlertas('ESTOQUE', this)">
            <i class="bi bi-box-seam me-1"></i>Estoque (<?= $total_estoque ?>)
        </button>
        <?php endif; ?>
        <!-- Botão de filtro por desvios de rota (visível apenas se houver registros) -->
        <?php if($total_desvios > 0): ?>
        <button class="btn btn-sm btn-outline-warning" onclick="filtrarAlertas('DESVIO_ROTA', this)">
            <i class="bi bi-geo-alt me-1"></i>Desvios (<?= $total_desvios ?>)
        </button>
        <?php endif; ?>
        <!-- Botão de filtro por paradas não programadas (visível apenas se houver registros) -->
        <?php if($total_paradas > 0): ?>
        <button class="btn btn-sm btn-outline-secondary" onclick="filtrarAlertas('PARADA_NAO_PROGRAMADA', this)">
            <i class="bi bi-pause-circle me-1"></i>Paradas (<?= $total_paradas ?>)
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ===== CARD DA CENTRAL DE ALERTAS ===== -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
        <div class="fw-bold text-dark mb-3 border-bottom pb-3 d-flex align-items-center gap-2">
            <i class="bi bi-bell-fill text-danger"></i> Central de Alertas
            <span class="badge bg-secondary rounded-pill ms-1 fw-normal" style="font-size:.7rem">gerado automaticamente</span>
        </div>

        <!-- Bloco PHP: exibe mensagem de sistema saudável se não há alertas -->
        <?php if(empty($alertas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-shield-check text-success fs-1 d-block mb-2"></i>
            <strong class="d-block">Tudo certo por aqui!</strong>
            <small>Nenhum alerta no momento — entregas, viagens e estoque dentro do esperado.</small>
        </div>

        <!-- Bloco PHP: lista os alertas quando houver -->
        <?php else: ?>
        <!-- Contêiner da lista de alertas com ID usado pelo filtro JavaScript -->
        <div class="d-flex flex-column gap-2" id="lista-alertas">
            <!-- Loop PHP: itera sobre cada alerta e renderiza um card visual -->
            <?php foreach($alertas as $a):
                /* Define cor do badge, ícone e borda esquerda conforme o tipo do alerta */
                [$bgBadge, $icon, $borderCls] = match($a['tipo']) {
                    'ATRASO'                => ['bg-danger',           'bi-clock-history',    'border-danger'],
                    'VIAGEM'               => ['bg-warning text-dark','bi-truck',             'border-warning'],
                    'ESTOQUE'              => ['bg-secondary',        'bi-box-seam',          'border-secondary'],
                    'DESVIO_ROTA'          => ['bg-warning text-dark','bi-geo-alt-fill',      'border-warning'],
                    'PARADA_NAO_PROGRAMADA'=> ['bg-secondary',        'bi-pause-circle-fill', 'border-secondary'],
                    default                => ['bg-dark',             'bi-exclamation',       'border-dark'],
                };
            ?>
            <!-- Card individual de alerta com data-tipo para filtragem via JavaScript -->
            <div class="d-flex align-items-start gap-3 p-3 rounded-3 border border-start border-3 <?= $borderCls ?> bg-white alerta-item"
                 data-tipo="<?= $a['tipo'] ?>">
                <!-- Ícone do tipo de alerta -->
                <div class="badge <?= $bgBadge ?> p-2 rounded-3 fs-5 flex-shrink-0">
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <!-- Título do alerta em negrito -->
                        <strong class="small"><?= $a['titulo'] ?></strong>
                        <!-- Badge com a referência do alerta (ex: #0001) -->
                        <span class="badge <?= $bgBadge ?> rounded-pill flex-shrink-0" style="font-size:.68rem"><?= $a['ref'] ?></span>
                    </div>
                    <!-- Descrição detalhada do alerta -->
                    <p class="mb-0 mt-1 text-muted" style="font-size:.82rem"><?= $a['descricao'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>
<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/alertas.js"></script>
</body></html>
