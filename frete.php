<?php
/*
 * Arquivo: frete.php
 * Finalidade: Entry point do módulo de Fretes e Notas Fiscais.
 * Verifica autenticação com perfil restrito, carrega dependências e
 * delega todo o processamento ao FreteController.
 *
 * Acesso restrito: apenas perfis ADMIN e GERENTE.
 */

// Verifica autenticação com perfil ADMIN ou GERENTE; redireciona se não autorizado
require_once 'config/auth.php';
requerPerfil(['ADMIN', 'GERENTE']);

// Estabelece a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o model, o DAO e o controller do módulo de Fretes
require_once 'model/Frete.php';
require_once 'dao/FreteDao.php';
require_once 'controller/FreteController.php';

// Instancia o DAO injetando a conexão PDO e repassa ao controller
$dao        = new FreteDao($pdo);
$controller = new FreteController($dao);

// Processa a requisição e renderiza a view (ou a DANFE se ?nf= presente)
$controller->processar();
