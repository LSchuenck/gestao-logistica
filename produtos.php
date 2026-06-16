<?php
/*
 * produtos.php
 * Entry point da página de gerenciamento de produtos.
 * Carrega as dependências, instancia o DAO e o Controller,
 * e delega toda a lógica ao ProdutoController.
 */

// Carrega o módulo de autenticação e restringe o acesso a ADMIN e GERENTE
require_once 'config/auth.php';
exigirPerfil(['ADMIN', 'GERENTE']);

// Inclui a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o model, o DAO e o Controller do módulo de produtos
require_once 'dao/ProdutoDao.php';
require_once 'controller/ProdutoController.php';

// Instancia o DAO com a conexão PDO e injeta no Controller
$produtoDao        = new ProdutoDao($pdo);
$produtoController = new ProdutoController($produtoDao);

// Executa o controller: processa GET/POST e inclui a view
$produtoController->processar();
