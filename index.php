<?php
/*
 * Arquivo: index.php
 * Finalidade: Entry point do Dashboard principal do sistema.
 * Verifica autenticação, carrega dependências e delega
 * todo o processamento ao DashboardController.
 *
 * Esta é a página inicial exibida após o login, funcionando como um
 * painel de controle (dashboard) com indicadores resumidos das operações.
 *
 * Acesso: qualquer usuário logado (exigirLogin).
 */

// Verifica autenticação; redireciona para login.php se necessário
require_once 'config/auth.php';
exigirLogin();

// Estabelece a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o DAO e o controller do Dashboard
require_once 'dao/DashboardDao.php';
require_once 'controller/DashboardController.php';

// Instancia o DAO injetando a conexão PDO e repassa ao controller
$dao        = new DashboardDao($pdo);
$controller = new DashboardController($dao);

// Processa a requisição e renderiza a view
$controller->processar();
