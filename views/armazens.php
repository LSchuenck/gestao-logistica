<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Armazéns - Gestão Logística</title>
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
            <span class="text-muted small text-uppercase fw-bold">Armazéns Ativos</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?> <span class="fs-6 text-muted fw-normal">centros</span></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-warning w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formArm">
                <i class="bi bi-building-fill-add"></i> Novo Armazém
            </button>
            <div class="collapse mb-4" id="formArm">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-geo-alt"></i> Dados do Centro de Distribuição</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome do Armazém</label>
                            <input type="text" name="nome" class="form-control form-control-sm" placeholder="Ex: CD Minas Gerais" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Endereço</label>
                            <input type="text" name="endereco" class="form-control form-control-sm">
                        </div>
                        <div class="col-8">
                            <label class="small fw-bold text-muted">Cidade</label>
                            <input type="text" name="cidade" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Estado</label>
                            <select name="estado" class="form-select form-select-sm" required>
                                <option value="">UF</option>
                                <?php foreach($estados as $uf): ?>
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold py-2">
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
                    <i class="bi bi-houses text-warning"></i> Centros de Distribuição
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Armazém</th>
                                <th>Localização</th>
                                <th class="text-center">SKUs</th>
                                <th class="text-center">Itens em Estoque</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum armazém cadastrado.</td></tr>
                        <?php else: foreach($lista as $a): ?>
                            <tr class="row-h">
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($a['nome']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($a['endereco'] ?? '—') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($a['estado'] ?? '—') ?></span>
                                    <small class="ms-1"><?= htmlspecialchars($a['cidade'] ?? '—') ?></small>
                                </td>
                                <td class="text-center"><span class="badge bg-info text-dark"><?= $a['total_skus'] ?></span></td>
                                <td class="text-center"><span class="fw-bold"><?= number_format($a['total_itens'],0,',','.') ?></span></td>
                                <td class="text-center">
                                    <a href="?excluir=<?= $a['id_armazem'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir este armazém?')">
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
