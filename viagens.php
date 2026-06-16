<?php
/*
 * Arquivo: viagens.php
 * Finalidade: Entry point do módulo de Viagens.
 * Verifica autenticação, carrega dependências e delega
 * todo o processamento ao ViagemController.
 *
 * Acesso: qualquer usuário logado (exigirLogin).
 */

// Verifica autenticação; redireciona para login.php se necessário
require_once 'config/auth.php';
exigirLogin();

// Estabelece a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o model, o DAO e o controller do módulo de Viagens
require_once 'dao/ViagemDao.php';
require_once 'controller/ViagemController.php';

// Instancia o DAO injetando a conexão PDO e repassa ao controller
$dao        = new ViagemDao($pdo);
$controller = new ViagemController($dao);

// Processa a requisição e renderiza a view
$controller->processar();
