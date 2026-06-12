<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fretes e NF - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">
    <?php if($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill fs-5"></i> <?= $erro ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Fretes Emitidos</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_fretes ?></h3>
        </div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Receita Total</span>
            <h3 class="fw-black text-primary m-0 mt-1" style="font-size:1.3rem">R$ <?= number_format($total_valor,2,',','.') ?></h3>
        </div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Custo Operacional</span>
            <h3 class="fw-black text-danger m-0 mt-1" style="font-size:1.3rem">R$ <?= number_format($total_custo,2,',','.') ?></h3>
        </div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Margem Bruta</span>
            <h3 class="fw-black <?= $margem>30?'text-success':'text-warning' ?> m-0 mt-1"><?= number_format($margem,1,',','.') ?>%</h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm text-white" style="background:#6f42c1" type="button" data-bs-toggle="collapse" data-bs-target="#formFrete">
                <i class="bi bi-file-earmark-plus-fill"></i> Emitir Frete / NF
            </button>
            <div class="collapse mb-4" id="formFrete">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-receipt"></i> Dados do Frete</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Viagem (sem frete)</label>
                            <select name="id_viagem" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($viagens_sem_frete as $v): ?>
                                <option value="<?= $v['id_viagem'] ?>">#<?= str_pad($v['id_viagem'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($v['motorista']) ?> (<?= htmlspecialchars($v['placa']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Transportadora</label>
                            <select name="id_transportadora" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($transportadoras as $t): ?>
                                <option value="<?= $t['id_transportadora'] ?>"><?= htmlspecialchars($t['nome_fantasia']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Valor do Frete (R$)</label>
                            <input type="number" step="0.01" name="valor" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Custo Operacional (R$)</label>
                            <input type="number" step="0.01" name="custo_operacional" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Nº Nota Fiscal</label>
                            <input type="text" name="nota_fiscal" class="form-control form-control-sm" placeholder="NF-000001">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Data de Emissão</label>
                            <input type="date" name="data_emissao" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-sm w-100 fw-bold py-2 text-white" style="background:#6f42c1">
                                <i class="bi bi-file-earmark-check-fill"></i> Emitir Frete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-receipt-cutoff text-warning"></i> Fretes Registrados
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>NF / Frete</th>
                                <th>Transportadora</th>
                                <th>Motorista / Veículo</th>
                                <th class="text-end">Valor</th>
                                <th>Emissão</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum frete registrado.</td></tr>
                        <?php else: foreach($lista as $f): ?>
                            <tr class="row-h">
                                <td>
                                    <strong class="d-block font-monospace"><?= htmlspecialchars($f['nota_fiscal'] ?? 'NF-'.str_pad($f['id_frete'],6,'0',STR_PAD_LEFT)) ?></strong>
                                    <small class="text-muted">Viagem #<?= str_pad($f['id_viagem'],4,'0',STR_PAD_LEFT) ?></small>
                                </td>
                                <td><small><?= htmlspecialchars($f['transportadora']) ?></small></td>
                                <td>
                                    <small class="d-block fw-bold"><?= htmlspecialchars($f['motorista']) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($f['placa']) ?></small>
                                </td>
                                <td class="text-end fw-bold text-primary">R$ <?= number_format($f['valor'],2,',','.') ?></td>
                                <td><small><?= $f['data_emissao'] ? date('d/m/Y', strtotime($f['data_emissao'])) : '—' ?></small></td>
                                <td class="text-center">
                                    <a href="?nf=<?= $f['id_frete'] ?>" class="btn btn-sm btn-outline-success px-2 me-1" title="Ver NF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <a href="?excluir=<?= $f['id_frete'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir este frete?')">
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
