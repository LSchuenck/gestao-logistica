<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportadoras - Gestão Logística</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    function maskCNPJ(i){
        var v=i.value.replace(/\D/g,'');
        v=v.replace(/^(\d{2})(\d)/,'$1.$2');
        v=v.replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3');
        v=v.replace(/\.(\d{3})(\d)/,'.$1/$2');
        v=v.replace(/(\d{4})(\d)/,'$1-$2');
        i.value=v.substr(0,18);
    }
    </script>
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
            <span class="text-muted small text-uppercase fw-bold">Total Cadastradas</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?> <span class="fs-6 text-muted fw-normal">empresas</span></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Ativas</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $ativas ?> <span class="fs-6 text-muted fw-normal">operacionais</span></h3>
        </div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Inativas</span>
            <h3 class="fw-black text-danger m-0 mt-1"><?= $total - $ativas ?> <span class="fs-6 text-muted fw-normal">suspensas</span></h3>
        </div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <button class="btn btn-primary w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formTransp">
                <i class="bi bi-patch-plus-fill"></i> Nova Transportadora
            </button>
            <div class="collapse mb-4" id="formTransp">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-file-earmark-text"></i> Dados da Empresa</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">CNPJ</label>
                            <input type="text" name="cnpj" oninput="maskCNPJ(this)" class="form-control form-control-sm font-monospace" placeholder="00.000.000/0000-00" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Razão Social</label>
                            <input type="text" name="razao_social" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Telefone</label>
                            <input type="text" name="telefone" class="form-control form-control-sm" placeholder="(XX) XXXXX-XXXX">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">E-mail</label>
                            <input type="email" name="email" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Endereço</label>
                            <input type="text" name="endereco" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card border-0 bg-dark text-white p-3 rounded-3 shadow-sm">
                <h6 class="small fw-bold text-warning mb-2"><i class="bi bi-info-circle"></i> Integridade Referencial</h6>
                <p class="m-0 text-muted" style="font-size:11px">Transportadoras são a raiz do sistema. Motoristas e Veículos dependem delas. Não é possível excluir enquanto houver vínculos ativos.</p>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-list-task text-primary"></i> Transportadoras Cadastradas
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Empresa</th>
                                <th>CNPJ</th>
                                <th>Contato</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma transportadora cadastrada.</td></tr>
                        <?php else: foreach($lista as $t): ?>
                            <tr class="row-h">
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($t['nome_fantasia']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($t['razao_social']) ?></small>
                                </td>
                                <td class="font-monospace small"><?= htmlspecialchars($t['cnpj']) ?></td>
                                <td>
                                    <small class="d-block"><?= htmlspecialchars($t['telefone'] ?? '—') ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($t['email'] ?? '—') ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $t['status']=='ATIVA' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <td class="text-center d-flex gap-1 justify-content-center">
                                    <a href="?toggle=<?= $t['id_transportadora'] ?>" class="btn btn-sm btn-outline-secondary px-2" title="Alternar Status">
                                        <i class="bi bi-toggle-<?= $t['status']=='ATIVA' ? 'on text-success' : 'off' ?>"></i>
                                    </a>
                                    <a href="?excluir=<?= $t['id_transportadora'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir esta transportadora?')">
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
