<?php
/*
 * Arquivo: controller/AlertaController.php
 * Finalidade: Controller do módulo de Alertas.
 * Responsável por coletar as três categorias de alertas via AlertaDao,
 * normalizá-los em um array uniforme, ordená-los por prioridade e
 * preparar as variáveis para a view views/alertas.php.
 *
 * Categorias de alertas:
 *  - ATRASO  → entregas com data prevista vencida
 *  - VIAGEM  → viagens com prazo de chegada ultrapassado
 *  - ESTOQUE → produtos com saldo abaixo de 10 unidades
 */

class AlertaController
{
    /** @var AlertaDao DAO responsável pelo acesso aos dados de alertas */
    private AlertaDao $dao;

    /**
     * Construtor: injeta o DAO de alertas.
     *
     * @param AlertaDao $dao Instância do DAO de alertas
     */
    public function __construct(AlertaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Ponto de entrada principal do controller.
     * Coleta os dados de cada categoria, normaliza para um formato único,
     * ordena por prioridade e carrega a view com todas as variáveis.
     *
     * @return void
     */
    public function processar(): void
    {
        // Array que acumulará todos os alertas de todas as categorias
        $alertas = [];

        /* -----------------------------------------------------------------
         * CATEGORIA 1: ENTREGAS ATRASADAS
         * Cada entrega atrasada vira um item do array $alertas com tipo ATRASO.
         * A prioridade é o número de dias em atraso — quanto maior, mais crítico.
         * ----------------------------------------------------------------- */
        $entrasadas = $this->dao->buscarEntregasAtrasadas();

        foreach ($entrasadas as $e) {
            $alertas[] = [
                'tipo'      => 'ATRASO',
                'titulo'    => 'Entrega atrasada — ' . htmlspecialchars($e['cliente']),
                // Descrição inclui data prevista, dias de atraso e cidade do cliente
                'descricao' => 'Previsão: ' . date('d/m/Y', strtotime($e['data_prevista']))
                             . ' · ' . $e['dias_atraso'] . ' dia(s) em atraso'
                             . ($e['cidade']
                                 ? ' · ' . htmlspecialchars($e['cidade'] . ($e['estado'] ? '/' . $e['estado'] : ''))
                                 : ''),
                // Referência formatada com zeros à esquerda para busca manual (ex.: #0042)
                'ref'       => 'Entrega #' . str_pad($e['id_entrega'], 4, '0', STR_PAD_LEFT),
                'status'    => $e['status'],
                'prioridade'=> $e['dias_atraso'], // Dias em atraso como critério de prioridade
            ];
        }

        /* -----------------------------------------------------------------
         * CATEGORIA 2: VIAGENS SEM RETORNO (ATRASADAS)
         * Cada viagem sem retorno vira um item do array $alertas com tipo VIAGEM.
         * A prioridade são as horas de atraso para maior granularidade.
         * ----------------------------------------------------------------- */
        $viagens_atrasadas = $this->dao->buscarViagensAtrasadas();

        foreach ($viagens_atrasadas as $v) {
            $horas = (int)$v['horas_atraso'];
            $alertas[] = [
                'tipo'      => 'VIAGEM',
                'titulo'    => 'Viagem sem retorno — ' . htmlspecialchars($v['motorista']),
                // Exibe o atraso em dias e horas quando supera 24h; só horas quando menor
                'descricao' => 'Prevista para ' . date('d/m/Y H:i', strtotime($v['data_chegada_prevista']))
                             . ' · ' . ($horas >= 24 ? floor($horas / 24) . 'd ' : '') . ($horas % 24) . 'h em atraso'
                             . ' · Veículo ' . htmlspecialchars($v['placa']),
                'ref'       => 'Viagem #' . str_pad($v['id_viagem'], 4, '0', STR_PAD_LEFT),
                'status'    => $v['status'],
                'prioridade'=> $horas, // Horas de atraso como critério de prioridade
            ];
        }

        /* -----------------------------------------------------------------
         * CATEGORIA 3: ESTOQUE CRÍTICO
         * Cada produto com estoque abaixo do mínimo vira um item com tipo ESTOQUE.
         * A prioridade é calculada como 999 - qtd: quantidade menor = prioridade maior.
         * ----------------------------------------------------------------- */
        $estoque_critico = $this->dao->buscarEstoqueCritico();

        foreach ($estoque_critico as $ec) {
            $alertas[] = [
                'tipo'      => 'ESTOQUE',
                'titulo'    => 'Estoque crítico — ' . htmlspecialchars($ec['descricao']),
                'descricao' => 'Apenas ' . (int)$ec['qtd'] . ' unidade(s) em ' . htmlspecialchars($ec['armazem']),
                'ref'       => 'Produto',
                'status'    => 'CRITICO',
                'prioridade'=> 999 - (int)$ec['qtd'], // Quanto menos estoque, maior a prioridade
            ];
        }

        /* -----------------------------------------------------------------
         * CATEGORIA 4: DESVIOS DE ROTA
         * Lidos da tabela `alerta` (registros persistidos pela simulação
         * de desvio na tela de Operações). Prioridade fixa média (500).
         * ----------------------------------------------------------------- */
        foreach ($this->dao->buscarDesviosRota() as $d) {
            $alertas[] = [
                'tipo'      => 'DESVIO_ROTA',
                'titulo'    => 'Desvio de rota — ' . htmlspecialchars($d['motorista']),
                'descricao' => htmlspecialchars($d['descricao'])
                             . ' · Registrado em ' . date('d/m/Y H:i', strtotime($d['data_hora'])),
                'ref'       => 'Viagem #' . str_pad($d['id_viagem'], 4, '0', STR_PAD_LEFT)
                             . ' · ' . htmlspecialchars($d['placa']),
                'status'    => 'DESVIO',
                'prioridade'=> 500,
            ];
        }

        /* -----------------------------------------------------------------
         * CATEGORIA 5: PARADAS NÃO PROGRAMADAS
         * Lidas da tabela `alerta` (registros criados pelo modal "Registrar
         * Parada" na tela de Operações). Prioridade fixa 400.
         * ----------------------------------------------------------------- */
        foreach ($this->dao->buscarParadasNaoProgramadas() as $p) {
            $alertas[] = [
                'tipo'      => 'PARADA_NAO_PROGRAMADA',
                'titulo'    => 'Parada não programada — ' . htmlspecialchars($p['motorista']),
                'descricao' => htmlspecialchars($p['descricao'])
                             . ' · Registrado em ' . date('d/m/Y H:i', strtotime($p['data_hora'])),
                'ref'       => 'Viagem #' . str_pad($p['id_viagem'], 4, '0', STR_PAD_LEFT)
                             . ' · ' . htmlspecialchars($p['placa']),
                'status'    => 'PARADA',
                'prioridade'=> 400,
            ];
        }

        /* -----------------------------------------------------------------
         * ORDENAÇÃO FINAL DOS ALERTAS
         * Todos os alertas são ordenados em ordem decrescente de prioridade
         * para que os mais urgentes apareçam no topo da listagem.
         * ----------------------------------------------------------------- */
        usort($alertas, fn($a, $b) => $b['prioridade'] - $a['prioridade']);

        /* -----------------------------------------------------------------
         * CONTADORES POR CATEGORIA
         * Calculados separadamente para exibir nos cards de KPI da view.
         * ----------------------------------------------------------------- */
        $total_atrasos = count(array_filter($alertas, fn($a) => $a['tipo'] === 'ATRASO'));
        $total_viagens = count(array_filter($alertas, fn($a) => $a['tipo'] === 'VIAGEM'));
        $total_estoque = count(array_filter($alertas, fn($a) => $a['tipo'] === 'ESTOQUE'));
        $total_desvios = count(array_filter($alertas, fn($a) => $a['tipo'] === 'DESVIO_ROTA'));
        $total_paradas = count(array_filter($alertas, fn($a) => $a['tipo'] === 'PARADA_NAO_PROGRAMADA'));

        // Marca a contagem atual como "vista" — o badge do navbar só reaparecer
        // quando novos alertas surgirem após esta visita.
        $_SESSION['alertas_seen_count'] = count($alertas);

        // Carrega a view de alertas com todas as variáveis preparadas
        include 'views/alertas.php';
    }
}
