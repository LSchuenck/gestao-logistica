<?php
/*
 * login.php
 * Entry point do fluxo de autenticação.
 * Responsabilidades deste arquivo:
 *  1. Estabelecer a conexão com o banco de dados
 *  2. Carregar as classes necessárias (DAO e Controller)
 *  3. Instanciar o controller e delegar o processamento do login
 * Toda a lógica de validação de credenciais e gestão de sessão
 * reside em AuthController::login().
 */

// Estabelece a conexão PDO com o banco de dados
require_once 'config/conexao.php';

// Carrega as classes necessárias para autenticação
require_once __DIR__ . '/dao/UsuarioDao.php';
require_once __DIR__ . '/controller/AuthController.php';

// Instancia o DAO e repassa ao AuthController
$dao        = new UsuarioDao($pdo);
$controller = new AuthController($dao);

// Delega todo o processamento do login (POST, validação, sessão, redirect e view) ao Controller
$controller->login();
