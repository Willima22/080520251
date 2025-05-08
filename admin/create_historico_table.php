<?php
/**
 * Script para criar a tabela de histórico (logs) se não existir
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Não incluir functions.php para evitar redeclaração de funções
// require_once __DIR__ . '/../includes/functions.php';

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

try {
    // Verificar se a tabela historico já existe
    $stmt = $conn->query("SHOW TABLES LIKE 'historico'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Criar a tabela historico
        $sql = "CREATE TABLE historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            usuario_nome VARCHAR(100) NOT NULL,
            acao VARCHAR(100) NOT NULL,
            detalhes TEXT DEFAULT NULL,
            modulo VARCHAR(100) DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            data_hora DATETIME NOT NULL
        )";
        
        $conn->exec($sql);
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0;'>
                <h3>Sucesso!</h3>
                <p>Tabela 'historico' criada com sucesso.</p>
              </div>";
    } else {
        // Verificar se as colunas existem
        $stmt = $conn->query("DESCRIBE historico");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingColumns = [];
        $requiredColumns = [
            'usuario_id' => 'INT NOT NULL',
            'usuario_nome' => 'VARCHAR(100) NOT NULL',
            'acao' => 'VARCHAR(100) NOT NULL',
            'detalhes' => 'TEXT DEFAULT NULL',
            'modulo' => 'VARCHAR(100) DEFAULT NULL',
            'ip' => 'VARCHAR(45) DEFAULT NULL',
            'data_hora' => 'DATETIME NOT NULL'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $missingColumns[$column] = $definition;
            }
        }
        
        if (!empty($missingColumns)) {
            // Adicionar as colunas que faltam
            foreach ($missingColumns as $column => $definition) {
                $sql = "ALTER TABLE historico ADD COLUMN $column $definition";
                $conn->exec($sql);
            }
            
            echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0;'>
                    <h3>Sucesso!</h3>
                    <p>Colunas adicionadas à tabela 'historico': " . implode(', ', array_keys($missingColumns)) . "</p>
                  </div>";
        } else {
            echo "<div style='background-color: #cce5ff; color: #004085; padding: 15px; border-radius: 4px; margin: 20px 0;'>
                    <h3>Informação</h3>
                    <p>A tabela 'historico' já existe e contém todas as colunas necessárias.</p>
                  </div>";
        }
    }
} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0;'>
            <h3>Erro!</h3>
            <p>Erro ao criar tabela 'historico': " . $e->getMessage() . "</p>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Tabela de Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Criar Tabela de Histórico</h1>
        
        <div class="mt-4">
            <a href="../logs.php" class="btn btn-primary">Ver Logs</a>
            <a href="../dashboard.php" class="btn btn-secondary">Voltar para o Dashboard</a>
        </div>
    </div>
</body>
</html>
