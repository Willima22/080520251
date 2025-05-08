<?php
/**
 * Visualizar Postagem
 * Detalhes de uma postagem agendada
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Conexão com banco
$database = new Database();
$conn = $database->connect();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('danger', 'ID da postagem não fornecido.');
    redirect('postagens_agendadas.php');
}

$postagem_id = $_GET['id'];

// Obter detalhes da postagem
$sql = "SELECT p.*, c.nome_cliente as cliente_nome, u.nome as usuario_nome 
        FROM postagens p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$postagem_id]);
$postagem = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se a postagem existe
if (!$postagem) {
    setFlashMessage('danger', 'Postagem não encontrada.');
    redirect('postagens_agendadas.php');
}

// Decodificar arquivos JSON
$arquivos = [];
if (isset($postagem['arquivos']) && !empty($postagem['arquivos'])) {
    $arquivos = json_decode($postagem['arquivos'], true) ?: [];
}

// Funções auxiliares
function getStatusBadge($status) {
    switch ($status) {
        case 'Agendado': return '<span class="badge bg-warning"><i class="fas fa-clock"></i> Agendado</span>';
        case 'Publicado': return '<span class="badge bg-success"><i class="fas fa-check"></i> Publicado</span>';
        case 'Cancelado': return '<span class="badge bg-danger"><i class="fas fa-ban"></i> Cancelado</span>';
        case 'Falha': return '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Falha</span>';
        default: return '<span class="badge bg-secondary">' . htmlspecialchars($status ?? '') . '</span>';
    }
}

function getTipoMidiaBadge($tipo) {
    switch ($tipo) {
        case 'imagem': return '<span class="badge bg-primary"><i class="fas fa-image"></i> Imagem</span>';
        case 'video': return '<span class="badge bg-danger"><i class="fas fa-video"></i> Vídeo</span>';
        case 'carrossel': return '<span class="badge bg-success"><i class="fas fa-images"></i> Carrossel</span>';
        default: return '<span class="badge bg-secondary">' . htmlspecialchars($tipo ?? '') . '</span>';
    }
}

?>

<!-- HTML da página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Visualizar Postagem #<?= htmlspecialchars($postagem['id'] ?? '') ?></h1>
    <div>
        <a href="postagens_agendadas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Detalhes da Postagem</h5>
                <div><?= getStatusBadge($postagem['status'] ?? '') ?></div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Cliente</h6>
                        <p><?= htmlspecialchars($postagem['cliente_nome'] ?? '') ?></p>
                        <h6>Título</h6>
                        <p><?= htmlspecialchars($postagem['titulo'] ?? '') ?></p>
                        <h6>Tipo de Mídia</h6>
                        <p><?= getTipoMidiaBadge($postagem['tipo_midia'] ?? '') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Data e Hora</h6>
                        <p>
                            <?php if (!empty($postagem['data_postagem'])): ?>
                                <i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y', strtotime($postagem['data_postagem'])) ?>
                                <br>
                                <i class="far fa-clock me-1"></i> <?= !empty($postagem['hora_postagem']) ? date('H:i', strtotime($postagem['hora_postagem'])) : 'Não definida' ?>
                            <?php else: ?>
                                Não definida
                            <?php endif; ?>
                        </p>
                        <h6>Criado por</h6>
                        <p><?= htmlspecialchars($postagem['usuario_nome'] ?? '') ?></p>
                    </div>
                </div>

                <h6>Descrição</h6>
                <div class="p-3 bg-light rounded mb-4">
                    <?= nl2br(htmlspecialchars($postagem['legenda'] ?? '')) ?>
                </div>

                <h6>Mídias</h6>
                <div class="row">
                    <?php if ($arquivos): ?>
                        <?php foreach ($arquivos as $arquivo): ?>
                        <div class="col-md-3 mb-3">
                            <div class="media-preview">
                                <?php if (is_string($arquivo) && (strpos($arquivo, '.mp4') !== false || strpos($arquivo, '.mov') !== false)): ?>
                                    <video controls class="img-fluid rounded"><source src="<?= htmlspecialchars($arquivo ?? '') ?>" type="video/mp4"></video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($arquivo ?? '') ?>" class="img-fluid rounded" alt="Mídia">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Nenhuma mídia anexada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5>Informações</h5></div>
            <div class="card-body">
                <h6>Status Webhook</h6>
                <p><?= isset($postagem['webhook_enviado']) && $postagem['webhook_enviado'] ? '<span class="badge bg-success">Enviado</span>' : '<span class="badge bg-secondary">Não Enviado</span>' ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.media-preview {
    height: 150px;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    justify-content: center;
    align-items: center;
}
.media-preview img, .media-preview video {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}
</style>

<?php require_once 'includes/footer.php'; ?>
