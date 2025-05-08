<?php
// Iniciar output buffering
ob_start();

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    redirect('index.php');
}

// Setup database tables if not exists
$database = new Database();
$database->setupTables();

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        setFlashMessage('danger', 'Por favor, preencha todos os campos.');
    } else {
        $user = verifyUser($username, $password);
        
        if ($user) {
            // Registrar tentativa bem-sucedida
            registrarTentativaLogin($username, true);
            
            // Login bem-sucedido
            createUserSession($user);
            
            // Enviar notificação via webhook
            enviarWebhook('login', [
                'acao' => 'Login',
                'detalhes' => 'Usuário realizou login no sistema'
            ]);
            
            // Redirecionar para a página principal
            redirect('dashboard.php');
        } else {
            // Registrar tentativa malsucedida
            registrarTentativaLogin($username, false);
            
            setFlashMessage('danger', 'Usuário ou senha inválidos.');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Login</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(45deg, #0A1C30, #6CBD45);
        }
        .login-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            height: 180px;
            margin-bottom: 15px;
        }
        .form-control {
            padding: 12px;
        }
        .btn-primary {
            background-color: #6CBD45;
            border-color: #6CBD45;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #5aa539;
            border-color: #5aa539;
        }
        .alert-danger {
            background-color: #ED4956;
            color: white;
            border: none;
        }
        .alert-warning {
            background-color: #FFA500;
            color: white;
            border: none;
        }
        .text-muted {
            color: #0A1C30 !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/img/logo.png" alt="<?= APP_NAME ?>">
        </div>
        
        <?php $flash = getFlashMessage(); ?>
        <?php if($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Usuário</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Digite seu usuário" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Digite sua senha" required>
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
            </div>
        </form>
        
        <div class="text-center mt-4">
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
