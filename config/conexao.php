<?php
/*
 * Arquivo: config/conexao.php
 * Finalidade: Estabelece a conexão com o banco de dados MySQL utilizando PDO.
 * Este arquivo deve ser incluído em todas as páginas que precisam acessar o banco.
 * Utiliza PDO (PHP Data Objects) para maior segurança e portabilidade.
 */

// Parâmetros de conexão com o banco de dados
$host = "localhost";          // Endereço do servidor de banco de dados
$db   = "gestao_logistica";   // Nome do banco de dados do sistema
$user = "root";               // Usuário do banco (padrão XAMPP)
$pass = "";                   // Senha do banco (vazia no ambiente de desenvolvimento local)

try {
    // Cria a instância PDO com charset UTF-8 para suporte a caracteres especiais (acentos, etc.)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

    // Configura o PDO para lançar exceções em caso de erros SQL,
    // facilitando a depuração e o tratamento de erros na aplicação
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Caso a conexão falhe (ex.: banco não existe, credenciais erradas, servidor fora do ar),
    // exibe uma mensagem amigável ao usuário e oferece o link para instalar/recriar o banco
    die("<div style='font-family:sans-serif;padding:30px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin:20px'>
         <h3>&#9888; Erro de Conexão com o Banco de Dados</h3>
         <p>" . $e->getMessage() . "</p>
         <a href='instalar.php' style='background:#0d6efd;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none'>
         &#128640; Instalar Banco de Dados</a></div>");
}
