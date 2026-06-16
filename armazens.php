<?php
/*
 * armazens.php
 * Entry point da página de gerenciamento de armazéns.
 * Carrega as dependências, instancia o DAO e o Controller,
 * e delega toda a lógica ao ArmazemController.
 */

// Carrega o módulo de autenticação e garante que apenas ADMIN ou GERENTE acessem esta página
require_once 'config/auth.php';
exigirPerfil(['ADMIN', 'GERENTE']);

// Inclui a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o model, o DAO e o Controller do módulo de armazéns
require_once 'dao/ArmazemDao.php';
require_once 'controller/ArmazemController.php';

// Instancia o DAO com a conexão PDO e injeta no Controller
$armazemDao        = new ArmazemDao($pdo);
$armazemController = new ArmazemController($armazemDao);

// Executa o controller: processa GET/POST e inclui a view
$armazemController->processar();
