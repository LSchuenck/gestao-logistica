<?php
/**
 * View: Entregas
 *
 * Exibe o painel de gerenciamento de entregas do sistema.
 * Apresenta KPIs (pendentes, em trânsito, entregues e atrasadas),
 * formulário para criar nova entrega com seleção dinâmica de produtos
 * e cálculo automático de peso/volume total via JavaScript, e tabela
 * com as entregas cadastradas com linhas expansíveis mostrando os produtos
 * vinculados e formulário de edição rápida do armazém de origem.
 *
 * Variáveis esperadas do controller:
 * - $erro                  (string) Mensagem de erro, se houver
 * - $sucesso               (string) Mensagem de sucesso, se houver
 * - $clientes              (array)  Lista de clientes para o select de destinatário
 * - $produtos              (array)  Lista de produtos com peso e volume unitários
 * - $armazens              (array)  Lista de armazéns para o select de origem
 * - $lista                 (array)  Lista de entregas cadastradas
 * - $produtos_por_entrega  (array)  Array indexado por id_entrega com itens vinculados
 * - $pendentes             (int)    Quantidade de entregas com status PENDENTE
 * - $em_transito           (int)    Quantidade de entregas com status EM_TRANSITO
 * - $entregues             (int)    Quantidade de entregas com status ENTREGUE
 * - $atrasadas             (int)    Quantidade de entregas com status ATRASADA
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregas - Gestão Logística</title>
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

    <!-- Bloco PHP: exibe alerta de erro se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3"><i class="bi bi-x-circle-fill fs-5"></i> <?= $erro ?></div>
    <?php endif; ?>

    <!-- Bloco PHP: exibe alerta de sucesso após operação bem-sucedida -->
    <?php if($sucesso): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3"><i class="bi bi-check-circle-fill fs-5"></i> <?= $sucesso ?></div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI DE ENTREGAS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Entregas aguardando saída (status PENDENTE) -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Pendentes</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $pendentes ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Entregas atualmente em rota (status EM_TRANSITO) -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Em Trânsito</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $em_transito ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Entregas concluídas com sucesso (status ENTREGUE) -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Entregues</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $entregues ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Entregas que passaram da data prevista (status ATRASADA) -->
        <div class="col-6 col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Atrasadas</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $atrasadas ?? 0 ?></h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE NOVA ENTREGA ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de nova entrega -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm"
                    type="button" data-bs-toggle="collapse" data-bs-target="#formEntrega">
                <i class="bi bi-plus-circle-fill"></i> Nova Entrega
            </button>

            <!-- Formulário colapsável para criar nova entrega -->
            <div class="collapse mb-3" id="formEntrega">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">

                    <!-- JSON com dados dos produtos para uso na tabela dinâmica de itens via JS -->
                    <script type="application/json" id="produtos-json">
                    <?= json_encode(array_map(fn($p) => [
                        'id'      => (int)$p['id_produto'],
                        'nome'    => $p['descricao'],
                        'peso'    => floatval($p['peso'] ?? 0),
                        'volume'  => floatval($p['volume'] ?? 0),
                    ], $produtos ?? []), JSON_HEX_TAG | JSON_HEX_AMP) ?>
                    </script>

                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-arrow-right me-1"></i>Dados da Entrega</h6>

                    <!-- Formulário POST para criar nova entrega com produtos vinculados -->
                    <form method="POST" id="form-nova-entrega">
                        <input type="hidden" name="acao" value="nova_entrega_completa">
                        <!-- Campos ocultos preenchidos via JS com o peso e volume totais calculados -->
                        <input type="hidden" name="peso_total"   id="input-peso-total">
                        <input type="hidden" name="volume_total" id="input-vol-total">

                        <div class="row g-3 mb-3">
                            <!-- Select do cliente destinatário da entrega -->
                            <div class="col-12">
                                <label class="small fw-bold text-muted">Cliente Destinatário</label>
                                <select name="id_cliente" class="form-select form-select-sm" required>
                                    <option value="">Selecione...</option>
                                    <!-- Loop PHP: lista os clientes cadastrados -->
                                    <?php foreach($clientes ?? [] as $c): ?>
                                    <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Select do armazém de origem com cidade/estado embutidos como data-attr para o Leaflet -->
                            <div class="col-12">
                                <label class="small fw-bold text-muted">Armazém de Origem</label>
                                <select name="id_armazem" id="sel-armazem-form" class="form-select form-select-sm"
                                        data-armazens="<?= htmlspecialchars(json_encode(array_map(fn($a) => [
                                            'id'     => (int)$a['id_armazem'],
                                            'cidade' => $a['cidade'] ?? '',
                                            'estado' => $a['estado'] ?? '',
                                        ], $armazens ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES) ?>">
                                    <option value="">Selecione...</option>
                                    <!-- Loop PHP: lista armazéns com cidade e estado (se disponíveis) -->
                                    <?php foreach($armazens ?? [] as $a): ?>
                                    <option value="<?= $a['id_armazem'] ?>">
                                        <?= htmlspecialchars($a['nome']) ?>
                                        <?php if(!empty($a['cidade'])): ?> — <?= htmlspecialchars($a['cidade'].($a['estado'] ? '/'.$a['estado'] : '')) ?><?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Campo de data prevista para a entrega -->
                            <div class="col-12">
                                <label class="small fw-bold text-muted">Data Prevista de Entrega</label>
                                <input type="date" name="data_prevista" class="form-control form-control-sm" required>
                            </div>
                        </div>

                        <!-- Seção de adição dinâmica de produtos à entrega -->
                        <div class="border-top pt-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-muted"><i class="bi bi-box-seam me-1"></i>Produtos</span>
                                <!-- Botão para adicionar uma nova linha de produto via JavaScript -->
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="addProdutoLinha()">
                                    <i class="bi bi-plus me-1"></i>Adicionar
                                </button>
                            </div>

                            <!-- Mensagem exibida quando nenhum produto foi adicionado ainda -->
                            <div id="itens-vazio" class="text-muted small fst-italic py-1 text-center">
                                Nenhum produto adicionado.
                            </div>

                            <!-- Tabela dinâmica de produtos (oculta até o primeiro produto ser adicionado) -->
                            <table class="table table-sm mb-0" id="tabela-itens" style="display:none">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th style="width:65px">Qtd</th>
                                        <th class="text-end" style="width:75px">Peso</th>
                                        <th class="text-end" style="width:75px">Volume</th>
                                        <th style="width:30px"></th>
                                    </tr>
                                </thead>
                                <!-- Corpo da tabela preenchido dinamicamente via JS (addProdutoLinha) -->
                                <tbody id="itens-tbody"></tbody>
                                <!-- Rodapé com totais de peso e volume calculados via JS (recalcTotais) -->
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="2" class="text-end small">Total</td>
                                        <td class="text-end small"><span id="total-peso-display">0,00</span> kg</td>
                                        <td class="text-end small"><span id="total-vol-display">0,0000</span> m³</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Botão de submissão do formulário de nova entrega -->
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2">
                                <i class="bi bi-check-circle me-1"></i>Criar Entrega
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE ENTREGAS CADASTRADAS ===== -->
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-3 border-bottom pb-3">
                    <i class="bi bi-list-check text-success"></i> Entregas Cadastradas
                    <span class="text-muted fw-normal small ms-1">— clique na linha para ver os produtos</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nº</th>
                                <th>Cliente</th>
                                <th>Armazém</th>
                                <th>Data Prevista</th>
                                <th class="text-center">Peso</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Bloco PHP: exibe mensagem se não há entregas cadastradas -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Nenhuma entrega cadastrada.</td></tr>
                        <!-- Loop PHP: itera sobre cada entrega e renderiza linha e linha expansível -->
                        <?php else: foreach($lista ?? [] as $e):
                            /* Define a cor do badge conforme o status da entrega */
                            $badge = match($e['status']) {
                                'PENDENTE'    => 'bg-warning text-dark',
                                'EM_TRANSITO' => 'bg-primary',
                                'ENTREGUE'    => 'bg-success',
                                'ATRASADA'    => 'bg-danger',
                                default       => 'bg-secondary'
                            };
                            /* Obtém os produtos vinculados a esta entrega */
                            $itens = $produtos_por_entrega[$e['id_entrega']] ?? [];
                        ?>
                            <!-- Linha da entrega (clicável para expandir o detalhe de produtos) -->
                            <tr class="row-h" style="cursor:pointer"
                                data-bs-toggle="collapse" data-bs-target="#prod-<?= $e['id_entrega'] ?>">
                                <!-- Coluna: número da entrega e badge com contagem de produtos -->
                                <td>
                                    <strong class="font-monospace">#<?= str_pad($e['id_entrega'],4,'0',STR_PAD_LEFT) ?></strong>
                                    <!-- Badge com contagem de produtos (visível apenas se houver itens) -->
                                    <?php if(!empty($itens)): ?>
                                    <span class="badge bg-secondary rounded-pill ms-1" style="font-size:.65rem"><?= count($itens) ?></span>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: nome do cliente destinatário em negrito -->
                                <td><strong><?= htmlspecialchars($e['cliente_nome']) ?></strong></td>
                                <!-- Coluna: nome e cidade do armazém de origem -->
                                <td>
                                    <?php if(!empty($e['armazem_nome'])): ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($e['armazem_nome']) ?></span>
                                    <?php if(!empty($e['armazem_cidade'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($e['armazem_cidade'].(!empty($e['armazem_estado']) ? '/'.$e['armazem_estado'] : '')) ?></small>
                                    <?php endif; ?>
                                    <?php else: ?><small class="text-muted">—</small><?php endif; ?>
                                </td>
                                <!-- Coluna: data prevista e data realizada (se disponível) -->
                                <td>
                                    <?= $e['data_prevista'] ? date('d/m/Y', strtotime($e['data_prevista'])) : '—' ?>
                                    <!-- Exibe a data realizada com ícone de confirmação se disponível -->
                                    <?php if($e['data_realizada']): ?>
                                    <br><small class="text-success"><i class="bi bi-check-circle-fill"></i> <?= date('d/m/Y', strtotime($e['data_realizada'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <!-- Coluna: peso total da entrega formatado -->
                                <td class="text-center">
                                    <small class="text-muted"><?= $e['peso_total'] ? number_format($e['peso_total'],1,',','.').' kg' : '—' ?></small>
                                </td>
                                <!-- Coluna: dropdown de alteração de status (stopPropagation evita colapso acidental) -->
                                <td class="text-center" onclick="event.stopPropagation()">
                                    <div class="dropdown">
                                        <button class="badge <?= $badge ?> border-0 dropdown-toggle" data-bs-toggle="dropdown" style="cursor:pointer">
                                            <?= str_replace('_',' ',$e['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item small" href="?status=PENDENTE&id=<?= $e['id_entrega'] ?>">Pendente</a></li>
                                            <li><a class="dropdown-item small" href="?status=EM_TRANSITO&id=<?= $e['id_entrega'] ?>">Em Trânsito</a></li>
                                            <li><a class="dropdown-item small" href="?status=ENTREGUE&id=<?= $e['id_entrega'] ?>">Entregue</a></li>
                                            <li><a class="dropdown-item small" href="?status=ATRASADA&id=<?= $e['id_entrega'] ?>">Atrasada</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <!-- Coluna: botão de exclusão com confirmação (stopPropagation evita colapso) -->
                                <td class="text-center" onclick="event.stopPropagation()">
                                    <a href="?excluir=<?= $e['id_entrega'] ?>" class="btn btn-sm btn-outline-danger px-2"
                                       onclick="return confirm('Excluir esta entrega?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <!-- Linha expansível com detalhe dos produtos e edição do armazém -->
                            <tr class="collapse" id="prod-<?= $e['id_entrega'] ?>">
                                <td colspan="7" class="bg-light py-2 px-4">

                                    <!-- Seção de edição rápida do armazém de origem via formulário inline -->
                                    <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                                        <i class="bi bi-building text-success" style="font-size:.85rem"></i>
                                        <span class="small fw-bold text-muted">Armazém de Origem:</span>
                                        <!-- Formulário POST para atualizar o armazém (stopPropagation evita fechar o colapso) -->
                                        <form method="POST" class="d-flex align-items-center gap-1 flex-grow-1" onclick="event.stopPropagation()">
                                            <input type="hidden" name="acao" value="atualizar_armazem">
                                            <input type="hidden" name="id_entrega" value="<?= $e['id_entrega'] ?>">
                                            <select name="id_armazem" class="form-select form-select-sm" style="max-width:260px">
                                                <option value="">Nenhum</option>
                                                <!-- Loop PHP: lista armazéns com o atual pré-selecionado -->
                                                <?php foreach($armazens ?? [] as $a): ?>
                                                <option value="<?= $a['id_armazem'] ?>"
                                                    <?= $e['id_armazem'] == $a['id_armazem'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($a['nome']) ?>
                                                    <?php if(!empty($a['cidade'])): ?> — <?= htmlspecialchars($a['cidade']) ?><?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <!-- Botão de confirmação da troca de armazém -->
                                            <button type="submit" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size:.75rem">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Exibe mensagem se nenhum produto foi vinculado à entrega -->
                                    <?php if(empty($itens)): ?>
                                    <span class="text-muted fst-italic small">Nenhum produto vinculado.</span>
                                    <!-- Tabela de produtos vinculados à entrega com quantidade, peso e volume -->
                                    <?php else: ?>
                                    <table class="table table-sm mb-0" style="font-size:.82rem">
                                        <thead><tr>
                                            <th class="fw-semibold text-muted">Produto</th>
                                            <th class="text-center fw-semibold text-muted" style="width:60px">Qtd</th>
                                            <th class="text-end fw-semibold text-muted" style="width:90px">Peso total</th>
                                            <th class="text-end fw-semibold text-muted" style="width:90px">Volume total</th>
                                        </tr></thead>
                                        <tbody>
                                        <!-- Loop PHP: itera sobre cada produto da entrega -->
                                        <?php foreach($itens as $it): ?>
                                        <tr>
                                            <!-- Coluna: descrição do produto -->
                                            <td><?= htmlspecialchars($it['descricao']) ?></td>
                                            <!-- Coluna: quantidade de unidades do produto -->
                                            <td class="text-center"><?= $it['quantidade'] ?></td>
                                            <!-- Coluna: peso total (peso unitário × quantidade) -->
                                            <td class="text-end text-muted">
                                                <?= $it['peso'] ? number_format($it['peso'] * $it['quantidade'], 2, ',', '.') . ' kg' : '—' ?>
                                            </td>
                                            <!-- Coluna: volume total (volume unitário × quantidade) -->
                                            <td class="text-end text-muted">
                                                <?= $it['volume'] ? number_format($it['volume'] * $it['quantidade'], 4, ',', '.') . ' m³' : '—' ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
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

<!-- Bootstrap JS para funcionalidades interativas (collapse, dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/entregas.js"></script>
</body></html>
