<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Consulta para obter as postagens com informações do cliente
$query = "SELECT p.*, c.nome_cliente
          FROM postagens p
          JOIN clientes c ON p.cliente_id = c.id
          ORDER BY p.data_postagem DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$postagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar as datas para exibição correta
foreach ($postagens as &$postagem) {
    $data_br = $postagem['data_postagem'] ?? '';
    $data_utc = $postagem['data_postagem_utc'] ?? '';
    
    // Inicializar com valores padrão
    $postagem['data_formatada'] = 'Data não informada';
    $postagem['hora_formatada'] = '';
    
    if (!empty($data_br) && $data_br !== '0000-00-00 00:00:00') {
        $timestamp = strtotime($data_br);
        if ($timestamp !== false) {
            $postagem['data_formatada'] = date('d/m/Y', $timestamp);
            $postagem['hora_formatada'] = date('H:i', $timestamp);
        }
    } elseif (!empty($data_utc)) {
        $data_convertida = str_replace(['T', 'Z'], [' ', ''], $data_utc);
        $timestamp = strtotime($data_convertida);
        if ($timestamp !== false) {
            // Ajustar para horário de Brasília (UTC-3)
            $timestamp_brasilia = $timestamp - (3 * 3600);
            $postagem['data_formatada'] = date('d/m/Y', $timestamp_brasilia);
            $postagem['hora_formatada'] = date('H:i', $timestamp_brasilia);
        }
    }
}

// Função para obter a primeira imagem de uma postagem
function getFirstImage($arquivos) {
    if (empty($arquivos)) {
        return 'assets/img/placeholder.jpg';
    }
    
    // Tenta decodificar o JSON
    $files = json_decode($arquivos, true);
    
    // Se não for um JSON válido ou estiver vazio, retorna placeholder
    if (!$files || empty($files)) {
        return 'assets/img/placeholder.jpg';
    }
    
    // Retorna a URL da primeira imagem
    return isset($files[0]['url']) ? $files[0]['url'] : 'assets/img/placeholder.jpg';
}

// Incluir o cabeçalho
require_once 'includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">Listagem de Postagens</h1>
            <p class="text-secondary">Visualize todas as postagens agendadas e publicadas.</p>
        </div>
        <div class="col-md-4 text-end align-self-center">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nova Postagem
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($postagens)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma postagem encontrada.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($postagens as $postagem): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <img src="<?= htmlspecialchars(getFirstImage($postagem['arquivos'])) ?>" 
                             class="card-img-top" alt="Imagem da Postagem" 
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($postagem['nome_cliente']) ?></h5>
                            <p class="card-text text-muted small">
                                <i class="far fa-calendar-alt me-1"></i>
                                Data da postagem: <?= htmlspecialchars($postagem['data_formatada']) ?>
                            </p>
                            <p class="card-text">
                                <span class="badge bg-<?= $postagem['webhook_status'] ? 'success' : 'warning' ?>">
                                    <?= $postagem['webhook_status'] ? 'Publicado' : 'Agendado' ?>
                                </span>
                                <span class="badge bg-info ms-1">
                                    <?= htmlspecialchars($postagem['tipo_postagem']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="visualizar_postagem.php?id=<?= $postagem['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Visualizar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
