<?php
/**
 * View: Armazéns
 *
 * Exibe o painel de gestão de armazéns (centros de distribuição) do sistema.
 * Apresenta KPI de total de armazéns ativos, formulário colapsável para
 * cadastro de novos armazéns (com busca automática de endereço via ViaCEP)
 * e tabela com os armazéns cadastrados, exibindo localização, SKUs e estoque.
 *
 * Variáveis esperadas do controller:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de armazéns ativos
 * - $estados (array)  Lista de UFs para o select de estado
 * - $lista   (array)  Lista de armazéns com total_skus e total_itens
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Armazéns - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    /**
     * Aplica máscara de formatação ao campo de CEP (00000-000)
     * e dispara a busca automática quando o CEP atingir 8 dígitos.
     * @param {HTMLInputElement} i - O elemento input de CEP
     */
    function maskCEP(i){
        var v=i.value.replace(/\D/g,'');
        var f=v.replace(/^(\d{5})(\d)/,'$1-$2');
        i.value=f.substr(0,9);
        if(v.length===8) buscaCEP(i);
    }

    /**
     * Consulta a API pública ViaCEP para preencher automaticamente
     * os campos de endereço com base no CEP informado.
     * @param {HTMLInputElement} input - O campo de CEP do formulário
     */
    async function buscaCEP(input){
        var cep=input.value.replace(/\D/g,'');
        if(cep.length!==8) return;
        try{
            var res=await fetch('https://viacep.com.br/ws/'+cep+'/json/');
            var d=await res.json();
            if(d.erro) return; // CEP inválido ou não encontrado
            var f=input.closest('form');
            // Preenche os campos de endereço com os dados retornados pela API
            f.querySelector('[name="logradouro"]').value=d.logradouro||'';
            f.querySelector('[name="bairro"]').value=d.bairro||'';
            f.querySelector('[name="cidade"]').value=d.localidade||'';
            f.querySelector('[name="estado"]').value=d.uf||'';
            f.querySelector('[name="numero"]').focus(); // Move o foco para o campo de número
        }catch(e){}
    }
    </script>
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

    <!-- ===== CARD DE KPI DE ARMAZÉNS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de armazéns/centros de distribuição ativos -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Armazéns Ativos</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?? 0?> <span class="fs-6 text-muted fw-normal">centros</span></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE CADASTRO ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de cadastro (colapso Bootstrap) -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formArm">
                <i class="bi bi-building-fill-add"></i> Novo Armazém
            </button>

            <!-- Formulário colapsável de cadastro de armazém -->
            <div class="collapse mb-4" id="formArm">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-geo-alt"></i> Dados do Centro de Distribuição</h6>

                    <!-- Formulário POST com nome e endereço do armazém -->
                    <form method="POST" class="row g-3">

                        <!-- Campo de nome do armazém/CD -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome do Armazém</label>
                            <input type="text" name="nome" class="form-control form-control-sm" placeholder="Ex: CD Minas Gerais" required>
                        </div>

                        <!-- Separador visual para a seção de endereço -->
                        <div class="col-12"><hr class="my-1"><small class="fw-bold text-secondary text-uppercase"><i class="bi bi-geo-alt"></i> Endereço</small></div>

                        <!-- Campo de CEP com máscara e busca automática via ViaCEP -->
                        <div class="col-5">
                            <label class="small fw-bold text-muted">CEP</label>
                            <input type="text" name="cep" oninput="maskCEP(this)" class="form-control form-control-sm font-monospace" placeholder="00000-000" maxlength="9">
                        </div>

                        <!-- Campo de logradouro (preenchido automaticamente pela busca de CEP) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Logradouro</label>
                            <input type="text" name="logradouro" class="form-control form-control-sm" placeholder="Rua, Av...">
                        </div>

                        <!-- Campos de número e complemento do endereço -->
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Número</label>
                            <input type="text" name="numero" class="form-control form-control-sm">
                        </div>
                        <div class="col-8">
                            <label class="small fw-bold text-muted">Complemento</label>
                            <input type="text" name="complemento" class="form-control form-control-sm" placeholder="Galpão, Bloco...">
                        </div>

                        <!-- Campo de bairro (preenchido automaticamente pela busca de CEP) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Bairro</label>
                            <input type="text" name="bairro" class="form-control form-control-sm">
                        </div>

                        <!-- Campos de cidade e estado (estado obrigatório) -->
                        <div class="col-8">
                            <label class="small fw-bold text-muted">Cidade</label>
                            <input type="text" name="cidade" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Estado</label>
                            <select name="estado" class="form-select form-select-sm" required>
                                <option value="">UF</option>
                                <!-- Loop PHP: popula o select com as siglas de estado -->
                                <?php foreach($estados ?? [] as $uf): ?>
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Botão de submissão do formulário -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE ARMAZÉNS ===== -->
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
                                <th>Endereço</th>
                                <th>Localização</th>
                                <th class="text-center">SKUs</th>
                                <th class="text-center">Itens em Estoque</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Bloco PHP: exibe mensagem se não há armazéns cadastrados -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum armazém cadastrado.</td></tr>
                        <!-- Loop PHP: itera sobre cada armazém e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $a): ?>
                            <tr class="row-h">
                                <!-- Coluna: nome do armazém e CEP em fonte monoespaçada -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($a['nome']) ?></strong>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($a['cep'] ?? '—') ?></small>
                                </td>
                                <!-- Coluna: logradouro com número e bairro -->
                                <td>
                                    <?php if($a['logradouro']): ?>
                                    <small class="d-block"><?= htmlspecialchars($a['logradouro'].($a['numero'] ? ', '.$a['numero'] : '')) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($a['bairro'] ?? '') ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: estado (badge) e cidade -->
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($a['estado'] ?? '—') ?></span>
                                    <small class="ms-1"><?= htmlspecialchars($a['cidade'] ?? '—') ?></small>
                                </td>
                                <!-- Coluna: total de SKUs (produtos distintos) no armazém -->
                                <td class="text-center"><span class="badge bg-info text-dark"><?= $a['total_skus'] ?></span></td>
                                <!-- Coluna: total de itens em estoque no armazém -->
                                <td class="text-center"><span class="fw-bold"><?= number_format($a['total_itens'],0,',','.') ?></span></td>
                                <!-- Coluna: botões de editar e excluir -->
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarArmazem"
                                                data-id="<?= $a['id_armazem'] ?>"
                                                data-nome="<?= htmlspecialchars($a['nome'], ENT_QUOTES) ?>"
                                                data-cep="<?= htmlspecialchars($a['cep'] ?? '', ENT_QUOTES) ?>"
                                                data-logradouro="<?= htmlspecialchars($a['logradouro'] ?? '', ENT_QUOTES) ?>"
                                                data-numero="<?= htmlspecialchars($a['numero'] ?? '', ENT_QUOTES) ?>"
                                                data-complemento="<?= htmlspecialchars($a['complemento'] ?? '', ENT_QUOTES) ?>"
                                                data-bairro="<?= htmlspecialchars($a['bairro'] ?? '', ENT_QUOTES) ?>"
                                                data-cidade="<?= htmlspecialchars($a['cidade'] ?? '', ENT_QUOTES) ?>"
                                                data-estado="<?= htmlspecialchars($a['estado'] ?? '', ENT_QUOTES) ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?excluir=<?= $a['id_armazem'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                           onclick="return confirm('Excluir este armazém?')">
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
<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal de edição de armazém -->
<div class="modal fade" id="modalEditarArmazem" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Armazém</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id_armazem" id="ea_id">
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="small fw-bold text-muted">Nome do Armazém</label>
            <input type="text" name="nome" id="ea_nome" class="form-control form-control-sm" required>
          </div>
          <div class="col-12"><hr class="my-1"><small class="fw-bold text-secondary text-uppercase"><i class="bi bi-geo-alt"></i> Endereço</small></div>
          <div class="col-5">
            <label class="small fw-bold text-muted">CEP</label>
            <input type="text" name="cep" id="ea_cep" oninput="maskCEP(this)" class="form-control form-control-sm font-monospace" maxlength="9">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Logradouro</label>
            <input type="text" name="logradouro" id="ea_logradouro" class="form-control form-control-sm">
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Número</label>
            <input type="text" name="numero" id="ea_numero" class="form-control form-control-sm">
          </div>
          <div class="col-8">
            <label class="small fw-bold text-muted">Complemento</label>
            <input type="text" name="complemento" id="ea_complemento" class="form-control form-control-sm">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Bairro</label>
            <input type="text" name="bairro" id="ea_bairro" class="form-control form-control-sm">
          </div>
          <div class="col-8">
            <label class="small fw-bold text-muted">Cidade</label>
            <input type="text" name="cidade" id="ea_cidade" class="form-control form-control-sm" required>
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Estado</label>
            <select name="estado" id="ea_estado" class="form-select form-select-sm" required>
              <option value="">UF</option>
              <?php foreach($estados ?? [] as $uf): ?>
              <option value="<?= $uf ?>"><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-warning fw-bold"><i class="bi bi-check-circle"></i> Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('modalEditarArmazem').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('ea_id').value          = b.dataset.id;
    document.getElementById('ea_nome').value        = b.dataset.nome;
    document.getElementById('ea_cep').value         = b.dataset.cep;
    document.getElementById('ea_logradouro').value  = b.dataset.logradouro;
    document.getElementById('ea_numero').value      = b.dataset.numero;
    document.getElementById('ea_complemento').value = b.dataset.complemento;
    document.getElementById('ea_bairro').value      = b.dataset.bairro;
    document.getElementById('ea_cidade').value      = b.dataset.cidade;
    document.getElementById('ea_estado').value      = b.dataset.estado;
});
</script>
</body></html>
