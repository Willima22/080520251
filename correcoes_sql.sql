-- Correções SQL para os problemas identificados

-- 1. Adicionar coluna 'status' na tabela postagens (para dashboard.php)
ALTER TABLE postagens ADD COLUMN status VARCHAR(50) NULL;

-- 3. Adicionar coluna 'nome' na tabela clientes (para clientes_visualizar.php)
-- Alternativa 1: Adicionar a coluna
ALTER TABLE clientes ADD COLUMN nome VARCHAR(255) NULL;

-- Alternativa 2: Copiar dados de nome_cliente para nome (execute após criar a coluna)
UPDATE clientes SET nome = nome_cliente WHERE nome IS NULL;
