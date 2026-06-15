<?php
/*
 * logout.php
 * Entry point do fluxo de encerramento de sessão.
 * Responsabilidades deste arquivo:
 *  1. Carregar o AuthController
 *  2. Instanciar o controller sem DAO (não há consulta ao banco no logout)
 *  3. Delegar o encerramento da sessão ao Controller
 * A lógica de destruição da sessão e redirecionamento reside
 * em AuthController::logout().
 */

// Carrega o Controller de autenticação
require_once __DIR__ . '/controller/AuthController.php';

// Instancia sem DAO pois o logout não realiza consultas ao banco
$controller = new AuthController();

// Delega o encerramento da sessão e redirecionamento ao Controller
$controller->logout();
