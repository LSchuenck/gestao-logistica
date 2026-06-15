<?php
/*
 * trocar_senha.php
 * Entry point do fluxo de troca de senha.
 * Responsabilidades deste arquivo:
 *  1. Carregar as funções de autenticação e a conexão com o banco
 *  2. Carregar as classes necessárias (DAO e Controller)
 *  3. Instanciar o controller e delegar o processamento
 * A lógica de validação, persistência e redirecionamento reside
 * em AuthController::trocarSenha().
 * Nota: requerLogin() é chamado dentro do método trocarSenha().
 */

// Carrega as funções de autenticação (requerLogin, requerPerfil, renderNavbar)
require_once 'config/auth.php';

// Estabelece a conexão PDO com o banco de dados
require_once 'config/conexao.php';

// Carrega as classes necessárias
require_once __DIR__ . '/model/Usuario.php';
require_once __DIR__ . '/dao/UsuarioDao.php';
require_once __DIR__ . '/controller/AuthController.php';

// Instancia o DAO com a conexão PDO e repassa ao AuthController
$dao        = new UsuarioDao($pdo);
$controller = new AuthController($dao);

// Delega o processamento da troca de senha (POST, validação, hash, sessão, view) ao Controller
$controller->trocarSenha();
