<?php
/**
 * Configurações
 * Configurações gerais do sistema
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Verificar se o usuário tem permissão de administrador
if (!isAdmin()) {
    setFlashMessage('danger', 'Você não tem permissão para acessar esta página.');
    redirect('dashboard.php');
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Atualizar webhook
    if ($acao === 'webhook') {
        $novo_webhook = $_POST['webhook_url'] ?? '';
        
        if (filter_var($novo_webhook, FILTER_VALIDATE_URL)) {
            // Em produção, isso seria salvo no banco de dados ou em um arquivo de configuração
            // Por enquanto, apenas simulamos a atualização
            setFlashMessage('success', 'URL do Webhook atualizada com sucesso!');
        } else {
            setFlashMessage('danger', 'URL inválida. Por favor, forneça uma URL válida.');
        }
        
        redirect('configuracoes.php');
    }
    
    // Atualizar tempo limite de sessão
    if ($acao === 'sessao') {
        $tempo_limite = (int) $_POST['tempo_limite'] ?? 5;
        
        if ($tempo_limite >= 1 && $tempo_limite <= 60) {
            // Em produção, isso seria salvo no banco de dados ou em um arquivo de configuração
            setFlashMessage('success', 'Tempo limite de sessão atualizado com sucesso!');
        } else {
            setFlashMessage('danger', 'Tempo limite inválido. Deve ser entre 1 e 60 minutos.');
        }
        
        redirect('configuracoes.php');
    }
}

// Obter as configurações atuais (no caso real, viriam do banco de dados)
$webhook_url = WEBHOOK_URL;
$tempo_limite_sessao = 5; // Minutos

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Configurações do Sistema</h1>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Configuração do Webhook -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configuração do Webhook</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="acao" value="webhook">
                    
                    <div class="mb-3">
                        <label for="webhook_url" class="form-label">URL do Webhook</label>
                        <input type="url" class="form-control" id="webhook_url" name="webhook_url" value="<?= htmlspecialchars($webhook_url) ?>" required>
                        <div class="form-text">
                            Esta URL será chamada sempre que uma postagem for agendada.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Formato do payload:</label>
                        <pre class="bg-light p-3 rounded"><code>{
  "post_id": 123,
  "client_id": 45,
  "client_name": "Nome do Cliente",
  "title": "Título da Postagem",
  "description": "Descrição da postagem...",
  "scheduled_date": "2025-05-05",
  "scheduled_time": "18:00:00",
  "media_type": "imagem",
  "media_urls": ["url1", "url2"]
}</code></pre>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configuração do Webhook
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Configuração de Sessão -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configuração de Sessão</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="acao" value="sessao">
                    
                    <div class="mb-3">
                        <label for="tempo_limite" class="form-label">Tempo limite de inatividade (minutos)</label>
                        <input type="number" class="form-control" id="tempo_limite" name="tempo_limite" min="1" max="60" value="<?= $tempo_limite_sessao ?>" required>
                        <div class="form-text">
                            O usuário será desconectado automaticamente após este período de inatividade.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configuração de Sessão
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Configuração de Email -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configuração de E-mail</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="configuracoes.php">
                    <input type="hidden" name="acao" value="email">
                    
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">Servidor SMTP</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="smtp.exemplo.com.br">
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_port" class="form-label">Porta SMTP</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="587">
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_user" class="form-label">Usuário SMTP</label>
                        <input type="email" class="form-control" id="smtp_user" name="smtp_user" value="email@exemplo.com.br">
                    </div>
                    
                    <div class="mb-3">
                        <label for="smtp_password" class="form-label">Senha SMTP</label>
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="********">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configuração de E-mail
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>