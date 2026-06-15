<?php
/**
 * View: Trocar Senha
 *
 * Exibe o formulário para o usuário criar uma nova senha pessoal.
 * Esta tela é apresentada quando o usuário precisa redefinir sua senha
 * (por exemplo, após receber uma senha temporária gerada pelo administrador).
 * A variável $erro é passada pelo controller caso as senhas não coincidam
 * ou não atendam ao critério mínimo de segurança.
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Senha — Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light login-page">

<!-- Contêiner centralizado verticalmente na tela -->
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh">
    <div class="card login-card shadow-lg p-4 w-100" style="max-width:420px">

        <!-- Cabeçalho do card: ícone de segurança, título e instrução -->
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock-fill text-warning fs-1"></i>
            <h5 class="fw-bold mt-2 mb-0">Crie sua nova senha</h5>
            <p class="text-muted small mt-1">Por segurança, você precisa definir uma senha pessoal antes de continuar.</p>
        </div>

        <!-- Bloco PHP: exibe alerta de erro de validação se houver -->
        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <!-- Formulário de troca de senha enviado via POST -->
        <form method="POST">

            <!-- Campo para a nova senha -->
            <div class="mb-3">
                <label class="form-label fw-semibold small">Nova senha</label>
                <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo 6 caracteres" required autofocus>
            </div>

            <!-- Campo para confirmar a nova senha -->
            <div class="mb-4">
                <label class="form-label fw-semibold small">Confirmar nova senha</label>
                <input type="password" name="confirma_senha" class="form-control" placeholder="Repita a senha" required>
            </div>

            <!-- Botão de envio para salvar a nova senha e continuar -->
            <button type="submit" class="btn btn-dark w-100">
                <i class="bi bi-check-lg"></i> Salvar e continuar
            </button>
        </form>
    </div>
</div>

<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
