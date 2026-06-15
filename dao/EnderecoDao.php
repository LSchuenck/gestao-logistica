<?php
/**
 * DAO: EnderecoDao
 * Responsável por todas as operações de banco de dados da tabela 'endereco'.
 * Reutilizado por múltiplos módulos que gerenciam entidades com endereço vinculado.
 */
class EnderecoDao {

    public function __construct(private PDO $pdo) {}

    /**
     * Insere um novo endereço no banco e retorna o ID gerado.
     *
     * @param array $dados Associativo com: cep, logradouro, numero, complemento, bairro, cidade, estado
     * @return int ID do endereço recém-inserido
     */
    public function inserir(array $dados): int {
        $sql = "INSERT INTO endereco (cep, logradouro, numero, complemento, bairro, cidade, estado)
                VALUES (:cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cep'         => $dados['cep']         ?? '',
            ':logradouro'  => $dados['logradouro']  ?? '',
            ':numero'      => $dados['numero']       ?? '',
            ':complemento' => $dados['complemento'] ?? '',
            ':bairro'      => $dados['bairro']       ?? '',
            ':cidade'      => $dados['cidade']       ?? '',
            ':estado'      => strtoupper($dados['estado'] ?? ''), // Garante sigla em maiúsculas (ex.: 'sp' -> 'SP')
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza os dados de um endereço existente.
     *
     * @param int   $id    ID do endereço a ser atualizado
     * @param array $dados Associativo com os campos a atualizar
     */
    public function atualizar(int $id, array $dados): void {
        $sql = "UPDATE endereco
                SET cep = :cep, logradouro = :logradouro, numero = :numero,
                    complemento = :complemento, bairro = :bairro,
                    cidade = :cidade, estado = :estado
                WHERE id_endereco = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cep'         => $dados['cep']         ?? '',
            ':logradouro'  => $dados['logradouro']  ?? '',
            ':numero'      => $dados['numero']       ?? '',
            ':complemento' => $dados['complemento'] ?? '',
            ':bairro'      => $dados['bairro']       ?? '',
            ':cidade'      => $dados['cidade']       ?? '',
            ':estado'      => strtoupper($dados['estado'] ?? ''),
            ':id'          => $id,
        ]);
    }

    /**
     * Remove um endereço pelo ID.
     *
     * @param int $id ID do endereço a ser removido
     */
    public function deletar(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM endereco WHERE id_endereco = ?");
        $stmt->execute([$id]);
    }
}
