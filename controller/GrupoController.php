<?php
session_start();
//Arquivo de conexão com o banco
include_once "../config/conexao.php";
//Arquivo com o mapeamento da tabela do banco
include_once "../model/grupo.php";
//Arquivo com os métodos que manipulam os dados no banco
include_once "../dao/GrupoDao.php";

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {
    
    //Objeto referente ao model
    $p1 = new Grupo(
        $_POST["txtIdGrupo"],
        $_POST["txtDescricao"]
    );

    //Objeto referente ao DAO
    $p1Dao = new GrupoDao();

    //Descobrindo a operação que o usuário quer fazer
    if (isset($_GET["id"])) {
        //Excluindo o registro do banco
        $resultado = $p1Dao->delete($_GET["id"]);
        $_SESSION["mensagem"] = "Excluído com sucesso!";
    } elseif ($_POST["txtIdGrupo"] == "") {
        //Cadastrar
        $resultado = $p1Dao->create($p1);
        $_SESSION["mensagem"] = "Cadastrado com sucesso!";
    } else {
        //Alterar
        $resultado = $p1Dao->update($p1);
        $_SESSION["mensagem"] = "Alterado com sucesso!";
    }
    $_SESSION["resultado"] = $resultado;
    header("location:../indexgrupo.php");
}
