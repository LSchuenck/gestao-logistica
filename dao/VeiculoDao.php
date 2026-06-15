<?php
/**
 * DAO: VeiculoDao
 * Responsável por todas as operações de banco de dados da tabela 'veiculo'.
 */
class VeiculoDao {

    public function __construct(private PDO $pdo) {}

    /**
     * Retorna todos os veículos com o nome fantasia da transportadora vinculada,
     * ordenados pelo mais recente (maior ID primeiro).
     *
     * @return array Lista de veículos com campo 'nome_fantasia' incluído
     */
    public function listar(): array {
        return $this->pdo->query("
            SELECT v.*, t.nome_fantasia
            FROM veiculo v
            JOIN transportadora t ON v.id_transportadora = t.id_transportadora
            ORDER BY v.id_veiculo DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um veículo pelo ID.
     *
     * @param int $id ID do veículo
     * @return array|false Registro do veículo ou false se não encontrado
     */
    public function buscarPorId(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM veiculo WHERE id_veiculo = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna transportadoras com status ATIVA para popular o select do formulário.
     * Somente transportadoras ativas podem receber novos veículos.
     *
     * @return array Lista com id_transportadora e nome_fantasia
     */
    public function listarTransportadorasAtivas(): array {
        return $this->pdo->query("
            SELECT id_transportadora, nome_fantasia
            FROM transportadora
            WHERE status = 'ATIVA'
            ORDER BY nome_fantasia
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insere um novo veículo no banco.
     *
     * @param array $dados Associativo com: id_transportadora, placa, modelo, tipo_veiculo, capacidade_carga
     */
    public function inserir(array $dados): void {
        $sql = "INSERT INTO veiculo (id_transportadora, placa, modelo, tipo_veiculo, capacidade_carga)
                VALUES (:id_transportadora, :placa, :modelo, :tipo_veiculo, :capacidade_carga)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_transportadora' => $dados['id_transportadora'],
            ':placa'             => strtoupper(trim($dados['placa'])), // Normaliza placa para maiúsculas sem espaços
            ':modelo'            => $dados['modelo'],
            ':tipo_veiculo'      => $dados['tipo_veiculo'],
            ':capacidade_carga'  => $dados['capacidade_carga'],
        ]);
    }

    public function atualizar(int $id, array $dados): void {
        $sql = "UPDATE veiculo
                SET id_transportadora=:id_transportadora, placa=:placa, modelo=:modelo,
                    tipo_veiculo=:tipo_veiculo, capacidade_carga=:capacidade_carga
                WHERE id_veiculo=:id";
        $this->pdo->prepare($sql)->execute([
            ':id_transportadora' => $dados['id_transportadora'],
            ':placa'             => strtoupper(trim($dados['placa'])),
            ':modelo'            => $dados['modelo'],
            ':tipo_veiculo'      => $dados['tipo_veiculo'],
            ':capacidade_carga'  => $dados['capacidade_carga'],
            ':id'                => $id,
        ]);
    }

    /**
     * Exclui um veículo pelo ID.
     * Lança PDOException se houver rotas ou viagens vinculadas (violação de FK).
     *
     * @param int $id ID do veículo a excluir
     */
    public function excluir(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM veiculo WHERE id_veiculo = ?");
        $stmt->execute([$id]);
    }

    /**
     * Atualiza o status de um veículo.
     * O status é validado pelo Controller antes de chamar este método.
     *
     * @param int    $id     ID do veículo
     * @param string $status Novo status: DISPONIVEL | EM_VIAGEM | MANUTENCAO
     */
    public function atualizarStatus(int $id, string $status): void {
        $stmt = $this->pdo->prepare("UPDATE veiculo SET status = ? WHERE id_veiculo = ?");
        $stmt->execute([$status, $id]);
    }
}
