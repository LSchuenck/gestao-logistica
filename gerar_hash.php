<?php
// Use esta página para gerar o hash de uma senha e depois DELETE este arquivo.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['senha'])) {
    $hash = password_hash($_POST['senha'], PASSWORD_BCRYPT);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerar Hash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card p-4 shadow" style="max-width:480px;width:100%">
    <h5 class="fw-bold mb-3">Gerar Hash de Senha</h5>
    <form method="POST">
        <div class="mb-3">
            <input type="text" name="senha" class="form-control" placeholder="Digite a senha" required>
        </div>
        <button class="btn btn-dark w-100">Gerar</button>
    </form>
    <?php if (!empty($hash)): ?>
    <div class="mt-3">
        <label class="form-label small fw-semibold">Hash gerado (copie para o INSERT):</label>
        <textarea class="form-control font-monospace small" rows="3" onclick="this.select()"><?= htmlspecialchars($hash) ?></textarea>
        <p class="text-muted small mt-2">
            SQL de exemplo:<br>
            <code>INSERT INTO usuario (nome, email, senha, perfil) VALUES ('Admin', 'admin@email.com', '&lt;hash&gt;', 'ADMIN');</code>
        </p>
    </div>
    <?php endif; ?>
    <div class="alert alert-warning mt-3 py-2 small"><strong>Atenção:</strong> Delete este arquivo após usar.</div>
</div>
</body>
</html>
