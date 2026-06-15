<?php
/*
 * Arquivo: dao/FreteDao.php
 * Finalidade: Data Access Object (DAO) para o módulo de Fretes.
 * Centraliza todas as queries SQL relacionadas à tabela `frete` e suas
 * tabelas relacionadas (viagem, rota, motorista, veiculo, transportadora,
 * entrega).
 * Recebe a conexão PDO via construtor — sem instanciar conexão própria.
 */

class FreteDao
{
    /** @var PDO Instância da conexão com o banco de dados */
    private PDO $pdo;

    /**
     * Construtor: injeta a dependência PDO.
     *
     * @param PDO $pdo Conexão com o banco de dados
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* =====================================================================
     * CONSULTAS DE LEITURA
     * ===================================================================== */

    /**
     * Retorna todos os fretes com dados de viagem, motorista, veículo e
     * transportadora, ordenados pelo frete mais recente primeiro.
     *
     * @return array Lista de fretes como arrays associativos
     */
    public function listarTodos(): array
    {
        return $this->pdo->query(
            "SELECT f.*, vi.status AS viagem_status,
                m.nome AS motorista, ve.placa, t.nome_fantasia AS transportadora
             FROM frete f
             JOIN viagem vi       ON f.id_viagem          = vi.id_viagem
             JOIN rota r          ON vi.id_rota            = r.id_rota
             JOIN motorista m     ON r.id_motorista        = m.id_motorista
             JOIN veiculo ve      ON r.id_veiculo          = ve.id_veiculo
             JOIN transportadora t ON f.id_transportadora  = t.id_transportadora
             ORDER BY f.id_frete DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna as viagens que ainda não possuem frete cadastrado.
     * Inclui dados de motorista, veículo, distância e totais de peso/volume
     * das entregas para auxiliar na calculadora de valor do frete.
     *
     * @return array Lista de viagens sem frete
     */
    public function listarViagensSemFrete(): array
    {
        return $this->pdo->query(
            "SELECT vi.id_viagem, m.nome AS motorista, ve.placa,
                t.id_transportadora, t.nome_fantasia AS transportadora_nome,
                COALESCE(r.distancia, 0) AS distancia,
                COALESCE(SUM(e.peso_total), 0) AS peso_total,
                COALESCE(SUM(e.volume_total), 0) AS volume_total,
                COUNT(DISTINCT re.id_entrega) AS total_entregas
             FROM viagem vi
             JOIN rota r             ON vi.id_rota         = r.id_rota
             JOIN motorista m        ON r.id_motorista     = m.id_motorista
             JOIN transportadora t   ON m.id_transportadora = t.id_transportadora
             JOIN veiculo ve         ON r.id_veiculo       = ve.id_veiculo
             LEFT JOIN rota_entrega re ON re.id_rota       = r.id_rota
             LEFT JOIN entrega e       ON e.id_entrega     = re.id_entrega
             LEFT JOIN frete f         ON vi.id_viagem     = f.id_viagem
             WHERE f.id_frete IS NULL
             GROUP BY vi.id_viagem, m.nome, ve.placa, r.distancia,
                      t.id_transportadora, t.nome_fantasia
             ORDER BY vi.id_viagem DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna as transportadoras com status ATIVA para o select do formulário.
     *
     * @return array Lista de transportadoras ativas
     */
    public function listarTransportadorasAtivas(): array
    {
        return $this->pdo->query(
            "SELECT * FROM transportadora WHERE status = 'ATIVA' ORDER BY nome_fantasia"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todos os dados necessários para compor a DANFE de um frete.
     * Inclui dados da viagem, motorista (com CNH), transportadora (com CNPJ)
     * e veículo (placa e tipo).
     *
     * @param int $idFrete ID do frete
     * @return array|false Dados do frete para a DANFE, ou false se não encontrado
     */
    public function buscarParaDanfe(int $idFrete)
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.*, vi.status AS viagem_status, vi.data_saida,
                vi.data_chegada_prevista, vi.data_chegada_real,
                m.nome AS motorista, m.cnh,
                t.nome_fantasia AS transportadora, t.cnpj,
                ve.placa, ve.tipo_veiculo
             FROM frete f
             JOIN viagem vi       ON f.id_viagem          = vi.id_viagem
             JOIN rota r          ON vi.id_rota            = r.id_rota
             JOIN motorista m     ON r.id_motorista        = m.id_motorista
             JOIN transportadora t ON f.id_transportadora  = t.id_transportadora
             JOIN veiculo ve      ON r.id_veiculo          = ve.id_veiculo
             WHERE f.id_frete = ?"
        );
        $stmt->execute([$idFrete]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Conta o total de fretes cadastrados.
     *
     * @return int Total de fretes
     */
    public function contarTotal(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM frete")->fetchColumn();
    }

    /**
     * Retorna a soma de todos os valores de frete (receita bruta).
     *
     * @return float Receita bruta total
     */
    public function somarValores(): float
    {
        return (float)$this->pdo->query(
            "SELECT COALESCE(SUM(valor), 0) FROM frete"
        )->fetchColumn();
    }

    /**
     * Retorna a soma de todos os custos operacionais.
     *
     * @return float Custo operacional total
     */
    public function somarCustos(): float
    {
        return (float)$this->pdo->query(
            "SELECT COALESCE(SUM(custo_operacional), 0) FROM frete"
        )->fetchColumn();
    }

    /* =====================================================================
     * OPERAÇÕES DE ESCRITA
     * ===================================================================== */

    /**
     * Insere um novo frete no banco de dados.
     * A constraint UNIQUE em id_viagem impede que a mesma viagem tenha
     * dois fretes — o controller trata a exceção de violação de unicidade.
     *
     * @param int    $idViagem          ID da viagem
     * @param int    $idTransportadora  ID da transportadora
     * @param float  $valor             Valor do frete
     * @param float  $custoOperacional  Custo operacional
     * @param string $notaFiscal        Número da nota fiscal
     * @param string $dataEmissao       Data de emissão da NF
     * @return void
     * @throws Exception Em caso de violação de unicidade ou outro erro SQL
     */
    public function inserir(
        int $idViagem,
        int $idTransportadora,
        float $valor,
        float $custoOperacional,
        string $notaFiscal,
        string $dataEmissao
    ): void {
        $this->pdo->prepare(
            "INSERT INTO frete
                (id_viagem, id_transportadora, valor, custo_operacional, nota_fiscal, data_emissao)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $idViagem, $idTransportadora,
            $valor, $custoOperacional,
            $notaFiscal, $dataEmissao,
        ]);
    }

    /**
     * Exclui um frete pelo seu ID.
     *
     * @param int $idFrete ID do frete a ser excluído
     * @return void
     * @throws Exception Em caso de violação de chave estrangeira
     */
    public function excluir(int $idFrete): void
    {
        $this->pdo->prepare(
            "DELETE FROM frete WHERE id_frete = ?"
        )->execute([$idFrete]);
    }
}
