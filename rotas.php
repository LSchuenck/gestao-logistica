<?php
/**
 * Entry point: rotas.php
 * Ponto de entrada do módulo de Rotas.
 * Verifica autenticação, instancia o DAO e o Controller, e delega o processamento.
 */

// Verifica autenticação e restringe o acesso apenas a ADMIN e GERENTE
require_once 'config/auth.php';
exigirPerfil(['ADMIN', 'GERENTE']);

// Estabelece a conexão com o banco de dados via PDO ($pdo)
require_once 'config/conexao.php';

// Carrega o DAO e o Controller do módulo de Rotas
require_once 'dao/RotaDao.php';
require_once 'controller/RotaController.php';

// Instancia o DAO injetando a conexão PDO e repassa ao Controller
$controller = new RotaController(new RotaDao($pdo));

// Delega todo o processamento da requisição ao Controller
$controller->processar();
