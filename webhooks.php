<?php
// Iniciar output buffering
ob_start();

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Verifica se o usuário tem permissão de administrador
requirePermission('Administrador');

// Get database connection
$database = new Database();
$conn = $database->connect();

// Processar formulário de adição/edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Adicionar ou editar webhook
    if ($action === 'save') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : null;
        $nome = $_POST['nome'] ?? '';
        $url = $_POST['url'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Validar campos
        if (empty($nome) || empty($url) || empty($tipo)) {
            setFlashMessage('danger', 'Todos os campos são obrigatórios.');
        } else {
            try {
                // Verificar se é uma URL válida
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    setFlashMessage('danger', 'A URL informada não é válida.');
                } else {
                    if ($id) {
                        // Atualizar webhook existente
                        $query = "UPDATE webhooks SET nome = :nome, url = :url, tipo = :tipo, ativo = :ativo WHERE id = :id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':id', $id);
                        $stmt->bindParam(':nome', $nome);
                        $stmt->bindParam(':url', $url);
                        $stmt->bindParam(':tipo', $tipo);
                        $stmt->bindParam(':ativo', $ativo);
                        $stmt->execute();
                        
                        setFlashMessage('success', 'Webhook atualizado com sucesso!');
                    } else {
                        // Adicionar novo webhook
                        $query = "INSERT INTO webhooks (nome, url, tipo, ativo, data_criacao) VALUES (:nome, :url, :tipo, :ativo, NOW())";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':nome', $nome);
                        $stmt->bindParam(':url', $url);
                        $stmt->bindParam(':tipo', $tipo);
                        $stmt->bindParam(':ativo', $ativo);
                        $stmt->execute();
                        
                        setFlashMessage('success', 'Webhook adicionado com sucesso!');
                    }
                    
                    // Registrar no log
                    registrarLog('Configuração de Webhook', json_encode([
                        'acao' => $id ? 'Atualização' : 'Criação',
                        'webhook_id' => $id ?: $conn->lastInsertId(),
                        'webhook_nome' => $nome
                    ]));
                }
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Erro ao salvar webhook: ' . $e->getMessage());
            }
        }
        
        redirect('webhooks.php');
    }
    
    // Excluir webhook
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        try {
            // Obter nome do webhook para o log
            $query = "SELECT nome FROM webhooks WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Excluir webhook
            $query = "DELETE FROM webhooks WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Registrar no log
            if ($webhook) {
                registrarLog('Exclusão de Webhook', json_encode([
                    'webhook_id' => $id,
                    'webhook_nome' => $webhook['nome']
                ]));
            }
            
            setFlashMessage('success', 'Webhook excluído com sucesso!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Erro ao excluir webhook: ' . $e->getMessage());
        }
        
        redirect('webhooks.php');
    }
    
    // Testar webhook
    if ($action === 'test' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        try {
            // Obter dados do webhook
            $query = "SELECT * FROM webhooks WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($webhook) {
                // Dados de teste
                $dados = [
                    'evento' => 'teste',
                    'mensagem' => 'Este é um teste de webhook',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'webhook_id' => $webhook['id'],
                    'webhook_nome' => $webhook['nome']
                ];
                
                // Inicializar cURL
                $ch = curl_init($webhook['url']);
                
                // Configurar opções
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'User-Agent: PostScheduler/1.0'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos
                
                // Executar requisição
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                
                // Fechar conexão
                curl_close($ch);
                
                // Atualizar status do webhook
                $status = ($httpCode >= 200 && $httpCode < 300) ? 'Sucesso' : "Erro: HTTP $httpCode";
                if (!empty($error)) {
                    $status .= " - $error";
                }
                
                $updateQuery = "UPDATE webhooks SET ultima_execucao = NOW(), status_ultima_execucao = :status WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':status', $status);
                $updateStmt->bindParam(':id', $id);
                $updateStmt->execute();
                
                // Registrar no log
                registrarLog('Teste de Webhook', json_encode([
                    'webhook_id' => $id,
                    'webhook_nome' => $webhook['nome'],
                    'status' => $status
                ]));
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    setFlashMessage('success', 'Teste enviado com sucesso! Resposta: ' . substr($response, 0, 100));
                } else {
                    setFlashMessage('warning', 'Teste enviado, mas o servidor respondeu com código ' . $httpCode . '. Erro: ' . $error);
                }
            } else {
                setFlashMessage('danger', 'Webhook não encontrado.');
            }
        } catch (Exception $e) {
            setFlashMessage('danger', 'Erro ao testar webhook: ' . $e->getMessage());
        }
        
        redirect('webhooks.php');
    }
}

// Obter webhook para edição
$webhookEdit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    
    $query = "SELECT * FROM webhooks WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $webhookEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Obter todos os webhooks
$query = "SELECT * FROM webhooks ORDER BY nome ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Carregar o cabeçalho
$currentPage = 'webhooks';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Webhooks</h1>
            <p class="text-secondary">Configure webhooks para receber notificações de eventos do sistema.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header">
                    <strong><?= $webhookEdit ? 'Editar Webhook' : 'Novo Webhook' ?></strong>
                </div>
                <div class="card-body">
                    <form action="webhooks.php" method="POST">
                        <input type="hidden" name="action" value="save">
                        <?php if ($webhookEdit): ?>
                        <input type="hidden" name="id" value="<?= $webhookEdit['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= $webhookEdit ? htmlspecialchars($webhookEdit['nome']) : '' ?>" required>
                            <div class="form-text">Um nome descritivo para identificar este webhook.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">URL *</label>
                            <input type="url" class="form-control" id="url" name="url" value="<?= $webhookEdit ? htmlspecialchars($webhookEdit['url']) : '' ?>" required>
                            <div class="form-text">URL completa para onde os dados serão enviados.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Evento *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <option value="login" <?= ($webhookEdit && $webhookEdit['tipo'] == 'login') ? 'selected' : '' ?>>Login</option>
                                <option value="logout" <?= ($webhookEdit && $webhookEdit['tipo'] == 'logout') ? 'selected' : '' ?>>Logout</option>
                                <option value="login_failed" <?= ($webhookEdit && $webhookEdit['tipo'] == 'login_failed') ? 'selected' : '' ?>>Tentativas de login malsucedidas</option>
                                <option value="todos" <?= ($webhookEdit && $webhookEdit['tipo'] == 'todos') ? 'selected' : '' ?>>Todos os eventos</option>
                            </select>
                            <div class="form-text">Tipo de evento que acionará este webhook.</div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" <?= (!$webhookEdit || $webhookEdit['ativo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                            <div class="form-text">Desmarque para desativar temporariamente este webhook.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> <?= $webhookEdit ? 'Atualizar' : 'Adicionar' ?>
                            </button>
                            <?php if ($webhookEdit): ?>
                            <a href="webhooks.php" class="btn btn-outline-secondary">
                                <i class="fas fa-plus me-2"></i> Novo Webhook
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Informações</strong>
                </div>
                <div class="card-body">
                    <p>Os webhooks permitem que o sistema envie notificações automáticas para outros sistemas quando determinados eventos ocorrem.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Configure webhooks para receber notificações sobre agendamentos, publicações, erros e outros eventos do sistema.
                    </div>
                    
                    <!-- Configuração do Webhook Principal -->
                    <div class="card mt-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <strong><i class="fas fa-link me-2"></i> Webhook Principal de Agendamento</strong>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="webhooks.php">
                                <input type="hidden" name="action" value="save">
                                <input type="hidden" name="tipo" value="agendamento">
                                
                                <div class="mb-3">
                                    <label for="url" class="form-label">URL do Webhook Principal</label>
                                    <input type="url" class="form-control" id="url" name="url" value="<?= htmlspecialchars(WEBHOOK_URL ?? '') ?>" required>
                                    <div class="form-text">
                                        Esta URL será chamada sempre que uma postagem for agendada.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" checked>
                                        <label class="form-check-label" for="ativo">Ativo</label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Salvar Webhook Principal
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="accordion mt-4" id="webhookPayloads">
                        <!-- Login Webhook Example -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingLogin">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLogin" aria-expanded="false" aria-controls="collapseLogin">
                                    <span class="badge bg-info me-2">Login</span> Exemplo de payload de login
                                </button>
                            </h2>
                            <div id="collapseLogin" class="accordion-collapse collapse" aria-labelledby="headingLogin" data-bs-parent="#webhookPayloads">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded"><code>{
  "evento": "login",
  "timestamp": "2025-05-06 13:30:45",
  "ip": "170.239.227.25",
  "usuario_id": 1,
  "usuario_nome": "Administrador Sistema",
  "usuario_tipo": "Administrador",
  "acao": "Login",
  "detalhes": "Usuário realizou login no sistema"
}</code></pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logout Webhook Example -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingLogout">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLogout" aria-expanded="false" aria-controls="collapseLogout">
                                    <span class="badge bg-warning me-2">Logout</span> Exemplo de payload de logout
                                </button>
                            </h2>
                            <div id="collapseLogout" class="accordion-collapse collapse" aria-labelledby="headingLogout" data-bs-parent="#webhookPayloads">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded"><code>{
  "evento": "logout",
  "timestamp": "2025-05-06 14:15:22",
  "ip": "170.239.227.25",
  "usuario_id": 1,
  "usuario_nome": "Administrador Sistema",
  "usuario_tipo": "Administrador",
  "acao": "Logout",
  "detalhes": "Usuário realizou logout do sistema"
}</code></pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Failed Login Webhook Example -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingLoginFailed">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLoginFailed" aria-expanded="false" aria-controls="collapseLoginFailed">
                                    <span class="badge bg-danger me-2">Tentativas de login malsucedidas</span> Exemplo de payload de alerta
                                </button>
                            </h2>
                            <div id="collapseLoginFailed" class="accordion-collapse collapse" aria-labelledby="headingLoginFailed" data-bs-parent="#webhookPayloads">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded"><code>{
  "evento": "login_failed",
  "timestamp": "2025-05-06 13:45:12",
  "ip": "170.239.227.25",
  "acao": "Tentativas de login malsucedidas",
  "detalhes": "Múltiplas tentativas de login malsucedidas para o usuário 'admin'",
  "username": "admin",
  "total_tentativas": 3,
  "tentativas": [
    {
      "ip": "170.239.227.25",
      "timestamp": "2025-05-06 13:45:12"
    },
    {
      "ip": "170.239.227.25",
      "timestamp": "2025-05-06 13:44:58"
    },
    {
      "ip": "170.239.227.25",
      "timestamp": "2025-05-06 13:44:32"
    }
  ]
}</code></pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Agendamento Webhook Example -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAgendamento">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAgendamento" aria-expanded="false" aria-controls="collapseAgendamento">
                                    <span class="badge bg-success me-2">Agendamento de Postagem</span> Exemplo de payload de agendamento
                                </button>
                            </h2>
                            <div id="collapseAgendamento" class="accordion-collapse collapse" aria-labelledby="headingAgendamento" data-bs-parent="#webhookPayloads">
                                <div class="accordion-body">
                                    <pre class="bg-light p-3 rounded"><code>{
  "post_id": 123,
  "client_id": 45,
  "client": {
    "id": 45,
    "name": "Nome do Cliente",
    "instagram": "cliente_instagram",
    "instagram_id": "17841407706889558",
    "business_link": "https://book.cc/6MAI",
    "ad_account": "2884319609180461"
  },
  "post_type": "feed",
  "format": "carrossel",
  "scheduled_date": "2025-05-06T18:00:00Z",
  "scheduled_date_brazil": "06/05/2025",
  "scheduled_time_brazil": "15:00",
  "caption": "Descrição da postagem...",
  "files": [
    "https://postar.agenciaraff.com.br/arquivos/cliente/imagem/cliente_05062025120205258.jpeg",
    "https://postar.agenciaraff.com.br/arquivos/cliente/imagem/cliente_05062025120205259.jpeg"
  ],
  "data_postagem": "06/05/2025",
  "hora_postagem": "15:00"
}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Webhooks Configurados</strong>
                            <span class="badge bg-primary"><?= count($webhooks) ?> webhooks</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($webhooks)): ?>
                        <div class="alert alert-info">
                            Nenhum webhook configurado. Adicione um novo webhook usando o formulário ao lado.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>URL</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($webhooks as $webhook): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($webhook['nome']) ?></td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($webhook['url']) ?>">
                                                <?= htmlspecialchars($webhook['url']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($webhook['tipo'] === 'login'): ?>
                                            <span class="badge bg-info">Login</span>
                                            <?php elseif ($webhook['tipo'] === 'logout'): ?>
                                            <span class="badge bg-warning">Logout</span>
                                            <?php elseif ($webhook['tipo'] === 'login_failed'): ?>
                                            <span class="badge bg-danger">Tentativas de login malsucedidas</span>
                                            <?php else: ?>
                                            <span class="badge bg-primary">Todos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($webhook['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="webhooks.php?edit=<?= $webhook['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="document.getElementById('test-webhook-id').value = <?= $webhook['id'] ?>; document.getElementById('test-webhook-form').submit();" 
                                                        title="Testar">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal" 
                                                        data-webhook-id="<?= $webhook['id'] ?>" 
                                                        data-webhook-name="<?= htmlspecialchars($webhook['nome']) ?>"
                                                        title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if (!empty($webhook['ultima_execucao']) && !empty($webhook['status_ultima_execucao'])): ?>
                                    <tr class="table-light">
                                        <td colspan="5" class="small">
                                            <strong>Última execução:</strong> <?= date('d/m/Y H:i:s', strtotime($webhook['ultima_execucao'])) ?> - 
                                            <strong>Status:</strong> 
                                            <?php if (strpos($webhook['status_ultima_execucao'], 'Sucesso') === 0): ?>
                                            <span class="text-success"><?= htmlspecialchars($webhook['status_ultima_execucao']) ?></span>
                                            <?php else: ?>
                                            <span class="text-danger"><?= htmlspecialchars($webhook['status_ultima_execucao']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o webhook <strong id="webhookName"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="webhooks.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="webhookId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Form para testar webhook -->
<form id="test-webhook-form" method="POST" action="webhooks.php" style="display: none;">
    <input type="hidden" name="action" value="test">
    <input type="hidden" name="id" id="test-webhook-id">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set webhook data in delete modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const webhookId = button.getAttribute('data-webhook-id');
            const webhookName = button.getAttribute('data-webhook-name');
            
            document.getElementById('webhookId').value = webhookId;
            document.getElementById('webhookName').textContent = webhookName;
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
