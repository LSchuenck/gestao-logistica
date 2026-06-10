<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Logística - Painel Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>

<div class="container mb-5">

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm bg-primary text-white p-3">
                <span class="small text-white-50 fw-bold text-uppercase">Transportadoras Ativas</span>
                <h3 class="fw-black m-0 mt-1"><?= $total_transp ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm bg-success text-white p-3">
                <span class="small text-white-50 fw-bold text-uppercase">Entregas Pendentes</span>
                <h3 class="fw-black m-0 mt-1"><?= $total_entregas ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm bg-warning text-dark p-3">
                <span class="small fw-bold text-uppercase opacity-75">Viagens em Curso</span>
                <h3 class="fw-black m-0 mt-1"><?= $total_viagens ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card shadow-sm bg-danger text-white p-3">
                <span class="small text-white-50 fw-bold text-uppercase">Alertas Ativos</span>
                <h3 class="fw-black m-0 mt-1"><?= $total_alertas ?></h3>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-center">
            <h4 class="fw-bold text-dark m-0">Painel de Controle</h4>
            <p class="text-muted small mb-0">Selecione o módulo que deseja gerenciar.</p>
        </div>
    </div>

    <div class="row g-4 text-center mb-4">
        <div class="col-md-4">
            <a href="transportadoras.php" class="card card-menu p-4 shadow-sm text-decoration-none text-dark h-100 d-flex flex-column justify-content-center">
                <div class="icon-box text-primary"><i class="bi bi-building"></i></div>
                <h5 class="fw-bold mb-1">Cadastros</h5>
                <p class="text-muted small mb-2">Transportadoras, Motoristas, Veículos e Clientes</p>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:10px">Transportadoras</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:10px">Motoristas</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:10px">Veículos</span>
                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size:10px">Clientes</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="entregas.php" class="card card-menu p-4 shadow-sm text-decoration-none text-white bg-primary h-100 d-flex flex-column justify-content-center">
                <div class="icon-box text-warning"><i class="bi bi-geo-alt-fill"></i></div>
                <h5 class="fw-bold mb-1">Planejamento e Roteirização</h5>
                <p class="mb-2 small opacity-75">Entregas, Rotas Otimizadas e Alterações em Tempo Real</p>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <span class="badge bg-light text-dark" style="font-size:10px">Clientes</span>
                    <span class="badge bg-light text-dark" style="font-size:10px">Entregas</span>
                    <span class="badge bg-light text-dark" style="font-size:10px">Rotas</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="viagens.php" class="card card-menu p-4 shadow-sm text-decoration-none text-white bg-dark h-100 d-flex flex-column justify-content-center">
                <div class="icon-box text-warning"><i class="bi bi-broadcast"></i></div>
                <h5 class="fw-bold mb-1">Monitoramento e Rastreamento</h5>
                <p class="mb-2 small opacity-75">GPS em Tempo Real, Alertas Automáticos e Status das Cargas</p>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <span class="badge bg-secondary" style="font-size:10px">Viagens</span>
                    <span class="badge bg-secondary" style="font-size:10px">GPS</span>
                    <span class="badge bg-danger" style="font-size:10px">Alertas</span>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="estoque.php" class="card card-menu p-4 shadow-sm text-decoration-none text-white bg-success h-100 d-flex flex-column justify-content-center">
                <div class="icon-box"><i class="bi bi-box-seam-fill"></i></div>
                <h5 class="fw-bold mb-1">Controle de Estoque e Armazenagem</h5>
                <p class="mb-2 small opacity-75">Entrada e Saída de Mercadorias, Inventário e Organização por Armazém</p>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <span class="badge bg-light text-dark" style="font-size:10px">Armazéns</span>
                    <span class="badge bg-light text-dark" style="font-size:10px">Produtos</span>
                    <span class="badge bg-light text-dark" style="font-size:10px">Movimentações</span>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="indicadores.php" class="card card-menu p-4 shadow-sm text-decoration-none text-white h-100 d-flex flex-column justify-content-center" style="background:#6f42c1">
                <div class="icon-box text-warning"><i class="bi bi-bar-chart-line-fill"></i></div>
                <h5 class="fw-bold mb-1">Faturamento e Indicadores Logísticos</h5>
                <p class="mb-2 small opacity-75">Notas Fiscais, Custos Operacionais e KPIs de Desempenho</p>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <span class="badge bg-light text-dark" style="font-size:10px">Fretes / NF</span>
                    <span class="badge bg-light text-dark" style="font-size:10px">Indicadores</span>
                    <span class="badge bg-light text-dark" style="font-size:10px">Relatórios</span>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm bg-white p-3">
        <p class="text-muted small fw-bold mb-2 text-uppercase"><i class="bi bi-lightning-fill text-warning"></i> Acesso Rápido</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="transportadoras.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-building"></i> Transportadoras</a>
            <a href="motoristas.php" class="btn btn-outline-success btn-sm"><i class="bi bi-person-badge"></i> Motoristas</a>
            <a href="veiculos.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-truck-front"></i> Veículos</a>
            <a href="clientes.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people"></i> Clientes</a>
            <a href="armazens.php" class="btn btn-outline-warning btn-sm"><i class="bi bi-houses"></i> Armazéns</a>
            <a href="produtos.php" class="btn btn-outline-info btn-sm"><i class="bi bi-box"></i> Produtos</a>
            <a href="entregas.php" class="btn btn-outline-success btn-sm"><i class="bi bi-box-arrow-right"></i> Entregas</a>
            <a href="rotas.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-map"></i> Rotas</a>
            <a href="viagens.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-broadcast"></i> Viagens</a>
            <a href="alertas.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-exclamation-triangle"></i> Alertas</a>
            <a href="frete.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-receipt-cutoff"></i> Fretes / NF</a>
            <a href="indicadores.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-bar-chart"></i> Indicadores</a>
        </div>
    </div>
</div>

<footer class="text-center text-muted small py-3 bg-white border-top">
    &copy; 2026 Gestão Logística &mdash; Sistema de Controle Operacional
</footer>
</body>
</html>
