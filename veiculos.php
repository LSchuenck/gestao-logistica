<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veículos - Gestão Logística</title>
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
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Frota Total</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Disponíveis</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $disponiveis ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Viagem</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= $em_viagem ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Capacidade Total</span>
            <h3 class="fw-black text-danger m-0 mt-1"><?= number_format($cap_total/1000,1,',','.') ?> <span class="fs-6 fw-normal text-muted">ton</span></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-danger w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formVeic">
                <i class="bi bi-plus-square-fill"></i> Cadastrar Veículo
            </button>
            <div class="collapse mb-4" id="formVeic">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-truck"></i> Dados do Veículo</h6>
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
                        <div class="col-5">
                            <label class="small fw-bold text-muted">Placa</label>
                            <input type="text" name="placa" class="form-control form-control-sm text-center fw-bold" placeholder="AAA-0000" required>
                        </div>
                        <div class="col-7">
                            <label class="small fw-bold text-muted">Modelo</label>
                            <input type="text" name="modelo" class="form-control form-control-sm" placeholder="Ex: Volvo FH 540">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Tipo</label>
                            <select name="tipo_veiculo" class="form-select form-select-sm" required>
                                <option value="Van">Van / Furgão</option>
                                <option value="Caminhão" selected>Caminhão Rígido</option>
                                <option value="Carreta">Carreta Articulada</option>
                                <option value="Bitrem">Bitrem</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Capacidade (kg)</label>
                            <input type="number" name="capacidade_carga" class="form-control form-control-sm" placeholder="Ex: 15000" required>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-save-fill"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-truck-front text-danger"></i> Frota Cadastrada
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Placa</th>
                                <th>Modelo / Tipo</th>
                                <th>Capacidade</th>
                                <th>Transportadora</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum veículo cadastrado.</td></tr>
                        <?php else: foreach($lista as $v):
                            $badge = match($v['status']) {
                                'DISPONIVEL' => 'bg-success',
                                'EM_VIAGEM'  => 'bg-primary',
                                default      => 'bg-warning text-dark'
                            };
                        ?>
                            <tr class="row-h">
                                <td><span class="placa"><?= htmlspecialchars($v['placa']) ?></span></td>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($v['modelo'] ?? '—') ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($v['tipo_veiculo']) ?></small>
                                </td>
                                <td>
                                    <span class="fw-medium"><?= number_format($v['capacidade_carga'],0,',','.') ?> kg</span>
                                    <small class="d-block text-muted">(<?= number_format($v['capacidade_carga']/1000,1,',','.') ?> ton)</small>
                                </td>
                                <td><small><?= htmlspecialchars($v['nome_fantasia']) ?></small></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="badge <?= $badge ?> border-0 dropdown-toggle" data-bs-toggle="dropdown" style="cursor:pointer">
                                            <?= str_replace('_',' ',$v['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item small" href="?status=DISPONIVEL&id=<?= $v['id_veiculo'] ?>">Disponível</a></li>
                                            <li><a class="dropdown-item small" href="?status=EM_VIAGEM&id=<?= $v['id_veiculo'] ?>">Em Viagem</a></li>
                                            <li><a class="dropdown-item small" href="?status=MANUTENCAO&id=<?= $v['id_veiculo'] ?>">Manutenção</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="?excluir=<?= $v['id_veiculo'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir este veículo?')">
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
