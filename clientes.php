<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total de Clientes</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?> <span class="fs-6 text-muted fw-normal">cadastrados</span></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-info text-white w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formCliente">
                <i class="bi bi-person-plus-fill"></i> Novo Cliente
            </button>
            <div class="collapse mb-4" id="formCliente">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-person-lines-fill"></i> Dados do Cliente</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome / Razão Social</label>
                            <input type="text" name="nome" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">CPF / CNPJ</label>
                            <input type="text" name="cpf_cnpj" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Telefone</label>
                            <input type="text" name="telefone" class="form-control form-control-sm" placeholder="(XX) XXXXX-XXXX">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Endereço de Entrega</label>
                            <input type="text" name="endereco" class="form-control form-control-sm" placeholder="Rua, Nº, Cidade - UF">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-info text-white btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-people text-info"></i> Clientes Cadastrados
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>CPF / CNPJ</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th class="text-center">Entregas</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum cliente cadastrado.</td></tr>
                        <?php else: foreach($lista as $c): ?>
                            <tr class="row-h">
                                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                                <td class="small font-monospace"><?= htmlspecialchars($c['cpf_cnpj'] ?? '—') ?></td>
                                <td class="small"><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($c['endereco'] ?? '—') ?></small></td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?= $c['total_entregas'] ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="?excluir=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir este cliente?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
