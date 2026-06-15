<?php
/*
 * Arquivo: operacoes.php
 * Finalidade: Entry point do módulo de Operações.
 *
 * Responsabilidades deste arquivo:
 *   1. Verificar autenticação e perfil de acesso
 *   2. Estabelecer a conexão com o banco de dados
 *   3. Instanciar o DAO e o Controller
 *   4. Delegar toda a lógica ao OperacaoController
 *
 * Toda lógica de negócio, queries SQL e preparação de variáveis
 * para a view estão encapsuladas em:
 *   - dao/OperacaoDao.php        → queries SQL e transações
 *   - controller/OperacaoController.php → lógica de request e dados para a view
 *   - views/operacoes.php        → renderização HTML (não modificada)
 */

// Verifica autenticação e restringe acesso a ADMIN, GERENTE e OPERADOR
require_once 'config/auth.php';
requerPerfil(['ADMIN', 'GERENTE', 'OPERADOR']);

// Inclui a conexão com o banco de dados via PDO
require_once 'config/conexao.php';

// Carrega o DAO e o Controller do módulo de Operações
require_once 'dao/OperacaoDao.php';
require_once 'controller/OperacaoController.php';

// Instancia o DAO com a conexão PDO e passa ao Controller
$dao        = new OperacaoDao($pdo);
$controller = new OperacaoController($dao);

// Delega toda a lógica de request, dados e renderização ao Controller
$controller->executar();
