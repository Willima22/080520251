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

// Check if user has admin permission
requirePermission('Administrador');

// Get database connection
$database = new Database();
$conn = $database->connect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if we're updating or creating
    $isUpdate = isset($_POST['user_id']);
    
    // Validate required fields
    $requiredFields = ['nome', 'email', 'cpf', 'usuario', 'tipo_usuario'];
    
    // Add senha to required fields if creating a new user or explicitly changing password
    if (!$isUpdate || (isset($_POST['change_password']) && $_POST['change_password'] == '1')) {
        $requiredFields[] = 'senha';
    }
    
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
            // Check if user with same username or email or CPF already exists
            $query = "SELECT COUNT(*) as count FROM usuarios WHERE (usuario = :usuario OR email = :email OR cpf = :cpf)";
            
            if ($isUpdate) {
                $query .= " AND id != :id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':usuario', $_POST['usuario']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':cpf', $_POST['cpf']);
            
            if ($isUpdate) {
                $stmt->bindParam(':id', $_POST['user_id']);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                setFlashMessage('danger', 'Já existe um usuário com este nome de usuário, e-mail ou CPF.');
            } else {
                if ($isUpdate) {
                    // Update existing user
                    if (isset($_POST['change_password']) && $_POST['change_password'] == '1') {
                        // Update with new password
                        $query = "UPDATE usuarios 
                                  SET nome = :nome, email = :email, cpf = :cpf, usuario = :usuario, 
                                      senha = :senha, tipo_usuario = :tipo_usuario
                                  WHERE id = :id";
                    } else {
                        // Update without changing password
                        $query = "UPDATE usuarios 
                                  SET nome = :nome, email = :email, cpf = :cpf, usuario = :usuario, 
                                      tipo_usuario = :tipo_usuario
                                  WHERE id = :id";
                    }
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':id', $_POST['user_id']);
                } else {
                    // Insert new user
                    $query = "INSERT INTO usuarios (nome, email, cpf, usuario, senha, tipo_usuario) 
                              VALUES (:nome, :email, :cpf, :usuario, :senha, :tipo_usuario)";
                    
                    $stmt = $conn->prepare($query);
                }
                
                // Bind parameters
                $stmt->bindParam(':nome', $_POST['nome']);
                $stmt->bindParam(':email', $_POST['email']);
                $stmt->bindParam(':cpf', $_POST['cpf']);
                $stmt->bindParam(':usuario', $_POST['usuario']);
                $stmt->bindParam(':tipo_usuario', $_POST['tipo_usuario']);
                
                // Hash password if creating or changing password
                if (!$isUpdate || (isset($_POST['change_password']) && $_POST['change_password'] == '1')) {
                    $hashedPassword = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                    $stmt->bindParam(':senha', $hashedPassword);
                }
                
                $stmt->execute();
                
                if ($isUpdate) {
                    setFlashMessage('success', 'Usuário atualizado com sucesso!');
                } else {
                    setFlashMessage('success', 'Usuário cadastrado com sucesso!');
                }
                
                // Clear form data
                $userData = null;
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            setFlashMessage('danger', 'Erro ao processar solicitação: ' . $e->getMessage());
        }
    }
}

// Handle delete request
if (isset($_POST['delete']) && isset($_POST['user_id'])) {
    try {
        // Don't allow deleting yourself
        if ($_POST['user_id'] == $_SESSION['user_id']) {
            setFlashMessage('danger', 'Você não pode excluir seu próprio usuário.');
        } else {
            $query = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $_POST['user_id']);
            $stmt->execute();
            
            setFlashMessage('success', 'Usuário excluído com sucesso!');
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        setFlashMessage('danger', 'Erro ao excluir usuário: ' . $e->getMessage());
    }
}

// Check if we're editing a user
$userData = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $query = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Get all users
$query = "SELECT * FROM usuarios ORDER BY usuario ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Usuários</h1>
            <p class="text-secondary">Gerencie os usuários do sistema.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header">
                    <strong><?= $userData ? 'Editar Usuário' : 'Novo Usuário' ?></strong>
                </div>
                <div class="card-body">
                    <form action="usuarios.php" method="POST">
                        <?php if ($userData): ?>
                        <input type="hidden" name="user_id" value="<?= $userData['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= $userData ? htmlspecialchars($userData['nome']) : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $userData ? htmlspecialchars($userData['email']) : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cpf" class="form-label">CPF *</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?= $userData ? htmlspecialchars($userData['cpf']) : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuário *</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" value="<?= $userData ? htmlspecialchars($userData['usuario']) : '' ?>" required>
                        </div>
                        
                        <?php if ($userData): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="change_password" name="change_password" value="1">
                            <label class="form-check-label" for="change_password">Alterar senha</label>
                        </div>
                        
                        <div id="password-fields" style="display: none;">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha <?= $userData ? '' : '*' ?></label>
                            <input type="password" class="form-control" id="senha" name="senha" <?= $userData ? '' : 'required' ?>>
                        </div>
                        
                        <?php if ($userData): ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário *</label>
                            <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                <option value="">Selecione o tipo</option>
                                <option value="Editor" <?= ($userData && $userData['tipo_usuario'] == 'Editor') ? 'selected' : '' ?>>Editor</option>
                                <option value="Administrador" <?= ($userData && $userData['tipo_usuario'] == 'Administrador') ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> <?= $userData ? 'Atualizar' : 'Cadastrar' ?>
                            </button>
                            <?php if ($userData): ?>
                            <a href="usuarios.php" class="btn btn-outline-secondary">
                                <i class="fas fa-plus me-2"></i> Novo Usuário
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Lista de Usuários</strong>
                        <span class="badge bg-primary"><?= count($users) ?> usuários</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Usuário</th>
                                    <th>E-mail</th>
                                    <th>Tipo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['nome']) ?></td>
                                    <td><?= htmlspecialchars($user['usuario']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $user['tipo_usuario'] === 'Administrador' ? 'bg-danger' : 'bg-info' ?>">
                                            <?= htmlspecialchars($user['tipo_usuario']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="usuarios.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal" 
                                                    data-user-id="<?= $user['id'] ?>" 
                                                    data-user-name="<?= htmlspecialchars($user['nome']) ?>"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                <p>Tem certeza que deseja excluir o usuário <strong id="userName"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="usuarios.php">
                    <input type="hidden" name="user_id" id="userId">
                    <button type="submit" name="delete" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password fields when changing password
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordFields = document.getElementById('password-fields');
    
    if (changePasswordCheckbox && passwordFields) {
        changePasswordCheckbox.addEventListener('change', function() {
            passwordFields.style.display = this.checked ? 'block' : 'none';
            
            // Toggle required attribute
            const senhaInput = document.getElementById('senha');
            if (senhaInput) {
                senhaInput.required = this.checked;
            }
        });
    }
    
    // Set user data in delete modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            document.getElementById('userId').value = userId;
            document.getElementById('userName').textContent = userName;
        });
    }
    
    // CPF formatting
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
