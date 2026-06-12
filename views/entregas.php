<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregas - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">
    <?php if($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3"><i class="bi bi-x-circle-fill fs-5"></i> <?= $erro ?></div>
    <?php endif; ?>
    <?php if($sucesso): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3"><i class="bi bi-check-circle-fill fs-5"></i> <?= $sucesso ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Pendentes</span>
            <h3 class="fw-black text-warning m-0 mt-1"><?= $pendentes ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Trânsito</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= $em_transito ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Entregues</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $entregues ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Atrasadas</span>
            <h3 class="fw-black text-danger m-0 mt-1"><?= $atrasadas ?></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formEntrega">
                <i class="bi bi-plus-circle-fill"></i> Nova Entrega
            </button>
            <div class="collapse mb-3" id="formEntrega">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-arrow-right"></i> Dados da Entrega</h6>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="nova_entrega">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Cliente Destinatário</label>
                            <select name="id_cliente" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <?php foreach($clientes as $c): ?>
                                <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Data Prevista de Entrega</label>
                            <input type="date" name="data_prevista" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Peso Total (kg)</label>
                            <input type="number" step="0.01" name="peso_total" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Volume Total (m³)</label>
                            <input type="number" step="0.01" name="volume_total" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Criar Entrega
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-seam"></i> Adicionar Produto à Entrega</h6>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="add_produto">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Entrega</label>
                        <select name="id_entrega" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($lista as $e): if($e['status'] != 'ENTREGUE'): ?>
                            <option value="<?= $e['id_entrega'] ?>">#<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($e['cliente_nome']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="small fw-bold text-muted">Produto</label>
                        <select name="id_produto" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($produtos as $p): ?>
                            <option value="<?= $p['id_produto'] ?>"><?= htmlspecialchars($p['descricao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-muted">Qtd</label>
                        <input type="number" name="quantidade" min="1" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-plus-circle"></i> Vincular Produto
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-list-check text-success"></i> Entregas Cadastradas
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nº</th>
                                <th>Cliente</th>
                                <th>Data Prevista</th>
                                <th class="text-center">Itens</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma entrega cadastrada.</td></tr>
                        <?php else: foreach($lista as $e):
                            $badge = match($e['status']) {
                                'PENDENTE'    => 'bg-warning text-dark',
                                'EM_TRANSITO' => 'bg-primary',
                                'ENTREGUE'    => 'bg-success',
                                'ATRASADA'    => 'bg-danger',
                                default       => 'bg-secondary'
                            };
                        ?>
                            <tr class="row-h">
                                <td><strong class="font-monospace">#<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($e['cliente_nome']) ?></strong>
                                    <small class="text-muted">Peso: <?= $e['peso_total'] ? number_format($e['peso_total'],1,',','.').' kg' : '—' ?></small>
                                </td>
                                <td>
                                    <?= $e['data_prevista'] ? date('d/m/Y', strtotime($e['data_prevista'])) : '—' ?>
                                    <?php if($e['data_realizada']): ?>
                                    <br><small class="text-success">Realizado: <?= date('d/m/Y', strtotime($e['data_realizada'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $e['total_itens'] ?></span></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="badge <?= $badge ?> border-0 dropdown-toggle" data-bs-toggle="dropdown" style="cursor:pointer">
                                            <?= $e['status'] ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item small" href="?status=PENDENTE&id=<?= $e['id_entrega'] ?>">Pendente</a></li>
                                            <li><a class="dropdown-item small" href="?status=EM_TRANSITO&id=<?= $e['id_entrega'] ?>">Em Trânsito</a></li>
                                            <li><a class="dropdown-item small" href="?status=ENTREGUE&id=<?= $e['id_entrega'] ?>">Entregue</a></li>
                                            <li><a class="dropdown-item small" href="?status=ATRASADA&id=<?= $e['id_entrega'] ?>">Atrasada</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="?excluir=<?= $e['id_entrega'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir esta entrega?')">
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
