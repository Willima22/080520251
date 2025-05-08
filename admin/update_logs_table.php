<?php
/**
 * Script para atualizar a estrutura da tabela de logs/histórico
 * Adiciona novas colunas para melhorar o registro de atividades
 */

// Incluir arquivos necessários
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar se o usuário está logado e é administrador
session_start();
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

try {
    // Verificar se a tabela historico existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'historico'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Criar a tabela historico se não existir
        $sql = "CREATE TABLE historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            usuario_nome VARCHAR(100) NOT NULL,
            acao VARCHAR(100) NOT NULL,
            detalhes TEXT,
            modulo VARCHAR(100),
            ip VARCHAR(45),
            data_hora DATETIME NOT NULL
        )";
        $conn->exec($sql);
        echo "<div class='alert alert-success'>Tabela de histórico criada com sucesso!</div>";
    } else {
        // Verificar se as colunas já existem
        $result = $conn->query("DESCRIBE historico");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $columnsToAdd = [];
        
        if (!in_array('usuario_nome', $columns)) {
            $columnsToAdd[] = "ADD COLUMN usuario_nome VARCHAR(100) AFTER usuario_id";
        }
        
        if (!in_array('detalhes', $columns)) {
            $columnsToAdd[] = "ADD COLUMN detalhes TEXT AFTER acao";
        }
        
        if (!in_array('modulo', $columns)) {
            $columnsToAdd[] = "ADD COLUMN modulo VARCHAR(100) AFTER detalhes";
        }
        
        if (!in_array('ip', $columns)) {
            $columnsToAdd[] = "ADD COLUMN ip VARCHAR(45) AFTER modulo";
        }
        
        if (!empty($columnsToAdd)) {
            $sql = "ALTER TABLE historico " . implode(", ", $columnsToAdd);
            $conn->exec($sql);
            echo "<div class='alert alert-success'>Tabela de histórico atualizada com sucesso!</div>";
        } else {
            echo "<div class='alert alert-info'>A tabela de histórico já está atualizada.</div>";
        }
    }
    
    // Registrar no histórico
    $acao = "Atualizou tabela de logs";
    $detalhes = "Atualizou a estrutura da tabela de logs/histórico";
    $modulo = "update_logs_table.php";
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $logQuery = "INSERT INTO historico (usuario_id, usuario_nome, acao, detalhes, modulo, ip, data_hora) 
                VALUES (:usuario_id, :usuario_nome, :acao, :detalhes, :modulo, :ip, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $logStmt->bindParam(':usuario_nome', $_SESSION['username'], PDO::PARAM_STR);
    $logStmt->bindParam(':acao', $acao, PDO::PARAM_STR);
    $logStmt->bindParam(':detalhes', $detalhes, PDO::PARAM_STR);
    $logStmt->bindParam(':modulo', $modulo, PDO::PARAM_STR);
    $logStmt->bindParam(':ip', $ip, PDO::PARAM_STR);
    $logStmt->execute();
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao atualizar tabela de histórico: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização da Tabela de Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3>Atualização da Tabela de Logs</h3>
                    </div>
                    <div class="card-body">
                        <p>A estrutura da tabela de logs/histórico foi atualizada para incluir as seguintes colunas:</p>
                        <ul>
                            <li><strong>usuario_nome</strong>: Nome do usuário que realizou a ação</li>
                            <li><strong>detalhes</strong>: Detalhes sobre a ação realizada</li>
                            <li><strong>modulo</strong>: Página ou módulo onde a ação foi realizada</li>
                            <li><strong>ip</strong>: Endereço IP do usuário</li>
                        </ul>
                        
                        <div class="mt-4">
                            <a href="../logs.php" class="btn btn-primary">Ver Logs</a>
                            <a href="../dashboard.php" class="btn btn-secondary">Voltar para o Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
