<?php
/**
 * DAO: MotoristaDao
 * Responsável por todas as operações de banco de dados da tabela 'motorista'.
 */
class MotoristaDao {

    public function __construct(private PDO $pdo) {}

    /**
     * Retorna todos os motoristas com o nome fantasia da transportadora vinculada,
     * ordenados pelo mais recente (maior ID primeiro).
     *
     * @return array Lista de motoristas com campo 'nome_fantasia' incluído
     */
    public function listar(): array {
        return $this->pdo->query("
            SELECT m.*, t.nome_fantasia
            FROM motorista m
            JOIN transportadora t ON m.id_transportadora = t.id_transportadora
            ORDER BY m.id_motorista DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um motorista pelo ID.
     *
     * @param int $id ID do motorista
     * @return array|false Registro do motorista ou false se não encontrado
     */
    public function buscarPorId(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM motorista WHERE id_motorista = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna transportadoras com status ATIVA para popular o select do formulário.
     * Somente transportadoras ativas podem receber novos motoristas.
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
     * Conta quantos motoristas têm status ATIVO.
     *
     * @return int Quantidade de motoristas ativos
     */
    public function contarAtivos(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM motorista WHERE status = 'ATIVO'")->fetchColumn();
    }

    /**
     * Insere um novo motorista no banco.
     *
     * @param array $dados Associativo com: id_transportadora, nome, cpf, cnh, categoria_cnh, validade_cnh, telefone
     */
    public function inserir(array $dados): void {
        $sql = "INSERT INTO motorista (id_transportadora, nome, cpf, cnh, categoria_cnh, validade_cnh, telefone)
                VALUES (:id_transportadora, :nome, :cpf, :cnh, :categoria_cnh, :validade_cnh, :telefone)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_transportadora' => $dados['id_transportadora'],
            ':nome'              => $dados['nome'],
            ':cpf'               => $dados['cpf'],
            ':cnh'               => $dados['cnh'],
            ':categoria_cnh'     => $dados['categoria_cnh'],
            ':validade_cnh'      => $dados['validade_cnh'],  // NULL quando não informada
            ':telefone'          => $dados['telefone'],
        ]);
    }

    public function atualizar(int $id, array $dados): void {
        $sql = "UPDATE motorista
                SET id_transportadora=:id_transportadora, nome=:nome, cpf=:cpf,
                    cnh=:cnh, categoria_cnh=:categoria_cnh, validade_cnh=:validade_cnh, telefone=:telefone
                WHERE id_motorista=:id";
        $this->pdo->prepare($sql)->execute([
            ':id_transportadora' => $dados['id_transportadora'],
            ':nome'              => $dados['nome'],
            ':cpf'               => $dados['cpf'],
            ':cnh'               => $dados['cnh'],
            ':categoria_cnh'     => $dados['categoria_cnh'],
            ':validade_cnh'      => $dados['validade_cnh'],
            ':telefone'          => $dados['telefone'],
            ':id'                => $id,
        ]);
    }

    /**
     * Exclui um motorista pelo ID.
     * Lança PDOException se houver rotas ou viagens vinculadas (violação de FK).
     *
     * @param int $id ID do motorista a excluir
     */
    public function excluir(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM motorista WHERE id_motorista = ?");
        $stmt->execute([$id]);
    }
}
