<?php
/**
 * DAO: TransportadoraDao
 * Responsável por todas as operações de banco de dados da tabela 'transportadora'.
 * Operações que envolvem endereço são coordenadas pelo Controller via EnderecoDao,
 * exceto exclusão atômica (transportadora + endereço) que ocorre em transação aqui.
 */
class TransportadoraDao {

    public function __construct(private PDO $pdo) {}

    /**
     * Retorna todas as transportadoras com dados de endereço via LEFT JOIN,
     * ordenadas pela mais recente (maior ID primeiro).
     *
     * @return array Lista de transportadoras com colunas de endereço incluídas
     */
    public function listar(): array {
        return $this->pdo->query("
            SELECT t.*, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado
            FROM transportadora t
            LEFT JOIN endereco e ON t.id_endereco = e.id_endereco
            ORDER BY t.id_transportadora DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna uma transportadora pelo ID, incluindo dados de endereço.
     *
     * @param int $id ID da transportadora
     * @return array|false Registro da transportadora ou false se não encontrado
     */
    public function buscarPorId(int $id): array|false {
        $stmt = $this->pdo->prepare("
            SELECT t.*, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado
            FROM transportadora t
            LEFT JOIN endereco e ON t.id_endereco = e.id_endereco
            WHERE t.id_transportadora = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Conta quantas transportadoras têm status ATIVA.
     *
     * @return int Quantidade de transportadoras ativas
     */
    public function contarAtivas(): int {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM transportadora WHERE status = 'ATIVA'")->fetchColumn();
    }

    /**
     * Insere uma nova transportadora e retorna o ID gerado.
     *
     * @param array $dados Associativo com: cnpj, razao_social, nome_fantasia, telefone, email, id_endereco
     * @return int ID da transportadora recém-inserida
     */
    public function inserir(array $dados): int {
        $sql = "INSERT INTO transportadora (cnpj, razao_social, nome_fantasia, telefone, email, id_endereco)
                VALUES (:cnpj, :razao_social, :nome_fantasia, :telefone, :email, :id_endereco)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cnpj'          => $dados['cnpj'],
            ':razao_social'  => $dados['razao_social'],
            ':nome_fantasia' => $dados['nome_fantasia'],
            ':telefone'      => $dados['telefone'],
            ':email'         => $dados['email'],
            ':id_endereco'   => $dados['id_endereco'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Busca o id_endereco vinculado a uma transportadora.
     * Utilizado antes da exclusão para também remover o endereço órfão.
     *
     * @param int $id ID da transportadora
     * @return int|false ID do endereço ou false se não houver
     */
    public function buscarIdEndereco(int $id): int|false {
        $stmt = $this->pdo->prepare("SELECT id_endereco FROM transportadora WHERE id_transportadora = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetchColumn();
        return $resultado !== false ? (int) $resultado : false;
    }

    /**
     * Exclui a transportadora e seu endereço vinculado dentro de uma transação atômica.
     * Lança exceção se houver motoristas ou veículos vinculados (violação de FK).
     *
     * @param int      $idTransportadora ID da transportadora a excluir
     * @param int|false $idEndereco      ID do endereço vinculado (ou false se não houver)
     */
    public function excluirComEndereco(int $idTransportadora, int|false $idEndereco): void {
        $this->pdo->beginTransaction();

        // Exclui a transportadora; lança PDOException se houver FK com motoristas/veículos
        $this->pdo->prepare("DELETE FROM transportadora WHERE id_transportadora = ?")
                  ->execute([$idTransportadora]);

        // Remove o endereço vinculado caso existia, evitando registros órfãos
        if ($idEndereco) {
            $this->pdo->prepare("DELETE FROM endereco WHERE id_endereco = ?")
                      ->execute([$idEndereco]);
        }

        $this->pdo->commit();
    }

    public function atualizar(int $id, array $dados): void {
        $sql = "UPDATE transportadora
                SET cnpj=:cnpj, razao_social=:razao_social, nome_fantasia=:nome_fantasia,
                    telefone=:telefone, email=:email
                WHERE id_transportadora=:id";
        $this->pdo->prepare($sql)->execute([
            ':cnpj'          => $dados['cnpj'],
            ':razao_social'  => $dados['razao_social'],
            ':nome_fantasia' => $dados['nome_fantasia'],
            ':telefone'      => $dados['telefone'],
            ':email'         => $dados['email'],
            ':id'            => $id,
        ]);
    }

    /**
     * Alterna o status da transportadora entre ATIVA e INATIVA.
     *
     * @param int $id ID da transportadora
     */
    public function alternarStatus(int $id): void {
        // Lê o status atual para calcular o inverso
        $stmt = $this->pdo->prepare("SELECT status FROM transportadora WHERE id_transportadora = ?");
        $stmt->execute([$id]);
        $atual = $stmt->fetchColumn();

        $novo = ($atual === 'ATIVA') ? 'INATIVA' : 'ATIVA';

        $this->pdo->prepare("UPDATE transportadora SET status = ? WHERE id_transportadora = ?")
                  ->execute([$novo, $id]);
    }
}
