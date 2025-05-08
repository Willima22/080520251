# 7. Segurança

## 7.1 Visão Geral de Segurança

O Sistema de Agendamento de Postagens AW7 implementa diversas medidas de segurança para proteger dados sensíveis, prevenir acessos não autorizados e garantir a integridade das informações. Esta seção documenta as práticas de segurança implementadas no sistema, potenciais vulnerabilidades e recomendações para manutenção da segurança.

## 7.2 Autenticação e Controle de Acesso

### 7.2.1 Sistema de Autenticação

O sistema utiliza um mecanismo de autenticação baseado em sessões PHP:

1. **Login**
   - Arquivo: `login.php`
   - Método: Formulário POST
   - Campos: Usuário e senha
   - Processamento: Verificação das credenciais no banco de dados

2. **Armazenamento de Senhas**
   - Método: Hash seguro com `password_hash()` e algoritmo bcrypt
   - Salt: Gerado automaticamente pelo PHP
   - Verificação: Usando `password_verify()`

3. **Sessões**
   - Inicialização: Em `config/config.php`
   - Timeout: Configurável nas configurações do sistema (padrão: 30 minutos)
   - Regeneração de ID: A cada login bem-sucedido

**Código Relevante:**
```php
// Hash de senha (ao criar/atualizar usuário)
$hashedPassword = password_hash($senha, PASSWORD_DEFAULT);

// Verificação de senha (ao fazer login)
if (password_verify($senha, $user['senha'])) {
    // Login bem-sucedido
}
```

### 7.2.2 Controle de Acesso

O sistema implementa um controle de acesso baseado em perfis:

1. **Perfis de Usuário**
   - Administrador: Acesso completo a todas as funcionalidades
   - Editor: Acesso limitado a funcionalidades específicas

2. **Verificação de Permissões**
   - Função: `isAdmin()` em `includes/auth.php`
   - Uso: Verificação em cada página restrita
   - Redirecionamento: Usuários sem permissão são redirecionados

3. **Proteção de Páginas**
   - Todas as páginas (exceto login) verificam a existência de sessão ativa
   - Páginas administrativas verificam se o usuário é administrador

**Código Relevante:**
```php
// Verificação de sessão ativa
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Verificação de permissão de administrador
if (!isAdmin()) {
    setFlashMessage('danger', 'Você não tem permissão para acessar esta página.');
    redirect('dashboard.php');
}
```

## 7.3 Proteção contra Vulnerabilidades Comuns

### 7.3.1 Injeção SQL

O sistema utiliza consultas preparadas (prepared statements) para prevenir injeção SQL:

1. **PDO com Prepared Statements**
   - Todas as consultas SQL utilizam parâmetros vinculados
   - Valores são escapados automaticamente pelo PDO

2. **Exemplo de Implementação**
   ```php
   $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
   $stmt = $conn->prepare($sql);
   $stmt->bindParam(':usuario', $usuario);
   $stmt->execute();
   ```

### 7.3.2 Cross-Site Scripting (XSS)

O sistema implementa as seguintes medidas para prevenir XSS:

1. **Sanitização de Entrada**
   - Função: `sanitize()` em `includes/functions.php`
   - Uso: Aplicada em todos os dados recebidos do usuário

2. **Escape de Saída**
   - Função: `htmlspecialchars()` 
   - Uso: Aplicada em todos os dados exibidos na interface

3. **Validação de Dados**
   - Verificação de tipo e formato antes do processamento
   - Rejeição de dados que não atendem aos critérios esperados

**Código Relevante:**
```php
// Sanitização de entrada
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Escape de saída
echo htmlspecialchars($usuario_nome, ENT_QUOTES, 'UTF-8');
```

### 7.3.3 Cross-Site Request Forgery (CSRF)

O sistema implementa proteção contra CSRF:

1. **Tokens CSRF**
   - Geração: Token único por sessão/formulário
   - Armazenamento: Na sessão do usuário
   - Verificação: Em todas as requisições POST

2. **Implementação**
   - Inclusão do token em todos os formulários
   - Verificação do token antes de processar a requisição

**Código Relevante:**
```php
// Geração de token CSRF
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificação de token CSRF
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Em formulários
<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

// Na validação
if (!verifyCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('danger', 'Erro de validação. Por favor, tente novamente.');
    redirect('index.php');
}
```

### 7.3.4 Upload de Arquivos

O sistema implementa medidas de segurança para uploads de arquivos:

1. **Validação de Tipo**
   - Verificação de extensão
   - Verificação de tipo MIME
   - Lista branca de tipos permitidos

2. **Validação de Tamanho**
   - Limite máximo configurável
   - Verificação antes do processamento

3. **Armazenamento Seguro**
   - Renomeação de arquivos com nomes únicos
   - Armazenamento fora da raiz web quando possível
   - Separação por cliente e tipo

**Código Relevante:**
```php
// Verificação de tipo
$allowedTypes = ALLOWED_IMAGE_TYPES;
if (!in_array($file['type'], $allowedTypes)) {
    setFlashMessage('danger', 'Tipo de arquivo não permitido.');
    redirect('index.php');
}

// Verificação de tamanho
if ($file['size'] > MAX_FILE_SIZE) {
    setFlashMessage('danger', 'Arquivo muito grande. Tamanho máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    redirect('index.php');
}

// Geração de nome único
$fileName = generateUniqueFilename($cliente_nome, $extensao);
```

## 7.4 Proteção de Dados

### 7.4.1 Dados Sensíveis

O sistema identifica e protege os seguintes dados sensíveis:

1. **Credenciais de Usuário**
   - Senhas: Armazenadas com hash seguro
   - Tokens de sessão: Armazenados apenas na sessão

2. **Dados de Clientes**
   - Informações de contato: Acesso restrito
   - Credenciais de redes sociais: Acesso restrito

3. **Configurações do Sistema**
   - Chaves de API: Acesso restrito a administradores
   - Configurações de webhook: Acesso restrito a administradores

### 7.4.2 Backup e Recuperação

O sistema implementa as seguintes práticas para backup e recuperação de dados:

1. **Backup Regular**
   - Funcionalidade: Backup manual e automático
   - Escopo: Estrutura e dados do banco de dados
   - Frequência: Configurável (diário, semanal, mensal)

2. **Armazenamento de Backups**
   - Local: Download direto para o computador do administrador
   - Recomendação: Armazenamento externo seguro

3. **Procedimento de Recuperação**
   - Importação do arquivo SQL de backup
   - Restauração de arquivos de mídia

## 7.5 Segurança na Comunicação

### 7.5.1 HTTPS

O sistema é projetado para funcionar com HTTPS:

1. **Recomendações**
   - Uso obrigatório de HTTPS em produção
   - Configuração de redirecionamento HTTP para HTTPS
   - Uso de certificados válidos e atualizados

2. **Configuração do Servidor**
   - Apache: Módulo mod_ssl e arquivo .htaccess
   - Nginx: Configuração no bloco server

**Exemplo de Configuração (Apache .htaccess):**
```
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 7.5.2 Webhooks

O sistema implementa medidas de segurança para comunicação via webhooks:

1. **Autenticação**
   - Uso de token de API no cabeçalho HTTP
   - Verificação do token no sistema receptor

2. **HTTPS**
   - Recomendação de uso exclusivo de URLs HTTPS
   - Validação de certificado

3. **Registro de Atividade**
   - Log de todas as tentativas de envio
   - Detalhes de sucesso/falha

## 7.6 Registro e Monitoramento

### 7.6.1 Logs de Sistema

O sistema mantém logs detalhados de atividades:

1. **Tabela `historico`**
   - Registro de ações de usuários
   - Registro de eventos do sistema
   - Registro de tentativas de login

2. **Informações Registradas**
   - Usuário
   - Ação
   - Detalhes
   - Data e hora
   - Endereço IP

3. **Interface de Visualização**
   - Arquivo: `logs.php`
   - Filtros: Por usuário, ação, período
   - Detalhes: Modal com informações completas

### 7.6.2 Monitoramento de Atividades Suspeitas

O sistema implementa monitoramento básico de atividades suspeitas:

1. **Tentativas de Login**
   - Registro de tentativas falhas
   - Alerta após múltiplas tentativas falhas

2. **Ações Administrativas**
   - Registro detalhado de todas as ações administrativas
   - Notificação para alterações críticas

## 7.7 Recomendações de Segurança

### 7.7.1 Configuração do Servidor

1. **PHP**
   - Versão: Manter atualizada (mínimo PHP 7.4)
   - Configurações: Desativar `display_errors` em produção
   - Extensões: Habilitar apenas as necessárias

2. **Banco de Dados**
   - Versão: Manter atualizada
   - Usuário: Privilégios mínimos necessários
   - Firewall: Restringir acesso apenas ao servidor web

3. **Servidor Web**
   - Versão: Manter atualizada
   - Módulos: Habilitar apenas os necessários
   - Headers: Configurar headers de segurança

**Headers de Segurança Recomendados:**
```
# Apache (.htaccess)
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;"
```

### 7.7.2 Manutenção de Segurança

1. **Atualizações**
   - Sistema: Verificar e aplicar atualizações regularmente
   - Dependências: Manter bibliotecas de terceiros atualizadas
   - Servidor: Manter sistema operacional e software do servidor atualizados

2. **Monitoramento**
   - Logs: Revisar logs regularmente
   - Atividade: Monitorar padrões de uso anormais
   - Recursos: Monitorar uso de recursos do servidor

3. **Backup**
   - Frequência: Realizar backups regularmente
   - Teste: Testar a restauração de backups periodicamente
   - Armazenamento: Manter backups em local seguro e separado

### 7.7.3 Políticas de Usuário

1. **Senhas**
   - Complexidade: Exigir senhas fortes
   - Expiração: Forçar alteração periódica
   - Histórico: Impedir reutilização de senhas antigas

2. **Contas**
   - Revisão: Auditar contas de usuário regularmente
   - Inatividade: Desativar contas inativas
   - Privilégios: Seguir o princípio do menor privilégio

3. **Treinamento**
   - Conscientização: Treinar usuários sobre práticas seguras
   - Phishing: Alertar sobre técnicas de engenharia social
   - Procedimentos: Documentar procedimentos de segurança

## 7.8 Resposta a Incidentes

### 7.8.1 Plano de Resposta

Em caso de incidente de segurança, seguir este plano básico:

1. **Identificação**
   - Detectar e confirmar o incidente
   - Determinar o tipo e escopo do incidente

2. **Contenção**
   - Isolar sistemas afetados
   - Bloquear acessos suspeitos
   - Preservar evidências

3. **Erradicação**
   - Remover a causa raiz
   - Corrigir vulnerabilidades exploradas
   - Verificar sistemas relacionados

4. **Recuperação**
   - Restaurar sistemas a partir de backups limpos
   - Verificar integridade dos dados
   - Monitorar para garantir que o problema não persista

5. **Lições Aprendidas**
   - Documentar o incidente
   - Identificar melhorias necessárias
   - Atualizar procedimentos de segurança

### 7.8.2 Contatos de Emergência

Manter uma lista atualizada de contatos para emergências:

1. **Equipe Interna**
   - Administrador do sistema
   - Equipe de TI
   - Gestores responsáveis

2. **Suporte Externo**
   - Suporte do provedor de hospedagem
   - Especialistas em segurança (se aplicável)
   - Autoridades (em caso de incidentes graves)

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
