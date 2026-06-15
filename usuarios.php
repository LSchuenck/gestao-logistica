<?php
/*
 * usuarios.php
 * Entry point do módulo de gerenciamento de usuários.
 * Responsabilidades deste arquivo:
 *  1. Verificar autenticação e restringir acesso ao perfil ADMIN
 *  2. Estabelecer a conexão com o banco de dados
 *  3. Carregar as classes necessárias (Model, DAO, Controller)
 *  4. Instanciar o controller e delegar todo o processamento a ele
 * Toda a lógica de negócio reside em UsuarioController.
 * Todo o SQL reside em UsuarioDao.
 */

// Verifica autenticação e restringe o acesso somente ao perfil ADMIN
require_once 'config/auth.php';
requerPerfil(['ADMIN']);

// Estabelece a conexão PDO com o banco de dados
require_once 'config/conexao.php';

// Carrega as classes do módulo de usuários
require_once __DIR__ . '/model/Usuario.php';
require_once __DIR__ . '/dao/UsuarioDao.php';
require_once __DIR__ . '/controller/UsuarioController.php';

// Instancia o DAO com a conexão PDO e repassa ao Controller
$dao        = new UsuarioDao($pdo);
$controller = new UsuarioController($dao);

// Delega todo o processamento (GET/POST, listagem, feedback e view) ao Controller
$controller->handle();
