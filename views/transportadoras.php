<?php
/**
 * View: Transportadoras
 *
 * Exibe o painel de gestão de transportadoras do sistema.
 * Apresenta KPIs (total, ativas, inativas), formulário colapsável para
 * cadastro de novas transportadoras (com máscaras de CNPJ, telefone e
 * busca automática de endereço via ViaCEP) e tabela com as transportadoras
 * cadastradas, permitindo alternar status e excluir registros.
 *
 * Observação: transportadoras são a entidade raiz do sistema — motoristas e
 * veículos dependem delas, por isso não é possível excluir enquanto houver vínculos.
 *
 * Variáveis esperadas do controller:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de transportadoras cadastradas
 * - $ativas  (int)    Transportadoras com status ATIVA
 * - $estados (array)  Lista de UFs para o select de estado
 * - $lista   (array)  Lista de transportadoras cadastradas
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportadoras - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    /**
     * Aplica máscara de formatação ao campo de CNPJ (00.000.000/0000-00).
     * @param {HTMLInputElement} i - O elemento input de CNPJ
     */
    function maskCNPJ(i){
        var v=i.value.replace(/\D/g,'');
        v=v.replace(/^(\d{2})(\d)/,'$1.$2');
        v=v.replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3');
        v=v.replace(/\.(\d{3})(\d)/,'.$1/$2');
        v=v.replace(/(\d{4})(\d)/,'$1-$2');
        i.value=v.substr(0,18);
    }

    /**
     * Aplica máscara de formatação ao campo de telefone.
     * Formata para (XX) XXXX-XXXX (fixo) ou (XX) XXXXX-XXXX (celular).
     * @param {HTMLInputElement} i - O elemento input de telefone
     */
    function maskPhone(i){
        var v=i.value.replace(/\D/g,'');
        if(v.length<=10){
            v=v.replace(/^(\d{2})(\d)/,'($1) $2');
            v=v.replace(/(\d{4})(\d)/,'$1-$2');
        } else {
            v=v.replace(/^(\d{2})(\d)/,'($1) $2');
            v=v.replace(/(\d{5})(\d)/,'$1-$2');
        }
        i.value=v.substr(0,15);
    }

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

    <!-- ===== CARDS DE KPI DE TRANSPORTADORAS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de transportadoras cadastradas -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total Cadastradas</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?? 0 ?> <span class="fs-6 text-muted fw-normal">empresas</span></h3>
        </div></div>
        <!-- KPI: Transportadoras com status ATIVA -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Ativas</span>
            <h3 class="fw-black text-success m-0 mt-1"><?= $ativas ?? 0 ?> <span class="fs-6 text-muted fw-normal">operacionais</span></h3>
        </div></div>
        <!-- KPI: Transportadoras inativas (calculado como total - ativas) -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Inativas</span>
            <h3 class="fw-black text-danger m-0 mt-1"><?= ($total ?? 0) - ($ativas ?? 0) ?> <span class="fs-6 text-muted fw-normal">suspensas</span></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE CADASTRO ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de cadastro (colapso Bootstrap) -->
            <button class="btn btn-primary w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formTransp">
                <i class="bi bi-patch-plus-fill"></i> Nova Transportadora
            </button>

            <!-- Formulário colapsável de cadastro de transportadora -->
            <div class="collapse mb-4" id="formTransp">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-file-earmark-text"></i> Dados da Empresa</h6>

                    <!-- Formulário POST com dados da empresa transportadora -->
                    <form method="POST" class="row g-3">

                        <!-- Campo de CNPJ com máscara JavaScript aplicada no evento oninput -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">CNPJ</label>
                            <input type="text" name="cnpj" oninput="maskCNPJ(this)" class="form-control form-control-sm font-monospace" placeholder="00.000.000/0000-00" required>
                        </div>

                        <!-- Campo de razão social da empresa -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Razão Social</label>
                            <input type="text" name="razao_social" class="form-control form-control-sm" required>
                        </div>

                        <!-- Campo de nome fantasia (como a empresa é conhecida) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome Fantasia</label>
                            <input type="text" name="nome_fantasia" class="form-control form-control-sm" required>
                        </div>

                        <!-- Campos de telefone e e-mail de contato -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Telefone</label>
                            <input type="text" name="telefone" oninput="maskPhone(this)" class="form-control form-control-sm font-monospace" placeholder="(XX) XXXXX-XXXX" maxlength="15">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">E-mail</label>
                            <input type="email" name="email" class="form-control form-control-sm">
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

                        <!-- Campos de número e complemento -->
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Número</label>
                            <input type="text" name="numero" class="form-control form-control-sm">
                        </div>
                        <div class="col-8">
                            <label class="small fw-bold text-muted">Complemento</label>
                            <input type="text" name="complemento" class="form-control form-control-sm" placeholder="Apto, Sala...">
                        </div>

                        <!-- Campo de bairro (preenchido automaticamente pela busca de CEP) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Bairro</label>
                            <input type="text" name="bairro" class="form-control form-control-sm">
                        </div>

                        <!-- Campos de cidade e estado -->
                        <div class="col-8">
                            <label class="small fw-bold text-muted">Cidade</label>
                            <input type="text" name="cidade" class="form-control form-control-sm">
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold text-muted">Estado</label>
                            <select name="estado" class="form-select form-select-sm">
                                <option value="">UF</option>
                                <!-- Loop PHP: popula o select com as siglas de estado -->
                                <?php foreach($estados ?? [] as $uf): ?>
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Botão de submissão do formulário -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE TRANSPORTADORAS ===== -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3 d-flex align-items-center gap-2">
                    <span><i class="bi bi-list-task text-primary"></i> Transportadoras Cadastradas</span>
                    <!-- Ícone de informação com popover explicando a integridade referencial -->
                    <i class="bi bi-info-circle text-muted"
                       style="cursor:pointer;font-size:.95rem"
                       data-bs-toggle="popover"
                       data-bs-trigger="hover focus"
                       data-bs-placement="right"
                       data-bs-title="Integridade Referencial"
                       data-bs-content="Transportadoras são a raiz do sistema. Motoristas e Veículos dependem delas. Não é possível excluir enquanto houver vínculos ativos."></i>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Empresa</th>
                                <th>CNPJ</th>
                                <th>Contato</th>
                                <th>Endereço</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Bloco PHP: exibe mensagem se não há transportadoras cadastradas -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma transportadora cadastrada.</td></tr>
                        <!-- Loop PHP: itera sobre cada transportadora e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $t): ?>
                            <tr class="row-h">
                                <!-- Coluna: nome fantasia e razão social -->
                                <td>
                                    <strong class="d-block"><?= htmlspecialchars($t['nome_fantasia']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($t['razao_social']) ?></small>
                                </td>
                                <!-- Coluna: CNPJ em fonte monoespaçada -->
                                <td class="font-monospace small"><?= htmlspecialchars($t['cnpj']) ?></td>
                                <!-- Coluna: telefone e e-mail de contato -->
                                <td>
                                    <small class="d-block"><?= htmlspecialchars($t['telefone'] ?? '—') ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($t['email'] ?? '—') ?></small>
                                </td>
                                <!-- Coluna: endereço completo (logradouro, número, cidade/estado) -->
                                <td>
                                    <?php if($t['logradouro']): ?>
                                    <small class="d-block"><?= htmlspecialchars($t['logradouro'].($t['numero'] ? ', '.$t['numero'] : '')) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars(($t['cidade'] ?? '').' / '.($t['estado'] ?? '')) ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: badge de status (verde = ATIVA, cinza = inativa) -->
                                <td class="text-center">
                                    <span class="badge <?= $t['status']=='ATIVA' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                                <!-- Coluna: botões de editar, alternar status e excluir -->
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarTransp"
                                                data-id="<?= $t['id_transportadora'] ?>"
                                                data-cnpj="<?= htmlspecialchars($t['cnpj'], ENT_QUOTES) ?>"
                                                data-razao="<?= htmlspecialchars($t['razao_social'], ENT_QUOTES) ?>"
                                                data-fantasia="<?= htmlspecialchars($t['nome_fantasia'], ENT_QUOTES) ?>"
                                                data-telefone="<?= htmlspecialchars($t['telefone'] ?? '', ENT_QUOTES) ?>"
                                                data-email="<?= htmlspecialchars($t['email'] ?? '', ENT_QUOTES) ?>"
                                                data-cep="<?= htmlspecialchars($t['cep'] ?? '', ENT_QUOTES) ?>"
                                                data-logradouro="<?= htmlspecialchars($t['logradouro'] ?? '', ENT_QUOTES) ?>"
                                                data-numero="<?= htmlspecialchars($t['numero'] ?? '', ENT_QUOTES) ?>"
                                                data-complemento="<?= htmlspecialchars($t['complemento'] ?? '', ENT_QUOTES) ?>"
                                                data-bairro="<?= htmlspecialchars($t['bairro'] ?? '', ENT_QUOTES) ?>"
                                                data-cidade="<?= htmlspecialchars($t['cidade'] ?? '', ENT_QUOTES) ?>"
                                                data-estado="<?= htmlspecialchars($t['estado'] ?? '', ENT_QUOTES) ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?toggle=<?= $t['id_transportadora'] ?>" class="btn btn-sm btn-outline-secondary px-2" title="Alternar Status">
                                            <i class="bi bi-toggle-<?= $t['status']=='ATIVA' ? 'on text-success' : 'off' ?>"></i>
                                        </a>
                                        <a href="?excluir=<?= $t['id_transportadora'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                           onclick="return confirm('Excluir esta transportadora?')">
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

<!-- Modal de edição de transportadora -->
<div class="modal fade" id="modalEditarTransp" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Transportadora</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id_transportadora" id="et_id">
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="small fw-bold text-muted">CNPJ</label>
            <input type="text" name="cnpj" id="et_cnpj" oninput="maskCNPJ(this)" class="form-control form-control-sm font-monospace" required>
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Razão Social</label>
            <input type="text" name="razao_social" id="et_razao" class="form-control form-control-sm" required>
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Nome Fantasia</label>
            <input type="text" name="nome_fantasia" id="et_fantasia" class="form-control form-control-sm" required>
          </div>
          <div class="col-6">
            <label class="small fw-bold text-muted">Telefone</label>
            <input type="text" name="telefone" id="et_telefone" oninput="maskPhone(this)" class="form-control form-control-sm font-monospace" maxlength="15">
          </div>
          <div class="col-6">
            <label class="small fw-bold text-muted">E-mail</label>
            <input type="email" name="email" id="et_email" class="form-control form-control-sm">
          </div>
          <div class="col-12"><hr class="my-1"><small class="fw-bold text-secondary text-uppercase"><i class="bi bi-geo-alt"></i> Endereço</small></div>
          <div class="col-5">
            <label class="small fw-bold text-muted">CEP</label>
            <input type="text" name="cep" id="et_cep" oninput="maskCEP(this)" class="form-control form-control-sm font-monospace" maxlength="9">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Logradouro</label>
            <input type="text" name="logradouro" id="et_logradouro" class="form-control form-control-sm">
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Número</label>
            <input type="text" name="numero" id="et_numero" class="form-control form-control-sm">
          </div>
          <div class="col-8">
            <label class="small fw-bold text-muted">Complemento</label>
            <input type="text" name="complemento" id="et_complemento" class="form-control form-control-sm">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Bairro</label>
            <input type="text" name="bairro" id="et_bairro" class="form-control form-control-sm">
          </div>
          <div class="col-8">
            <label class="small fw-bold text-muted">Cidade</label>
            <input type="text" name="cidade" id="et_cidade" class="form-control form-control-sm">
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Estado</label>
            <select name="estado" id="et_estado" class="form-select form-select-sm">
              <option value="">UF</option>
              <?php foreach($estados ?? [] as $uf): ?>
              <option value="<?= $uf ?>"><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-check-circle"></i> Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));
document.getElementById('modalEditarTransp').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('et_id').value          = b.dataset.id;
    document.getElementById('et_cnpj').value        = b.dataset.cnpj;
    document.getElementById('et_razao').value       = b.dataset.razao;
    document.getElementById('et_fantasia').value    = b.dataset.fantasia;
    document.getElementById('et_telefone').value    = b.dataset.telefone;
    document.getElementById('et_email').value       = b.dataset.email;
    document.getElementById('et_cep').value         = b.dataset.cep;
    document.getElementById('et_logradouro').value  = b.dataset.logradouro;
    document.getElementById('et_numero').value      = b.dataset.numero;
    document.getElementById('et_complemento').value = b.dataset.complemento;
    document.getElementById('et_bairro').value      = b.dataset.bairro;
    document.getElementById('et_cidade').value      = b.dataset.cidade;
    document.getElementById('et_estado').value      = b.dataset.estado;
});
</script>
</body></html>
