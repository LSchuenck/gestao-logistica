<?php
/*
 * entregas.php
 * ------------
 * Entry point do módulo de Entregas.
 *
 * Responsabilidades deste arquivo:
 *  - Verificar autenticação do usuário
 *  - Carregar a conexão PDO e as classes necessárias
 *  - Instanciar o EntregaDao e o EntregaController
 *  - Delegar todo o processamento ao controller
 *
 * Toda a lógica de negócio e SQL está em:
 *  - dao/EntregaDao.php       (queries e regras de estoque)
 *  - controller/EntregaController.php (roteamento de requisições)
 *  - model/Entrega.php        (entidade de dados)
 *  - views/entregas.php       (renderização HTML)
 */

require_once 'config/auth.php';
exigirLogin(); // Garante que apenas usuários autenticados acessem este módulo

include 'config/conexao.php'; // Disponibiliza $pdo

// Carrega as classes do padrão DAO/MVC
require_once 'dao/EntregaDao.php';
require_once 'controller/EntregaController.php';

// Instancia o DAO com a conexão PDO e injeta no controller
$entregaDao        = new EntregaDao($pdo);
$entregaController = new EntregaController($entregaDao);

// Delega todo o processamento (ações GET/POST + carregamento de view) ao controller
$entregaController->processar();
