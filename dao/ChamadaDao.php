<?php
class ChamadaDao
{
    public function create(Chamada $chamada)
    {
        //Insere uma nova chamada do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO chamada(id, atendido, data, hora, observacao) VALUES(?,?,?,?,?)";
            $query = $pdo->prepare($sql);
            $query->execute([$chamada->id, $chamada->atendido, $chamada->data, $chamada->hora, $chamada->observacao]);
            conexao::desconectar();
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }

    public function read()
    {
        //Lista todas as chamadas do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM chamada ORDER BY data, hora";
            $result = $pdo->query($sql); # só exibe diretamente os dados capturados do banco de dados
            $lista = [];
            foreach ($result as $linha) {
                //Criando o objeto referente ao registro(linha) do banco
                $lista[] = new Chamada ($linha["idchamada"], $linha["id"], $linha["atendido"], $linha["data"], $linha["hora"], 
                $linha["observacao"]);
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $exception) {
            return null;
        }
    }

    public function readId($id)
    {
        //Lista todas as chamadas do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM chamada WHERE idchamada=?";
            $query = $pdo->prepare($sql);
            $query->execute([$id]);
            $lista = $query->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $lista;
        } catch (PDOException $exception) {
            return null;
        }
    }

    public function update(Chamada $chamada)
    {
        //Altera um registro(chamada) do banco de dados
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE chamada SET id = ?, atendido = ?, data= ?, hora = ?, observacao = ? WHERE idchamada = ?";
            $query = $pdo->prepare($sql);
            $query->execute([$chamada->id, $chamada->atendido, $chamada->data, $chamada->hora, $chamada->observacao, $chamada->idchamada]);
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
            $sql = "DELETE FROM chamada WHERE idchamada = ?";
            $query = $pdo->prepare($sql);
            $query->execute([$id]);
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }
}
