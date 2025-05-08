<?php
// Arquivo para verificar a estrutura das tabelas no banco de dados

// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'config/db.php';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

echo "<h1>Estrutura das Tabelas</h1>";

// Verificar tabela clientes
echo "<h2>Tabela: clientes</h2>";
try {
    $query = "DESCRIBE clientes";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar alguns registros
    echo "<h3>Registros na tabela clientes</h3>";
    $query = "SELECT * FROM clientes LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($clients) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($clients[0]) as $key) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        foreach ($clients as $client) {
            echo "<tr>";
            foreach ($client as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum registro encontrado.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro ao verificar a tabela clientes: " . $e->getMessage() . "</p>";
}

// Verificar tabela postagens
echo "<h2>Tabela: postagens</h2>";
try {
    $query = "DESCRIBE postagens";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro ao verificar a tabela postagens: " . $e->getMessage() . "</p>";
}

// Verificar tabela usuarios
echo "<h2>Tabela: usuarios</h2>";
try {
    $query = "DESCRIBE usuarios";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro ao verificar a tabela usuarios: " . $e->getMessage() . "</p>";
}
?>
