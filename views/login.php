<?php
/**
 * View: Login
 *
 * Exibe a tela de autenticação do sistema Gestão Logística.
 * Apresenta um formulário com campos de e-mail e senha, além de
 * mensagem de erro caso as credenciais informadas sejam inválidas.
 * A variável $erro é passada pelo controller de login.
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<!-- Contêiner centralizado com o card de login -->
<div class="px-3 w-100 d-flex justify-content-center">
    <div class="card login-card shadow-lg p-4">

        <!-- Cabeçalho do card: ícone, título e subtítulo -->
        <div class="text-center mb-4">
            <span style="font-size:48px"><i class="bi bi-truck text-warning"></i></span>
            <h5 class="fw-bold mt-2 mb-0">GESTÃO LOGÍSTICA</h5>
            <p class="text-muted small">Sistema de Controle Operacional</p>
        </div>

        <!-- Bloco PHP: exibe alerta de erro se as credenciais forem inválidas -->
        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <!-- Formulário de autenticação enviado via POST -->
        <form method="POST" novalidate>

            <!-- Campo de e-mail com ícone e preenchimento automático do último valor digitado -->
            <div class="mb-3">
                <label class="form-label fw-semibold small">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="seu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>

            <!-- Campo de senha com ícone de cadeado -->
            <div class="mb-4">
                <label class="form-label fw-semibold small">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <!-- Botão de envio do formulário -->
            <button type="submit" class="btn btn-dark w-100 fw-bold">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>
    </div>
</div>
</body>
</html>
