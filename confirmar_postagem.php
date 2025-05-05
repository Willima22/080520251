<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// Redirect if no post data in session
if (!isset($_SESSION['post_data'])) {
    setFlashMessage('danger', 'Dados do agendamento não encontrados. Por favor, preencha o formulário novamente.');
    redirect('index.php');
}

$postData = $_SESSION['post_data'];

// Get database connection
$database = new Database();
$conn = $database->connect();

// Get client details
$query = "SELECT * FROM clientes WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $postData['cliente_id']);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    setFlashMessage('danger', 'Cliente não encontrado. Por favor, tente novamente.');
    redirect('index.php');
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Convert date and time to UTC ISO 8601
    $dateTime = convertToUTC($postData['data_postagem'], $postData['hora_postagem']);
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Insert into postagens table
        $query = "INSERT INTO postagens (cliente_id, tipo_postagem, formato, data_postagem, data_postagem_utc, legenda, arquivos, status) 
                  VALUES (:cliente_id, :tipo_postagem, :formato, :data_postagem, :data_postagem_utc, :legenda, :arquivos, 'Agendado')";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':cliente_id', $postData['cliente_id']);
        $stmt->bindParam(':tipo_postagem', $postData['tipo_postagem']);
        $stmt->bindParam(':formato', $postData['formato']);
        
        // Create datetime object for database
        $dbDateTime = $postData['data_postagem'] . ' ' . $postData['hora_postagem'];
        $stmt->bindParam(':data_postagem', $dbDateTime);
        $stmt->bindParam(':data_postagem_utc', $dateTime);
        $stmt->bindParam(':legenda', $postData['legenda']);
        
        // Encode array of files to JSON
        $arquivosJson = json_encode($postData['arquivos']);
        $stmt->bindParam(':arquivos', $arquivosJson);
        
        $stmt->execute();
        $postId = $conn->lastInsertId();
        
        // Prepare data for webhook
        $webhookData = [
            'post_id' => $postId,
            'client' => [
                'id' => $client['id'],
                'name' => $client['nome'],
                'instagram' => $client['instagram'],
                'instagram_id' => $client['id_instagram'],
                'business_link' => $client['link_business'],
                'ad_account' => $client['conta_anuncio']
            ],
            'post_type' => $postData['tipo_postagem'],
            'format' => $postData['formato'],
            'scheduled_date' => $dateTime,
            'caption' => $postData['legenda'],
            'files' => $postData['arquivos']
        ];
        
        // Send webhook request
        $webhookResponse = '';
        try {
            // Initialize cURL session
            $ch = curl_init(WEBHOOK_URL);
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            // Execute the cURL request
            $webhookResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Close cURL session
            curl_close($ch);
            
            // Check if webhook call was successful
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new Exception("Webhook call failed with HTTP code: $httpCode");
            }
        } catch (Exception $e) {
            error_log("Webhook Error: " . $e->getMessage());
            $webhookResponse = 'Error: ' . $e->getMessage();
        }
        
        // Update post with webhook response and set webhook_enviado flag
        $webhookEnviado = ($httpCode >= 200 && $httpCode < 300) ? 1 : 0;
        $query = "UPDATE postagens SET webhook_response = :response, webhook_enviado = :enviado WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':response', $webhookResponse);
        $stmt->bindParam(':enviado', $webhookEnviado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $postId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Clear session post data
        unset($_SESSION['post_data']);
        
        // Set success message and redirect
        setFlashMessage('success', 'Postagem agendada com sucesso!');
        redirect('dashboard.php');
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        error_log("Database Error: " . $e->getMessage());
        setFlashMessage('danger', 'Erro ao agendar postagem: ' . $e->getMessage());
    }
}

// Format date for display
$formattedDate = DateTime::createFromFormat('d/m/Y', $postData['data_postagem']);
if ($formattedDate) {
    $displayDate = $formattedDate->format('d/m/Y');
} else {
    $displayDate = $postData['data_postagem'];
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Confirmar Agendamento</h1>
            <p class="text-secondary">Revise os dados da postagem antes de confirmar o agendamento.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Detalhes da Postagem</strong>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Cliente:</div>
                        <div class="col-md-8"><?= htmlspecialchars($client['nome']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Instagram:</div>
                        <div class="col-md-8"><?= htmlspecialchars($client['instagram']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Tipo de Postagem:</div>
                        <div class="col-md-8"><?= htmlspecialchars($postData['tipo_postagem']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Formato:</div>
                        <div class="col-md-8"><?= htmlspecialchars($postData['formato']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Data e Hora:</div>
                        <div class="col-md-8">
                            <?= $displayDate ?> às <?= $postData['hora_postagem'] ?> (horário de Brasília)
                        </div>
                    </div>
                    
                    <?php if (!empty($postData['legenda'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Legenda:</div>
                        <div class="col-md-8">
                            <div style="white-space: pre-line;"><?= htmlspecialchars($postData['legenda']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Mídia</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($postData['arquivos'])): ?>
                    <div class="upload-preview">
                        <?php foreach ($postData['arquivos'] as $file): ?>
                        <div class="preview-item">
                            <?php 
                            $filePath = UPLOAD_DIR . $file;
                            $fileType = mime_content_type($filePath);
                            
                            if (strpos($fileType, 'image/') === 0): 
                            ?>
                                <img src="<?= 'uploads/' . $file ?>" alt="Preview">
                            <?php else: ?>
                                <div class="video-preview d-flex flex-column align-items-center justify-content-center h-100">
                                    <i class="fas fa-video fa-2x mb-2"></i>
                                    <span class="small">Vídeo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        Nenhum arquivo selecionado.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="confirmar_postagem.php">
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Voltar e Editar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calendar-check me-2"></i> Agendar Publicação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
