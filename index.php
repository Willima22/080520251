<?php
// Iniciar output buffering
ob_start();

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect('login.php');
}

// Define tempo de login se não estiver setado
if (isset($_SESSION['user_id']) && !isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
}

// Verifica inatividade (5 minutos)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
    session_unset();
    session_destroy();
    redirect('login.php?reason=inactivity');
}
$_SESSION['last_activity'] = time();

// Calcula o tempo logado
$loginTime = $_SESSION['login_time'] ?? time();
$timeLoggedIn = time() - $loginTime;
$hours = floor($timeLoggedIn / 3600);
$minutes = floor(($timeLoggedIn % 3600) / 60);
$seconds = $timeLoggedIn % 60;
$timeLoggedInString = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

// Agora carrega o HTML
require_once 'includes/header.php';

// Get database connection
$database = new Database();
$conn = $database->connect();

// Get all clients for dropdown
$query = "SELECT id, nome_cliente as nome FROM clientes ORDER BY nome_cliente ASC";
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
        
        // Obter informações do cliente para o upload
        $cliente_id = $_POST['cliente_id'];
        $query = "SELECT id, nome_cliente FROM clientes WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $cliente_id);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        $cliente_nome = $cliente['nome_cliente'] ?? 'cliente' . $cliente_id;
        
        if ($formato === 'Imagem Única' || $formato === 'Vídeo Único') {
            // Single file upload
            if (!isset($_FILES['singleFile']) || $_FILES['singleFile']['error'] === UPLOAD_ERR_NO_FILE) {
                setFlashMessage('danger', 'Por favor, selecione um arquivo para upload.');
                $uploadSuccess = false;
            } else {
                $file = $_FILES['singleFile'];
                
                // Determinar o tipo de arquivo (imagem ou vídeo)
                $tipo_arquivo = in_array($file['type'], ALLOWED_IMAGE_TYPES) ? 'imagem' : 'video';
                
                // Obter o caminho de upload para este cliente e tipo de arquivo
                $upload_path = getUploadPath($cliente_id, $cliente_nome, $tipo_arquivo);
                
                // Registrar informações para depuração
                error_log("Processando upload: Cliente ID: {$cliente_id}, Nome: {$cliente_nome}, Tipo: {$tipo_arquivo}");
                error_log("Caminho de upload: " . print_r($upload_path, true));
                
                // Obter a extensão do arquivo
                $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
                
                // Gerar nome de arquivo único no formato solicitado
                $fileName = generateUniqueFilename($cliente_nome, $extensao);
                
                // Caminho completo para o arquivo
                $targetPath = $upload_path['dir'] . $fileName;
                
                error_log("Tentando mover arquivo para: {$targetPath}");
                
                // Verificar se o diretório existe e tem permissões corretas
                if (!file_exists($upload_path['dir'])) {
                    error_log("Diretório não existe, tentando criar: {$upload_path['dir']}");
                    if (!@mkdir($upload_path['dir'], 0755, true)) {
                        error_log("Falha ao criar diretório: {$upload_path['dir']}");
                        
                        // Tentar um caminho alternativo
                        $alt_path = dirname(__FILE__) . '/arquivos/' . $upload_path['cliente_slug'] . '/' . $upload_path['tipo'] . '/';
                        error_log("Tentando caminho alternativo: {$alt_path}");
                        
                        if (!file_exists($alt_path)) {
                            @mkdir($alt_path, 0755, true);
                        }
                        
                        if (file_exists($alt_path)) {
                            $targetPath = $alt_path . $fileName;
                            error_log("Usando caminho alternativo: {$targetPath}");
                        } else {
                            setFlashMessage('danger', 'Falha ao criar diretório para upload.');
                            $uploadSuccess = false;
                        }
                    }
                }
                
                // Verificar se podemos escrever no diretório
                if ($uploadSuccess && !is_writable(dirname($targetPath))) {
                    error_log("Diretório sem permissão de escrita: " . dirname($targetPath));
                    @chmod(dirname($targetPath), 0755);
                }
                
                // Tentar fazer o upload do arquivo
                $uploadResult = false;
                if ($uploadSuccess) {
                    // Primeiro, tentar o método padrão
                    $uploadResult = move_uploaded_file($file['tmp_name'], $targetPath);
                    
                    // Se falhar, tentar método alternativo
                    if (!$uploadResult) {
                        error_log("Falha no move_uploaded_file, tentando copy");
                        $uploadResult = copy($file['tmp_name'], $targetPath);
                    }
                    
                    // Definir permissões do arquivo se o upload foi bem-sucedido
                    if ($uploadResult) {
                        @chmod($targetPath, 0644);
                        error_log("Upload bem-sucedido: {$targetPath}");
                        error_log("URL do arquivo: {$upload_path['url']}{$fileName}");
                    } else {
                        error_log("Falha ao mover/copiar arquivo: {$file['tmp_name']} para {$targetPath}");
                        error_log("Erro PHP: " . error_get_last()['message']);
                    }
                }
                
                if ($uploadResult) {
                    // Armazenar a URL completa do arquivo
                    $fileUrl = $upload_path['url'] . $fileName;
                    $uploadedFiles[] = [
                        'name' => $fileName,
                        'path' => $targetPath,
                        'url' => $fileUrl
                    ];
                } else {
                    error_log("Falha ao mover arquivo: {$file['tmp_name']} para {$targetPath}");
                    error_log("Erro de upload: " . print_r($file['error'], true));
                    setFlashMessage('danger', 'Falha ao fazer upload do arquivo. Verifique as permissões do diretório.');
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
                        
                        // Determinar o tipo de arquivo (imagem ou vídeo)
                        $tipo_arquivo = in_array($file['type'], ALLOWED_IMAGE_TYPES) ? 'imagem' : 'video';
                        
                        // Obter o caminho de upload para este cliente e tipo de arquivo
                        $upload_path = getUploadPath($cliente_id, $cliente_nome, $tipo_arquivo);
                        
                        // Registrar informações para depuração
                        error_log("Processando upload de carrossel #{$i}: Cliente ID: {$cliente_id}, Nome: {$cliente_nome}, Tipo: {$tipo_arquivo}");
                        
                        // Obter a extensão do arquivo
                        $extensao = pathinfo($file['name'], PATHINFO_EXTENSION);
                        
                        // Gerar nome de arquivo único no formato solicitado
                        $fileName = generateUniqueFilename($cliente_nome, $extensao);
                        
                        // Caminho completo para o arquivo
                        $targetPath = $upload_path['dir'] . $fileName;
                        
                        // Verificar se o diretório existe e tem permissões corretas
                        if (!file_exists($upload_path['dir'])) {
                            if (!@mkdir($upload_path['dir'], 0755, true)) {
                                // Tentar um caminho alternativo
                                $alt_path = dirname(__FILE__) . '/arquivos/' . $upload_path['cliente_slug'] . '/' . $upload_path['tipo'] . '/';
                                
                                if (!file_exists($alt_path)) {
                                    @mkdir($alt_path, 0755, true);
                                }
                                
                                if (file_exists($alt_path)) {
                                    $targetPath = $alt_path . $fileName;
                                } else {
                                    error_log("Falha ao criar diretório: {$upload_path['dir']}");
                                    setFlashMessage('danger', 'Falha ao criar diretório para upload.');
                                    $uploadSuccess = false;
                                    break;
                                }
                            }
                        }
                        
                        // Verificar se podemos escrever no diretório
                        if (!is_writable(dirname($targetPath))) {
                            @chmod(dirname($targetPath), 0755);
                        }
                        
                        // Tentar fazer o upload do arquivo
                        $uploadResult = false;
                        
                        // Primeiro, tentar o método padrão
                        $uploadResult = move_uploaded_file($file['tmp_name'], $targetPath);
                        
                        // Se falhar, tentar método alternativo
                        if (!$uploadResult) {
                            $uploadResult = copy($file['tmp_name'], $targetPath);
                        }
                        
                        if ($uploadResult) {
                            // Definir permissões do arquivo
                            @chmod($targetPath, 0644);
                            
                            // Armazenar a URL completa do arquivo
                            $fileUrl = $upload_path['url'] . $fileName;
                            $uploadedFiles[] = [
                                'name' => $fileName,
                                'path' => $targetPath,
                                'url' => $fileUrl
                            ];
                        } else {
                            error_log("Falha ao mover arquivo de carrossel: {$file['tmp_name']} para {$targetPath}");
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
                'cliente_nome' => $cliente_nome,
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

<div class="container my-4">
    <div class="mb-4">
        <h1 class="h3 mb-0">Agendar Postagem</h1>
        <p class="text-muted">Preencha o formulário abaixo para agendar uma nova postagem no Instagram.</p>
    </div>
    
    <div class="form-container">
        <div class="form-body">
                    <form action="index.php" method="POST" enctype="multipart/form-data" id="postForm">
                        <!-- Cliente -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-user-circle"></i>Informações Básicas</h3>
                            <div class="mb-4">
                                <label for="cliente_id" class="form-label">Cliente *</label>
                                <select class="form-select form-select-lg" id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['nome'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Selecione o cliente para esta postagem.</div>
                            </div>
                        
                            <!-- Tipo de Postagem -->
                            <div class="mb-4">
                                <label class="form-label">Tipo de Postagem *</label>
                                <div class="option-buttons">
                                    <div class="option-button post-type-option" data-value="Feed">
                                        <i class="fas fa-th"></i>
                                        <span>Feed</span>
                                    </div>
                                    <div class="option-button post-type-option" data-value="Stories">
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>Stories</span>
                                    </div>
                                    <div class="option-button post-type-option" data-value="Feed e Stories">
                                        <i class="fas fa-clone"></i>
                                        <span>Feed e Stories</span>
                                    </div>
                                </div>
                                <input type="hidden" name="tipo_postagem" id="tipo_postagem" required>
                                <div class="invalid-feedback">Selecione o tipo de postagem</div>
                            </div>
                            
                            <!-- Formato -->
                            <div class="mb-4">
                                <label class="form-label">Formato *</label>
                                <div class="option-buttons">
                                    <div class="option-button format-option" data-value="Imagem Única">
                                        <i class="fas fa-image"></i>
                                        <span>Imagem Única</span>
                                    </div>
                                    <div class="option-button format-option" data-value="Vídeo Único">
                                        <i class="fas fa-video"></i>
                                        <span>Vídeo Único</span>
                                    </div>
                                    <div class="option-button format-option" data-value="Carrossel">
                                        <i class="fas fa-images"></i>
                                        <span>Carrossel</span>
                                    </div>
                                </div>
                                <input type="hidden" name="formato" id="formato" required>
                                <div class="invalid-feedback">Selecione o formato da postagem</div>
                            </div>
                        </div>
                        
                        <!-- Data e Hora -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-clock"></i>Agendamento</h3>
                        
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="data_postagem" class="form-label">Data da Postagem *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control datepicker" id="data_postagem" name="data_postagem" placeholder="DD/MM/AAAA" required autocomplete="off">
                                        <span class="input-group-text calendar-trigger" data-input="data_postagem"><i class="fas fa-calendar-alt"></i></span>
                                    </div>
                                    <div class="form-text">Data em horário do Brasil</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="hora_postagem" class="form-label">Hora da Postagem *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control timepicker" id="hora_postagem" name="hora_postagem" value="06:00" placeholder="HH:MM" required autocomplete="off">
                                        <div class="input-group-text dropdown">
                                            <span class="clock-trigger me-2" data-input="hora_postagem"><i class="fas fa-clock"></i></span>
                                            <a class="dropdown-toggle text-decoration-none text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-caret-down"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end time-presets">
                                                <li><a class="dropdown-item" href="#" data-value="06:00">06:00 (Manhã)</a></li>
                                                <li><a class="dropdown-item" href="#" data-value="12:00">12:00 (Meio-dia)</a></li>
                                                <li><a class="dropdown-item" href="#" data-value="15:00">15:00 (Tarde)</a></li>
                                                <li><a class="dropdown-item" href="#" data-value="18:00">18:00 (Final da Tarde)</a></li>
                                                <li><a class="dropdown-item" href="#" data-value="21:00">21:00 (Noite)</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="form-text">Hora em horário do Brasil</div>
                                </div>
                            </div>
                            
                            <!-- Agendamento Recorrente -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="agendamento_recorrente" name="agendamento_recorrente" value="1">
                                    <label class="form-check-label" for="agendamento_recorrente">Agendamento Recorrente?</label>
                                </div>
                                
                                <div id="recorrencia_options" class="recorrencia-container mt-3 d-none animate-fade-in">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="frequencia" class="form-label">Frequência</label>
                                            <select class="form-select" id="frequencia" name="frequencia">
                                                <option value="diario">Diário</option>
                                                <option value="semanal" selected>Semanal</option>
                                                <option value="mensal">Mensal</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4" id="dia_semana_container">
                                            <label for="dia_semana" class="form-label">Dia da Semana</label>
                                            <select class="form-select" id="dia_semana" name="dia_semana">
                                                <option value="1">Segunda-feira</option>
                                                <option value="2">Terça-feira</option>
                                                <option value="3">Quarta-feira</option>
                                                <option value="4">Quinta-feira</option>
                                                <option value="5">Sexta-feira</option>
                                                <option value="6">Sábado</option>
                                                <option value="0">Domingo</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4 d-none" id="dia_mes_container">
                                            <label for="dia_mes" class="form-label">Dia do Mês</label>
                                            <select class="form-select" id="dia_mes" name="dia_mes">
                                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i> O sistema criará automaticamente novas postagens com base nesta configuração.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload de Mídia -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-photo-video"></i>Mídia</h3>
                        
                            <!-- Single Upload (Image or Video) -->
                            <div id="single-upload-container" class="mb-4">
                                <div class="upload-area p-4 text-center border border-2 border-dashed rounded">
                                    <div class="mb-3">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="mb-2">Clique ou arraste um arquivo</h5>
                                    <p class="text-muted mb-1">Formatos suportados: JPG, PNG, GIF, MP4, MOV</p>
                                    <p class="text-muted mb-3">Tamanho máximo: 1GB</p>
                                    <button type="button" class="btn btn-outline-primary select-file-btn" data-target="singleFile">
                                        <i class="fas fa-folder-open me-2"></i>Selecionar arquivo
                                    </button>
                                    <input type="file" id="singleFile" name="singleFile" class="file-upload d-none" data-preview="singlePreview" accept="image/jpeg,image/png,image/gif,video/mp4,video/mov,video/avi">
                                </div>
                                <div id="singlePreview" class="mt-3 upload-preview"></div>
                            </div>
                            
                            <!-- Carousel Upload (Multiple Images/Videos) -->
                            <div id="carousel-upload-container" class="mb-4 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Carrossel de Imagens/Vídeos</h5>
                                    <span class="badge bg-primary" id="carousel-counter">0/20</span>
                                </div>
                                
                                <div class="upload-area p-4 text-center border border-2 border-dashed rounded">
                                    <div class="mb-3">
                                        <i class="fas fa-images fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="mb-2">Clique ou arraste até 20 arquivos</h5>
                                    <p class="text-muted mb-1">Formatos suportados: JPG, PNG, GIF, MP4, MOV</p>
                                    <p class="text-muted mb-3">Tamanho máximo total: 1GB</p>
                                    <button type="button" class="btn btn-outline-primary select-file-btn" data-target="carouselFiles">
                                        <i class="fas fa-folder-open me-2"></i>Selecionar arquivos
                                    </button>
                                    <input type="file" id="carouselFiles" name="carouselFiles[]" class="file-upload d-none" data-preview="carouselPreview" accept="image/jpeg,image/png,image/gif,video/mp4,video/mov,video/avi" multiple>
                                </div>
                                
                                <div class="alert alert-info mt-3 d-flex align-items-center" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>Arraste as imagens para reordenar. A primeira imagem será a capa do carrossel.</div>
                                </div>
                                
                                <div id="carouselPreview" class="mt-3 upload-preview"></div>
                            </div>
                            <div class="invalid-feedback">Selecione pelo menos um arquivo para upload</div>
                        
                        <!-- Legenda (Opcional) -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-comment-alt"></i>Legenda</h3>
                            <div class="legenda-container mb-4">
                                <label for="legenda" class="form-label">Legenda da Postagem <small class="text-muted">(opcional)</small></label>
                                <textarea class="form-control" id="legenda" name="legenda" rows="5" placeholder="Digite a legenda da postagem..."></textarea>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="form-text text-muted"><i class="fas fa-info-circle me-1"></i> Hashtags, emojis e menções são permitidos.</small>
                                    <small class="character-counter"><span id="character-count">1000</span> caracteres restantes</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-actions">
                            <a href="postagens_cards.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-check me-2"></i> Prosseguir para Confirmação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
