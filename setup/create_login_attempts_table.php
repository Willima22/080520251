<?php
require_once '../config/config.php';
require_once '../config/db.php';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Verificar se a tabela já existe
$stmt = $conn->prepare("SHOW TABLES LIKE 'login_attempts'");
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

if (!$tableExists) {
    // Criar tabela de tentativas de login
    $sql = "CREATE TABLE login_attempts (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(100) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        timestamp DATETIME NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX idx_username (username),
        INDEX idx_ip (ip),
        INDEX idx_timestamp (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $conn->exec($sql);
        echo "Tabela 'login_attempts' criada com sucesso!";
    } catch (PDOException $e) {
        echo "Erro ao criar tabela 'login_attempts': " . $e->getMessage();
    }
} else {
    echo "A tabela 'login_attempts' já existe.";
}
?>
