<?php
require_once 'config/conexao.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['usuario'])) {
    header('Location: index.php'); exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare("SELECT id_usuario, nome, senha, perfil, status FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($senha, $user['senha'])) {
            $erro = 'E-mail ou senha inválidos.';
        } elseif ($user['status'] === 'INATIVO') {
            $erro = 'Usuário inativo. Contate o administrador.';
        } else {
            $_SESSION['usuario'] = [
                'id'     => $user['id_usuario'],
                'nome'   => $user['nome'],
                'perfil' => $user['perfil'],
            ];
            header('Location: index.php'); exit;
        }
    }
}
include 'views/login.php';
