# 3. Banco de Dados

## 3.1 Visão Geral do Banco de Dados

O Sistema de Agendamento de Postagens AW7 utiliza um banco de dados relacional MariaDB/MySQL para armazenar todos os dados da aplicação. O esquema do banco foi projetado para garantir integridade referencial, desempenho e escalabilidade.

## 3.2 Diagrama Entidade-Relacionamento

O banco de dados é composto por várias tabelas interconectadas que armazenam diferentes tipos de informações:

```
+-------------+       +---------------+       +---------------+
|   usuarios  |------>|   postagens   |<------|   clientes    |
+-------------+       +---------------+       +---------------+
       |                     |
       |                     |
       v                     v
+-------------+       +---------------+
|  historico  |       |   webhooks    |
+-------------+       +---------------+
       ^
       |
+-------------+
|configuracoes|
+-------------+
```

## 3.3 Estrutura Detalhada das Tabelas

### 3.3.1 Tabela `usuarios`

Armazena informações sobre os usuários do sistema.

| Coluna           | Tipo          | Restrições       | Descrição                                  |
|------------------|---------------|------------------|-------------------------------------------|
| id               | INT(11)       | PK, AUTO_INCREMENT | Identificador único do usuário             |
| nome             | VARCHAR(100)  | NOT NULL         | Nome completo do usuário                   |
| usuario          | VARCHAR(50)   | NOT NULL, UNIQUE | Nome de usuário para login                 |
| email            | VARCHAR(100)  | NOT NULL, UNIQUE | Email do usuário                           |
| senha            | VARCHAR(255)  | NOT NULL         | Senha criptografada                        |
| foto             | VARCHAR(255)  | NULL             | Caminho para a foto de perfil              |
| tipo_usuario     | VARCHAR(20)   | NOT NULL         | Tipo de usuário (Administrador ou Editor)  |
| ativo            | TINYINT(1)    | NOT NULL, DEFAULT 1 | Status do usuário (1=ativo, 0=inativo)    |
| ultimo_login     | DATETIME      | NULL             | Data e hora do último login                |
| senha_alterada   | TINYINT(1)    | NOT NULL, DEFAULT 0 | Indica se a senha já foi alterada         |
| data_criacao     | DATETIME      | NOT NULL         | Data de criação do registro                |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_usuario` (`usuario`)
- UNIQUE KEY `uk_email` (`email`)
- KEY `idx_tipo_usuario` (`tipo_usuario`)
- KEY `idx_ativo` (`ativo`)

**Exemplo de Dados:**
```sql
INSERT INTO usuarios (nome, usuario, email, senha, tipo_usuario, ativo, data_criacao) 
VALUES ('Administrador', 'admin', 'admin@exemplo.com', '$2y$10$...', 'Administrador', 1, NOW());
```

### 3.3.2 Tabela `clientes`

Armazena informações sobre os clientes para os quais as postagens são agendadas.

| Coluna           | Tipo          | Restrições       | Descrição                                  |
|------------------|---------------|------------------|-------------------------------------------|
| id               | INT(11)       | PK, AUTO_INCREMENT | Identificador único do cliente             |
| nome_cliente     | VARCHAR(100)  | NOT NULL         | Nome do cliente                            |
| email            | VARCHAR(100)  | NULL             | Email do cliente                           |
| telefone         | VARCHAR(20)   | NULL             | Telefone do cliente                        |
| ativo            | TINYINT(1)    | NOT NULL, DEFAULT 1 | Status do cliente (1=ativo, 0=inativo)    |
| data_criacao     | DATETIME      | NOT NULL         | Data de criação do registro                |
| usuario_id       | INT(11)       | NULL, FK         | ID do usuário que criou o cliente          |

**Índices:**
- PRIMARY KEY (`id`)
- KEY `idx_nome_cliente` (`nome_cliente`)
- KEY `idx_ativo` (`ativo`)
- KEY `fk_cliente_usuario` (`usuario_id`)

**Restrições:**
- FOREIGN KEY `fk_cliente_usuario` (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL

**Exemplo de Dados:**
```sql
INSERT INTO clientes (nome_cliente, email, telefone, ativo, data_criacao, usuario_id) 
VALUES ('Empresa ABC', 'contato@abc.com', '(11) 1234-5678', 1, NOW(), 1);
```

### 3.3.3 Tabela `postagens`

Armazena informações sobre as postagens agendadas.

| Coluna                   | Tipo          | Restrições       | Descrição                                  |
|--------------------------|---------------|------------------|-------------------------------------------|
| id                       | INT(11)       | PK, AUTO_INCREMENT | Identificador único da postagem            |
| cliente_id               | INT(11)       | NOT NULL, FK     | ID do cliente                              |
| usuario_id               | INT(11)       | NOT NULL, FK     | ID do usuário que criou a postagem         |
| agendamento_recorrente_id| INT(11)       | NULL, FK         | ID do agendamento recorrente (se aplicável)|
| tipo_postagem            | VARCHAR(50)   | NOT NULL         | Tipo de postagem (Instagram, Facebook, etc)|
| formato                  | VARCHAR(50)   | NOT NULL         | Formato (Imagem Única, Vídeo, Carrossel)   |
| data_postagem            | DATETIME      | NOT NULL         | Data e hora programada para a postagem     |
| data_postagem_utc        | DATETIME      | NOT NULL         | Data e hora em UTC                         |
| legenda                  | TEXT          | NULL             | Texto da postagem                          |
| arquivos                 | TEXT          | NOT NULL         | URLs dos arquivos em formato JSON          |
| post_id_unique           | VARCHAR(100)  | NOT NULL, UNIQUE | Identificador único da postagem            |
| webhook_status           | TINYINT(1)    | NOT NULL, DEFAULT 0 | Status do envio do webhook               |
| status                   | VARCHAR(50)   | NULL, DEFAULT 'agendado' | Status da postagem                 |
| data_criacao             | DATETIME      | NOT NULL         | Data de criação do registro                |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_post_id_unique` (`post_id_unique`)
- KEY `idx_cliente_id` (`cliente_id`)
- KEY `idx_usuario_id` (`usuario_id`)
- KEY `idx_data_postagem` (`data_postagem`)
- KEY `idx_status` (`status`)
- KEY `idx_webhook_status` (`webhook_status`)
- KEY `idx_agendamento_recorrente_id` (`agendamento_recorrente_id`)

**Restrições:**
- FOREIGN KEY `fk_postagem_cliente` (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
- FOREIGN KEY `fk_postagem_usuario` (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
- FOREIGN KEY `fk_postagem_agendamento` (`agendamento_recorrente_id`) REFERENCES `agendamentos_recorrentes` (`id`) ON DELETE SET NULL

**Exemplo de Dados:**
```sql
INSERT INTO postagens (cliente_id, usuario_id, tipo_postagem, formato, data_postagem, data_postagem_utc, legenda, arquivos, post_id_unique, webhook_status, status, data_criacao) 
VALUES (1, 1, 'Instagram', 'Imagem Única', '2025-05-10 15:00:00', '2025-05-10 18:00:00', 'Texto da postagem #hashtag', '["uploads/cliente1_20250506123045.jpg"]', 'cliente1_20250506123045', 0, 'agendado', NOW());
```

### 3.3.4 Tabela `historico`

Armazena o histórico de ações realizadas no sistema.

| Coluna           | Tipo          | Restrições       | Descrição                                  |
|------------------|---------------|------------------|-------------------------------------------|
| id               | INT(11)       | PK, AUTO_INCREMENT | Identificador único do registro            |
| usuario_id       | INT(11)       | NULL             | ID do usuário que realizou a ação          |
| usuario_nome     | VARCHAR(100)  | NULL             | Nome do usuário                            |
| acao             | VARCHAR(100)  | NOT NULL         | Descrição da ação realizada                |
| detalhes         | TEXT          | NULL             | Detalhes adicionais em formato JSON        |
| modulo           | VARCHAR(100)  | NULL             | Nome do módulo onde a ação foi realizada   |
| ip               | VARCHAR(45)   | NULL             | Endereço IP do usuário                     |
| data_hora        | DATETIME      | NOT NULL         | Data e hora da ação                        |

**Índices:**
- PRIMARY KEY (`id`)
- KEY `idx_usuario_id` (`usuario_id`)
- KEY `idx_data_hora` (`data_hora`)
- KEY `idx_acao` (`acao`)
- KEY `idx_modulo` (`modulo`)

**Exemplo de Dados:**
```sql
INSERT INTO historico (usuario_id, usuario_nome, acao, detalhes, modulo, ip, data_hora) 
VALUES (1, 'Administrador', 'Login', '{"browser":"Chrome","os":"Windows"}', 'auth', '192.168.1.1', NOW());
```

### 3.3.5 Tabela `configuracoes`

Armazena as configurações do sistema.

| Coluna           | Tipo          | Restrições       | Descrição                                  |
|------------------|---------------|------------------|-------------------------------------------|
| id               | INT(11)       | PK, AUTO_INCREMENT | Identificador único do registro            |
| chave            | VARCHAR(50)   | NOT NULL, UNIQUE | Nome da configuração                       |
| valor            | TEXT          | NOT NULL         | Valor da configuração                      |
| descricao        | VARCHAR(255)  | NULL             | Descrição da configuração                  |
| tipo             | VARCHAR(20)   | DEFAULT 'texto'  | Tipo de dado (texto, numero, boolean)      |
| data_atualizacao | DATETIME      | NOT NULL         | Data da última atualização                 |
| usuario_id       | INT(11)       | NULL, FK         | ID do usuário que atualizou                |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY `uk_chave` (`chave`)
- KEY `fk_config_usuario` (`usuario_id`)

**Restrições:**
- FOREIGN KEY `fk_config_usuario` (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL

**Exemplo de Dados:**
```sql
INSERT INTO configuracoes (chave, valor, descricao, tipo, data_atualizacao) 
VALUES ('tempo_limite_sessao', '5', 'Tempo limite de sessão em minutos', 'numero', NOW());
```

### 3.3.6 Tabela `agendamentos_recorrentes`

Armazena informações sobre agendamentos recorrentes.

| Coluna           | Tipo          | Restrições       | Descrição                                  |
|------------------|---------------|------------------|-------------------------------------------|
| id               | INT(11)       | PK, AUTO_INCREMENT | Identificador único do registro            |
| cliente_id       | INT(11)       | NOT NULL, FK     | ID do cliente                              |
| tipo_postagem    | VARCHAR(20)   | NOT NULL         | Tipo de postagem                           |
| formato          | VARCHAR(20)   | NOT NULL         | Formato da postagem                        |
| frequencia       | VARCHAR(20)   | NOT NULL         | Frequência (diario, semanal, mensal)       |
| dia_semana       | INT(1)        | NULL             | Dia da semana (0=domingo, 1=segunda, etc)  |
| dia_mes          | INT(2)        | NULL             | Dia do mês (1-31)                          |
| hora             | TIME          | NOT NULL         | Hora do agendamento                        |
| legenda          | TEXT          | NULL             | Texto da postagem                          |
| hashtags         | TEXT          | NULL             | Hashtags para incluir na postagem          |
| arquivo_path     | VARCHAR(255)  | NULL             | Caminho para o arquivo                     |
| ativo            | TINYINT(1)    | NOT NULL, DEFAULT 1 | Status do agendamento                     |
| usuario_id       | INT(11)       | NOT NULL, FK     | ID do usuário que criou o agendamento      |
| data_criacao     | DATETIME      | NOT NULL         | Data de criação do registro                |
| ultima_execucao  | DATETIME      | NULL             | Data da última execução                    |
| proxima_execucao | DATETIME      | NULL             | Data da próxima execução                   |

**Índices:**
- PRIMARY KEY (`id`)
- KEY `idx_cliente_id` (`cliente_id`)
- KEY `idx_ativo` (`ativo`)
- KEY `idx_proxima_execucao` (`proxima_execucao`)
- KEY `fk_agendamento_usuario` (`usuario_id`)

**Restrições:**
- FOREIGN KEY `fk_agendamento_cliente` (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
- FOREIGN KEY `fk_agendamento_usuario` (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE

**Exemplo de Dados:**
```sql
INSERT INTO agendamentos_recorrentes (cliente_id, tipo_postagem, formato, frequencia, dia_semana, hora, legenda, ativo, usuario_id, data_criacao, proxima_execucao) 
VALUES (1, 'Instagram', 'Imagem Única', 'semanal', 1, '15:00:00', 'Postagem recorrente #segunda', 1, 1, NOW(), '2025-05-12 15:00:00');
```

### 3.3.7 Tabela `webhooks`

Armazena configurações de webhooks para integração com sistemas externos.

| Coluna           | Tipo          | Restrições       | Descrição                                  |
|------------------|---------------|------------------|-------------------------------------------|
| id               | INT(11)       | PK, AUTO_INCREMENT | Identificador único do registro            |
| nome             | VARCHAR(100)  | NOT NULL         | Nome do webhook                            |
| url              | VARCHAR(255)  | NOT NULL         | URL para envio do webhook                  |
| eventos          | VARCHAR(255)  | NOT NULL         | Eventos que disparam o webhook             |
| headers          | TEXT          | NULL             | Cabeçalhos HTTP em formato JSON            |
| ativo            | TINYINT(1)    | NOT NULL, DEFAULT 1 | Status do webhook                          |
| data_criacao     | DATETIME      | NOT NULL         | Data de criação do registro                |
| ultima_execucao  | DATETIME      | NULL             | Data da última execução                    |

**Índices:**
- PRIMARY KEY (`id`)
- KEY `idx_ativo` (`ativo`)

**Exemplo de Dados:**
```sql
INSERT INTO webhooks (nome, url, eventos, headers, ativo, data_criacao) 
VALUES ('Integração Instagram', 'https://exemplo.com/webhook', 'postagem_criada,postagem_atualizada', '{"Authorization":"Bearer token123"}', 1, NOW());
```

## 3.4 Relacionamentos

### 3.4.1 Usuários e Clientes
- Um usuário pode criar/gerenciar múltiplos clientes
- Um cliente é criado/gerenciado por um único usuário

### 3.4.2 Usuários e Postagens
- Um usuário pode criar múltiplas postagens
- Uma postagem é criada por um único usuário

### 3.4.3 Clientes e Postagens
- Um cliente pode ter múltiplas postagens
- Uma postagem pertence a um único cliente

### 3.4.4 Agendamentos Recorrentes e Postagens
- Um agendamento recorrente pode gerar múltiplas postagens
- Uma postagem pode ser gerada por um agendamento recorrente (opcional)

## 3.5 Integridade Referencial

O banco de dados mantém integridade referencial através de chaves estrangeiras com as seguintes regras:

### 3.5.1 Exclusão de Usuário
- Postagens: Mantidas, mas o `usuario_id` é definido como NULL
- Clientes: Mantidos, mas o `usuario_id` é definido como NULL
- Histórico: Mantido, mas sem referência ao usuário excluído
- Configurações: Mantidas, mas o `usuario_id` é definido como NULL

### 3.5.2 Exclusão de Cliente
- Postagens: Excluídas em cascata
- Agendamentos Recorrentes: Excluídos em cascata

### 3.5.3 Exclusão de Agendamento Recorrente
- Postagens: Mantidas, mas o `agendamento_recorrente_id` é definido como NULL

## 3.6 Consultas Comuns

### 3.6.1 Listar Postagens Agendadas
```sql
SELECT p.id, p.data_postagem, p.tipo_postagem, p.formato, p.status,
       c.nome_cliente, u.nome as usuario_nome
FROM postagens p
JOIN clientes c ON p.cliente_id = c.id
JOIN usuarios u ON p.usuario_id = u.id
WHERE p.data_postagem >= CURRENT_DATE()
  AND p.status = 'agendado'
ORDER BY p.data_postagem ASC;
```

### 3.6.2 Buscar Histórico de Ações de um Usuário
```sql
SELECT h.acao, h.detalhes, h.modulo, h.ip, h.data_hora
FROM historico h
WHERE h.usuario_id = ?
ORDER BY h.data_hora DESC
LIMIT 50;
```

### 3.6.3 Verificar Postagens Pendentes de Webhook
```sql
SELECT id, cliente_id, post_id_unique, data_postagem
FROM postagens
WHERE webhook_status = 0
  AND data_postagem <= DATE_ADD(NOW(), INTERVAL 24 HOUR);
```

### 3.6.4 Estatísticas de Postagens por Cliente
```sql
SELECT c.nome_cliente, 
       COUNT(p.id) as total_postagens,
       SUM(CASE WHEN p.status = 'agendado' THEN 1 ELSE 0 END) as agendadas,
       SUM(CASE WHEN p.status = 'publicado' THEN 1 ELSE 0 END) as publicadas,
       SUM(CASE WHEN p.status = 'erro' THEN 1 ELSE 0 END) as erros
FROM clientes c
LEFT JOIN postagens p ON c.id = p.cliente_id
WHERE c.ativo = 1
GROUP BY c.id, c.nome_cliente
ORDER BY total_postagens DESC;
```

## 3.7 Índices e Otimização

O banco de dados utiliza vários índices para otimizar consultas frequentes:

### 3.7.1 Índices Primários
- Chaves primárias em todas as tabelas (`id`)

### 3.7.2 Índices Únicos
- `usuarios.usuario` e `usuarios.email`
- `postagens.post_id_unique`
- `configuracoes.chave`

### 3.7.3 Índices de Busca
- `clientes.nome_cliente` para buscas por nome de cliente
- `postagens.data_postagem` para filtragem por data
- `postagens.status` e `postagens.webhook_status` para filtragem por status
- `historico.data_hora` para ordenação cronológica

### 3.7.4 Índices de Junção
- Chaves estrangeiras para otimizar JOINs entre tabelas

## 3.8 Backup e Restauração

O sistema inclui funcionalidades para backup e restauração do banco de dados:

### 3.8.1 Backup Manual
Executado através do script `admin/backup_database.php`, que gera um arquivo SQL com a estrutura e dados do banco.

### 3.8.2 Backup Automático
Configurável para execução periódica (diária, semanal ou mensal) através das configurações do sistema.

### 3.8.3 Restauração
Realizada através da importação do arquivo SQL de backup no sistema de gerenciamento de banco de dados.

## 3.9 Migrações e Atualizações

O sistema inclui scripts para atualização do esquema do banco de dados:

### 3.9.1 Criação Inicial
O script `admin/setup_database.php` cria a estrutura inicial do banco de dados.

### 3.9.2 Atualizações
Scripts específicos para adicionar novas tabelas ou colunas, como `admin/create_historico_table.php`.

### 3.9.3 Correções
O script `admin/fix_database.php` realiza verificações e correções na estrutura do banco de dados.

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
