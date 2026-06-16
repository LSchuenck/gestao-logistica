<?php
/**
 * View: Clientes
 *
 * Exibe o painel de gestão de clientes do sistema.
 * Apresenta KPI de total de clientes, formulário colapsável para
 * cadastro de novos clientes (com máscara de telefone, CEP e busca
 * automática de endereço via API ViaCEP) e tabela com os clientes
 * cadastrados exibindo endereço e quantidade de entregas.
 *
 * Variáveis esperadas do controller:
 * - $erro    (string) Mensagem de erro, se houver
 * - $total   (int)    Total de clientes cadastrados
 * - $estados (array)  Lista de UFs para o select de estado
 * - $lista   (array)  Lista de clientes cadastrados com total_entregas
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Gestão Logística</title>
    <!-- Bootstrap CSS para estilização responsiva -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons para ícones visuais -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Estilos personalizados do sistema -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
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
     * os campos de endereço (logradouro, bairro, cidade e estado)
     * com base no CEP informado pelo usuário.
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
<?php exibirNavegacao(); ?>
<div class="container-fluid px-4 mb-5">

    <!-- Bloco PHP: exibe alerta de aviso se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= $erro ?>
    </div>
    <?php endif; ?>

    <!-- ===== CARD DE KPI DE CLIENTES ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de clientes cadastrados no sistema -->
        <div class="col-md-4"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Total de Clientes</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total ?? 0 ?> <span class="fs-6 text-muted fw-normal">cadastrados</span></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE CADASTRO ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de cadastro (colapso Bootstrap) -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formCliente">
                <i class="bi bi-person-plus-fill"></i> Novo Cliente
            </button>

            <!-- Formulário colapsável de cadastro de cliente -->
            <div class="collapse mb-4" id="formCliente">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-person-lines-fill"></i> Dados do Cliente</h6>

                    <!-- Formulário POST com dados do cliente e endereço de entrega -->
                    <form method="POST" class="row g-3">

                        <!-- Campo de nome ou razão social do cliente -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Nome / Razão Social</label>
                            <input type="text" name="nome" class="form-control form-control-sm" required>
                        </div>

                        <!-- Campo de CPF ou CNPJ (opcional) -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">CPF / CNPJ</label>
                            <input type="text" name="cpf_cnpj" class="form-control form-control-sm" placeholder="Opcional">
                        </div>

                        <!-- Campo de telefone com máscara JavaScript aplicada no evento oninput -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Telefone</label>
                            <input type="text" name="telefone" oninput="maskPhone(this)" class="form-control form-control-sm font-monospace" placeholder="(XX) XXXXX-XXXX" maxlength="15">
                        </div>

                        <!-- Separador visual para a seção de endereço -->
                        <div class="col-12"><hr class="my-1"><small class="fw-bold text-secondary text-uppercase"><i class="bi bi-geo-alt"></i> Endereço de Entrega</small></div>

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
                            <button type="submit" class="btn btn-info text-white btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle"></i> Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE CLIENTES ===== -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-people text-info"></i> Clientes Cadastrados
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>CPF / CNPJ</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th class="text-center">Entregas</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Bloco PHP: exibe mensagem se não há clientes cadastrados -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum cliente cadastrado.</td></tr>
                        <!-- Loop PHP: itera sobre cada cliente e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $c): ?>
                            <tr class="row-h">
                                <!-- Coluna: nome/razão social do cliente -->
                                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                                <!-- Coluna: CPF ou CNPJ formatado em fonte monoespaçada -->
                                <td class="small font-monospace"><?= htmlspecialchars($c['cpf_cnpj'] ?? '—') ?></td>
                                <!-- Coluna: telefone de contato -->
                                <td class="small"><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                                <!-- Coluna: endereço (logradouro+número e cidade/estado) -->
                                <td>
                                    <?php if($c['logradouro']): ?>
                                    <small class="d-block"><?= htmlspecialchars($c['logradouro'].($c['numero'] ? ', '.$c['numero'] : '')) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars(($c['cidade'] ?? '').' / '.($c['estado'] ?? '')) ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: quantidade total de entregas vinculadas ao cliente -->
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?= $c['total_entregas'] ?></span>
                                </td>
                                <!-- Coluna: botões de editar e excluir -->
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarCliente"
                                                data-id="<?= $c['id_cliente'] ?>"
                                                data-nome="<?= htmlspecialchars($c['nome'], ENT_QUOTES) ?>"
                                                data-cpfcnpj="<?= htmlspecialchars($c['cpf_cnpj'] ?? '', ENT_QUOTES) ?>"
                                                data-telefone="<?= htmlspecialchars($c['telefone'] ?? '', ENT_QUOTES) ?>"
                                                data-cep="<?= htmlspecialchars($c['cep'] ?? '', ENT_QUOTES) ?>"
                                                data-logradouro="<?= htmlspecialchars($c['logradouro'] ?? '', ENT_QUOTES) ?>"
                                                data-numero="<?= htmlspecialchars($c['numero'] ?? '', ENT_QUOTES) ?>"
                                                data-complemento="<?= htmlspecialchars($c['complemento'] ?? '', ENT_QUOTES) ?>"
                                                data-bairro="<?= htmlspecialchars($c['bairro'] ?? '', ENT_QUOTES) ?>"
                                                data-cidade="<?= htmlspecialchars($c['cidade'] ?? '', ENT_QUOTES) ?>"
                                                data-estado="<?= htmlspecialchars($c['estado'] ?? '', ENT_QUOTES) ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?excluir=<?= $c['id_cliente'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                           onclick="return confirm('Excluir este cliente?')">
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

<!-- Modal de edição de cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Editar Cliente</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id_cliente" id="ec_id">
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="small fw-bold text-muted">Nome / Razão Social</label>
            <input type="text" name="nome" id="ec_nome" class="form-control form-control-sm" required>
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">CPF / CNPJ</label>
            <input type="text" name="cpf_cnpj" id="ec_cpfcnpj" class="form-control form-control-sm" placeholder="Opcional">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Telefone</label>
            <input type="text" name="telefone" id="ec_telefone" oninput="maskPhone(this)" class="form-control form-control-sm font-monospace" maxlength="15">
          </div>
          <div class="col-12"><hr class="my-1"><small class="fw-bold text-secondary text-uppercase"><i class="bi bi-geo-alt"></i> Endereço de Entrega</small></div>
          <div class="col-5">
            <label class="small fw-bold text-muted">CEP</label>
            <input type="text" name="cep" id="ec_cep" oninput="maskCEP(this)" class="form-control form-control-sm font-monospace" maxlength="9">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Logradouro</label>
            <input type="text" name="logradouro" id="ec_logradouro" class="form-control form-control-sm">
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Número</label>
            <input type="text" name="numero" id="ec_numero" class="form-control form-control-sm">
          </div>
          <div class="col-8">
            <label class="small fw-bold text-muted">Complemento</label>
            <input type="text" name="complemento" id="ec_complemento" class="form-control form-control-sm">
          </div>
          <div class="col-12">
            <label class="small fw-bold text-muted">Bairro</label>
            <input type="text" name="bairro" id="ec_bairro" class="form-control form-control-sm">
          </div>
          <div class="col-8">
            <label class="small fw-bold text-muted">Cidade</label>
            <input type="text" name="cidade" id="ec_cidade" class="form-control form-control-sm">
          </div>
          <div class="col-4">
            <label class="small fw-bold text-muted">Estado</label>
            <select name="estado" id="ec_estado" class="form-select form-select-sm">
              <option value="">UF</option>
              <?php foreach($estados ?? [] as $uf): ?>
              <option value="<?= $uf ?>"><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-info text-white fw-bold"><i class="bi bi-check-circle"></i> Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('modalEditarCliente').addEventListener('show.bs.modal', function(e) {
    var b = e.relatedTarget;
    document.getElementById('ec_id').value          = b.dataset.id;
    document.getElementById('ec_nome').value        = b.dataset.nome;
    document.getElementById('ec_cpfcnpj').value     = b.dataset.cpfcnpj;
    document.getElementById('ec_telefone').value    = b.dataset.telefone;
    document.getElementById('ec_cep').value         = b.dataset.cep;
    document.getElementById('ec_logradouro').value  = b.dataset.logradouro;
    document.getElementById('ec_numero').value      = b.dataset.numero;
    document.getElementById('ec_complemento').value = b.dataset.complemento;
    document.getElementById('ec_bairro').value      = b.dataset.bairro;
    document.getElementById('ec_cidade').value      = b.dataset.cidade;
    document.getElementById('ec_estado').value      = b.dataset.estado;
});
</script>
</body></html>
