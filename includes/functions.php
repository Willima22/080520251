<?php
/**
 * Funções gerais do sistema
 */

// Incluir funções de log
require_once __DIR__ . '/log_functions.php';

/**
 * Verifica se o usuário atual é um administrador
 * 
 * @return bool Retorna true se o usuário for administrador, false caso contrário
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

/**
 * Verifica se o usuário está logado
 * 
 * @return bool Retorna true se o usuário estiver logado, false caso contrário
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

/**
 * Redireciona para uma URL específica
 * 
 * @param string $url URL para redirecionamento
 * @return void
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Define uma mensagem flash para ser exibida na próxima requisição
 * 
 * @param string $type Tipo da mensagem (success, danger, warning, info)
 * @param string $message Conteúdo da mensagem
 * @return void
 */
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

/**
 * Obtém e limpa a mensagem flash atual
 * 
 * @return array|null Retorna a mensagem flash ou null se não houver mensagem
 */
if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

/**
 * Sanitiza uma string para evitar injeção de SQL e XSS
 * 
 * @param string $str String a ser sanitizada
 * @return string String sanitizada
 */
if (!function_exists('sanitize')) {
    function sanitize($str) {
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Formata uma data no padrão brasileiro
 * 
 * @param string $date Data no formato Y-m-d
 * @param bool $withTime Se deve incluir o horário
 * @return string Data formatada
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $withTime = false) {
        if (empty($date)) return '';
        
        $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
        return date($format, strtotime($date));
    }
}
?>
