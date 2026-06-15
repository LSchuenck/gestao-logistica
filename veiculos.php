<?php
/**
 * veiculos.php
 * Entry point do módulo de veículos.
 * Verifica autenticação, instancia as dependências e delega tudo ao Controller.
 */

// Garante que apenas usuários com perfil ADMIN ou GERENTE possam acessar esta página
require_once 'config/auth.php';
requerPerfil(['ADMIN', 'GERENTE']);

// Estabelece a conexão PDO com o banco de dados ($pdo)
include 'config/conexao.php';

// Carrega o DAO e o Controller do módulo
require_once 'dao/VeiculoDao.php';
require_once 'controller/VeiculoController.php';

// Instancia e executa o Controller passando as dependências via construtor
(new VeiculoController(
    new VeiculoDao($pdo)
))->handle();
