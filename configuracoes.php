<?php
/**
 * Configurações
 * Configurações gerais do sistema
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/log_functions.php';

// Verificar se o usuário tem permissão de administrador
if (!isAdmin()) {
    setFlashMessage('danger', 'Você não tem permissão para acessar esta página.');
    redirect('dashboard.php');
}

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Verificar se a tabela de configurações existe
$tabela_existe = false;
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'configuracoes'");
    $tabela_existe = $check_table->rowCount() > 0;
} catch (PDOException $e) {
    setFlashMessage('danger', 'Erro ao verificar tabela de configurações: ' . $e->getMessage());
}

// Se a tabela não existir, exibir mensagem e criar link para o script de criação
if (!$tabela_existe) {
    include_once 'includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="alert alert-warning">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Tabela de Configurações não encontrada!</h4>
            <p>A tabela de configurações não foi encontrada no banco de dados. É necessário executar o script de criação da tabela.</p>
            <hr>
            <p class="mb-0">
                <a href="admin/create_configuracoes_table.php" class="btn btn-primary">Criar Tabela de Configurações</a>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Voltar para o Dashboard</a>
            </p>
        </div>
    </div>
    <?php
    include_once 'includes/footer.php';
    exit;
}

// Função para obter configuração do banco de dados
function getConfiguracao($chave, $padrao = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = :chave");
        $stmt->bindParam(':chave', $chave);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['valor'] : $padrao;
    } catch (PDOException $e) {
        error_log("Erro ao obter configuração {$chave}: " . $e->getMessage());
        return $padrao;
    }
}

// Função para atualizar configuração no banco de dados
function atualizarConfiguracao($chave, $valor) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE configuracoes SET valor = :valor, usuario_id = :usuario_id WHERE chave = :chave");
        $usuario_id = $_SESSION['user_id'] ?? null;
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':chave', $chave);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar configuração {$chave}: " . $e->getMessage());
        return false;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Atualizar tempo limite de sessão
    if ($acao === 'sessao') {
        $tempo_limite = (int) $_POST['tempo_limite'] ?? 5;
        $permitir_multiplos_logins = isset($_POST['permitir_multiplos_logins']) ? 1 : 0;
        
        if ($tempo_limite >= 1 && $tempo_limite <= 60) {
            $success = true;
            
            // Atualizar configurações no banco de dados
            if (!atualizarConfiguracao('tempo_limite_sessao', $tempo_limite)) {
                $success = false;
            }
            
            if (!atualizarConfiguracao('permitir_multiplos_logins', $permitir_multiplos_logins)) {
                $success = false;
            }
            
            if ($success) {
                setFlashMessage('success', 'Configurações de sessão atualizadas com sucesso!');
                
                // Registrar no histórico
                registrarLogSessao(
                    'Atualizou configurações de sessão', 
                    "Alterou tempo limite para {$tempo_limite} minutos e múltiplos logins: " . ($permitir_multiplos_logins ? 'Sim' : 'Não'),
                    'configuracoes.php'
                );
            } else {
                setFlashMessage('danger', 'Erro ao atualizar configurações de sessão.');
            }
        } else {
            setFlashMessage('danger', 'Tempo limite inválido. Deve ser entre 1 e 60 minutos.');
        }
        
        redirect('configuracoes.php');
    }
    
    // Atualizar informações gerais
    if ($acao === 'geral') {
        $nome_sistema = sanitize($_POST['nome_sistema'] ?? 'AW7 Postagens');
        $nome_empresa = sanitize($_POST['nome_empresa'] ?? 'AW7 Comunicação e Marketing');
        
        $success = true;
        
        // Atualizar configurações no banco de dados
        if (!atualizarConfiguracao('nome_sistema', $nome_sistema)) {
            $success = false;
        }
        
        if (!atualizarConfiguracao('nome_empresa', $nome_empresa)) {
            $success = false;
        }
        
        // Upload de logo (em uma implementação real, isso seria processado)
        $logo_file = $_FILES['logo'] ?? null;
        if ($logo_file && $logo_file['error'] === UPLOAD_ERR_OK) {
            // Verificar tipo de arquivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $logo_file['type'];
            
            if (in_array($file_type, $allowed_types)) {
                // Criar diretório de uploads se não existir
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Gerar nome de arquivo único
                $logo_filename = 'logo_' . time() . '_' . basename($logo_file['name']);
                $logo_path = $upload_dir . $logo_filename;
                
                // Mover arquivo para o diretório de uploads
                if (move_uploaded_file($logo_file['tmp_name'], $logo_path)) {
                    // Atualizar caminho do logo no banco de dados
                    if (!atualizarConfiguracao('logo_path', $logo_path)) {
                        $success = false;
                    }
                } else {
                    setFlashMessage('warning', 'Erro ao fazer upload do logo.');
                    $success = false;
                }
            } else {
                setFlashMessage('warning', 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.');
                $success = false;
            }
        }
        
        if ($success) {
            setFlashMessage('success', 'Informações gerais atualizadas com sucesso!');
            
            // Registrar no histórico
            registrarLogSessao(
                'Atualizou informações gerais', 
                "Alterou nome do sistema para '{$nome_sistema}' e nome da empresa para '{$nome_empresa}'",
                'configuracoes.php'
            );
        } else {
            setFlashMessage('danger', 'Erro ao atualizar informações gerais.');
        }
        
        redirect('configuracoes.php');
    }
    
    // Atualizar configurações de usuários
    if ($acao === 'usuarios') {
        $tipo_padrao = sanitize($_POST['tipo_padrao'] ?? 'editor');
        $alterar_senha_primeiro_login = isset($_POST['alterar_senha_primeiro_login']) ? 1 : 0;
        $autenticacao_dois_fatores = isset($_POST['autenticacao_dois_fatores']) ? 1 : 0;
        
        $success = true;
        
        // Atualizar configurações no banco de dados
        if (!atualizarConfiguracao('tipo_padrao', $tipo_padrao)) {
            $success = false;
        }
        
        if (!atualizarConfiguracao('alterar_senha_primeiro_login', $alterar_senha_primeiro_login)) {
            $success = false;
        }
        
        if (!atualizarConfiguracao('autenticacao_dois_fatores', $autenticacao_dois_fatores)) {
            $success = false;
        }
        
        if ($success) {
            setFlashMessage('success', 'Configurações de usuários atualizadas com sucesso!');
            
            // Registrar no histórico
            registrarLogSessao(
                'Atualizou configurações de usuários', 
                "Alterou tipo padrão para '{$tipo_padrao}', alteração de senha no primeiro login: " . 
                ($alterar_senha_primeiro_login ? 'Sim' : 'Não') . ", autenticação de dois fatores: " . 
                ($autenticacao_dois_fatores ? 'Sim' : 'Não'),
                'configuracoes.php'
            );
        } else {
            setFlashMessage('danger', 'Erro ao atualizar configurações de usuários.');
        }
        
        redirect('configuracoes.php');
    }
    
    // Atualizar configurações de backup
    if ($acao === 'backup') {
        $periodicidade_backup = sanitize($_POST['periodicidade_backup'] ?? 'semanal');
        
        if (atualizarConfiguracao('periodicidade_backup', $periodicidade_backup)) {
            setFlashMessage('success', 'Configurações de backup atualizadas com sucesso!');
            
            // Registrar no histórico
            registrarLogSessao(
                'Atualizou configurações de backup', 
                "Alterou periodicidade para '{$periodicidade_backup}'",
                'configuracoes.php'
            );
        } else {
            setFlashMessage('danger', 'Erro ao atualizar configurações de backup.');
        }
        
        redirect('configuracoes.php');
    }
}

// Obter as configurações atuais do banco de dados
$tempo_limite_sessao = (int) getConfiguracao('tempo_limite_sessao', 5);
$permitir_multiplos_logins = (bool) getConfiguracao('permitir_multiplos_logins', false);
$nome_sistema = getConfiguracao('nome_sistema', 'AW7 Postagens');
$nome_empresa = getConfiguracao('nome_empresa', 'AW7 Comunicação e Marketing');
$logo_path = getConfiguracao('logo_path', 'assets/img/logo.png');
$tipo_padrao = getConfiguracao('tipo_padrao', 'editor');
$alterar_senha_primeiro_login = (bool) getConfiguracao('alterar_senha_primeiro_login', true);
$autenticacao_dois_fatores = (bool) getConfiguracao('autenticacao_dois_fatores', false);
$periodicidade_backup = getConfiguracao('periodicidade_backup', 'semanal');

// Incluir o cabeçalho
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Configurações do Sistema</h1>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Configuração de Tempo de Sessão -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Tempo Limite de Sessão</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="acao" value="sessao">
                    
                    <div class="mb-3">
                        <label for="tempo_limite" class="form-label">Tempo limite (minutos)</label>
                        <input type="number" class="form-control" id="tempo_limite" name="tempo_limite" value="<?= $tempo_limite_sessao ?>" min="1" max="60" required>
                        <div class="form-text">
                            Tempo de inatividade até que a sessão expire automaticamente.
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="permitir_multiplos_logins" name="permitir_multiplos_logins" <?= $permitir_multiplos_logins ? 'checked' : '' ?>>
                        <label class="form-check-label" for="permitir_multiplos_logins">Permitir múltiplos logins simultâneos</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configuração
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Informações Gerais -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações Gerais</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="geral">
                    
                    <div class="mb-3">
                        <label for="nome_sistema" class="form-label">Nome do Sistema</label>
                        <input type="text" class="form-control" id="nome_sistema" name="nome_sistema" value="<?= htmlspecialchars($nome_sistema) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nome_empresa" class="form-label">Nome da Empresa</label>
                        <input type="text" class="form-control" id="nome_empresa" name="nome_empresa" value="<?= htmlspecialchars($nome_empresa) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo do Sistema</label>
                        <input type="file" class="form-control" id="logo" name="logo">
                        <?php if (!empty($logo_path) && file_exists($logo_path)): ?>
                            <div class="mt-2">
                                <img src="<?= $logo_path ?>" alt="Logo atual" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configuração
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Configurações de Usuários -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações de Usuários</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="acao" value="usuarios">
                    
                    <div class="mb-3">
                        <label for="tipo_padrao" class="form-label">Tipo Padrão de Usuário</label>
                        <select class="form-select" id="tipo_padrao" name="tipo_padrao">
                            <option value="editor" <?= $tipo_padrao === 'editor' ? 'selected' : '' ?>>Editor</option>
                            <option value="administrador" <?= $tipo_padrao === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="alterar_senha_primeiro_login" name="alterar_senha_primeiro_login" <?= $alterar_senha_primeiro_login ? 'checked' : '' ?>>
                        <label class="form-check-label" for="alterar_senha_primeiro_login">Alterar senha no primeiro login</label>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="autenticacao_dois_fatores" name="autenticacao_dois_fatores" <?= $autenticacao_dois_fatores ? 'checked' : '' ?>>
                        <label class="form-check-label" for="autenticacao_dois_fatores">Autenticação de dois fatores</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configuração
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Configurações de Backup -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações de Backup</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="acao" value="backup">
                    
                    <div class="mb-3">
                        <label for="periodicidade_backup" class="form-label">Periodicidade de Backup</label>
                        <select class="form-select" id="periodicidade_backup" name="periodicidade_backup">
                            <option value="diario" <?= $periodicidade_backup === 'diario' ? 'selected' : '' ?>>Diário</option>
                            <option value="semanal" <?= $periodicidade_backup === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="mensal" <?= $periodicidade_backup === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Configuração
                        </button>
                        
                        <a href="admin/backup_database.php" class="btn btn-primary" style="background-color: #0d6efd; border-color: #0d6efd;">
                            <i class="fas fa-download"></i> Gerar Backup Agora
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Informações do Sistema -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações do Sistema</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nome do Sistema:</strong> <?= htmlspecialchars(APP_NAME) ?></p>
                        <p><strong>Versão:</strong> <?= htmlspecialchars(APP_VERSION) ?></p>
                        <p><strong>Ambiente:</strong> <?= htmlspecialchars(APP_ENV) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data de Instalação:</strong> <?= date('d/m/Y', strtotime(APP_INSTALLED_DATE)) ?></p>
                        <p><strong>Última Atualização:</strong> <?= date('d/m/Y', strtotime(APP_LAST_UPDATE)) ?></p>
                        <p><strong>URL do Sistema:</strong> <?= htmlspecialchars(APP_URL) ?></p>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i> Para configurar webhooks, acesse a página <a href="webhooks.php" class="alert-link">Webhooks</a>.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>