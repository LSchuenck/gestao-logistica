<?php
$host = "localhost";
$db   = "gestao_logistica";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;margin:20px'>
         <h3>&#9888; Erro de Conexão com o Banco de Dados</h3>
         <p>" . $e->getMessage() . "</p>
         <a href='instalar.php' style='background:#0d6efd;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none'>
         &#128640; Instalar Banco de Dados</a></div>");
}
