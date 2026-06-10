<?php
require_once 'config/auth.php';
requerPerfil(['ADMIN']);
require_once 'config/conexao.php';

$erro = '';
$sucesso = '';

if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    if ($id === 1) {
        $erro = 'Essa conta não pode ser excluída!';
    } else {
        if ($id === (int) $_SESSION['usuario']['id']) {
            $erro = 'Você não pode excluir sua própria conta.';
        } else {
            $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$id]);
            header('Location: usuarios.php');
            exit;
        }
    }

}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    if ($id === 1) {
        $erro = 'Essa conta não pode desativada!';
    } else {
        if ($id === (int) $_SESSION['usuario']['id']) {
            $erro = 'Você não pode desativar sua própria conta.';
        } else {
            $stmt = $pdo->prepare("SELECT status FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $atual = $stmt->fetchColumn();
            $novo = ($atual === 'ATIVO') ? 'INATIVO' : 'ATIVO';
            $pdo->prepare("UPDATE usuario SET status = ? WHERE id_usuario = ?")->execute([$novo, $id]);
            header('Location: usuarios.php');
            exit;
        }
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id_usuario'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $perfil = $_POST['perfil'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $status = $_POST['status'] ?? 'ATIVO';

    if ($nome === '' || $email === '' || !in_array($perfil, ['ADMIN', 'GERENTE', 'OPERADOR'])) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        try {
            if ($id > 0) {
                if ($senha !== '') {
                    $hash = password_hash($senha, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE usuario SET nome=?, email=?, perfil=?, status=?, senha=? WHERE id_usuario=?")
                        ->execute([$nome, $email, $perfil, $status, $hash, $id]);
                } else {
                    $pdo->prepare("UPDATE usuario SET nome=?, email=?, perfil=?, status=? WHERE id_usuario=?")
                        ->execute([$nome, $email, $perfil, $status, $id]);
                }
                $sucesso = 'Usuário atualizado com sucesso.';
            } else {
                if ($senha === '') {
                    $erro = 'Informe uma senha para o novo usuário.';
                } else {
                    $hash = password_hash($senha, PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO usuario (nome, email, senha, perfil, status) VALUES (?,?,?,?,?)")
                        ->execute([$nome, $email, $hash, $perfil, $status]);
                    $sucesso = 'Usuário cadastrado com sucesso.';
                }
            }
            if (!$erro) {
                header('Location: usuarios.php?ok=' . urlencode($sucesso));
                exit;
            }
        } catch (Exception $e) {
            $erro = 'E-mail já cadastrado ou erro ao salvar.';
        }
    }
}

if (isset($_GET['ok']))
    $sucesso = htmlspecialchars($_GET['ok']);

$usuarios = $pdo->query("SELECT id_usuario, nome, email, perfil, status, data_cadastro FROM usuario ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
include 'views/usuarios.php';
