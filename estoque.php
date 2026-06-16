<?php
/*
 * estoque.php
 * -----------
 * Entry point do módulo de Estoque.
 *
 * Responsabilidades deste arquivo:
 *  - Verificar autenticação do usuário
 *  - Carregar a conexão PDO e as classes necessárias
 *  - Instanciar os DAOs (EstoqueDao, MovimentacaoDao) e o EstoqueController
 *  - Delegar todo o processamento ao controller
 *
 * Toda a lógica de negócio e SQL está em:
 *  - dao/EstoqueDao.php            (saldo de estoque, ON DUPLICATE KEY UPDATE)
 *  - dao/MovimentacaoDao.php       (histórico de movimentações)
 *  - controller/EstoqueController.php (roteamento, validações, indicadores)
 *  - model/Estoque.php             (entidade de dados)
 *  - views/estoque.php             (renderização HTML)
 */

require_once 'config/auth.php';
exigirLogin(); // Garante que apenas usuários autenticados acessem este módulo

include 'config/conexao.php'; // Disponibiliza $pdo

// Carrega as classes do padrão DAO/MVC
require_once 'model/Estoque.php';
require_once 'dao/EstoqueDao.php';
require_once 'dao/MovimentacaoDao.php';
require_once 'controller/EstoqueController.php';

// Captura o ID do usuário logado para registrar o responsável pela movimentação
$idUsuario = (int)$_SESSION['usuario']['id'];

// Instancia os DAOs com a conexão PDO e injeta no controller
$estoqueDao       = new EstoqueDao($pdo);
$movimentacaoDao  = new MovimentacaoDao($pdo);
$estoqueController = new EstoqueController($estoqueDao, $movimentacaoDao, $idUsuario);

// Delega todo o processamento (ação POST + carregamento de view) ao controller
$estoqueController->processar();
