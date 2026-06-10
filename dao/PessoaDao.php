<?php
class PessoaDao
{
    public function create(Pessoa $pessoa)
    {
        //Insere uma nova pessoa do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO pessoa(nome, endereco, telefone, email, sexo, idgrupo) VALUES(?,?,?,?,?,?)";
            $query = $pdo->prepare($sql);
            $query->execute([$pessoa->nome, $pessoa->endereco, $pessoa->telefone, $pessoa->email, $pessoa->sexo, $pessoa->idgrupo]);
            conexao::desconectar();
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function read()
    {
        //Lista todas as pessoas do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM pessoa ORDER BY nome";
            $result = $pdo->query($sql); # só exibe diretamente os dados capturados do banco de dados
            $lista = [];
            foreach ($result as $linha) {
                $lista[] = new Pessoa($linha["id"], $linha["nome"], $linha["endereco"], $linha["telefone"], $linha["email"], $linha["sexo"], $linha["idgrupo"]);
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $exception) {
            return null;
        }
    }

    public function readId($id)
    {
        //Lista todas as pessoas do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM pessoa WHERE id=? ORDER BY nome";
            $query = $pdo->prepare($sql);
            $query->execute([$id]);
            $lista = $query->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $lista;
        } catch (PDOException $exception) {
            return null;
        }
    }

    public function update(Pessoa $pessoa)
    {
        //Altera um registro(pessoa) do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE pessoa SET nome = ?, endereco = ?, telefone= ?, email = ?, sexo = ?, idgrupo = ? WHERE id = ?";
            $query = $pdo->prepare($sql);
            $query->execute([$pessoa->nome, $pessoa->endereco, $pessoa->telefone, $pessoa->email, $pessoa->sexo, $pessoa->idgrupo, $pessoa->id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function delete($id)
    {
        //Apaga um registro(pessoa) do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "DELETE FROM pessoa WHERE id = ?";
            $query = $pdo->prepare($sql);
            $query->execute([$id]);
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }
}
