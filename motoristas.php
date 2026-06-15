<?php
/**
 * motoristas.php
 * Entry point do módulo de motoristas.
 * Verifica autenticação, instancia as dependências e delega tudo ao Controller.
 */

// Garante que apenas usuários com perfil ADMIN ou GERENTE possam acessar esta página
require_once 'config/auth.php';
requerPerfil(['ADMIN', 'GERENTE']);

// Estabelece a conexão PDO com o banco de dados ($pdo)
include 'config/conexao.php';

// Carrega o DAO e o Controller do módulo
require_once 'dao/MotoristaDao.php';
require_once 'controller/MotoristaController.php';

// Instancia e executa o Controller passando as dependências via construtor
(new MotoristaController(
    new MotoristaDao($pdo)
))->handle();
