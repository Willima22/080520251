<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// Check if user has permission
requirePermission('Editor');

// Get database connection
$database = new Database();
$conn = $database->connect();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $requiredFields = ['nome', 'id_grupo', 'instagram', 'id_instagram', 'conta_anuncio', 'link_business'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        setFlashMessage('danger', 'Por favor, preencha todos os campos obrigatórios.');
    } else {
        try {
            // Check if client with same Instagram already exists
            $query = "SELECT COUNT(*) as count FROM clientes WHERE instagram = :instagram";
            if (isset($_POST['client_id'])) {
                $query .= " AND id != :id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':instagram', $_POST['instagram']);
            
            if (isset($_POST['client_id'])) {
                $stmt->bindParam(':id', $_POST['client_id']);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                setFlashMessage('danger', 'Já existe um cliente com este nome de Instagram.');
            } else {
                if (isset($_POST['client_id'])) {
                    // Update existing client
                    $query = "UPDATE clientes 
                              SET nome = :nome, id_grupo = :id_grupo, instagram = :instagram, 
                                  id_instagram = :id_instagram, conta_anuncio = :conta_anuncio, 
                                  link_business = :link_business
                              WHERE id = :id";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':id', $_POST['client_id']);
                } else {
                    // Insert new client
                    $query = "INSERT INTO clientes (nome, id_grupo, instagram, id_instagram, conta_anuncio, link_business) 
                              VALUES (:nome, :id_grupo, :instagram, :id_instagram, :conta_anuncio, :link_business)";
                    
                    $stmt = $conn->prepare($query);
                }
                
                // Bind parameters
                $stmt->bindParam(':nome', $_POST['nome']);
                $stmt->bindParam(':id_grupo', $_POST['id_grupo']);
                $stmt->bindParam(':instagram', $_POST['instagram']);
                $stmt->bindParam(':id_instagram', $_POST['id_instagram']);
                $stmt->bindParam(':conta_anuncio', $_POST['conta_anuncio']);
                $stmt->bindParam(':link_business', $_POST['link_business']);
                
                $stmt->execute();
                
                if (isset($_POST['client_id'])) {
                    setFlashMessage('success', 'Cliente atualizado com sucesso!');
                } else {
                    setFlashMessage('success', 'Cliente cadastrado com sucesso!');
                }
                
                redirect('clientes_visualizar.php');
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            setFlashMessage('danger', 'Erro ao processar solicitação: ' . $e->getMessage());
        }
    }
}

// Check if we're editing a client
$clientData = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $query = "SELECT * FROM clientes WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $clientData = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        setFlashMessage('danger', 'Cliente não encontrado.');
        redirect('clientes_visualizar.php');
    }
}

$pageTitle = $clientData ? 'Editar Cliente' : 'Cadastro de Cliente';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3"><?= $pageTitle ?></h1>
            <p class="text-secondary">
                <?= $clientData ? 'Atualize os dados do cliente.' : 'Preencha o formulário abaixo para cadastrar um novo cliente.' ?>
            </p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <strong><?= $clientData ? 'Formulário de Edição' : 'Formulário de Cadastro' ?></strong>
                </div>
                <div class="card-body">
                    <form action="clientes.php" method="POST">
                        <?php if ($clientData): ?>
                        <input type="hidden" name="client_id" value="<?= $clientData['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome do Cliente *</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= $clientData ? htmlspecialchars($clientData['nome']) : '' ?>" required>
                                <div class="form-text">Nome completo ou razão social do cliente.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="id_grupo" class="form-label">ID do Grupo *</label>
                                <input type="text" class="form-control" id="id_grupo" name="id_grupo" value="<?= $clientData ? htmlspecialchars($clientData['id_grupo']) : '' ?>" required>
                                <div class="form-text">Identificador do grupo do cliente.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="instagram" class="form-label">Instagram *</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" id="instagram" name="instagram" value="<?= $clientData ? htmlspecialchars($clientData['instagram']) : '' ?>" required>
                                </div>
                                <div class="form-text">Nome de usuário do Instagram sem o '@'.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="id_instagram" class="form-label">ID do Instagram *</label>
                                <input type="text" class="form-control" id="id_instagram" name="id_instagram" value="<?= $clientData ? htmlspecialchars($clientData['id_instagram']) : '' ?>" required>
                                <div class="form-text">ID numérico da conta do Instagram.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="conta_anuncio" class="form-label">Conta de Anúncio *</label>
                                <input type="text" class="form-control" id="conta_anuncio" name="conta_anuncio" value="<?= $clientData ? htmlspecialchars($clientData['conta_anuncio']) : '' ?>" required>
                                <div class="form-text">Identificador da conta de anúncios.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="link_business" class="form-label">Link do Business *</label>
                                <input type="url" class="form-control" id="link_business" name="link_business" value="<?= $clientData ? htmlspecialchars($clientData['link_business']) : '' ?>" required>
                                <div class="form-text">URL completa do perfil business do Instagram.</div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="clientes_visualizar.php" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> <?= $clientData ? 'Atualizar' : 'Cadastrar' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
