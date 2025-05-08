<?php
/**
 * Arquivo para buscar o histórico de postagens de um cliente específico
 * Retorna um JSON com as postagens do cliente
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Definir cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do cliente não fornecido']);
    exit;
}

$client_id = (int) $_GET['client_id'];

try {
    // Obter conexão com o banco de dados
    $database = new Database();
    $conn = $database->connect();
    
    // Buscar informações do cliente
    $clientQuery = "SELECT nome_cliente, instagram FROM clientes WHERE id = ?";
    $clientStmt = $conn->prepare($clientQuery);
    $clientStmt->execute([$client_id]);
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
        exit;
    }
    
    // Verificar se a tabela postagens existe
    try {
        // Buscar postagens do cliente
        $query = "SELECT p.id, p.tipo_postagem, p.formato, p.data_postagem, 
                 COALESCE(p.status, 'Pendente') as status, 
                 p.data_criacao, p.usuario_id 
                 FROM postagens p 
                 WHERE p.cliente_id = ? 
                 ORDER BY p.data_postagem DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$client_id]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatar datas para exibição
        foreach ($posts as &$post) {
            if (isset($post['data_postagem']) && $post['data_postagem']) {
                $date = new DateTime($post['data_postagem']);
                $post['data_postagem_formatada'] = $date->format('d/m/Y H:i');
            } else {
                $post['data_postagem_formatada'] = 'Não definida';
            }
        }
        
        // Tentar registrar no histórico, mas não falhar se der erro
        try {
            $acao = "Visualizou histórico de postagens";
            $detalhes = "Visualizou histórico de postagens do cliente {$client['nome_cliente']}";
            $modulo = "clientes_visualizar.php";
            
            if (function_exists('registrarLog')) {
                registrarLog($acao, $detalhes, $modulo);
            }
        } catch (Exception $logError) {
            // Ignorar erros de log para não interromper a funcionalidade principal
            error_log("Erro ao registrar log: " . $logError->getMessage());
        }
        
        // Retornar os dados
        echo json_encode([
            'success' => true, 
            'client' => $client,
            'posts' => $posts
        ]);
        
    } catch (PDOException $tableError) {
        // Verificar se o erro é relacionado à tabela não existente
        if (strpos($tableError->getMessage(), "doesn't exist") !== false) {
            echo json_encode(['success' => true, 'posts' => [], 'message' => 'Nenhuma postagem encontrada']);
        } else {
            throw $tableError; // Re-lançar o erro para ser capturado pelo catch externo
        }
    }
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar histórico de postagens: ' . $e->getMessage()]);
}
