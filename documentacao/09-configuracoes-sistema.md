# 9. Configurações do Sistema

## 9.1 Visão Geral das Configurações

O Sistema de Agendamento de Postagens AW7 possui diversos parâmetros configuráveis que permitem personalizar seu comportamento, aparência e integrações. Esta seção documenta todas as configurações disponíveis, seus valores padrão, localização e impacto no sistema.

## 9.2 Configurações Principais

### 9.2.1 Arquivo de Configuração Principal

O arquivo principal de configuração é `config/config.php`, que contém constantes e funções de configuração global:

```php
// Application constants
define('APP_NAME', 'AW7 Postagens');
define('APP_VERSION', '1.0.0');
define('APP_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
define('WEBHOOK_URL', 'https://automacao2.aw7agencia.com.br/webhook/agendarpostagem');
define('FILES_BASE_URL', 'https://postar.agenciaraff.com.br');
define('APP_ENV', 'Produção');
define('APP_INSTALLED_DATE', '2025-01-01');
define('APP_LAST_UPDATE', '2025-05-06');
```

| Constante | Descrição | Valor Padrão | Impacto |
|-----------|-----------|--------------|---------|
| APP_NAME | Nome da aplicação | AW7 Postagens | Exibido no título das páginas e cabeçalhos |
| APP_VERSION | Versão atual do sistema | 1.0.0 | Usado para controle de versão e atualizações |
| APP_URL | URL base do sistema | Detectado automaticamente | Base para links internos |
| WEBHOOK_URL | URL para envio de webhooks | https://automacao2.aw7agencia.com.br/webhook/agendarpostagem | Endpoint para notificações de postagens |
| FILES_BASE_URL | URL base para arquivos | https://postar.agenciaraff.com.br | URL pública para acesso aos arquivos de mídia |
| APP_ENV | Ambiente atual | Produção | Controla comportamentos específicos por ambiente |
| APP_INSTALLED_DATE | Data de instalação | 2025-01-01 | Informativo |
| APP_LAST_UPDATE | Data da última atualização | 2025-05-06 | Informativo |

### 9.2.2 Configurações de Upload de Arquivos

```php
// File upload settings
define('UPLOAD_BASE_DIR', __DIR__ . '/../arquivos/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 1073741824); // 1GB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/mov', 'video/avi']);
```

| Constante | Descrição | Valor Padrão | Impacto |
|-----------|-----------|--------------|---------|
| UPLOAD_BASE_DIR | Diretório base para arquivos permanentes | __DIR__ . '/../arquivos/' | Onde os arquivos de mídia são armazenados |
| UPLOAD_DIR | Diretório para uploads temporários | __DIR__ . '/../uploads/' | Onde os arquivos são armazenados temporariamente |
| MAX_FILE_SIZE | Tamanho máximo de arquivo permitido | 1073741824 (1GB) | Limita o tamanho dos arquivos enviados |
| ALLOWED_IMAGE_TYPES | Tipos MIME permitidos para imagens | ['image/jpeg', 'image/png', 'image/gif'] | Restringe os formatos de imagem aceitos |
| ALLOWED_VIDEO_TYPES | Tipos MIME permitidos para vídeos | ['video/mp4', 'video/mov', 'video/avi'] | Restringe os formatos de vídeo aceitos |

## 9.3 Configurações de Banco de Dados

As configurações de conexão com o banco de dados estão no arquivo `config/db.php`:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'opapopol_02052025');
define('DB_USER', 'opapopol_02052025');
define('DB_PASS', 'Aroma19@');
define('DB_CHARSET', 'utf8mb4');
```

| Constante | Descrição | Impacto |
|-----------|-----------|---------|
| DB_HOST | Servidor do banco de dados | Endereço do servidor MariaDB/MySQL |
| DB_NAME | Nome do banco de dados | Nome do banco de dados a ser utilizado |
| DB_USER | Usuário do banco de dados | Usuário para autenticação no banco |
| DB_PASS | Senha do banco de dados | Senha para autenticação no banco |
| DB_CHARSET | Conjunto de caracteres | Codificação de caracteres do banco |

## 9.4 Configurações de Sessão

### 9.4.1 Configurações no PHP

As configurações de sessão são definidas no início do arquivo `config/config.php`:

```php
// Session configuration
session_start();
```

Configurações adicionais podem ser definidas antes de `session_start()`:

```php
// Configurações recomendadas para produção
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Apenas para HTTPS
ini_set('session.gc_maxlifetime', 1800); // 30 minutos
```

### 9.4.2 Configurações na Tabela `configuracoes`

Algumas configurações de sessão são armazenadas na tabela `configuracoes` do banco de dados:

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| tempo_limite_sessao | Tempo limite de sessão em minutos | 30 | Tempo de inatividade antes do logout automático |
| permitir_multiplos_logins | Permitir múltiplos logins simultâneos | 1 (sim) | Se o mesmo usuário pode estar logado em múltiplos dispositivos |

## 9.5 Configurações de Webhook

### 9.5.1 Configurações Básicas

As configurações de webhook são definidas em `config/config.php` e na tabela `configuracoes`:

```php
define('WEBHOOK_URL', 'https://automacao2.aw7agencia.com.br/webhook/agendarpostagem');
```

### 9.5.2 Configurações Avançadas na Tabela `configuracoes`

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| webhook_api_key | Chave de API para autenticação | (valor específico) | Autenticação das requisições webhook |
| webhook_timeout | Timeout para requisições webhook em segundos | 10 | Tempo máximo de espera por resposta |
| webhook_retry_count | Número de tentativas em caso de falha | 3 | Quantas vezes tentar reenviar em caso de falha |
| webhook_retry_interval | Intervalo entre tentativas em segundos | 300 | Tempo entre tentativas de reenvio |

## 9.6 Configurações de Interface

### 9.6.1 Tema e Aparência

As configurações de tema são armazenadas na tabela `configuracoes`:

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| tema_cor_primaria | Cor primária do tema | #6CBD45 | Cor principal dos botões e elementos de destaque |
| tema_cor_secundaria | Cor secundária do tema | #C62E60 | Cor secundária para elementos de interface |
| tema_cor_fundo | Cor de fundo geral | #FAF6F1 | Cor de fundo das páginas |
| tema_cor_texto | Cor principal de texto | #4A4A4A | Cor padrão para textos |
| logo_url | URL da logo do sistema | assets/img/logo.png | Logo exibida no cabeçalho e login |
| favicon_url | URL do favicon | assets/img/favicon.ico | Ícone exibido na aba do navegador |

### 9.6.2 Configurações de Paginação

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| itens_por_pagina | Número de itens por página em listagens | 20 | Quantidade de itens exibidos em tabelas paginadas |
| max_paginas_navegacao | Máximo de links de página na navegação | 5 | Número de links de página exibidos na navegação |

## 9.7 Configurações de Timezone

### 9.7.1 Configuração do PHP

A configuração de timezone é definida em `config/config.php`:

```php
// Timezone settings
date_default_timezone_set('America/Sao_Paulo'); // Brazil timezone for display
```

### 9.7.2 Configurações na Tabela `configuracoes`

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| timezone_display | Timezone para exibição | America/Sao_Paulo | Timezone usado para exibir datas e horas |
| formato_data | Formato de exibição de data | d/m/Y | Formato para exibição de datas |
| formato_hora | Formato de exibição de hora | H:i | Formato para exibição de horas |
| formato_data_hora | Formato de exibição de data e hora | d/m/Y H:i | Formato para exibição de data e hora |

## 9.8 Configurações de Segurança

### 9.8.1 Configurações de Senha

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| senha_min_tamanho | Tamanho mínimo de senha | 8 | Número mínimo de caracteres para senhas |
| senha_requer_maiuscula | Exigir letra maiúscula | 1 (sim) | Se senhas devem conter pelo menos uma letra maiúscula |
| senha_requer_numero | Exigir número | 1 (sim) | Se senhas devem conter pelo menos um número |
| senha_requer_especial | Exigir caractere especial | 1 (sim) | Se senhas devem conter pelo menos um caractere especial |
| senha_expiracao_dias | Dias até expiração da senha | 90 | Número de dias até a senha expirar |

### 9.8.2 Configurações de Login

| Chave | Descrição | Valor Padrão | Impacto |
|-------|-----------|--------------|---------|
| max_tentativas_login | Máximo de tentativas de login | 5 | Número de tentativas antes de bloquear |
| tempo_bloqueio_login | Tempo de bloqueio após falhas (minutos) | 30 | Duração do bloqueio após exceder tentativas |
| captcha_ativo | Ativar CAPTCHA após falhas | 1 (sim) | Se o CAPTCHA deve ser ativado após falhas de login |

## 9.9 Arquivos Sensíveis

Os seguintes arquivos contêm informações sensíveis e devem ser protegidos:

| Arquivo | Conteúdo Sensível | Recomendação de Proteção |
|---------|-------------------|--------------------------|
| config/db.php | Credenciais de banco de dados | Permissões 640, fora da raiz web |
| config/config.php | Chaves de API, URLs de webhook | Permissões 640, fora da raiz web |
| .htaccess | Regras de segurança e redirecionamento | Permissões 644, não editável via web |
| logs/* | Logs de sistema com informações sensíveis | Diretório com permissões 750, fora da raiz web |
| backups/* | Backups do banco de dados | Diretório com permissões 750, fora da raiz web |

## 9.10 Alterando Configurações

### 9.10.1 Alterando Constantes

Para alterar constantes definidas em `config/config.php` ou `config/db.php`:

1. Faça backup do arquivo original
2. Edite o arquivo com um editor de texto
3. Modifique os valores das constantes
4. Salve o arquivo
5. Teste o sistema para garantir que tudo funciona corretamente

### 9.10.2 Alterando Configurações no Banco de Dados

Para alterar configurações armazenadas na tabela `configuracoes`:

1. Acesse o sistema como administrador
2. Navegue até a página de Configurações
3. Modifique os valores desejados
4. Clique em Salvar

Alternativamente, você pode modificar diretamente no banco de dados:

```sql
UPDATE configuracoes SET valor = 'novo_valor' WHERE chave = 'nome_da_configuracao';
```

### 9.10.3 Recarregando Configurações

Após alterar configurações no banco de dados, o sistema carrega automaticamente os novos valores na próxima requisição. Não é necessário reiniciar o servidor web.

## 9.11 Configurações Recomendadas por Ambiente

### 9.11.1 Ambiente de Desenvolvimento

```php
define('APP_ENV', 'Desenvolvimento');
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### 9.11.2 Ambiente de Produção

```php
define('APP_ENV', 'Produção');
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);
ini_set('log_errors', 1);
ini_set('error_log', '/caminho/para/logs/php_errors.log');
```

### 9.11.3 Configurações de PHP Recomendadas

Recomendações para o arquivo `php.ini` em produção:

```ini
; Configurações gerais
max_execution_time = 60
memory_limit = 256M
post_max_size = 100M
upload_max_filesize = 50M
max_file_uploads = 20

; Configurações de sessão
session.cookie_httponly = 1
session.use_only_cookies = 1
session.cookie_secure = 1
session.gc_maxlifetime = 1800
session.cookie_samesite = "Lax"

; Configurações de segurança
expose_php = Off
```

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
