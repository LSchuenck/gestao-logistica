<?php
/**
 * View: Fretes e NF (Notas Fiscais)
 *
 * Exibe o painel de gestão de fretes e notas fiscais do sistema.
 * Apresenta KPIs financeiros (fretes emitidos, receita total, custo
 * operacional e margem bruta), formulário colapsável com calculadora
 * de frete (peso cubado, tarifas por km e kg) e tabela com os fretes
 * registrados, exibindo nota fiscal, transportadora, motorista e valor.
 *
 * Variáveis esperadas do controller:
 * - $erro               (string) Mensagem de erro, se houver
 * - $total_fretes       (int)    Total de fretes emitidos
 * - $total_valor        (float)  Soma dos valores de todos os fretes
 * - $total_custo        (float)  Soma dos custos operacionais
 * - $margem             (float)  Margem bruta percentual
 * - $viagens_sem_frete  (array)  Viagens concluídas ainda sem frete emitido
 * - $transportadoras    (array)  Lista de transportadoras para o select
 * - $lista              (array)  Lista de fretes registrados
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fretes e NF - Gestão Logística</title>
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

    <!-- Bloco PHP: exibe alerta de erro se houver mensagem de erro do controller -->
    <?php if($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4"><i class="bi bi-x-circle-fill fs-5"></i> <?= $erro ?></div>
    <?php endif; ?>

    <!-- ===== CARDS DE KPI FINANCEIROS ===== -->
    <div class="row g-3 mb-4">
        <!-- KPI: Total de fretes emitidos -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Fretes Emitidos</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= $total_fretes ?? 0 ?></h3>
        </div></div>
        <!-- KPI: Receita total acumulada -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Receita Total</span>
            <h3 class="fw-black text-dark m-0 mt-1" style="font-size:1.3rem">R$ <?= number_format($total_valor ?? 0,2,',','.') ?></h3>
        </div></div>
        <!-- KPI: Custo operacional total -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Custo Operacional</span>
            <h3 class="fw-black text-dark m-0 mt-1" style="font-size:1.3rem">R$ <?= number_format($total_custo ?? 0,2,',','.') ?></h3>
        </div></div>
        <!-- KPI: Margem bruta em percentual -->
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-white p-3">
            <span class="text-muted small text-uppercase fw-bold">Margem Bruta</span>
            <h3 class="fw-black text-dark m-0 mt-1"><?= number_format($margem ?? 0,1,',','.') ?>%</h3>
        </div></div>
    </div>

    <div class="row g-4">

        <!-- ===== COLUNA ESQUERDA: FORMULÁRIO DE EMISSÃO DE FRETE ===== -->
        <div class="col-xl-4 col-lg-5">

            <!-- Botão que abre/fecha o formulário de emissão de frete -->
            <button class="btn btn-success w-100 mb-3 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formFrete">
                <i class="bi bi-file-earmark-plus-fill"></i> Emitir Frete / NF
            </button>

            <!-- JSON com dados das viagens sem frete (lido por assets/js/frete.js) -->
            <script type="application/json" id="viagens-json">
            <?= json_encode(array_map(fn($v) => [
                'id'                  => (int)$v['id_viagem'],
                'id_transportadora'   => (int)$v['id_transportadora'],
                'transportadora_nome' => $v['transportadora_nome'],
                'distancia'           => floatval($v['distancia']),
                'peso_total'          => floatval($v['peso_total']),
                'volume_total'        => floatval($v['volume_total']),
                'total_entregas'      => (int)$v['total_entregas'],
            ], $viagens_sem_frete ?? []), JSON_HEX_TAG | JSON_HEX_AMP) ?>
            </script>

            <!-- Formulário colapsável de emissão de frete -->
            <div class="collapse mb-4" id="formFrete">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-receipt"></i> Dados do Frete</h6>

                    <!-- Formulário POST para emitir novo frete -->
                    <form method="POST" class="row g-3" id="form-frete">

                        <!-- Select de viagem sem frete para associar ao novo frete -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Viagem (sem frete)</label>
                            <select name="id_viagem" id="sel-viagem" class="form-select form-select-sm" required onchange="onViagemChange()">
                                <option value="">Selecione...</option>
                                <!-- Loop PHP: lista viagens concluídas sem frete emitido -->
                                <?php foreach($viagens_sem_frete ?? [] as $v): ?>
                                <option value="<?= $v['id_viagem'] ?>">#<?= str_pad($v['id_viagem'],4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($v['motorista']) ?> (<?= htmlspecialchars($v['placa']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Painel de informações da viagem selecionada (exibido via JS após seleção) -->
                        <div class="col-12" id="painel-viagem" style="display:none">
                            <div class="rounded-3 border bg-light p-2">
                                <!-- Cards resumidos de distância, peso e volume da viagem -->
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <div class="small text-muted fw-bold text-uppercase" style="font-size:.67rem">Distância</div>
                                        <div class="fw-bold" id="info-dist">—</div>
                                        <div class="text-muted" style="font-size:.7rem">km</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted fw-bold text-uppercase" style="font-size:.67rem">Peso Total</div>
                                        <div class="fw-bold" id="info-peso">—</div>
                                        <div class="text-muted" style="font-size:.7rem">kg</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted fw-bold text-uppercase" style="font-size:.67rem">Volume Total</div>
                                        <div class="fw-bold" id="info-vol">—</div>
                                        <div class="text-muted" style="font-size:.7rem">m³</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Calculadora de tarifas de frete -->
                            <div class="mt-2 p-2 border rounded-3 bg-white">
                                <div class="small fw-bold text-muted mb-2"><i class="bi bi-calculator me-1"></i>Tarifas para cálculo</div>
                                <div class="row g-2">
                                    <!-- Campo de tarifa por quilômetro rodado -->
                                    <div class="col-6">
                                        <label class="small text-muted" style="font-size:.72rem">R$ por km</label>
                                        <input type="number" step="0.01" min="0" id="tarifa-km" value="3.50"
                                               class="form-control form-control-sm" oninput="calcularFrete()">
                                    </div>
                                    <!-- Campo de tarifa por kg taxado (considera peso cubado) -->
                                    <div class="col-6">
                                        <label class="small text-muted" style="font-size:.72rem">R$ por kg taxado</label>
                                        <input type="number" step="0.001" min="0" id="tarifa-kg" value="0.50"
                                               class="form-control form-control-sm" oninput="calcularFrete()">
                                    </div>
                                </div>
                                <!-- Painel de resultado do cálculo de frete sugerido -->
                                <div class="mt-2 p-2 rounded-2" style="background:#f3f0ff">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">Peso cubado (vol × 300):</span>
                                        <span class="small fw-bold" id="info-cubado">—</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">Peso taxado (maior):</span>
                                        <span class="small fw-bold" id="info-taxado">—</span>
                                    </div>
                                    <hr class="my-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-secondary">Valor sugerido:</span>
                                        <span class="fw-bold text-success" id="info-sugerido">R$ —</span>
                                    </div>
                                    <!-- Botão para aplicar o valor calculado automaticamente no campo de valor -->
                                    <button type="button" class="btn btn-sm w-100 mt-2 fw-bold" style="background:#ede7f6;color:#6f42c1;font-size:.8rem" onclick="aplicarValorSugerido()">
                                        <i class="bi bi-arrow-down-circle me-1"></i>Aplicar valor sugerido
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Transportadora derivada automaticamente da viagem selecionada -->
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Transportadora</label>
                            <input type="hidden" name="id_transportadora" id="input-id-transportadora">
                            <div class="form-control form-control-sm bg-light text-muted" id="display-transportadora"
                                 style="cursor:default;min-height:31px">
                                <span class="text-secondary fst-italic" style="font-size:.82rem">Selecione uma viagem acima</span>
                            </div>
                        </div>

                        <!-- Campos de valor do frete e custo operacional -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Valor do Frete (R$)</label>
                            <input type="number" step="0.01" name="valor" id="input-valor-frete" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Custo Operacional (R$)</label>
                            <input type="number" step="0.01" name="custo_operacional" class="form-control form-control-sm">
                        </div>

                        <!-- Campos de número da nota fiscal e data de emissão -->
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Nº Nota Fiscal</label>
                            <input type="text" name="nota_fiscal" class="form-control form-control-sm" placeholder="NF-000001">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">Data de Emissão</label>
                            <!-- Valor padrão é a data atual -->
                            <input type="date" name="data_emissao" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Botão de submissão para emitir o frete -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-sm w-100 fw-bold py-2 text-white" style="background:#6f42c1">
                                <i class="bi bi-file-earmark-check-fill"></i> Emitir Frete
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== COLUNA DIREITA: TABELA DE FRETES REGISTRADOS ===== -->
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
                        <!-- Bloco PHP: exibe mensagem se não há fretes registrados -->
                        <?php if(empty($lista)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum frete registrado.</td></tr>
                        <!-- Loop PHP: itera sobre cada frete e renderiza uma linha na tabela -->
                        <?php else: foreach($lista as $f): ?>
                            <tr class="row-h">
                                <!-- Coluna: número da NF (ou NF gerado com ID) e viagem vinculada -->
                                <td>
                                    <strong class="d-block font-monospace"><?= htmlspecialchars($f['nota_fiscal'] ?? 'NF-'.str_pad($f['id_frete'],6,'0',STR_PAD_LEFT)) ?></strong>
                                    <small class="text-muted">Viagem #<?= str_pad($f['id_viagem'],4,'0',STR_PAD_LEFT) ?></small>
                                </td>
                                <!-- Coluna: nome fantasia da transportadora -->
                                <td><small><?= htmlspecialchars($f['transportadora']) ?></small></td>
                                <!-- Coluna: motorista e placa do veículo -->
                                <td>
                                    <small class="d-block fw-bold"><?= htmlspecialchars($f['motorista']) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($f['placa']) ?></small>
                                </td>
                                <!-- Coluna: valor do frete em R$ destacado em azul -->
                                <td class="text-end fw-bold text-primary">R$ <?= number_format($f['valor'],2,',','.') ?></td>
                                <!-- Coluna: data de emissão da NF formatada -->
                                <td><small><?= $f['data_emissao'] ? date('d/m/Y', strtotime($f['data_emissao'])) : '—' ?></small></td>
                                <!-- Coluna: botões de visualizar NF em PDF e excluir -->
                                <td class="text-center">
                                    <!-- Botão de visualização da NF (redirecionamento via GET) -->
                                    <a href="?nf=<?= $f['id_frete'] ?>" class="btn btn-sm btn-outline-success px-2 me-1" title="Ver NF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <!-- Botão de exclusão com confirmação JavaScript -->
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

<!-- Bootstrap JS para funcionalidades interativas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/frete.js"></script>
</body></html>
