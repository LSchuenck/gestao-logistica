<?php
/*
 * clientes.php
 * Entry point da página de gerenciamento de clientes.
 * Carrega as dependências, instancia o DAO e o Controller,
 * e delega toda a lógica ao ClienteController.
 */

// Garante que apenas usuários com perfil ADMIN ou GERENTE possam acessar esta página
require_once 'config/auth.php';
requerPerfil(['ADMIN', 'GERENTE']);

// Inclui a conexão PDO com o banco de dados
include 'config/conexao.php';

// Carrega o model, o DAO e o Controller do módulo de clientes
require_once 'model/Cliente.php';
require_once 'dao/ClienteDao.php';
require_once 'controller/ClienteController.php';

// Instancia o DAO com a conexão PDO e injeta no Controller
$clienteDao        = new ClienteDao($pdo);
$clienteController = new ClienteController($clienteDao);

// Executa o controller: processa GET/POST e inclui a view
$clienteController->executar();
