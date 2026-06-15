<?php
/**
 * View: Veículos
 *
 * Exibe o painel de gestão da frota de veículos do sistema.
 * Apresenta KPIs de frota (total, disponíveis, em viagem, capacidade total),
 * formulário colapsável para cadastro de novos veículos e tabela
 * com os veículos cadastrados, permitindo alterar status e excluir registros.
 *
 * Variáveis esperadas do controller:
 * - $erro          (string)  Mensagem de erro, se houver
 * - $total         (int)     Total de veículos na frota
 * - $disponiveis   (int)     Veículos com status DISPONIVEL
 * - $em_viagem     (int)     Veículos com status EM_VIAGEM
 * - $cap_total     (float)   Soma da capacidade de carga de todos os veículos (em kg)
 * - $transp        (array)   Lista de transportadoras para o select do formulário
 * - $lista         (array)   Lista de veículos cadastrados
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veículos - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<!-- Renderiza a barra de navegação superior do sistema -->
<?php renderNavbar(); ?>
<div class="container-fluid px-4 mb-5">

    <!-- Bloco PHP: exibe alerta de aviso se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI DA FROTA ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de veículos na frota -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Frota Total</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Veículos disponíveis para uso -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Disponíveis</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $disponiveis ?></h3>
        </div></div>
        <!-- KPI: Veículos atualmente em viagem -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Viagem</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $em_viagem ?></h3>
        </div></div>
        <!-- KPI: Capacidade total da frota convertida para toneladas -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Capacidade Total</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= number_format($cap_total/1000,1,',','.') ?> <span class="fs-6 fw-normal text-muted">ton</span></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE CADASTRO ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de cadastro (colapso Bootstrap) -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formVeic">
                <i class="bi bi-plus-square-fill"></i> Cadastrar Veículo
            </button>

            <!-- Formulário colapsável de cadastro de veículo -->
            <div class="collapse mb-4" id="formVeic">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-truck"></i> Dados do Veículo</h6>

                    <!-- Formulário POST com campos de dados do veículo -->
                    <form method="POST" class="row g-3">

                        <!-- Select de transportadora vinculada ao veículo -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Transportadora</label>
                            <select name="id_transportadora" class="form-select form-select-sm" required>
                                <option value="">Selecione...</option>
                                <!-- Loop PHP: popula o select com as transportadoras disponíveis -->
                                <?php foreach($transp ?? [] as $tr): ?>
                                <option value="<?= $tr['id_transportadora'] ?>"><?= htmlspecialchars($tr['nome_fantasia']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campo de placa do veículo -->
                        <div class="col-5">
                            <label class="small fw-bold text-muted">Placa</label>
                            <input type="text" name="placa" class="form-control form-control-sm text-center fw-bold" placeholder="AAA-0000" required>
                        </div>

                        <!-- Campo de modelo do veículo -->
                        <div class="col-7">
                            <label class="small fw-bold text-muted">Modelo</label>
                            <input type="text" name="modelo" class="form-control form-control-sm" placeholder="Ex: Volvo FH 540">
                        </div>

                        <!-- Select do tipo de veículo -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Tipo</label>
                            <select name="tipo_veiculo" class="form-select form-select-sm" required>
                                <option value="Van">Van / Furgão</option>
                                <option value="Caminhão" selected>Caminhão Rígido</option>
                                <option value="Carreta">Carreta Articulada</option>
                                <option value="Bitrem">Bitrem</option>
                            </select>
                        </div>

                        <!-- Campo de capacidade de carga em quilogramas -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Capacidade (kg)</label>
                            <input type="number" name="capacidade_carga" class="form-control form-control-sm" placeholder="Ex: 15000" required>
                        </div>

                        <!-- Botão de submissão do formulário -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-save-fill"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE VEÍCULOS ===== -->
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
                        <!-- Bloco PHP: exibe mensagem se não há veículos cadastrados -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum veículo cadastrado.</td></tr>
                        <!-- Loop PHP: itera sobre cada veículo e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $v):
                            /* Define a cor do badge de status conforme o status do veículo:
                               DISPONIVEL = verde, EM_VIAGEM = azul, outros = amarelo */
                            $badge = match($v['status']) {
                                'DISPONIVEL' => 'bg-success',
                                'EM_VIAGEM'  => 'bg-primary',
                                default      => 'bg-warning text-dark'
                            };
                        ?>
                            <tr class="row-h">
                                <!-- Coluna: placa do veículo em destaque -->
                                <td><span class="placa"><?= htmlspecialchars($v['placa']) ?></span></td>
                                <!-- Coluna: modelo e tipo do veículo -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($v['modelo'] ?? '—') ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($v['tipo_veiculo']) ?></small>
                                </td>
                                <!-- Coluna: capacidade em kg e toneladas -->
                                <td>
                                    <span class="fw-medium"><?= number_format($v['capacidade_carga'],0,',','.') ?> kg</span>
                                    <small class="d-block text-muted">(<?= number_format($v['capacidade_carga']/1000,1,',','.') ?> ton)</small>
                                </td>
                                <!-- Coluna: nome fantasia da transportadora -->
                                <td><small><?= htmlspecialchars($v['nome_fantasia']) ?></small></td>
                                <!-- Coluna: dropdown para alternar o status do veículo via GET -->
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
                                <!-- Coluna: botões de editar e excluir -->
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarVeiculo"
                                                data-id="<?= $v['id_veiculo'] ?>"
                                                data-idtransp="<?= $v['id_transportadora'] ?>"
                                                data-placa="<?= htmlspecialchars($v['placa'], ENT_QUOTES) ?>"
                                                data-modelo="<?= htmlspecialchars($v['modelo'] ?? '', ENT_QUOTES) ?>"
                                                data-tipo="<?= htmlspecialchars($v['tipo_veiculo'], ENT_QUOTES) ?>"
                                                data-capacidade="<?= htmlspecialchars($v['capacidade_carga'], ENT_QUOTES) ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?excluir=<?= $v['id_veiculo'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                           onclick="return confirm('Excluir este veículo?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
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
<!-- Bootstrap JS para funcionalidades interativas (colapso, dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal de edição de veículo -->
<div class="modal fade" id="modalEditarVeiculo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Veículo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id_veiculo" id="ev_id">
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="small fw-bold text-muted">Transportadora</label>
            <select name="id_transportadora" id="ev_transp" class="form-select form-select-sm" required>
              <option value="">Selecione...</option>
              <?php foreach($transp ?? [] as $tr): ?>
              <option value="<?= $tr['id_transportadora'] ?>"><?= htmlspecialchars($tr['nome_fantasia']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-5">
            <label class="small fw-bold text-muted">Placa</label>
            <input type="text" name="placa" id="ev_placa" class="form-control form-control-sm text-center fw-bold" required>
          </div>
          <div class="col-7">
            <label class="small fw-bold text-muted">Modelo</label>
            <input type="text" name="modelo" id="ev_modelo" class="form-control form-control-sm">
          </div>
          <div class="col-6">
            <label class="small fw-bold text-muted">Tipo</label>
            <select name="tipo_veiculo" id="ev_tipo" class="form-select form-select-sm" required>
              <option value="Van">Van / Furgão</option>
              <option value="Caminhão">Caminhão Rígido</option>
              <option value="Carreta">Carreta Articulada</option>
              <option value="Bitrem">Bitrem</option>
            </select>
          </div>
          <div class="col-6">
            <label class="small fw-bold text-muted">Capacidade (kg)</label>
            <input type="number" name="capacidade_carga" id="ev_capacidade" class="form-control form-control-sm" required>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-danger fw-bold"><i class="bi bi-check-circle"></i> Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('modalEditarVeiculo').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('ev_id').value        = b.dataset.id;
    document.getElementById('ev_transp').value    = b.dataset.idtransp;
    document.getElementById('ev_placa').value     = b.dataset.placa;
    document.getElementById('ev_modelo').value    = b.dataset.modelo;
    document.getElementById('ev_tipo').value      = b.dataset.tipo;
    document.getElementById('ev_capacidade').value = b.dataset.capacidade;
});
</script>
</body></html>
