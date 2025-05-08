-- Script de criação de tabelas para o sistema de agendamento de postagens
-- Compatível com MySQL/MariaDB para CPanel

-- Criar tabela de usuários
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela de clientes
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar tabela de postagens
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir usuário administrador padrão (senha: admin123)
-- A senha está com hash usando password_hash() do PHP
INSERT INTO usuarios (nome, email, cpf, usuario, senha, tipo_usuario) 
VALUES ('Administrador', 'admin@example.com', '000.000.000-00', 'admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'Administrador')
ON DUPLICATE KEY UPDATE id = id;
