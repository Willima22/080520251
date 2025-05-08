<?php
require_once '../config/config.php';
require_once '../config/db.php';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Verificar se a tabela já existe
$stmt = $conn->prepare("SHOW TABLES LIKE 'webhooks'");
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

if (!$tableExists) {
    // Criar tabela de webhooks
    $sql = "CREATE TABLE webhooks (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        url VARCHAR(255) NOT NULL,
        tipo VARCHAR(50) NOT NULL COMMENT 'login, logout, login_failed, todos',
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        data_criacao DATETIME NOT NULL,
        ultima_execucao DATETIME NULL,
        status_ultima_execucao VARCHAR(50) NULL,
        PRIMARY KEY (id),
        INDEX idx_tipo (tipo),
        INDEX idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $conn->exec($sql);
        echo "Tabela 'webhooks' criada com sucesso!";
    } catch (PDOException $e) {
        echo "Erro ao criar tabela 'webhooks': " . $e->getMessage();
    }
} else {
    echo "A tabela 'webhooks' já existe.";
}
?>
