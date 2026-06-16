<?php
/**
 * transportadoras.php
 * Entry point do módulo de transportadoras.
 * Verifica autenticação, instancia as dependências e delega tudo ao Controller.
 */

// Garante que apenas usuários com perfil ADMIN ou GERENTE possam acessar esta página
require_once 'config/auth.php';
exigirPerfil(['ADMIN', 'GERENTE']);

// Estabelece a conexão PDO com o banco de dados ($pdo)
include 'config/conexao.php';

// Carrega os DAOs e o Controller do módulo
require_once 'dao/EnderecoDao.php';
require_once 'dao/TransportadoraDao.php';
require_once 'controller/TransportadoraController.php';

// Instancia e executa o Controller passando as dependências via construtor
(new TransportadoraController(
    new TransportadoraDao($pdo),
    new EnderecoDao($pdo)
))->processar();
