<?php
/*
 * Arquivo: indicadores.php
 * Finalidade: Entry point do módulo de Indicadores de Desempenho (KPIs).
 * Verifica autenticação com perfil restrito, carrega dependências e
 * delega todo o processamento ao IndicadorController.
 *
 * Acesso restrito: apenas perfis ADMIN e GERENTE.
 */

// Verifica autenticação com perfil ADMIN ou GERENTE; redireciona se não autorizado
require_once 'config/auth.php';
requerPerfil(['ADMIN', 'GERENTE']);

// Estabelece a conexão com o banco de dados via PDO
include 'config/conexao.php';

// Carrega o DAO e o controller do módulo de Indicadores
require_once 'dao/IndicadorDao.php';
require_once 'controller/IndicadorController.php';

// Instancia o DAO injetando a conexão PDO e repassa ao controller
$dao        = new IndicadorDao($pdo);
$controller = new IndicadorController($dao);

// Processa a requisição e renderiza a view
$controller->processar();
