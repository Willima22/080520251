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
        // Use PostgreSQL environment variables
        $this->host = getenv('PGHOST');
        $this->db_name = getenv('PGDATABASE');
        $this->username = getenv('PGUSER');
        $this->password = getenv('PGPASSWORD');
    }

    // Connect to database
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "pgsql:host={$this->host};dbname={$this->db_name}",
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
            // Create tipo_usuario enum type
            $query = "
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'tipo_usuario_enum') THEN
                    CREATE TYPE tipo_usuario_enum AS ENUM ('Editor', 'Administrador');
                END IF;
            END
            $$;";
            
            $conn->exec($query);
            
            // Create post_type enum type
            $query = "
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'post_type_enum') THEN
                    CREATE TYPE post_type_enum AS ENUM ('Feed', 'Stories', 'Feed e Stories');
                END IF;
            END
            $$;";
            
            $conn->exec($query);
            
            // Create format enum type
            $query = "
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'format_enum') THEN
                    CREATE TYPE format_enum AS ENUM ('Imagem Única', 'Vídeo Único', 'Carrossel');
                END IF;
            END
            $$;";
            
            $conn->exec($query);
            
            // Create status enum type
            $query = "
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'status_enum') THEN
                    CREATE TYPE status_enum AS ENUM ('Agendado', 'Publicado', 'Falha');
                END IF;
            END
            $$;";
            
            $conn->exec($query);
            
            // Users Table
            $query = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                cpf VARCHAR(14) NOT NULL UNIQUE,
                usuario VARCHAR(50) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                tipo_usuario tipo_usuario_enum NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );";
            
            $conn->exec($query);
            
            // Clients Table
            $query = "
            CREATE TABLE IF NOT EXISTS clientes (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                id_grupo VARCHAR(100) NOT NULL,
                instagram VARCHAR(100) NOT NULL,
                id_instagram VARCHAR(100) NOT NULL,
                conta_anuncio VARCHAR(100) NOT NULL,
                link_business VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );";
            
            $conn->exec($query);
            
            // Posts Table
            $query = "
            CREATE TABLE IF NOT EXISTS postagens (
                id SERIAL PRIMARY KEY,
                cliente_id INT NOT NULL,
                tipo_postagem post_type_enum NOT NULL,
                formato format_enum NOT NULL,
                data_postagem TIMESTAMP NOT NULL,
                data_postagem_utc VARCHAR(30) NOT NULL,
                legenda TEXT,
                arquivos TEXT NOT NULL,
                status status_enum DEFAULT 'Agendado',
                webhook_response TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
            );";
            
            $conn->exec($query);
            
            // Function to update timestamps on update
            $query = "
            CREATE OR REPLACE FUNCTION update_timestamp()
            RETURNS TRIGGER AS $$
            BEGIN
               NEW.updated_at = CURRENT_TIMESTAMP;
               RETURN NEW;
            END;
            $$ language 'plpgsql';";
            
            $conn->exec($query);
            
            // Triggers for updated_at field updates
            $tables = ['usuarios', 'clientes', 'postagens'];
            foreach ($tables as $table) {
                $query = "
                DROP TRIGGER IF EXISTS {$table}_update_timestamp ON {$table};
                CREATE TRIGGER {$table}_update_timestamp
                BEFORE UPDATE ON {$table}
                FOR EACH ROW
                EXECUTE PROCEDURE update_timestamp();";
                
                $conn->exec($query);
            }
            
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
