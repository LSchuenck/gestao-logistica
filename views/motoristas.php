<?php
/**
 * View: Motoristas
 *
 * Exibe o painel de gestão de motoristas do sistema.
 * Apresenta KPIs (total, ativos, CNH vencida), formulário colapsável
 * para cadastro de novos motoristas e tabela com os motoristas cadastrados,
 * indicando situação da CNH e permitindo exclusão de registros.
 *
 * Variáveis esperadas do controller:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de motoristas cadastrados
 * - $ativos  (int)    Motoristas com status ATIVO
 * - $vencidos(int)    Motoristas com CNH vencida
 * - $hoje    (string) Data atual no formato Y-m-d para comparação de CNH
 * - $transp  (array)  Lista de transportadoras para o select do formulário
 * - $lista   (array)  Lista de motoristas cadastrados
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motoristas - Gestão Logística</title>
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
<div class="container-fluid px-4 mb-5">

    <!-- Bloco PHP: exibe alerta de aviso se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI DE MOTORISTAS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de motoristas cadastrados -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total Cadastrados</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?? 0 ?> <span class="fs-6 text-muted fw-normal">motoristas</span></h3>
        </div></div>
        <!-- KPI: Motoristas ativos/habilitados -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Ativos</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $ativos ?? 0 ?> <span class="fs-6 text-muted fw-normal">habilitados</span></h3>
        </div></div>
        <!-- KPI: Motoristas com CNH vencida -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">CNH Vencida</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $vencidos ?> <span class="fs-6 text-muted fw-normal">pendentes</span></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE CADASTRO ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de cadastro (colapso Bootstrap) -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formMot">
                <i class="bi bi-person-plus-fill"></i> Cadastrar Motorista
            </button>

            <!-- Formulário colapsável de cadastro de motorista -->
            <div class="collapse mb-4" id="formMot">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-person-lines-fill"></i> Ficha Cadastral</h6>

                    <!-- Formulário POST com dados pessoais e da CNH do motorista -->
                    <form method="POST" class="row g-3">

                        <!-- Select de transportadora à qual o motorista é vinculado -->
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

                        <!-- Campo de nome completo do motorista -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome Completo</label>
                            <input type="text" name="nome" class="form-control form-control-sm" required>
                        </div>

                        <!-- Campo de CPF do motorista -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">CPF</label>
                            <input type="text" name="cpf" class="form-control form-control-sm" placeholder="000.000.000-00">
                        </div>

                        <!-- Campo de telefone de contato -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Telefone</label>
                            <input type="text" name="telefone" class="form-control form-control-sm" placeholder="(XX) XXXXX-XXXX">
                        </div>

                        <!-- Campo do número da CNH -->
                        <div class="col-5">
                            <label class="small fw-bold text-muted">Nº CNH</label>
                            <input type="text" name="cnh" class="form-control form-control-sm" required>
                        </div>

                        <!-- Select de categoria da CNH (B, C, D ou E) -->
                        <div class="col-3">
                            <label class="small fw-bold text-muted">Categoria</label>
                            <select name="categoria_cnh" class="form-select form-select-sm">
                                <option value="B">B</option>
                                <option value="C" selected>C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>

                        <!-- Campo de data de validade da CNH -->
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Validade CNH</label>
                            <input type="date" name="validade_cnh" class="form-control form-control-sm">
                        </div>

                        <!-- Botão de submissão do formulário -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE MOTORISTAS ===== -->
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
                        <!-- Bloco PHP: exibe mensagem se não há motoristas cadastrados -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum motorista cadastrado.</td></tr>
                        <!-- Loop PHP: itera sobre cada motorista e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $m):
                            /* Verifica se a CNH do motorista está vencida comparando com a data atual */
                            $vencido = !empty($m['validade_cnh']) && $m['validade_cnh'] < ($hoje ?? date('Y-m-d'));
                        ?>
                            <tr class="row-h">
                                <!-- Coluna: nome e CPF do motorista -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($m['nome']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($m['cpf'] ?? '—') ?></small>
                                </td>
                                <!-- Coluna: número e categoria da CNH -->
                                <td>
                                    <span class="font-monospace small d-block"><?= htmlspecialchars($m['cnh']) ?></span>
                                    <span class="badge bg-primary">CNH <?= htmlspecialchars($m['categoria_cnh'] ?? '—') ?></span>
                                </td>
                                <!-- Coluna: validade da CNH com badge colorido (vermelho se vencida, verde se válida) -->
                                <td>
                                    <?php if(!empty($m['validade_cnh'])): ?>
                                        <span class="badge <?= $vencido ? 'bg-danger' : 'bg-success' ?>">
                                            <?= date('d/m/Y', strtotime($m['validade_cnh'])) ?>
                                        </span>
                                    <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                </td>
                                <!-- Coluna: nome fantasia da transportadora vinculada -->
                                <td><small><?= htmlspecialchars($m['nome_fantasia']) ?></small></td>
                                <!-- Coluna: status do motorista (ATIVO = verde, outros = cinza) -->
                                <td class="text-center">
                                    <span class="badge <?= $m['status']=='ATIVO' ? 'bg-success' : 'bg-secondary' ?>"><?= $m['status'] ?></span>
                                </td>
                                <!-- Coluna: botões de editar e excluir -->
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarMotorista"
                                                data-id="<?= $m['id_motorista'] ?>"
                                                data-idtransp="<?= $m['id_transportadora'] ?>"
                                                data-nome="<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>"
                                                data-cpf="<?= htmlspecialchars($m['cpf'] ?? '', ENT_QUOTES) ?>"
                                                data-telefone="<?= htmlspecialchars($m['telefone'] ?? '', ENT_QUOTES) ?>"
                                                data-cnh="<?= htmlspecialchars($m['cnh'], ENT_QUOTES) ?>"
                                                data-categoria="<?= htmlspecialchars($m['categoria_cnh'], ENT_QUOTES) ?>"
                                                data-validade="<?= htmlspecialchars($m['validade_cnh'] ?? '', ENT_QUOTES) ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?excluir=<?= $m['id_motorista'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                           onclick="return confirm('Excluir este motorista?')">
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

<!-- Modal de edição de motorista -->
<div class="modal fade" id="modalEditarMotorista" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Motorista</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id_motorista" id="em_id">
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="small fw-bold text-muted">Transportadora</label>
            <select name="id_transportadora" id="em_transp" class="form-select form-select-sm" required>
              <option value="">Selecione...</option>
              <?php foreach($transp ?? [] as $tr): ?>
              <option value="<?= $tr['id_transportadora'] ?>"><?= htmlspecialchars($tr['nome_fantasia']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Nome Completo</label>
            <input type="text" name="nome" id="em_nome" class="form-control form-control-sm" required>
          </div>
          <div class="col-6">
            <label class="small fw-bold text-muted">CPF</label>
            <input type="text" name="cpf" id="em_cpf" class="form-control form-control-sm" placeholder="000.000.000-00">
          </div>
          <div class="col-6">
            <label class="small fw-bold text-muted">Telefone</label>
            <input type="text" name="telefone" id="em_telefone" class="form-control form-control-sm">
          </div>
          <div class="col-5">
            <label class="small fw-bold text-muted">Nº CNH</label>
            <input type="text" name="cnh" id="em_cnh" class="form-control form-control-sm" required>
          </div>
          <div class="col-3">
            <label class="small fw-bold text-muted">Categoria</label>
            <select name="categoria_cnh" id="em_categoria" class="form-select form-select-sm">
              <option value="B">B</option>
              <option value="C">C</option>
              <option value="D">D</option>
              <option value="E">E</option>
            </select>
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Validade CNH</label>
            <input type="date" name="validade_cnh" id="em_validade" class="form-control form-control-sm">
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-circle"></i> Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('modalEditarMotorista').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('em_id').value        = b.dataset.id;
    document.getElementById('em_transp').value    = b.dataset.idtransp;
    document.getElementById('em_nome').value      = b.dataset.nome;
    document.getElementById('em_cpf').value       = b.dataset.cpf;
    document.getElementById('em_telefone').value  = b.dataset.telefone;
    document.getElementById('em_cnh').value       = b.dataset.cnh;
    document.getElementById('em_categoria').value = b.dataset.categoria;
    document.getElementById('em_validade').value  = b.dataset.validade;
});
</script>
</body></html>
