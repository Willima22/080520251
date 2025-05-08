<?php
/**
 * Database configuration and connection
 * Uses PDO for secure database operations
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // CPanel database credentials
        $this->host = 'localhost';
        $this->db_name = 'opapopol_02052025';
        $this->username = 'opapopol_02052025';
        $this->password = 'Aroma19@';
    }

    // Connect to database
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }

    // Create tables if they don't exist
    public function setupTables() {
        $conn = $this->connect();
        
        try {
            // Users Table
            $query = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                cpf VARCHAR(14) NOT NULL UNIQUE,
                usuario VARCHAR(50) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                tipo_usuario ENUM('Editor', 'Administrador') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($query);
            
            // Clients Table
            $query = "
            CREATE TABLE IF NOT EXISTS clientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                id_grupo VARCHAR(100) NOT NULL,
                instagram VARCHAR(100) NOT NULL,
                id_instagram VARCHAR(100) NOT NULL,
                conta_anuncio VARCHAR(100) NOT NULL,
                link_business VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($query);
            
            // Posts Table
            $query = "
            CREATE TABLE IF NOT EXISTS postagens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                tipo_postagem ENUM('Feed', 'Stories', 'Feed e Stories') NOT NULL,
                formato ENUM('Imagem Única', 'Vídeo Único', 'Carrossel') NOT NULL,
                data_postagem TIMESTAMP NOT NULL,
                data_postagem_utc VARCHAR(30) NOT NULL,
                legenda TEXT,
                arquivos TEXT NOT NULL,
                status ENUM('Agendado', 'Publicado', 'Falha', 'Cancelado') DEFAULT 'Agendado',
                webhook_response TEXT,
                webhook_enviado TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($query);
            
            // Create a default admin user if none exists
            $query = "SELECT COUNT(*) as count FROM usuarios";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row['count'] == 0) {
                $query = "INSERT INTO usuarios (nome, email, cpf, usuario, senha, tipo_usuario) 
                          VALUES ('Administrador', 'admin@example.com', '000.000.000-00', 'admin', :senha, 'Administrador')";
                $stmt = $conn->prepare($query);
                // Hash the password
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt->bindParam(':senha', $hashedPassword);
                $stmt->execute();
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Database Setup Error: " . $e->getMessage());
            return false;
        }
    }
}
