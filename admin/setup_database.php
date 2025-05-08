<?php
/**
 * Script para configurar o banco de dados
 * Executa o arquivo SQL para criar as tabelas necessárias
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se o usuário está logado e é administrador
// A sessão já foi iniciada em config.php, não precisamos iniciar novamente
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Administrador') {
    echo "Acesso negado. Você precisa ser administrador para executar este script.";
    exit;
}

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Ler o arquivo SQL
$sqlFile = file_get_contents(__DIR__ . '/database_setup.sql');

// Dividir o arquivo em consultas individuais
$queries = explode(';', $sqlFile);

// Executar cada consulta
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "<h1>Configuração do Banco de Dados</h1>";
echo "<p>Executando consultas SQL...</p>";

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    try {
        $conn->exec($query);
        $successCount++;
    } catch (PDOException $e) {
        $errorCount++;
        $errors[] = $e->getMessage();
    }
}

// Exibir resultados
echo "<h2>Resultado</h2>";
echo "<p>{$successCount} consultas executadas com sucesso.</p>";

if ($errorCount > 0) {
    echo "<p>{$errorCount} erros encontrados:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nenhum erro encontrado. Banco de dados configurado com sucesso!</p>";
}

// Adicionar link para voltar
echo "<p><a href='../dashboard.php'>Voltar para o Dashboard</a></p>";
?>
