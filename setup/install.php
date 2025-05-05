<?php
/**
 * Installation script for Instagram Post Scheduler
 * 
 * This script will:
 * 1. Check system requirements
 * 2. Create database tables
 * 3. Create initial admin user
 * 4. Set up necessary directories
 */

// Disable direct access if already installed
if (file_exists(__DIR__ . '/../config/installed.php')) {
    die('Application is already installed. Remove the config/installed.php file to reinstall.');
}

// Initialize variables
$errors = [];
$success = false;
$dbConfig = [
    'host' => 'localhost',
    'name' => '',
    'user' => '',
    'pass' => ''
];

// Process installation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database configuration from form
    $dbConfig['host'] = isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost';
    $dbConfig['name'] = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';
    $dbConfig['user'] = isset($_POST['db_user']) ? trim($_POST['db_user']) : '';
    $dbConfig['pass'] = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
    
    // Admin user details
    $adminName = isset($_POST['admin_name']) ? trim($_POST['admin_name']) : '';
    $adminEmail = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
    $adminUser = isset($_POST['admin_user']) ? trim($_POST['admin_user']) : '';
    $adminPass = isset($_POST['admin_pass']) ? $_POST['admin_pass'] : '';
    $adminCpf = isset($_POST['admin_cpf']) ? trim($_POST['admin_cpf']) : '';
    
    // Validate inputs
    if (empty($dbConfig['name'])) {
        $errors[] = 'Database name is required.';
    }
    
    if (empty($dbConfig['user'])) {
        $errors[] = 'Database username is required.';
    }
    
    if (empty($adminName)) {
        $errors[] = 'Admin name is required.';
    }
    
    if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid admin email is required.';
    }
    
    if (empty($adminUser)) {
        $errors[] = 'Admin username is required.';
    }
    
    if (empty($adminPass) || strlen($adminPass) < 6) {
        $errors[] = 'Admin password must be at least 6 characters.';
    }
    
    if (empty($adminCpf)) {
        $errors[] = 'Admin CPF is required.';
    }
    
    // If no errors, proceed with installation
    if (empty($errors)) {
        try {
            // Test database connection
            $dsn = "mysql:host={$dbConfig['host']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbConfig['name']}`");
            
            // Create users table
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                cpf VARCHAR(14) NOT NULL UNIQUE,
                usuario VARCHAR(50) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                tipo_usuario ENUM('Editor', 'Administrador') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Create clients table
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS clientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                id_grupo VARCHAR(100) NOT NULL,
                instagram VARCHAR(100) NOT NULL,
                id_instagram VARCHAR(100) NOT NULL,
                conta_anuncio VARCHAR(100) NOT NULL,
                link_business VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Create posts table
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS postagens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                tipo_postagem ENUM('Feed', 'Stories', 'Feed e Stories') NOT NULL,
                formato ENUM('Imagem Única', 'Vídeo Único', 'Carrossel') NOT NULL,
                data_postagem DATETIME NOT NULL,
                data_postagem_utc VARCHAR(30) NOT NULL,
                legenda TEXT,
                arquivos TEXT NOT NULL,
                status ENUM('Agendado', 'Publicado', 'Falha') DEFAULT 'Agendado',
                webhook_response TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Create admin user
            $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, cpf, usuario, senha, tipo_usuario) 
            VALUES (:nome, :email, :cpf, :usuario, :senha, 'Administrador')");
            
            $stmt->bindParam(':nome', $adminName);
            $stmt->bindParam(':email', $adminEmail);
            $stmt->bindParam(':cpf', $adminCpf);
            $stmt->bindParam(':usuario', $adminUser);
            $stmt->bindParam(':senha', $hashedPassword);
            $stmt->execute();
            
            // Create upload directory
            $uploadDir = __DIR__ . '/../uploads';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Create db config file
            $dbConfigContent = "<?php
/**
 * Database configuration
 * Generated on " . date('Y-m-d H:i:s') . "
 */

class Database {
    private \$host = '{$dbConfig['host']}';
    private \$db_name = '{$dbConfig['name']}';
    private \$username = '{$dbConfig['user']}';
    private \$password = '{$dbConfig['pass']}';
    private \$conn;

    public function connect() {
        \$this->conn = null;

        try {
            \$this->conn = new PDO(
                \"mysql:host={\$this->host};dbname={\$this->db_name};charset=utf8mb4\",
                \$this->username,
                \$this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException \$e) {
            error_log(\"Database Connection Error: \" . \$e->getMessage());
            die(\"Connection failed: \" . \$e->getMessage());
        }

        return \$this->conn;
    }

    public function setupTables() {
        // Tables are already set up during installation
        return true;
    }
}
";
            
            // Write the config file
            file_put_contents(__DIR__ . '/../config/db_config.php', $dbConfigContent);
            
            // Create installed marker file
            file_put_contents(__DIR__ . '/../config/installed.php', "<?php\n// Installation completed on " . date('Y-m-d H:i:s') . "\ndefine('APP_INSTALLED', true);\n");
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = 'Database Error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Check system requirements
$requirements = [
    'php_version' => [
        'name' => 'PHP Version',
        'required' => '>= 7.4.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Extension',
        'required' => 'Enabled',
        'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('pdo_mysql')
    ],
    'json' => [
        'name' => 'JSON Extension',
        'required' => 'Enabled',
        'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
        'status' => extension_loaded('json')
    ],
    'file_uploads' => [
        'name' => 'File Uploads',
        'required' => 'Enabled',
        'current' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
        'status' => ini_get('file_uploads')
    ],
    'max_upload_size' => [
        'name' => 'Max Upload Size',
        'required' => '>= 100MB',
        'current' => ini_get('upload_max_filesize'),
        'status' => (int)ini_get('upload_max_filesize') >= 100
    ],
    'post_max_size' => [
        'name' => 'Post Max Size',
        'required' => '>= 100MB',
        'current' => ini_get('post_max_size'),
        'status' => (int)ini_get('post_max_size') >= 100
    ]
];

$allRequirementsMet = true;
foreach ($requirements as $req) {
    if (!$req['status']) {
        $allRequirementsMet = false;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Post Scheduler - Installation</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .installer-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(45deg, #F77737, #E1306C, #833AB4, #405DE6);
            padding: 20px;
            color: white;
            text-align: center;
        }
        .installer-body {
            padding: 30px;
        }
        .requirement-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .requirement-status {
            font-weight: bold;
        }
        .status-success {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="installer-container">
            <div class="installer-header">
                <h1>Instagram Post Scheduler</h1>
                <p class="mb-0">Installation Wizard</p>
            </div>
            
            <div class="installer-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> Installation Successful!</h4>
                        <p>Your Instagram Post Scheduler has been successfully installed.</p>
                        <hr>
                        <p class="mb-0">
                            <a href="../login.php" class="btn btn-primary">Go to Login Page</a>
                        </p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">Installation Errors</h4>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h4>System Requirements</h4>
                        <div class="card">
                            <div class="card-body p-0">
                                <?php foreach ($requirements as $requirement): ?>
                                    <div class="requirement-item d-flex justify-content-between">
                                        <div>
                                            <strong><?= $requirement['name'] ?></strong>
                                            <br>
                                            <small class="text-muted">Required: <?= $requirement['required'] ?></small>
                                        </div>
                                        <div class="requirement-status">
                                            <div class="<?= $requirement['status'] ? 'status-success' : 'status-error' ?>">
                                                <?= $requirement['current'] ?>
                                                <?= $requirement['status'] ? '<i class="fas fa-check-circle ms-1"></i>' : '<i class="fas fa-times-circle ms-1"></i>' ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($allRequirementsMet): ?>
                        <form method="POST" action="install.php">
                            <h4 class="mb-3">Database Configuration</h4>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="db_host" class="form-label">Database Host</label>
                                            <input type="text" class="form-control" id="db_host" name="db_host" value="<?= htmlspecialchars($dbConfig['host']) ?>" required>
                                            <div class="form-text">Usually 'localhost' or '127.0.0.1'</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="db_name" class="form-label">Database Name</label>
                                            <input type="text" class="form-control" id="db_name" name="db_name" value="<?= htmlspecialchars($dbConfig['name']) ?>" required>
                                            <div class="form-text">The database must exist or the user must have permission to create it</div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="db_user" class="form-label">Database Username</label>
                                            <input type="text" class="form-control" id="db_user" name="db_user" value="<?= htmlspecialchars($dbConfig['user']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="db_pass" class="form-label">Database Password</label>
                                            <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?= htmlspecialchars($dbConfig['pass']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h4 class="mb-3">Admin Account</h4>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="admin_name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="admin_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="admin_user" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="admin_user" name="admin_user" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="admin_cpf" class="form-label">CPF</label>
                                            <input type="text" class="form-control" id="admin_cpf" name="admin_cpf" required>
                                            <div class="form-text">Format: 000.000.000-00</div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="admin_pass" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                                            <div class="form-text">At least 6 characters</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cog me-2"></i> Install System
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> System Requirements Not Met</h4>
                            <p>Please fix the above requirements before proceeding with installation.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted">
            <p>&copy; 2023 Instagram Post Scheduler. All rights reserved.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // CPF formatting
        const cpfInput = document.getElementById('admin_cpf');
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
</body>
</html>
