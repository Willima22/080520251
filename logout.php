<?php
require_once 'config/config.php';
require_once 'config/db.php';

// Registrar o logout no log do sistema
if (isset($_SESSION['user_id'])) {
    registrarLog('Logout', 'Usuário realizou logout do sistema');
    
    // Enviar notificação via webhook
    enviarWebhook('logout', [
        'acao' => 'Logout',
        'detalhes' => 'Usuário realizou logout do sistema',
        'usuario_id' => $_SESSION['user_id'],
        'usuario_nome' => $_SESSION['user_name'] ?? 'Usuário'
    ]);
}

// Destruir a sessão
session_start();
session_unset();
session_destroy();

// Redirecionar para a página de login
redirect('login.php');
exit;
