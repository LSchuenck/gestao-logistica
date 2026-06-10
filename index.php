<?php
require_once 'config/auth.php';
requerLogin();
include 'config/conexao.php';
$total_transp = $pdo->query("SELECT COUNT(*) FROM transportadora WHERE status='ATIVA'")->fetchColumn();
$total_entregas = $pdo->query("SELECT COUNT(*) FROM entrega WHERE status IN ('PENDENTE','EM_TRANSITO')")->fetchColumn();
$total_viagens = $pdo->query("SELECT COUNT(*) FROM viagem WHERE status IN ('INICIADA','EM_TRANSITO')")->fetchColumn();
$total_alertas = $pdo->query("SELECT COUNT(*) FROM alerta")->fetchColumn();
include 'views/index.php';
