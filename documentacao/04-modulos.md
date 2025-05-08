# 4. Módulos e Funcionalidades

## 4.1 Visão Geral dos Módulos

O Sistema de Agendamento de Postagens AW7 é organizado em módulos funcionais que atendem a diferentes aspectos do gerenciamento de postagens em redes sociais. Cada módulo possui suas próprias páginas, funcionalidades e fluxos de trabalho.

Os principais módulos do sistema são:

1. **Autenticação e Controle de Acesso**
2. **Dashboard e Navegação**
3. **Agendamento de Postagens**
4. **Gerenciamento de Clientes**
5. **Gerenciamento de Usuários**
6. **Logs e Histórico**
7. **Configurações do Sistema**
8. **Webhooks e Integrações**
9. **Administração e Manutenção**

Esta seção detalha cada um desses módulos, suas funcionalidades, arquivos relacionados e fluxos de trabalho.

## 4.2 Autenticação e Controle de Acesso

Este módulo gerencia a autenticação de usuários, controle de sessões e permissões de acesso.

### 4.2.1 Arquivos Principais

- `login.php`: Página de login
- `logout.php`: Script para encerrar sessão
- `includes/auth.php`: Funções de autenticação
- `perfil.php`: Gerenciamento de perfil do usuário

### 4.2.2 Funcionalidades

#### Login de Usuário

**Arquivo:** `login.php`

**Descrição:** Permite que usuários autentiquem-se no sistema.

**Parâmetros:**
- `usuario`: Nome de usuário
- `senha`: Senha do usuário

**Processo:**
1. Validação dos campos de entrada
2. Verificação das credenciais no banco de dados
3. Criação de sessão para usuário autenticado
4. Registro da ação no histórico
5. Redirecionamento para o dashboard

**Código Relevante:**
```php
// Verificar credenciais
$sql = "SELECT id, nome, usuario, email, senha, tipo_usuario FROM usuarios WHERE usuario = :usuario AND ativo = 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':usuario', $usuario);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($senha, $user['senha'])) {
    // Criar sessão
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['username'] = $user['usuario'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['tipo_usuario'];
    $_SESSION['login_time'] = time();
    
    // Registrar login
    registrarLog('Login', 'Login realizado com sucesso', 'auth');
    
    // Redirecionar
    redirect('dashboard.php');
} else {
    setFlashMessage('danger', 'Usuário ou senha inválidos.');
}
```

#### Logout

**Arquivo:** `logout.php`

**Descrição:** Encerra a sessão do usuário.

**Processo:**
1. Registro da ação no histórico
2. Destruição da sessão
3. Redirecionamento para a página de login

**Código Relevante:**
```php
// Registrar logout
if (isset($_SESSION['user_id'])) {
    registrarLog('Logout', 'Logout realizado com sucesso', 'auth');
}

// Destruir sessão
session_unset();
session_destroy();

// Redirecionar
redirect('login.php');
```

#### Verificação de Autenticação

**Arquivo:** `includes/auth.php`

**Descrição:** Verifica se o usuário está autenticado e tem permissão para acessar a página.

**Funções Principais:**
- `checkSession()`: Verifica se a sessão é válida
- `isAdmin()`: Verifica se o usuário é administrador
- `requireAdmin()`: Redireciona se o usuário não for administrador

**Código Relevante:**
```php
function checkSession() {
    // Verificar se existe sessão de usuário
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
    
    // Verificar inatividade
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
        session_unset();
        session_destroy();
        redirect('login.php?reason=inactivity');
    }
    
    // Atualizar timestamp de última atividade
    $_SESSION['last_activity'] = time();
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Administrador';
}
```

#### Gerenciamento de Perfil

**Arquivo:** `perfil.php`

**Descrição:** Permite ao usuário visualizar e editar seu perfil.

**Funcionalidades:**
- Atualização de informações pessoais
- Alteração de senha
- Upload de foto de perfil

**Código Relevante:**
```php
// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    
    // Atualizar informações básicas
    $sql = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Atualizar sessão
        $_SESSION['user_name'] = $nome;
        $_SESSION['user_email'] = $email;
        
        setFlashMessage('success', 'Perfil atualizado com sucesso!');
    } else {
        setFlashMessage('danger', 'Erro ao atualizar perfil.');
    }
}
```

### 4.2.3 Controle de Permissões

O sistema implementa um controle de acesso baseado em perfis de usuário:

- **Administrador**: Acesso completo a todas as funcionalidades
- **Editor**: Acesso limitado a funcionalidades específicas

A verificação de permissões é realizada em cada página através da função `isAdmin()`:

```php
// Verificar se o usuário tem permissão de administrador
if (!isAdmin()) {
    setFlashMessage('danger', 'Você não tem permissão para acessar esta página.');
    redirect('dashboard.php');
}
```

## 4.3 Dashboard e Navegação

Este módulo fornece a interface principal do sistema e elementos de navegação.

### 4.3.1 Arquivos Principais

- `dashboard.php`: Página inicial após login
- `includes/header.php`: Cabeçalho com menu de navegação
- `includes/footer.php`: Rodapé com informações e scripts

### 4.3.2 Funcionalidades

#### Dashboard

**Arquivo:** `dashboard.php`

**Descrição:** Exibe um resumo das atividades e estatísticas do sistema.

**Componentes:**
- Contadores (total de clientes, postagens agendadas, etc.)
- Gráficos de atividade
- Lista de postagens recentes
- Alertas e notificações

**Código Relevante:**
```php
// Obter estatísticas
$sql_clientes = "SELECT COUNT(*) FROM clientes WHERE ativo = 1";
$total_clientes = $conn->query($sql_clientes)->fetchColumn();

$sql_postagens = "SELECT COUNT(*) FROM postagens WHERE data_postagem >= CURRENT_DATE()";
$total_postagens = $conn->query($sql_postagens)->fetchColumn();

$sql_usuarios = "SELECT COUNT(*) FROM usuarios WHERE ativo = 1";
$total_usuarios = $conn->query($sql_usuarios)->fetchColumn();

// Obter postagens recentes
$sql_recentes = "SELECT p.id, p.data_postagem, p.tipo_postagem, c.nome_cliente 
                FROM postagens p 
                JOIN clientes c ON p.cliente_id = c.id 
                WHERE p.data_postagem >= CURRENT_DATE() 
                ORDER BY p.data_postagem ASC 
                LIMIT 5";
$stmt_recentes = $conn->prepare($sql_recentes);
$stmt_recentes->execute();
$postagens_recentes = $stmt_recentes->fetchAll(PDO::FETCH_ASSOC);
```

#### Navegação Principal

**Arquivo:** `includes/header.php`

**Descrição:** Fornece o menu de navegação e elementos comuns do cabeçalho.

**Componentes:**
- Logo do sistema
- Menu de navegação
- Informações do usuário logado
- Mensagens flash

**Código Relevante:**
```php
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <img src="<?= APP_URL ?>/assets/img/logo.png" alt="Logo" height="30">
            <?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Agendar Postagem</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="postagens_agendadas.php">Postagens Agendadas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clientes_visualizar.php">Clientes</a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="usuarios.php">Usuários</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php">Logs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="configuracoes.php">Configurações</a>
                </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="perfil.php">Meu Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
```

#### Mensagens Flash

**Arquivos:** `includes/header.php`, `includes/functions.php`

**Descrição:** Sistema para exibir mensagens temporárias ao usuário.

**Funções:**
- `setFlashMessage($type, $message)`: Define uma mensagem
- `getFlashMessage()`: Obtém e limpa a mensagem atual

**Código Relevante:**
```php
// Em functions.php
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Em header.php
<?php
$flashMessage = getFlashMessage();
if ($flashMessage): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show">
        <?= $flashMessage['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
```

## 4.4 Agendamento de Postagens

Este módulo gerencia o processo de agendamento de postagens, desde o formulário inicial até a confirmação e envio de webhooks.

### 4.4.1 Arquivos Principais

- `index.php`: Formulário de agendamento
- `confirmar_postagem.php`: Confirmação e finalização do agendamento
- `postagens_agendadas.php`: Lista de postagens agendadas
- `editar_postagem.php`: Edição de postagens existentes

### 4.4.2 Funcionalidades

#### Formulário de Agendamento

**Arquivo:** `index.php`

**Descrição:** Permite ao usuário preencher os dados da postagem e fazer upload de arquivos.

**Campos:**
- Cliente
- Tipo de postagem (Instagram, Facebook, etc.)
- Formato (Imagem Única, Vídeo, Carrossel)
- Data e hora
- Legenda
- Arquivos de mídia

**Processo:**
1. Validação dos campos obrigatórios
2. Upload e processamento de arquivos
3. Armazenamento temporário dos dados na sessão
4. Redirecionamento para confirmação

**Código Relevante:**
```php
// Upload de arquivos
if ($formato === 'Imagem Única' || $formato === 'Vídeo Único') {
    // Single file upload
    $file = $_FILES['singleFile'];
    
    // Determinar o tipo de arquivo
    $tipo_arquivo = in_array($file['type'], ALLOWED_IMAGE_TYPES) ? 'imagem' : 'video';
    
    // Obter o caminho de upload
    $upload_path = getUploadPath($cliente_id, $cliente_nome, $tipo_arquivo);
    
    // Obter a extensão do arquivo
    $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Gerar nome de arquivo único
    $fileName = generateUniqueFilename($cliente_nome, $extensao);
    
    // Caminho completo para o arquivo
    $targetPath = $upload_path['dir'] . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Armazenar a URL completa do arquivo
        $fileUrl = $upload_path['url'] . $fileName;
        $uploadedFiles[] = [
            'name' => $fileName,
            'path' => $targetPath,
            'url' => $fileUrl
        ];
    }
}
```

#### Confirmação de Postagem

**Arquivo:** `confirmar_postagem.php`

**Descrição:** Exibe os detalhes da postagem para confirmação e finaliza o agendamento.

**Processo:**
1. Recuperação dos dados da sessão
2. Exibição dos detalhes para confirmação
3. Conversão de data/hora para UTC
4. Inserção no banco de dados
5. Envio de webhook
6. Registro no histórico

**Código Relevante:**
```php
// Inserir no banco de dados
$query = "INSERT INTO postagens (cliente_id, tipo_postagem, formato, data_postagem, data_postagem_utc, legenda, post_id_unique, webhook_status, data_criacao, usuario_id, arquivos) 
          VALUES (:cliente_id, :tipo_postagem, :formato, :data_postagem, :data_postagem_utc, :legenda, :post_id_unique, 0, :data_criacao, :usuario_id, :arquivos)";

$stmt = $conn->prepare($query);
$stmt->bindParam(':cliente_id', $postData['cliente_id']);
$stmt->bindParam(':tipo_postagem', $postData['tipo_postagem']);
$stmt->bindParam(':formato', $postData['formato']);
$stmt->bindParam(':data_postagem', $dbDateTime);
$stmt->bindParam(':data_postagem_utc', $dateTime);
$stmt->bindParam(':legenda', $postData['legenda']);
$stmt->bindParam(':post_id_unique', $post_id_unique);
$stmt->bindParam(':data_criacao', $data_criacao);
$stmt->bindParam(':usuario_id', $usuario_id);
$stmt->bindParam(':arquivos', $arquivos_json);

$stmt->execute();
$postId = $conn->lastInsertId();

// Enviar webhook
$webhookSuccess = sendWebhook($postId, $postData, $dateTime);

// Atualizar status do webhook
if ($webhookSuccess) {
    $updateWebhook = "UPDATE postagens SET webhook_status = 1 WHERE id = :id";
    $stmtWebhook = $conn->prepare($updateWebhook);
    $stmtWebhook->bindParam(':id', $postId);
    $stmtWebhook->execute();
}
```

#### Lista de Postagens Agendadas

**Arquivo:** `postagens_agendadas.php`

**Descrição:** Exibe a lista de todas as postagens agendadas com opções de filtro.

**Filtros:**
- Cliente
- Período (data inicial e final)
- Status (agendado, publicado, erro)

**Funcionalidades:**
- Visualização detalhada
- Edição de postagens
- Exclusão de postagens
- Reenvio de webhook

**Código Relevante:**
```php
// Construir consulta com filtros
$sql = "SELECT p.*, c.nome_cliente, u.nome as usuario_nome 
        FROM postagens p
        JOIN clientes c ON p.cliente_id = c.id
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE 1=1";

$params = [];

if (!empty($filtro_cliente)) {
    $sql .= " AND p.cliente_id = ?";
    $params[] = $filtro_cliente;
}

if (!empty($filtro_data_inicio)) {
    $sql .= " AND p.data_postagem >= ?";
    $params[] = $filtro_data_inicio . ' 00:00:00';
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND p.data_postagem <= ?";
    $params[] = $filtro_data_fim . ' 23:59:59';
}

if (!empty($filtro_status)) {
    $sql .= " AND p.status = ?";
    $params[] = $filtro_status;
}

$sql .= " ORDER BY p.data_postagem DESC";

// Executar consulta
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$postagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

#### Edição de Postagem

**Arquivo:** `editar_postagem.php`

**Descrição:** Permite editar uma postagem existente.

**Funcionalidades:**
- Alteração de dados da postagem
- Substituição de arquivos
- Atualização de webhook

**Código Relevante:**
```php
// Atualizar postagem
$sql = "UPDATE postagens SET 
        tipo_postagem = :tipo_postagem,
        formato = :formato,
        data_postagem = :data_postagem,
        data_postagem_utc = :data_postagem_utc,
        legenda = :legenda,
        arquivos = :arquivos,
        webhook_status = 0
        WHERE id = :id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':tipo_postagem', $tipo_postagem);
$stmt->bindParam(':formato', $formato);
$stmt->bindParam(':data_postagem', $dbDateTime);
$stmt->bindParam(':data_postagem_utc', $dateTimeUTC);
$stmt->bindParam(':legenda', $legenda);
$stmt->bindParam(':arquivos', $arquivos_json);
$stmt->bindParam(':id', $postagem_id);

if ($stmt->execute()) {
    // Reenviar webhook
    $webhookSuccess = sendWebhook($postagem_id, $postData, $dateTimeUTC);
    
    // Atualizar status do webhook
    if ($webhookSuccess) {
        $updateWebhook = "UPDATE postagens SET webhook_status = 1 WHERE id = :id";
        $stmtWebhook = $conn->prepare($updateWebhook);
        $stmtWebhook->bindParam(':id', $postagem_id);
        $stmtWebhook->execute();
    }
    
    setFlashMessage('success', 'Postagem atualizada com sucesso!');
} else {
    setFlashMessage('danger', 'Erro ao atualizar postagem.');
}
```

### 4.4.3 Sistema de Upload de Arquivos

O sistema de upload de arquivos é um componente crítico do módulo de agendamento de postagens:

**Funções Principais:**
- `getUploadPath($cliente_id, $cliente_nome, $tipo_arquivo)`: Gera o caminho de upload para um cliente específico
- `generateUniqueFilename($cliente_nome, $extensao)`: Gera um nome de arquivo único

**Estrutura de Diretórios:**
```
/arquivos/
├── [cliente_slug]/
│   ├── imagem/
│   │   └── [arquivos de imagem]
│   └── video/
│       └── [arquivos de vídeo]
```

**Validação de Arquivos:**
- Verificação de tipo MIME
- Verificação de tamanho
- Limitação de quantidade (para carrosséis)

**Código Relevante:**
```php
// Em config.php
function getUploadPath($cliente_id, $cliente_nome, $tipo_arquivo = 'imagem') {
    $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente_nome));
    if (empty($cliente_slug)) {
        $cliente_slug = 'cliente' . $cliente_id;
    }
    
    $tipo = (strtolower($tipo_arquivo) === 'video') ? 'video' : 'imagem';
    
    // Criar diretório base se não existir
    if (!file_exists(UPLOAD_BASE_DIR)) {
        mkdir(UPLOAD_BASE_DIR, 0755, true);
    }
    
    // Definir diretório específico para o cliente
    $upload_dir = UPLOAD_BASE_DIR . $cliente_slug . '/' . $tipo . '/';
    
    // Criar diretório do cliente se não existir
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $upload_url = rtrim(APP_URL, '/') . '/arquivos/' . $cliente_slug . '/' . $tipo . '/';
    
    return [
        'dir' => $upload_dir,
        'url' => $upload_url
    ];
}

function generateUniqueFilename($cliente_nome, $extensao) {
    $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente_nome));
    $timestamp = date('mdYHis') . substr(microtime(), 2, 3);
    return $cliente_slug . '_' . $timestamp . '.' . $extensao;
}
```

### 4.4.4 Sistema de Webhooks

O sistema de webhooks permite a integração com plataformas externas:

**Processo:**
1. Preparação dos dados em formato JSON
2. Envio via cURL para a URL configurada
3. Verificação da resposta
4. Atualização do status no banco de dados

**Dados Enviados:**
- Informações do cliente
- Detalhes da postagem
- URLs dos arquivos
- Data e hora em UTC

**Código Relevante:**
```php
function sendWebhook($postId, $postData, $dateTimeUTC) {
    // Obter URL do webhook das configurações
    $webhookUrl = WEBHOOK_URL;
    
    // Preparar dados
    $webhookData = [
        'post_id' => $postId,
        'client_id' => $postData['cliente_id'],
        'client_name' => $postData['cliente_nome'],
        'post_type' => $postData['tipo_postagem'],
        'format' => $postData['formato'],
        'scheduled_date' => $dateTimeUTC,
        'caption' => $postData['legenda'],
        'files' => array_map(function($file) {
            return $file['url'];
        }, $postData['arquivos']),
        'scheduled_date_brazil' => date('d/m/Y', strtotime($postData['data_postagem'])),
        'scheduled_time_brazil' => $postData['hora_postagem']
    ];
    
    // Configurar cURL
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . WEBHOOK_API_KEY
    ]);
    
    // Enviar requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verificar resposta
    $success = ($httpCode >= 200 && $httpCode < 300);
    
    // Registrar tentativa
    registrarLog(
        $success ? 'Webhook enviado' : 'Erro ao enviar webhook',
        json_encode([
            'post_id' => $postId,
            'response_code' => $httpCode,
            'response' => $response
        ]),
        'webhook'
    );
    
    return $success;
}
```

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
