<?php
require_once '../config/config.php';
require_once '../config/db.php';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Verificar se a tabela já existe
$stmt = $conn->prepare("SHOW TABLES LIKE 'logs'");
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

if (!$tableExists) {
    // Criar tabela de logs
    $sql = "CREATE TABLE logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        usuario_id INT(11) NULL,
        acao VARCHAR(100) NOT NULL,
        detalhes TEXT NULL,
        ip VARCHAR(45) NOT NULL,
        data_hora DATETIME NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_usuario (usuario_id),
        INDEX idx_data_hora (data_hora),
        INDEX idx_acao (acao),
        CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $conn->exec($sql);
        echo "Tabela 'logs' criada com sucesso!";
    } catch (PDOException $e) {
        echo "Erro ao criar tabela 'logs': " . $e->getMessage();
    }
} else {
    echo "A tabela 'logs' já existe.";
}
?>
