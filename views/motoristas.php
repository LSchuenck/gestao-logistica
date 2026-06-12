<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoristas - Gestão Logística</title>
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
            <span class="text-muted small text-uppercase fw-bold">Total Cadastrados</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?> <span class="fs-6 text-muted fw-normal">motoristas</span></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Ativos</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $ativos ?> <span class="fs-6 text-muted fw-normal">habilitados</span></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">CNH Vencida</span>
            <h3 class="fw-black <?= $vencidos>0 ? 'text-danger' : 'text-success' ?> m-0 mt-1"><?= $vencidos ?> <span class="fs-6 text-muted fw-normal">pendentes</span></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formMot">
                <i class="bi bi-person-plus-fill"></i> Cadastrar Motorista
            </button>
            <div class="collapse mb-4" id="formMot">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-person-lines-fill"></i> Ficha Cadastral</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Transportadora</label>
                            <select name="id_transportadora" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($transp as $tr): ?>
                                <option value="<?= $tr['id_transportadora'] ?>"><?= htmlspecialchars($tr['nome_fantasia']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome Completo</label>
                            <input type="text" name="nome" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">CPF</label>
                            <input type="text" name="cpf" class="form-control form-control-sm" placeholder="000.000.000-00">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Telefone</label>
                            <input type="text" name="telefone" class="form-control form-control-sm" placeholder="(XX) XXXXX-XXXX">
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-muted">Nº CNH</label>
                            <input type="text" name="cnh" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-3">
                            <label class="small fw-bold text-muted">Categoria</label>
                            <select name="categoria_cnh" class="form-select form-select-sm">
                                <option value="B">B</option>
                                <option value="C" selected>C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Validade CNH</label>
                            <input type="date" name="validade_cnh" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
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
                    <i class="bi bi-person-lines-fill text-success"></i> Motoristas Cadastrados
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Motorista</th>
                                <th>CNH / Categoria</th>
                                <th>Validade CNH</th>
                                <th>Transportadora</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum motorista cadastrado.</td></tr>
                        <?php else: foreach($lista as $m):
                            $vencido = !empty($m['validade_cnh']) && $m['validade_cnh'] < $hoje;
                        ?>
                            <tr class="row-h">
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($m['nome']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($m['cpf'] ?? '—') ?></small>
                                </td>
                                <td>
                                    <span class="font-monospace small d-block"><?= htmlspecialchars($m['cnh']) ?></span>
                                    <span class="badge bg-primary">CNH <?= htmlspecialchars($m['categoria_cnh'] ?? '—') ?></span>
                                </td>
                                <td>
                                    <?php if(!empty($m['validade_cnh'])): ?>
                                        <span class="badge <?= $vencido ? 'bg-danger' : 'bg-success' ?>">
                                            <?= date('d/m/Y', strtotime($m['validade_cnh'])) ?>
                                        </span>
                                    <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                </td>
                                <td><small><?= htmlspecialchars($m['nome_fantasia']) ?></small></td>
                                <td class="text-center">
                                    <span class="badge <?= $m['status']=='ATIVO' ? 'bg-success' : 'bg-secondary' ?>"><?= $m['status'] ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="?excluir=<?= $m['id_motorista'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir este motorista?')">
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
