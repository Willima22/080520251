# 1. Visão Geral do Sistema

## 1.1 Introdução

O Sistema de Agendamento de Postagens AW7 é uma aplicação web desenvolvida em PHP para gerenciar e agendar postagens em redes sociais para diferentes clientes. O sistema permite o upload de arquivos de mídia (imagens e vídeos), agendamento de postagens, gerenciamento de clientes e usuários, e integração com sistemas externos via webhooks.

## 1.2 Objetivos do Sistema

- Centralizar o gerenciamento de postagens para redes sociais
- Automatizar o processo de agendamento de conteúdo
- Organizar arquivos de mídia por cliente
- Manter histórico de postagens e ações no sistema
- Permitir integração com plataformas externas via webhooks
- Fornecer controle de acesso baseado em perfis de usuário
- Facilitar o trabalho de equipes de marketing e comunicação

## 1.3 Tecnologias Utilizadas

### 1.3.1 Backend

- **Linguagem de Programação**: PHP 7.4+
- **Banco de Dados**: MariaDB/MySQL
- **Servidor Web**: Apache ou Nginx

### 1.3.2 Frontend

- **HTML5**: Estruturação do conteúdo
- **CSS3**: Estilização e responsividade
- **JavaScript**: Interatividade e validações no lado do cliente
- **Bootstrap 5**: Framework CSS para design responsivo
- **jQuery**: Biblioteca JavaScript para manipulação do DOM e AJAX

### 1.3.3 Bibliotecas e Componentes

- **DataTables**: Para tabelas interativas com paginação, ordenação e filtragem
- **Summernote**: Editor WYSIWYG para campos de texto formatado
- **Bootstrap Datepicker**: Para seleção de datas
- **FontAwesome**: Para ícones
- **Chart.js**: Para gráficos e visualizações de dados

## 1.4 Requisitos de Sistema

### 1.4.1 Requisitos de Servidor

- PHP 7.4 ou superior
- Extensões PHP: PDO, PDO_MySQL, GD, FileInfo, JSON, Session
- MariaDB 10.3+ ou MySQL 5.7+
- Apache 2.4+ ou Nginx 1.18+
- Mínimo de 2GB de RAM
- Espaço em disco: 500MB para a aplicação + espaço para uploads (recomendado 10GB+)

### 1.4.2 Requisitos de Cliente (Navegador)

- Google Chrome 88+
- Mozilla Firefox 85+
- Microsoft Edge 88+
- Safari 14+
- Suporte a JavaScript habilitado
- Cookies habilitados

## 1.5 Perfis de Usuário

### 1.5.1 Administrador

- Acesso completo a todas as funcionalidades do sistema
- Gerenciamento de usuários e permissões
- Configurações do sistema
- Visualização de logs e histórico
- Gerenciamento de webhooks e integrações

### 1.5.2 Editor

- Agendamento de postagens
- Gerenciamento de clientes
- Visualização de postagens agendadas
- Edição do próprio perfil

## 1.6 Principais Funcionalidades

- **Agendamento de Postagens**: Permite agendar postagens com imagens ou vídeos para datas e horários específicos
- **Gerenciamento de Clientes**: Cadastro e manutenção de informações de clientes
- **Upload de Arquivos**: Sistema para upload e organização de arquivos de mídia
- **Histórico de Ações**: Registro detalhado de todas as ações realizadas no sistema
- **Webhooks**: Integração com sistemas externos via webhooks
- **Relatórios**: Geração de relatórios de atividades e postagens
- **Configurações**: Personalização de diversos aspectos do sistema
- **Backup**: Ferramentas para backup e restauração do banco de dados

## 1.7 Estrutura de Diretórios

```
/Postagens/
├── admin/                 # Scripts administrativos
│   ├── backup_database.php
│   ├── create_historico_table.php
│   ├── fix_database.php
│   ├── fix_upload_system.php
│   └── setup_database.php
├── ajax/                  # Endpoints para requisições AJAX
│   ├── check_webhook_status.php
│   ├── get_client_history.php
│   └── toggle_cliente_status.php
├── arquivos/              # Arquivos de mídia organizados por cliente
├── assets/                # Recursos estáticos
│   ├── css/               # Folhas de estilo
│   ├── js/                # Scripts JavaScript
│   ├── img/               # Imagens do sistema
│   └── vendor/            # Bibliotecas de terceiros
├── config/                # Arquivos de configuração
│   ├── config.php         # Configurações globais
│   └── db.php             # Configuração de banco de dados
├── documentacao/          # Documentação do sistema
├── includes/              # Componentes reutilizáveis
│   ├── auth.php           # Funções de autenticação
│   ├── footer.php         # Rodapé comum
│   ├── functions.php      # Funções utilitárias
│   ├── header.php         # Cabeçalho comum
│   └── log_functions.php  # Funções de log
├── uploads/               # Diretório temporário para uploads
└── vendor/                # Bibliotecas de terceiros (se aplicável)
```

## 1.8 Convenções de Nomenclatura

- **Arquivos PHP**: Nomes em minúsculas com underscores (ex: `cliente_adicionar.php`)
- **Classes**: CamelCase com primeira letra maiúscula (ex: `Database`)
- **Funções**: camelCase com primeira letra minúscula (ex: `getUploadPath()`)
- **Variáveis**: camelCase com primeira letra minúscula (ex: `$clienteId`)
- **Constantes**: Todas maiúsculas com underscores (ex: `UPLOAD_BASE_DIR`)
- **Tabelas do Banco**: Nomes no singular, minúsculas (ex: `usuario`, `cliente`)
- **Colunas do Banco**: snake_case, minúsculas (ex: `nome_cliente`, `data_criacao`)

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
