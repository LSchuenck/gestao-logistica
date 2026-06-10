<?php
class GrupoDao
{
    public function create(Grupo $grupo)
    {
        //Insere uma novo grupo no banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO grupo(descricao) VALUES(?)";
            $query = $pdo->prepare($sql);
            $query->execute([$grupo->descricao]);
            conexao::desconectar();
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function read()
    {
        //Lista todas os grupos do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM grupo ORDER BY descricao";
            $result = $pdo->query($sql); # só exibe diretamente os dados capturados do banco de dados
            $lista = [];
            foreach ($result as $linha) {
                $lista[] = new Grupo($linha["idgrupo"], $linha["descricao"]);
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $exception) {
            return null;
        }
    }

    public function readId($id)
    {
        //Lista todas os grupos do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM grupo WHERE idgrupo=?";
            $query = $pdo->prepare($sql);
            $query->execute([$id]);
            $lista = $query->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $lista;
        } catch (PDOException $exception) {
            return null;
        }
    }

    public function update(Grupo $grupo)
    {
        //Altera um registro(grupo) do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE grupo SET descricao = ? WHERE idgrupo = ?";
            $query = $pdo->prepare($sql);
            $query->execute([$grupo->descricao, $grupo->idgrupo]);
            conexao::desconectar();
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function delete($id)
    {
        //Apaga um registro(grupo) do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "DELETE FROM grupo WHERE idgrupo = ?";
            $query = $pdo->prepare($sql);
            $query->execute([$id]);
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }
}
