<?php
/**
 * View: Painel Principal (Dashboard)
 *
 * Exibe o painel de controle do sistema com KPIs (Key Performance Indicators)
 * separados em dois grupos: Entregas e Operação. Também apresenta atalhos
 * rápidos para as principais funcionalidades do sistema.
 *
 * Variáveis esperadas do controller:
 * - $entregas_pendentes  (int) Quantidade de entregas com status PENDENTE
 * - $entregas_transito   (int) Quantidade de entregas em trânsito
 * - $entregas_atrasadas  (int) Quantidade de entregas atrasadas
 * - $viagens_ativas      (int) Número de viagens ativas no momento
 * - $alertas_recentes    (int) Alertas gerados nos últimos 7 dias
 * - $veiculos_disponiveis(int) Veículos com status DISPONIVEL
 * - $frete_mes           (float) Valor total de fretes no mês corrente
 * - $acesso_negado       (bool) Indica se o usuário tentou acessar página sem permissão
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Logística - Painel</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Estilo base dos cards de KPI */
        .kpi-card {
            border: none;
            border-radius: 12px;
            transition: transform .15s, box-shadow .15s;
        }
        /* Efeito de elevação ao passar o mouse sobre os cards */
        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,.10) !important;
        }
        /* Ícone circular exibido dentro de cada card de KPI */
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        /* Valor numérico em destaque nos cards de KPI */
        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }
        /* Botões de atalho rápido */
        .quick-btn {
            border-radius: 10px;
            padding: .65rem 1.1rem;
            font-weight: 600;
            font-size: .875rem;
            transition: transform .12s;
        }
        /* Efeito de elevação nos botões de atalho */
        .quick-btn:hover { transform: translateY(-2px); }
        /* Título de seção estilizado em caixa alta com letras espaçadas */
        .section-title {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: .75rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Renderiza a barra de navegação superior do sistema -->
    <?php renderNavbar(); ?>

    <!-- Bloco PHP: exibe alerta se o acesso à página anterior foi negado -->
    <?php if ($acesso_negado ?? false): ?>
    <div class="container mt-3">
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-0">
            <i class="bi bi-shield-lock-fill fs-5"></i>
            <span><strong>Acesso negado.</strong> Você não tem permissão para acessar essa página.</span>
        </div>
    </div>
    <?php endif; ?>

    <main class="container py-4 mb-5">

        <!-- Saudação personalizada com o nome do usuário logado -->
        <div class="mb-4">
            <h5 class="fw-bold mb-0">
                Olá, <?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Usuário') ?>
            </h5>
            <p class="text-muted small mb-0">Aqui está um resumo do dia de hoje.</p>
        </div>

        <!-- ===== SEÇÃO DE KPIs: ENTREGAS ===== -->
        <p class="section-title">Entregas</p>
        <div class="row g-3 mb-4">

            <!-- Card: Entregas Pendentes -->
            <div class="col-6 col-md-4">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-primary-subtle text-primary">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <!-- Exibe a contagem de entregas pendentes -->
                            <div class="kpi-value text-primary"><?= (int)$entregas_pendentes ?></div>
                            <div class="text-muted small fw-semibold">Pendentes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Entregas Em Trânsito -->
            <div class="col-6 col-md-4">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div>
                            <!-- Exibe a contagem de entregas em trânsito -->
                            <div class="kpi-value text-success"><?= (int)$entregas_transito ?></div>
                            <div class="text-muted small fw-semibold">Em Trânsito</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Entregas Atrasadas -->
            <div class="col-6 col-md-4">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-danger-subtle text-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <!-- Exibe a contagem de entregas atrasadas -->
                            <div class="kpi-value text-danger"><?= (int)$entregas_atrasadas ?></div>
                            <div class="text-muted small fw-semibold">Atrasadas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SEÇÃO DE KPIs: OPERAÇÃO ===== -->
        <p class="section-title">Operação</p>
        <div class="row g-3 mb-4">

            <!-- Card: Viagens Ativas no momento -->
            <div class="col-6 col-md-3">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-warning-subtle text-warning">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <div>
                            <!-- Exibe a quantidade de viagens ativas -->
                            <div class="kpi-value text-warning"><?= (int)$viagens_ativas ?></div>
                            <div class="text-muted small fw-semibold">Viagens Ativas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Alertas gerados nos últimos 7 dias -->
            <div class="col-6 col-md-3">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-danger-subtle text-danger">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div>
                            <!-- Exibe a quantidade de alertas recentes -->
                            <div class="kpi-value text-danger"><?= (int)$alertas_recentes ?></div>
                            <div class="text-muted small fw-semibold">Alertas Ativos</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Veículos disponíveis para operação -->
            <div class="col-6 col-md-3">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-secondary-subtle text-secondary">
                            <i class="bi bi-car-front-fill"></i>
                        </div>
                        <div>
                            <!-- Exibe a quantidade de veículos livres -->
                            <div class="kpi-value text-secondary"><?= (int)$veiculos_disponiveis ?></div>
                            <div class="text-muted small fw-semibold">Veíc. Livres</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Valor total de fretes no mês atual -->
            <div class="col-6 col-md-3">
                <div class="card kpi-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <!-- Exibe o valor monetário formatado em R$ -->
                            <div class="kpi-value text-success" style="font-size:1.3rem">
                                R$ <?= number_format((float)$frete_mes, 0, ',', '.') ?>
                            </div>
                            <div class="text-muted small fw-semibold">Frete do Mês</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SEÇÃO DE ATALHOS RÁPIDOS ===== -->
        <p class="section-title">Atalhos Rápidos</p>
        <div class="card shadow-sm border-0 rounded-3 p-3">
            <div class="row g-2">
                <!-- Atalho para página de Entregas -->
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="entregas.php" class="btn btn-outline-primary quick-btn w-100">
                        <i class="bi bi-plus-circle me-1"></i> Nova Entrega
                    </a>
                </div>
                <!-- Atalho para página de Rotas -->
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="rotas.php" class="btn btn-outline-secondary quick-btn w-100">
                        <i class="bi bi-signpost-split me-1"></i> Nova Rota
                    </a>
                </div>
                <!-- Atalho para página de Operações -->
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="operacoes.php" class="btn btn-outline-warning quick-btn w-100">
                        <i class="bi bi-play-circle me-1"></i> Operações
                    </a>
                </div>
                <!-- Atalho para página de Fretes e NF -->
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="frete.php" class="btn btn-outline-success quick-btn w-100">
                        <i class="bi bi-receipt me-1"></i> Frete / NF
                    </a>
                </div>
                <!-- Atalho para página de Estoque -->
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="estoque.php" class="btn btn-outline-dark quick-btn w-100">
                        <i class="bi bi-box-seam me-1"></i> Estoque
                    </a>
                </div>
                <!-- Atalho para página de Indicadores -->
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="indicadores.php" class="btn btn-outline-danger quick-btn w-100">
                        <i class="bi bi-bar-chart-line me-1"></i> Indicadores
                    </a>
                </div>
            </div>
        </div>

    </main>

    <!-- Rodapé do sistema -->
    <footer class="text-center text-muted small py-3 bg-white border-top">
        &copy; 2026 Gestão Logística &mdash; Sistema de Controle Operacional
    </footer>

    <!-- Bootstrap JS para funcionalidades interativas -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
