<?php
/*
 * Arquivo: alertas.php
 * Finalidade: Entry point do módulo de Alertas.
 * Verifica autenticação, carrega dependências e delega
 * todo o processamento ao AlertaController.
 *
 * Acesso: qualquer usuário logado (requerLogin).
 */

// Verifica autenticação; redireciona para login.php se necessário
require_once 'config/auth.php';
requerLogin();

// Estabelece a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o DAO e o controller do módulo de Alertas
require_once 'dao/AlertaDao.php';
require_once 'controller/AlertaController.php';

// Instancia o DAO injetando a conexão PDO e repassa ao controller
$dao        = new AlertaDao($pdo);
$controller = new AlertaController($dao);

// Processa a requisição e renderiza a view
$controller->processar();
