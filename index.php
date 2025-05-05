<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// Get database connection
$database = new Database();
$conn = $database->connect();

// Get all clients for dropdown
$query = "SELECT id, nome FROM clientes ORDER BY nome ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $requiredFields = ['cliente_id', 'tipo_postagem', 'formato', 'data_postagem', 'hora_postagem'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        setFlashMessage('danger', 'Por favor, preencha todos os campos obrigatórios.');
    } else {
        // Process file uploads
        $uploadedFiles = [];
        $uploadSuccess = true;
        $formato = $_POST['formato'];
        
        if ($formato === 'Imagem Única' || $formato === 'Vídeo Único') {
            // Single file upload
            if (!isset($_FILES['singleFile']) || $_FILES['singleFile']['error'] === UPLOAD_ERR_NO_FILE) {
                setFlashMessage('danger', 'Por favor, selecione um arquivo para upload.');
                $uploadSuccess = false;
            } else {
                $file = $_FILES['singleFile'];
                $fileName = time() . '_' . basename($file['name']);
                $targetPath = UPLOAD_DIR . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $uploadedFiles[] = $fileName;
                } else {
                    setFlashMessage('danger', 'Falha ao fazer upload do arquivo.');
                    $uploadSuccess = false;
                }
            }
        } else if ($formato === 'Carrossel') {
            // Multiple files upload
            if (!isset($_FILES['carouselFiles']) || empty($_FILES['carouselFiles']['name'][0])) {
                setFlashMessage('danger', 'Por favor, selecione pelo menos um arquivo para o carrossel.');
                $uploadSuccess = false;
            } else {
                $totalSize = 0;
                $fileCount = count($_FILES['carouselFiles']['name']);
                
                if ($fileCount > 20) {
                    setFlashMessage('danger', 'Um carrossel pode ter no máximo 20 imagens.');
                    $uploadSuccess = false;
                } else {
                    for ($i = 0; $i < $fileCount; $i++) {
                        $file = [
                            'name' => $_FILES['carouselFiles']['name'][$i],
                            'type' => $_FILES['carouselFiles']['type'][$i],
                            'tmp_name' => $_FILES['carouselFiles']['tmp_name'][$i],
                            'error' => $_FILES['carouselFiles']['error'][$i],
                            'size' => $_FILES['carouselFiles']['size'][$i]
                        ];
                        
                        $totalSize += $file['size'];
                        
                        if ($totalSize > MAX_FILE_SIZE) {
                            setFlashMessage('danger', 'O tamanho total dos arquivos excede o limite de 1GB.');
                            $uploadSuccess = false;
                            break;
                        }
                        
                        $fileName = time() . '_' . $i . '_' . basename($file['name']);
                        $targetPath = UPLOAD_DIR . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $uploadedFiles[] = $fileName;
                        } else {
                            setFlashMessage('danger', 'Falha ao fazer upload de um ou mais arquivos.');
                            $uploadSuccess = false;
                            break;
                        }
                    }
                }
            }
        }
        
        if ($uploadSuccess) {
            // Save post data to session for confirmation page
            $_SESSION['post_data'] = [
                'cliente_id' => $_POST['cliente_id'],
                'tipo_postagem' => $_POST['tipo_postagem'],
                'formato' => $formato,
                'data_postagem' => $_POST['data_postagem'],
                'hora_postagem' => $_POST['hora_postagem'],
                'legenda' => isset($_POST['legenda']) ? $_POST['legenda'] : '',
                'arquivos' => $uploadedFiles
            ];
            
            // Redirect to confirmation page
            redirect('confirmar_postagem.php');
        }
    }
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Agendar Postagem</h1>
            <p class="text-secondary">Preencha o formulário abaixo para agendar uma nova postagem no Instagram.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <strong>Formulário de Agendamento</strong>
                </div>
                <div class="card-body">
                    <form action="index.php" method="POST" enctype="multipart/form-data" id="postForm">
                        <!-- Cliente -->
                        <div class="mb-4">
                            <label for="cliente_id" class="form-label">Cliente *</label>
                            <select class="form-select" id="cliente_id" name="cliente_id" required>
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione o cliente para esta postagem.</div>
                        </div>
                        
                        <!-- Tipo de Postagem -->
                        <div class="mb-4">
                            <label class="form-label">Tipo de Postagem *</label>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="custom-radio-btn post-type-option" data-value="Feed">
                                    <i class="fas fa-th me-2"></i> Feed
                                </div>
                                <div class="custom-radio-btn post-type-option" data-value="Stories">
                                    <i class="fas fa-circle me-2"></i> Stories
                                </div>
                                <div class="custom-radio-btn post-type-option" data-value="Feed e Stories">
                                    <i class="fas fa-clone me-2"></i> Feed e Stories
                                </div>
                            </div>
                            <input type="hidden" name="tipo_postagem" id="tipo_postagem" required>
                        </div>
                        
                        <!-- Formato -->
                        <div class="mb-4">
                            <label class="form-label">Formato *</label>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="custom-radio-btn format-option" data-value="Imagem Única">
                                    <i class="fas fa-image me-2"></i> Imagem Única
                                </div>
                                <div class="custom-radio-btn format-option" data-value="Vídeo Único">
                                    <i class="fas fa-video me-2"></i> Vídeo Único
                                </div>
                                <div class="custom-radio-btn format-option" data-value="Carrossel">
                                    <i class="fas fa-images me-2"></i> Carrossel
                                </div>
                            </div>
                            <input type="hidden" name="formato" id="formato" required>
                        </div>
                        
                        <!-- Data e Hora -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="data_postagem" class="form-label">Data da Postagem *</label>
                                <input type="text" class="form-control datepicker" id="data_postagem" name="data_postagem" placeholder="DD/MM/AAAA" required>
                                <div class="form-text">Data em horário do Brasil (será convertida para UTC).</div>
                            </div>
                            <div class="col-md-6">
                                <label for="hora_postagem" class="form-label">Hora da Postagem *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control timepicker" id="hora_postagem" name="hora_postagem" value="06:00" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end time-presets">
                                            <li><a class="dropdown-item" href="#" data-value="06:00">06:00 (Manhã)</a></li>
                                            <li><a class="dropdown-item" href="#" data-value="12:00">12:00 (Meio-dia)</a></li>
                                            <li><a class="dropdown-item" href="#" data-value="15:00">15:00 (Tarde)</a></li>
                                            <li><a class="dropdown-item" href="#" data-value="18:00">18:00 (Final da Tarde)</a></li>
                                            <li><a class="dropdown-item" href="#" data-value="21:00">21:00 (Noite)</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-text">Hora em horário do Brasil (será convertida para UTC).</div>
                            </div>
                        </div>
                        
                        <!-- Upload de Mídia -->
                        <div class="mb-4">
                            <label class="form-label">Mídia *</label>
                            
                            <!-- Single Upload (Image or Video) -->
                            <div id="single-upload-container" class="mb-3">
                                <label for="singleFile" class="custom-file-upload">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>Clique ou arraste um arquivo</div>
                                    <small class="text-muted">Imagem ou vídeo único</small>
                                </label>
                                <input type="file" id="singleFile" name="singleFile" class="file-upload d-none" data-preview="singlePreview" accept="image/*, video/*">
                                <div id="singlePreview" class="upload-preview mt-3"></div>
                            </div>
                            
                            <!-- Carousel Upload (Multiple Images) -->
                            <div id="carousel-upload-container" class="mb-3 d-none">
                                <label for="carouselFiles" class="custom-file-upload">
                                    <i class="fas fa-images"></i>
                                    <div>Clique ou arraste até 20 imagens</div>
                                    <small class="text-muted">Máximo de 1GB no total</small>
                                </label>
                                <input type="file" id="carouselFiles" name="carouselFiles[]" class="file-upload d-none" data-preview="carouselPreview" accept="image/*" multiple>
                                <div id="carouselPreview" class="upload-preview mt-3"></div>
                            </div>
                        </div>
                        
                        <!-- Legenda -->
                        <div class="mb-4">
                            <label for="legenda" class="form-label">Legenda</label>
                            <textarea class="form-control" id="legenda" name="legenda" rows="4" maxlength="1000" placeholder="Digite a legenda da postagem..."></textarea>
                            <div class="d-flex justify-content-between">
                                <div class="form-text">Legenda que será exibida na postagem.</div>
                                <div class="form-text">Restam <span id="character-count">1000</span> caracteres</div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i> Prosseguir para Confirmação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
