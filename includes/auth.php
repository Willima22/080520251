<?php
/**
 * Authentication and authorization functions
 */

// Verify user credentials
function verifyUser($username, $password) {
    $database = new Database();
    $conn = $database->connect();
    
    $query = "SELECT id, nome, email, tipo_usuario, senha FROM usuarios WHERE usuario = :usuario LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['senha'])) {
            return $user;
        }
    }
    
    return false;
}

// Create user session
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['tipo_usuario'];
}

// Check if user has access to a specific module
function checkPermission($requiredLevel = 'Editor') {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    // Admin has access to everything
    if ($_SESSION['user_type'] === 'Administrador') {
        return true;
    }
    
    // Editor has access only to Editor level features
    if ($_SESSION['user_type'] === 'Editor' && $requiredLevel === 'Editor') {
        return true;
    }
    
    return false;
}

// Redirect unauthorized users
function requirePermission($requiredLevel = 'Editor') {
    if (!checkPermission($requiredLevel)) {
        setFlashMessage('danger', 'Você não tem permissão para acessar esta página.');
        redirect('dashboard.php');
        exit;
    }
}
