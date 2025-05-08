-- Script SQL para configurar o banco de dados
-- Criação e atualização das tabelas necessárias para o sistema

-- Tabela de histórico (logs)
CREATE TABLE IF NOT EXISTS `historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `usuario_nome` varchar(100) NOT NULL,
  `acao` varchar(100) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `modulo` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `data_hora` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_data_hora` (`data_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de agendamentos recorrentes
CREATE TABLE IF NOT EXISTS `agendamentos_recorrentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `tipo_postagem` varchar(20) NOT NULL,
  `formato` varchar(20) NOT NULL,
  `frequencia` varchar(20) NOT NULL COMMENT 'diario, semanal, mensal',
  `dia_semana` int(1) DEFAULT NULL COMMENT '0=domingo, 1=segunda, ..., 6=sábado',
  `dia_mes` int(2) DEFAULT NULL COMMENT '1-31',
  `hora` time NOT NULL,
  `legenda` text DEFAULT NULL,
  `hashtags` text DEFAULT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `usuario_id` int(11) NOT NULL,
  `data_criacao` datetime NOT NULL,
  `ultima_execucao` datetime DEFAULT NULL,
  `proxima_execucao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cliente_id` (`cliente_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_proxima_execucao` (`proxima_execucao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_atualizacao` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir configurações padrão
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`, `data_atualizacao`) VALUES
('nome_sistema', 'AW7 Postagens', 'Nome do sistema', NOW()),
('nome_empresa', 'AW7 Comunicação e Marketing', 'Nome da empresa', NOW()),
('logo_path', 'assets/img/logo.png', 'Caminho para o logo', NOW()),
('tempo_limite_sessao', '5', 'Tempo limite de sessão em minutos', NOW()),
('permitir_multiplos_logins', '0', 'Permitir múltiplos logins simultâneos (0=não, 1=sim)', NOW()),
('tipo_usuario_padrao', 'editor', 'Tipo padrão para novos usuários (editor, administrador)', NOW()),
('alterar_senha_primeiro_login', '1', 'Exigir alteração de senha no primeiro login (0=não, 1=sim)', NOW()),
('autenticacao_dois_fatores', '0', 'Exigir autenticação de dois fatores (0=não, 1=sim)', NOW()),
('periodicidade_backup', 'semanal', 'Periodicidade do backup automático (diario, semanal, mensal)', NOW());

-- Verificar se a tabela de postagens existe e adicionar coluna para agendamento recorrente
ALTER TABLE `postagens` 
ADD COLUMN IF NOT EXISTS `agendamento_recorrente_id` int(11) DEFAULT NULL AFTER `cliente_id`,
ADD INDEX IF NOT EXISTS `idx_agendamento_recorrente_id` (`agendamento_recorrente_id`);

-- Verificar se a tabela de usuários existe e adicionar coluna para foto de perfil
ALTER TABLE `usuarios` 
ADD COLUMN IF NOT EXISTS `foto` varchar(255) DEFAULT NULL AFTER `senha`,
ADD COLUMN IF NOT EXISTS `ultimo_login` datetime DEFAULT NULL AFTER `foto`,
ADD COLUMN IF NOT EXISTS `senha_alterada` tinyint(1) NOT NULL DEFAULT 0 AFTER `ultimo_login`;

-- Criar tabela de webhooks se não existir
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `eventos` varchar(255) NOT NULL,
  `headers` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` datetime NOT NULL,
  `ultima_execucao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
