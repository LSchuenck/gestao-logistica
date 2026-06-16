<?php
/**
 * View: Usuários
 *
 * Exibe o painel de gerenciamento de usuários do sistema.
 * Apresenta a tabela com todos os usuários cadastrados, indicando
 * perfil (ADMIN, GERENTE, OPERADOR), status (ATIVO/INATIVO) e data
 * de cadastro. Permite criar novos usuários e editar/desativar/excluir
 * os existentes por meio de um modal Bootstrap.
 *
 * Variáveis esperadas do controller:
 * - $erro     (string) Mensagem de erro, se houver
 * - $sucesso  (string) Mensagem de sucesso, se houver
 * - $usuarios (array)  Lista de usuários cadastrados no sistema
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários — Gestão Logística</title>
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

<div class="container mb-5">

    <!-- Cabeçalho da página com título e botão de novo usuário -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-people-fill text-dark"></i> Usuários</h4>
            <p class="text-muted small mb-0">Gerencie os acessos ao sistema</p>
        </div>
        <!-- Botão que abre o modal de criação de novo usuário e limpa o formulário via JS -->
        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                onclick="limparForm()">
            <i class="bi bi-plus-lg"></i> Novo Usuário
        </button>
    </div>

    <!-- Bloco PHP: exibe alerta de erro se houver mensagem de erro do controller -->
    <?php if ($erro): ?>
        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <!-- Bloco PHP: exibe alerta de sucesso se houver mensagem de sucesso do controller -->
    <?php if ($sucesso): ?>
        <div class="alert alert-success py-2"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <!-- ===== TABELA DE USUÁRIOS ===== -->
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
                    <!-- Loop PHP: itera sobre cada usuário e renderiza uma linha na tabela -->
                    <?php foreach ($usuarios ?? [] as $u): ?>
                        <tr>
                            <!-- Coluna: nome do usuário em negrito -->
                            <td class="fw-semibold"><?= htmlspecialchars($u['nome']) ?></td>
                            <!-- Coluna: e-mail em texto secundário menor -->
                            <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                            <!-- Coluna: badge de perfil colorido (vermelho=ADMIN, amarelo=GERENTE, cinza=OPERADOR) -->
                            <td>
                                <?php
                                /* Define a cor do badge com base no perfil do usuário */
                                $bc = $u['perfil'] === 'ADMIN' ? 'danger' : ($u['perfil'] === 'GERENTE' ? 'warning text-dark' : 'secondary');
                                echo '<span class="badge bg-' . $bc . '">' . $u['perfil'] . '</span>';
                                ?>
                            </td>
                            <!-- Coluna: badge de status (verde=ATIVO, cinza=INATIVO) -->
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'ATIVO' ? 'success' : 'secondary' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <!-- Coluna: data de cadastro formatada -->
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($u['data_cadastro'])) ?></td>
                            <!-- Coluna: botões de ação (editar, ativar/desativar, excluir) -->
                            <td class="text-center">
                                <!-- Botão de editar: abre o modal pré-preenchido com os dados do usuário via JS -->
                                <button class="btn btn-outline-primary btn-sm"
                                        onclick="preencherForm(<?= htmlspecialchars(json_encode($u)) ?>)"
                                        data-bs-toggle="modal" data-bs-target="#modalUsuario">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Botão de toggle de status (ativo/inativo) via GET -->
                                <a href="usuarios.php?toggle=<?= $u['id_usuario'] ?>"
                                   class="btn btn-outline-warning btn-sm"
                                   title="<?= $u['status'] === 'ATIVO' ? 'Desativar' : 'Ativar' ?>">
                                    <i class="bi bi-<?= $u['status'] === 'ATIVO' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                </a>
                                <!-- Botão de exclusão com confirmação JavaScript -->
                                <a href="usuarios.php?excluir=<?= $u['id_usuario'] ?>"
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Excluir usuário <?= htmlspecialchars(addslashes($u['nome'])) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- Bloco PHP: exibe linha vazia se não houver usuários -->
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL DE CRIAÇÃO/EDIÇÃO DE USUÁRIO ===== -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <!-- Formulário POST dentro do modal para criar ou editar usuário -->
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <!-- Título do modal (alterado via JS para "Novo Usuário" ou "Editar Usuário") -->
                <h5 class="modal-title fw-bold" id="modalTitulo">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Campo oculto com o ID do usuário (0 para novo cadastro) -->
                <input type="hidden" name="id_usuario" id="id_usuario" value="0">

                <!-- Campo de nome do usuário -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nome *</label>
                    <input type="text" name="nome" id="nome" class="form-control" required>
                </div>

                <!-- Campo de e-mail do usuário -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small">E-mail *</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>

                <!-- Campos de perfil e status lado a lado -->
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

                <!-- Campo de nova senha (visível apenas ao editar usuário existente) -->
                <div class="mb-1" id="campoSenha" style="display:none">
                    <label class="form-label fw-semibold small">Nova senha <span class="text-muted fw-normal">(deixe em branco para não alterar)</span></label>
                    <input type="password" name="senha" id="senha" class="form-control" placeholder="••••••••">
                </div>

                <!-- Aviso de senha automática (visível apenas ao criar novo usuário) -->
                <div id="avisoSenhaEmail" class="alert alert-info py-2 small mb-0" style="display:none">
                    <i class="bi bi-envelope-fill"></i> Uma senha temporária será gerada e enviada automaticamente para o e-mail do usuário.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark btn-sm">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Rodapé do sistema -->
<footer class="text-center text-muted small py-3 bg-white border-top">
    &copy; 2026 Gestão Logística &mdash; Sistema de Controle Operacional
</footer>

<!-- Bootstrap JS para funcionalidades interativas (modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * Limpa e prepara o formulário do modal para criação de novo usuário.
 * Exibe o aviso de senha automática por e-mail e oculta o campo de senha.
 */
function limparForm() {
    document.getElementById('id_usuario').value = '0';
    document.getElementById('nome').value = '';
    document.getElementById('email').value = '';
    document.getElementById('perfil').value = 'OPERADOR';
    document.getElementById('status').value = 'ATIVO';
    document.getElementById('senha').value = '';
    document.getElementById('modalTitulo').textContent = 'Novo Usuário';
    document.getElementById('campoSenha').style.display = 'none';
    document.getElementById('avisoSenhaEmail').style.display = 'block';
}

/**
 * Preenche o formulário do modal com os dados do usuário selecionado para edição.
 * Exibe o campo de senha e oculta o aviso de senha automática.
 * @param {Object} u - Objeto com os dados do usuário (id_usuario, nome, email, perfil, status)
 */
function preencherForm(u) {
    document.getElementById('id_usuario').value = u.id_usuario;
    document.getElementById('nome').value = u.nome;
    document.getElementById('email').value = u.email;
    document.getElementById('perfil').value = u.perfil;
    document.getElementById('status').value = u.status;
    document.getElementById('senha').value = '';
    document.getElementById('modalTitulo').textContent = 'Editar Usuário';
    document.getElementById('campoSenha').style.display = 'block';
    document.getElementById('avisoSenhaEmail').style.display = 'none';
}
</script>
</body>
</html>
