<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - Gestão Logística</title>
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
            <span class="text-muted small text-uppercase fw-bold">Volume Total</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= number_format($total_itens,0,',','.') ?> <span class="fs-6 fw-normal text-muted">un</span></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">SKUs Cadastrados</span>
            <h3 class="fw-black text-primary m-0 mt-1"><?= count($produtos) ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Estoque Crítico</span>
            <h3 class="fw-black <?= $criticos>0?'text-danger':'text-success' ?> m-0 mt-1"><?= $criticos ?></h3>
        </div></div>
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Produtos Vencidos</span>
            <h3 class="fw-black <?= $vencidos>0?'text-danger':'text-success' ?> m-0 mt-1"><?= $vencidos ?></h3>
        </div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-arrow-left-right text-success"></i> Registrar Movimentação</div>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="movimentacao">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Produto</label>
                        <select name="id_produto" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($produtos as $p): ?>
                            <option value="<?= $p['id_produto'] ?>"><?= htmlspecialchars($p['descricao']) ?> (<?= $p['qtd_estoque'] ?> em estoque)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Tipo</label>
                        <select name="tipo_movimentacao" class="form-select form-select-sm" required>
                            <option value="ENTRADA">⬆ ENTRADA</option>
                            <option value="SAIDA">⬇ SAÍDA</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold text-muted">Quantidade</label>
                        <input type="number" name="quantidade" min="1" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-check-circle-fill"></i> Confirmar Movimentação
                        </button>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-geo-alt text-warning"></i> Registrar Localização em Armazém</div>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="localizacao">
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Produto</label>
                        <select name="id_produto_loc" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($produtos as $p): ?>
                            <option value="<?= $p['id_produto'] ?>"><?= htmlspecialchars($p['descricao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Armazém</label>
                        <select name="id_armazem" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach($armazens as $a): ?>
                            <option value="<?= $a['id_armazem'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-muted">Corredor</label>
                        <input type="text" name="corredor" class="form-control form-control-sm" placeholder="A1">
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-muted">Prateleira</label>
                        <input type="text" name="prateleira" class="form-control form-control-sm" placeholder="P3">
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-muted">Nível</label>
                        <input type="text" name="nivel" class="form-control form-control-sm" placeholder="N2">
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Quantidade nesse Local</label>
                        <input type="number" name="qtd_loc" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold py-2">
                            <i class="bi bi-pin-map-fill"></i> Registrar Localização
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-grid-3x3 text-primary"></i> Inventário Atual</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr><th>Produto</th><th class="text-center">Estoque</th><th>Validade</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach($produtos as $p):
                            $vencido = !empty($p['validade']) && $p['validade'] < $hoje;
                            $critico = $p['qtd_estoque'] < 10;
                        ?>
                            <tr class="row-h">
                                <td>
                                    <small class="fw-bold"><?= htmlspecialchars($p['descricao']) ?></small>
                                    <?php if($critico): ?><span class="badge bg-danger ms-1" style="font-size:9px">CRÍTICO</span><?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill <?= $critico?'bg-danger':'bg-success' ?>"><?= $p['qtd_estoque'] ?> un</span>
                                </td>
                                <td>
                                    <?php if(!empty($p['validade'])): ?>
                                        <span class="badge <?= $vencido?'bg-danger':'bg-light text-dark border' ?>" style="font-size:10px">
                                            <?= date('d/m/Y', strtotime($p['validade'])) ?>
                                        </span>
                                    <?php else: ?><small class="text-muted">—</small><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-pin-map text-warning"></i> Localizações por Armazém</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr><th>Produto</th><th>Armazém</th><th class="text-center">Endereçamento</th><th class="text-center">Qtd</th></tr>
                        </thead>
                        <tbody>
                        <?php if(empty($localizacoes)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma localização registrada.</td></tr>
                        <?php else: foreach($localizacoes as $l): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($l['descricao']) ?></small></td>
                                <td><small><?= htmlspecialchars($l['armazem_nome']) ?></small></td>
                                <td class="text-center"><code><?= htmlspecialchars($l['corredor']) ?>-<?= htmlspecialchars($l['prateleira']) ?>-<?= htmlspecialchars($l['nivel']) ?></code></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= $l['quantidade'] ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-secondary"></i> Últimas 20 Movimentações</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr><th>Produto</th><th class="text-center">Tipo</th><th class="text-center">Qtd</th><th>Data/Hora</th></tr>
                        </thead>
                        <tbody>
                        <?php if(empty($historico)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma movimentação registrada.</td></tr>
                        <?php else: foreach($historico as $h): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($h['descricao']) ?></small></td>
                                <td class="text-center">
                                    <span class="badge <?= $h['tipo_movimentacao']=='ENTRADA'?'bg-success':'bg-danger' ?>">
                                        <?= $h['tipo_movimentacao'] == 'ENTRADA' ? '⬆' : '⬇' ?> <?= $h['tipo_movimentacao'] ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold"><?= $h['quantidade'] ?></td>
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['data_movimentacao'])) ?></small></td>
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
