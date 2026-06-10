<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requerLogin() {
    if (empty($_SESSION['usuario'])) {
        header('Location: login.php');
        exit;
    }
}

function requerPerfil(array $perfis) {
    requerLogin();
    if (!in_array($_SESSION['usuario']['perfil'], $perfis)) {
        header('Location: index.php?acesso=negado');
        exit;
    }
}

function renderNavbar() {
    $u       = $_SESSION['usuario'] ?? [];
    $nome    = htmlspecialchars($u['nome'] ?? '');
    $perfil  = $u['perfil'] ?? '';

    if ($perfil === 'ADMIN') {
        $badgeClass = 'bg-danger';
    } elseif ($perfil === 'GERENTE') {
        $badgeClass = 'bg-warning text-dark';
    } else {
        $badgeClass = 'bg-secondary';
    }

    $adminLink = ($perfil === 'ADMIN')
        ? '<a href="usuarios.php" class="btn btn-outline-light btn-sm"><i class="bi bi-people"></i> Usuários</a>'
        : '';

    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
            <i class="bi bi-truck text-warning"></i> GESTÃO LOGÍSTICA
        </a>
        <div class="d-flex align-items-center gap-2">
            ' . $adminLink . '
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-house-door"></i> Menu</a>
            <span class="text-white-50 small d-none d-md-flex align-items-center gap-2 ms-1">
                <i class="bi bi-person-circle"></i> ' . $nome . '
                <span class="badge ' . $badgeClass . '">' . $perfil . '</span>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </div>
    </div>
</nav>';
}
