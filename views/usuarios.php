<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários — Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<?php renderNavbar(); ?>

<div class="container mb-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-people-fill text-dark"></i> Usuários</h4>
            <p class="text-muted small mb-0">Gerencie os acessos ao sistema</p>
        </div>
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                onclick="limparForm()">
            <i class="bi bi-plus-lg"></i> Novo Usuário
        </button>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div class="alert alert-success py-2"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($u['nome']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php
                                $bc = $u['perfil'] === 'ADMIN' ? 'danger' : ($u['perfil'] === 'GERENTE' ? 'warning text-dark' : 'secondary');
                                echo '<span class="badge bg-' . $bc . '">' . $u['perfil'] . '</span>';
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'ATIVO' ? 'success' : 'secondary' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($u['data_cadastro'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm"
                                        onclick="preencherForm(<?= htmlspecialchars(json_encode($u)) ?>)"
                                        data-bs-toggle="modal" data-bs-target="#modalUsuario">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="usuarios.php?toggle=<?= $u['id_usuario'] ?>"
                                   class="btn btn-outline-warning btn-sm"
                                   title="<?= $u['status'] === 'ATIVO' ? 'Desativar' : 'Ativar' ?>">
                                    <i class="bi bi-<?= $u['status'] === 'ATIVO' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                </a>
                                <a href="usuarios.php?excluir=<?= $u['id_usuario'] ?>"
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Excluir usuário <?= htmlspecialchars(addslashes($u['nome'])) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitulo">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_usuario" id="id_usuario" value="0">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nome *</label>
                    <input type="text" name="nome" id="nome" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">E-mail *</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold small">Perfil *</label>
                        <select name="perfil" id="perfil" class="form-select" required>
                            <option value="OPERADOR">Operador</option>
                            <option value="GERENTE">Gerente</option>
                            <option value="ADMIN">Admin</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold small">Senha <span id="senhaHint" class="text-muted fw-normal">(obrigatória)</span></label>
                    <input type="password" name="senha" id="senha" class="form-control" placeholder="••••••••">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark btn-sm">Salvar</button>
            </div>
        </form>
    </div>
</div>

<footer class="text-center text-muted small py-3 bg-white border-top">
    &copy; 2026 Gestão Logística &mdash; Sistema de Controle Operacional
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function limparForm() {
    document.getElementById('id_usuario').value = '0';
    document.getElementById('nome').value = '';
    document.getElementById('email').value = '';
    document.getElementById('perfil').value = 'OPERADOR';
    document.getElementById('status').value = 'ATIVO';
    document.getElementById('senha').value = '';
    document.getElementById('senhaHint').textContent = '(obrigatória)';
    document.getElementById('modalTitulo').textContent = 'Novo Usuário';
}
function preencherForm(u) {
    document.getElementById('id_usuario').value = u.id_usuario;
    document.getElementById('nome').value = u.nome;
    document.getElementById('email').value = u.email;
    document.getElementById('perfil').value = u.perfil;
    document.getElementById('status').value = u.status;
    document.getElementById('senha').value = '';
    document.getElementById('senhaHint').textContent = '(deixe em branco para não alterar)';
    document.getElementById('modalTitulo').textContent = 'Editar Usuário';
}
</script>
</body>
</html>
