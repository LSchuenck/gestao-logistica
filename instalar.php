<?php
/*
 * Arquivo: instalar.php
 * Finalidade: Script de instalação do sistema de Gestão Logística.
 * Executa o script SQL autoritativo (config/gestao_logistica.sql),
 * que cria o banco, todas as tabelas e os dados de exemplo.
 *
 * ATENÇÃO: Reexecutar este script APAGA e recria todo o banco de dados.
 *
 * Credenciais do administrador padrão:
 *   Usuário: admin@gestao.com
 *   Senha:   admin123
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Instalar Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card shadow border-0 p-4" style="max-width:600px;width:100%">
    <h4 class="fw-bold mb-4">Instalação do Sistema</h4>
<?php
try {
    $sqlFile = __DIR__ . '/config/gestao_logistica.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo config/gestao_logistica.sql não encontrado.");
    }

    // Conecta ao MySQL sem banco de dados especificado
    $pdo = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lê o script SQL e divide em statements individuais
    $sql = file_get_contents($sqlFile);

    // Remove comentários de linha (--)
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    // Remove comentários de bloco (/* */)
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Divide por ponto-e-vírgula e executa cada statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $count = 0;
    foreach ($statements as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
            $count++;
        }
    }

    echo "<p class='text-success'><strong>&#10003;</strong> Banco de dados criado.</p>";
    echo "<p class='text-success'><strong>&#10003;</strong> Todas as tabelas criadas.</p>";
    echo "<p class='text-success'><strong>&#10003;</strong> Dados de exemplo inseridos.</p>";

    echo "<div class='alert alert-success mt-3'>
        <h5>&#10003; Instalação concluída com sucesso!</h5>
        <p class='mb-1'><strong>Usuário:</strong> admin@gestao.com</p>
        <p class='mb-3'><strong>Senha:</strong> admin123</p>
        <a href='index.php' class='btn btn-success'>Ir para o Sistema &rarr;</a>
    </div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
}
?>
</div>
</body>
</html>
