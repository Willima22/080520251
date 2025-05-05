<?php
/**
 * Visualizar Postagem
 * Detalhes de uma postagem agendada
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'ID da postagem não fornecido.');
    redirect('postagens_agendadas.php');
}

$postagem_id = $_GET['id'];

// Obter detalhes da postagem
$sql = "SELECT p.*, c.nome as cliente_nome, u.nome as usuario_nome 
        FROM postagens p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$postagem_id]);
$postagem = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se a postagem existe
if (!$postagem) {
    setFlashMessage('danger', 'Postagem não encontrada.');
    redirect('postagens_agendadas.php');
}

// Decodificar arquivos JSON
$arquivos = json_decode($postagem['arquivos'], true) ?: [];

// Processar envio manual para webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'webhook') {
    // Preparar payload
    $payload = [
        'post_id' => $postagem['id'],
        'client_id' => $postagem['cliente_id'],
        'client_name' => $postagem['cliente_nome'],
        'title' => $postagem['titulo'],
        'description' => $postagem['descricao'],
        'scheduled_date' => $postagem['data_agendamento'],
        'scheduled_time' => $postagem['hora_agendamento'],
        'media_type' => $postagem['tipo_midia'],
        'media_urls' => $arquivos
    ];
    
    // Inicializar cURL
    $ch = curl_init(WEBHOOK_URL);
    
    // Configurar cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Executar cURL
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    // Fechar cURL
    curl_close($ch);
    
    // Verificar resultado
    if ($http_code >= 200 && $http_code < 300 && !$error) {
        // Atualizar status do webhook
        $sql = "UPDATE postagens SET webhook_enviado = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$postagem_id]);
        
        setFlashMessage('success', 'Webhook enviado com sucesso!');
    } else {
        setFlashMessage('danger', "Erro ao enviar webhook: {$error} (HTTP {$http_code})");
    }
    
    redirect("visualizar_postagem.php?id={$postagem_id}");
}

// Obter status de badge
function getStatusBadge($status) {
    switch ($status) {
        case 'Agendado':
            return '<span class="badge bg-warning"><i class="fas fa-clock"></i> Agendado</span>';
        case 'Publicado':
            return '<span class="badge bg-success"><i class="fas fa-check"></i> Publicado</span>';
        case 'Cancelado':
            return '<span class="badge bg-danger"><i class="fas fa-ban"></i> Cancelado</span>';
        case 'Falha':
            return '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Falha</span>';
        default:
            return '<span class="badge bg-secondary">' . $status . '</span>';
    }
}

// Obter tipo de mídia badge
function getTipoMidiaBadge($tipo) {
    switch ($tipo) {
        case 'imagem':
            return '<span class="badge bg-primary"><i class="fas fa-image"></i> Imagem</span>';
        case 'video':
            return '<span class="badge bg-danger"><i class="fas fa-video"></i> Vídeo</span>';
        case 'carrossel':
            return '<span class="badge bg-success"><i class="fas fa-images"></i> Carrossel</span>';
        default:
            return '<span class="badge bg-secondary">' . $tipo . '</span>';
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Visualizar Postagem #<?= $postagem['id'] ?></h1>
    <div>
        <a href="postagens_agendadas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <?php if ($postagem['status'] === 'Agendado'): ?>
        <a href="index.php?editar=<?= $postagem['id'] ?>" class="btn btn-primary ms-2">
            <i class="fas fa-edit"></i> Editar
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Detalhes da Postagem -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detalhes da Postagem</h5>
                <div>
                    <?= getStatusBadge($postagem['status']) ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Cliente</h6>
                        <p class="mb-3"><?= htmlspecialchars($postagem['cliente_nome']) ?></p>
                        
                        <h6>Título</h6>
                        <p class="mb-3"><?= htmlspecialchars($postagem['titulo']) ?></p>
                        
                        <h6>Tipo de Mídia</h6>
                        <p class="mb-3"><?= getTipoMidiaBadge($postagem['tipo_midia']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Data de Agendamento</h6>
                        <p class="mb-3"><?= date('d/m/Y', strtotime($postagem['data_agendamento'])) ?></p>
                        
                        <h6>Hora de Agendamento</h6>
                        <p class="mb-3"><?= $postagem['hora_agendamento'] ?></p>
                        
                        <h6>Criado em</h6>
                        <p class="mb-3"><?= date('d/m/Y H:i', strtotime($postagem['criado_em'])) ?></p>
                    </div>
                </div>
                
                <h6>Descrição</h6>
                <div class="p-3 bg-light rounded mb-4">
                    <?= nl2br(htmlspecialchars($postagem['descricao'])) ?>
                </div>
                
                <h6>Mídias</h6>
                <div class="row mb-3">
                    <?php if (count($arquivos) > 0): ?>
                        <?php foreach ($arquivos as $arquivo): ?>
                        <div class="col-md-3 mb-3">
                            <div class="media-preview">
                                <?php if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'mp4'): ?>
                                <video controls class="img-fluid rounded">
                                    <source src="<?= $arquivo ?>" type="video/mp4">
                                    Seu navegador não suporta vídeos HTML5.
                                </video>
                                <?php else: ?>
                                <img src="<?= $arquivo ?>" class="img-fluid rounded" alt="Mídia da postagem">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                Nenhuma mídia anexada.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Informações Adicionais -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações Adicionais</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Postado por</h6>
                    <p><?= htmlspecialchars($postagem['usuario_nome']) ?></p>
                </div>
                
                <div class="mb-3">
                    <h6>Status do Webhook</h6>
                    <?php if ($postagem['webhook_enviado']): ?>
                        <p><span class="badge bg-success"><i class="fas fa-check"></i> Enviado</span></p>
                    <?php else: ?>
                        <p>
                            <span class="badge bg-secondary mb-2"><i class="fas fa-times"></i> Pendente</span>
                            <form action="visualizar_postagem.php?id=<?= $postagem['id'] ?>" method="post">
                                <input type="hidden" name="action" value="webhook">
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="fas fa-paper-plane"></i> Enviar Agora
                                </button>
                            </form>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <h6>Última Modificação</h6>
                    <p><?= isset($postagem['atualizado_em']) ? date('d/m/Y H:i', strtotime($postagem['atualizado_em'])) : 'Não modificado' ?></p>
                </div>
                
                <?php if ($postagem['status'] === 'Agendado'): ?>
                <div class="mt-4">
                    <form action="postagens_agendadas.php" method="post" onsubmit="return confirm('Tem certeza que deseja cancelar esta postagem?');">
                        <input type="hidden" name="action" value="cancelar">
                        <input type="hidden" name="postagem_id" value="<?= $postagem['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-block w-100">
                            <i class="fas fa-ban"></i> Cancelar Postagem
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Log de Atividades -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Log de Atividades</h5>
            </div>
            <div class="card-body">
                <ul class="timeline">
                    <li class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">Postagem criada</h6>
                            <p class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime($postagem['criado_em'])) ?></p>
                        </div>
                    </li>
                    <?php if ($postagem['webhook_enviado']): ?>
                    <li class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">Webhook enviado</h6>
                            <p class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime($postagem['atualizado_em'] ?? $postagem['criado_em'])) ?></p>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if ($postagem['status'] === 'Publicado'): ?>
                    <li class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">Publicado no Instagram</h6>
                            <p class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime($postagem['data_postagem'])) ?></p>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if ($postagem['status'] === 'Cancelado'): ?>
                    <li class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">Postagem cancelada</h6>
                            <p class="text-sm text-muted"><?= date('d/m/Y H:i', strtotime($postagem['atualizado_em'] ?? $postagem['criado_em'])) ?></p>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline style */
.timeline {
    position: relative;
    padding-left: 1.5rem;
    list-style: none;
}

.timeline .timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline .timeline-item:last-child {
    padding-bottom: 0;
}

.timeline .timeline-marker {
    position: absolute;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    border: 2px solid #6CBD45;
    background: white;
    margin-top: 0.25rem;
    left: -1.5rem;
}

.timeline .timeline-item:not(:last-child):after {
    content: '';
    position: absolute;
    left: -1.1rem;
    bottom: 0;
    height: calc(100% - 2rem);
    width: 2px;
    background: #e5e5e5;
}

.media-preview {
    height: 150px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.media-preview img, .media-preview video {
    max-height: 100%;
    max-width: 100%;
    object-fit: cover;
}
</style>

<?php require_once 'includes/footer.php'; ?>