<?php
/**
 * Configuration file
 * Contains application constants and settings
 */

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session configuration
session_start();

// Timezone settings
date_default_timezone_set('America/Sao_Paulo'); // Brazil timezone for display

// Application constants
define('APP_NAME', 'AW7 Postagens');
define('APP_VERSION', '1.0.0');
define('APP_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
define('WEBHOOK_URL', 'https://automacao2.aw7agencia.com.br/webhook/agendarpostagem');
if (!defined('FILES_BASE_URL')) {
    define('FILES_BASE_URL', 'https://postar.agenciaraff.com.br');
}
define('APP_ENV', 'Produção');
define('APP_INSTALLED_DATE', '2025-01-01');
define('APP_LAST_UPDATE', '2025-05-06');

// File upload settings
define('UPLOAD_BASE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/arquivos/');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('MAX_FILE_SIZE', 1073741824); // 1GB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/mov', 'video/avi']);

/**
 * Obtém o caminho de upload para um cliente específico
 * @param int $cliente_id
 * @param string $cliente_nome
 * @param string $tipo_arquivo
 * @return array
 */
function getUploadPath($cliente_id, $cliente_nome, $tipo_arquivo = 'imagem') {
    // Remover espaços e converter para minúsculo
    $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente_nome));
    if (empty($cliente_slug)) {
        $cliente_slug = 'cliente' . $cliente_id;
    }
    
    $tipo = (strtolower($tipo_arquivo) === 'video') ? 'video' : 'imagem';
    
    // Verificar se DOCUMENT_ROOT está definido
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        error_log("AVISO: DOCUMENT_ROOT não está definido. Usando caminho alternativo.");
        // Caminho alternativo baseado no diretório atual do script
        $base_path = dirname(dirname(__FILE__)) . '/arquivos/';
    } else {
        $base_path = $_SERVER['DOCUMENT_ROOT'] . '/arquivos/';
    }
    
    // Criar diretório base se não existir
    if (!file_exists($base_path)) {
        if (!@mkdir($base_path, 0755, true)) {
            error_log("ERRO: Não foi possível criar o diretório base: {$base_path}");
            // Tentar caminho alternativo
            $base_path = dirname(dirname(__FILE__)) . '/arquivos/';
            if (!file_exists($base_path)) {
                @mkdir($base_path, 0755, true);
            }
        }
    }
    
    // Criar diretório de uploads temporários se não existir
    if (!file_exists(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Definir diretório específico para o cliente
    $upload_dir = $base_path . $cliente_slug . '/' . $tipo . '/';
    
    // Criar diretório do cliente se não existir
    if (!file_exists($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) {
            error_log("ERRO: Não foi possível criar o diretório do cliente: {$upload_dir}");
            // Tentar criar cada nível separadamente
            if (!file_exists($base_path . $cliente_slug)) {
                @mkdir($base_path . $cliente_slug, 0755);
            }
            if (!file_exists($base_path . $cliente_slug . '/' . $tipo)) {
                @mkdir($base_path . $cliente_slug . '/' . $tipo, 0755);
            }
        }
    }
    
    // Verificar permissões do diretório
    if (file_exists($upload_dir) && !is_writable($upload_dir)) {
        error_log("AVISO: Diretório não tem permissão de escrita: {$upload_dir}");
        @chmod($upload_dir, 0755);
    }
    
    // Usar FILES_BASE_URL para a URL dos arquivos
    $upload_url = rtrim(FILES_BASE_URL, '/') . '/arquivos/' . $cliente_slug . '/' . $tipo . '/';
    
    // Registrar o caminho para debug
    error_log("Upload path: {$upload_dir} | URL: {$upload_url}");
    
    return [
        'dir' => $upload_dir,
        'url' => $upload_url,
        'base_path' => $base_path,
        'cliente_slug' => $cliente_slug,
        'tipo' => $tipo
    ];
}

/**
 * Gera um nome de arquivo único no formato solicitado
 * @param string $cliente_nome
 * @param string $extensao
 * @return string
 */
function generateUniqueFilename($cliente_nome, $extensao) {
    $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente_nome));
    $timestamp = date('mdYHis') . substr(microtime(), 2, 3);
    return $cliente_slug . '_' . $timestamp . '.' . $extensao;
}

/**
 * Converte uma data brasileira (DD/MM/YYYY HH:MM) para UTC (ISO 8601)
 * @param string $data Data no formato d/m/Y ou data e hora combinados
 * @param string $hora Hora no formato H:i (opcional)
 * @return string
 */
function convertToUTC($data, $hora = null) {
    // Verifica se a data já inclui a hora ou se a hora foi passada como parâmetro separado
    if ($hora !== null) {
        // Formato antigo: dois parâmetros separados
        $dataHoraBr = $data . ' ' . $hora;
    } else {
        // Novo formato: um único parâmetro
        $dataHoraBr = $data;
    }
    
    // Tenta primeiro com formato d/m/Y H:i:s (data com hora e segundos)
    $dataFormatada = DateTime::createFromFormat('d/m/Y H:i:s', $dataHoraBr);
    
    // Se falhar, tenta com formato d/m/Y H:i (data com hora, sem segundos)
    if (!$dataFormatada) {
        $dataFormatada = DateTime::createFromFormat('d/m/Y H:i', $dataHoraBr);
        
        // Se falhar, tenta com formato d/m/Y (apenas data, sem hora)
        if (!$dataFormatada) {
            $dataFormatada = DateTime::createFromFormat('d/m/Y', $dataHoraBr);
            
            // Se ainda falhar, tenta com formato Y-m-d H:i:s (formato ISO)
            if (!$dataFormatada) {
                $dataFormatada = DateTime::createFromFormat('Y-m-d H:i:s', $dataHoraBr);
                
                // Se ainda falhar, lança exceção
                if (!$dataFormatada) {
                    throw new Exception("Formato de data inválido: " . $dataHoraBr);
                }
            } else {
                // Define hora como 00:00 se apenas a data foi especificada
                $dataFormatada->setTime(0, 0, 0);
            }
        }
    }

    // Garante que o timezone está configurado corretamente
    $dataFormatada->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    $dataFormatada->setTimezone(new DateTimeZone('UTC'));

    return $dataFormatada->format('Y-m-d\TH:i:s\Z');
}

/**
 * Converte uma data UTC para horário do Brasil
 * @param string $utcDateTime
 * @return string
 */
function convertToBrazilTime($utcDateTime) {
    $datetime = new DateTime($utcDateTime, new DateTimeZone('UTC'));
    $datetime->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    return $datetime->format('d/m/Y H:i');
}

/**
 * Sanitiza um input
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona para uma URL
 * @param string $url
 */
function redirect($url) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
    }
    exit;
}

/**
 * Define uma flash message
 * @param string $type
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtém e remove a flash message da sessão
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Verifica se o usuário está logado
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário é administrador
 * @return bool
 */
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Administrador';
    }
}

/**
 * Registra uma ação no log do sistema
 * @param string $acao Tipo de ação realizada
 * @param string $detalhes Detalhes da ação em formato texto ou JSON
 * @return bool
 */
if (!function_exists('registrarLog')) {
    function registrarLog($acao, $detalhes = '') {
        global $conn;
        
        // Se não houver conexão com o banco, tenta criar uma
        if (!isset($conn) || !$conn) {
            $database = new Database();
            $conn = $database->connect();
        }
        
        // Obter ID do usuário da sessão
        $usuario_id = $_SESSION['user_id'] ?? null;
        $usuario_nome = $_SESSION['user_name'] ?? 'Sistema';
        
        // Obter IP do usuário
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Preparar query
        $query = "INSERT INTO historico (usuario_id, usuario_nome, acao, detalhes, ip, data_hora) 
                  VALUES (:usuario_id, :usuario_nome, :acao, :detalhes, :ip, NOW())";
        
        try {
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':usuario_nome', $usuario_nome);
            $stmt->bindParam(':acao', $acao);
            $stmt->bindParam(':detalhes', $detalhes);
            $stmt->bindParam(':ip', $ip);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Em caso de erro, apenas retorna falso (não interrompe o fluxo da aplicação)
            return false;
        }
    }
}

/**
 * Envia notificação via webhook
 * @param string $tipo Tipo de evento (login, logout, todos)
 * @param array $dados Dados a serem enviados no webhook
 * @return bool
 */
function enviarWebhook($tipo, $dados = []) {
    global $conn;
    
    // Se não houver conexão com o banco, tenta criar uma
    if (!isset($conn) || !$conn) {
        $database = new Database();
        $conn = $database->connect();
    }
    
    // Buscar webhooks ativos para este tipo de evento
    $query = "SELECT * FROM webhooks WHERE ativo = 1 AND (tipo = :tipo OR tipo = 'todos')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->execute();
    
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sucessos = 0;
    
    // Se não houver webhooks configurados, retorna true
    if (empty($webhooks)) {
        return true;
    }
    
    // Adicionar informações padrão aos dados
    $dados['evento'] = $tipo;
    $dados['timestamp'] = date('Y-m-d H:i:s');
    $dados['ip'] = $_SERVER['REMOTE_ADDR'];
    
    if (isset($_SESSION['user_id'])) {
        $dados['usuario_id'] = $_SESSION['user_id'];
        $dados['usuario_nome'] = $_SESSION['user_name'] ?? 'Usuário';
        $dados['usuario_tipo'] = $_SESSION['user_type'] ?? 'Não definido';
    }
    
    // Enviar para cada webhook configurado
    foreach ($webhooks as $webhook) {
        try {
            // Inicializar cURL
            $ch = curl_init($webhook['url']);
            
            // Configurar opções
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: PostScheduler/1.0'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos
            
            // Executar requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            // Fechar conexão
            curl_close($ch);
            
            // Atualizar status do webhook
            $status = ($httpCode >= 200 && $httpCode < 300) ? 'Sucesso' : "Erro: HTTP $httpCode";
            if (!empty($error)) {
                $status .= " - $error";
            }
            
            $updateQuery = "UPDATE webhooks SET ultima_execucao = NOW(), status_ultima_execucao = :status WHERE id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':id', $webhook['id']);
            $updateStmt->execute();
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $sucessos++;
            }
            
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao enviar webhook {$webhook['id']}: " . $e->getMessage());
            
            // Atualizar status do webhook
            $status = "Exceção: " . $e->getMessage();
            $updateQuery = "UPDATE webhooks SET ultima_execucao = NOW(), status_ultima_execucao = :status WHERE id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':id', $webhook['id']);
            $updateStmt->execute();
        }
    }
    
    // Retorna true se pelo menos um webhook foi enviado com sucesso
    return $sucessos > 0;
}

/**
 * Registra uma tentativa de login
 * @param string $username Nome de usuário
 * @param bool $success Se a tentativa foi bem-sucedida
 * @return bool
 */
function registrarTentativaLogin($username, $success = false) {
    global $conn;
    
    // Se não houver conexão com o banco, tenta criar uma
    if (!isset($conn) || !$conn) {
        $database = new Database();
        $conn = $database->connect();
    }
    
    try {
        $query = "INSERT INTO login_attempts (username, ip, timestamp, success) VALUES (:username, :ip, NOW(), :success)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
        $stmt->bindValue(':success', $success ? 1 : 0, PDO::PARAM_INT);
        $result = $stmt->execute();
        
        // Se a tentativa falhou, verifica se deve enviar alerta
        if (!$success) {
            verificarTentativasFalhas($username);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erro ao registrar tentativa de login: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se há muitas tentativas de login malsucedidas para um usuário
 * @param string $username Nome de usuário
 * @return bool
 */
function verificarTentativasFalhas($username) {
    global $conn;
    
    // Se não houver conexão com o banco, tenta criar uma
    if (!isset($conn) || !$conn) {
        $database = new Database();
        $conn = $database->connect();
    }
    
    try {
        // Buscar tentativas de login malsucedidas nas últimas 24 horas
        $query = "SELECT COUNT(*) as total FROM login_attempts 
                 WHERE username = :username 
                 AND success = 0 
                 AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalTentativas = $result['total'];
        
        // Se houver mais de 2 tentativas malsucedidas, envia alerta
        if ($totalTentativas > 2) {
            // Buscar detalhes das tentativas
            $query = "SELECT ip, timestamp FROM login_attempts 
                     WHERE username = :username 
                     AND success = 0 
                     AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     ORDER BY timestamp DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $tentativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enviar webhook de alerta
            enviarWebhook('login_failed', [
                'acao' => 'Tentativas de login malsucedidas',
                'detalhes' => "Múltiplas tentativas de login malsucedidas para o usuário '$username'",
                'username' => $username,
                'total_tentativas' => $totalTentativas,
                'tentativas' => $tentativas
            ]);
            
            // Registrar no log do sistema
            registrarLog('Segurança', json_encode([
                'acao' => 'Múltiplas tentativas de login malsucedidas',
                'username' => $username,
                'total_tentativas' => $totalTentativas,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]));
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erro ao verificar tentativas de login: " . $e->getMessage());
        return false;
    }
}

?>
