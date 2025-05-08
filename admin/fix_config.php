<?php
/**
 * Script para aplicar configurações recomendadas de upload
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Verificar se o formulário foi enviado
if (!isset($_POST['fix_config'])) {
    header('Location: debug_upload.php');
    exit;
}

// Obter os caminhos recomendados
$uploadBaseDir = $_POST['upload_base_dir'] ?? '';
$uploadDir = $_POST['upload_dir'] ?? '';

if (empty($uploadBaseDir) || empty($uploadDir)) {
    die('Erro: Caminhos de upload não fornecidos.');
}

// Criar os diretórios se não existirem
if (!file_exists($uploadBaseDir)) {
    if (!@mkdir($uploadBaseDir, 0755, true)) {
        die('Erro: Não foi possível criar o diretório ' . $uploadBaseDir);
    }
}

if (!file_exists($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        die('Erro: Não foi possível criar o diretório ' . $uploadDir);
    }
}

// Definir permissões
@chmod($uploadBaseDir, 0755);
@chmod($uploadDir, 0755);

// Ler o arquivo de configuração
$configFile = __DIR__ . '/../config/config.php';
$configContent = file_get_contents($configFile);

if ($configContent === false) {
    die('Erro: Não foi possível ler o arquivo de configuração.');
}

// Backup do arquivo de configuração original
$backupFile = $configFile . '.bak.' . date('YmdHis');
if (!file_put_contents($backupFile, $configContent)) {
    die('Erro: Não foi possível criar backup do arquivo de configuração.');
}

// Substituir as definições de UPLOAD_BASE_DIR e UPLOAD_DIR
$pattern = '/define\s*\(\s*[\'"]UPLOAD_BASE_DIR[\'"]\s*,\s*.*?\)\s*;/';
$replacement = "define('UPLOAD_BASE_DIR', '" . addslashes($uploadBaseDir) . "');";
$configContent = preg_replace($pattern, $replacement, $configContent);

$pattern = '/define\s*\(\s*[\'"]UPLOAD_DIR[\'"]\s*,\s*.*?\)\s*;/';
$replacement = "define('UPLOAD_DIR', '" . addslashes($uploadDir) . "');";
$configContent = preg_replace($pattern, $replacement, $configContent);

// Escrever o arquivo de configuração atualizado
if (!file_put_contents($configFile, $configContent)) {
    die('Erro: Não foi possível escrever no arquivo de configuração.');
}

// Registrar a ação no log
$database = new Database();
$conn = $database->connect();

$acao = "Atualização de configuração de upload";
$detalhes = "UPLOAD_BASE_DIR: $uploadBaseDir, UPLOAD_DIR: $uploadDir";
$modulo = "Configuração";
$ip = $_SERVER['REMOTE_ADDR'];

$logQuery = "INSERT INTO logs (usuario_id, usuario_nome, acao, detalhes, modulo, ip, data_hora) 
            VALUES (:usuario_id, :usuario_nome, :acao, :detalhes, :modulo, :ip, NOW())";
$logStmt = $conn->prepare($logQuery);
$logStmt->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
$logStmt->bindParam(':usuario_nome', $_SESSION['user_name'] ?? $_SESSION['username'], PDO::PARAM_STR);
$logStmt->bindParam(':acao', $acao, PDO::PARAM_STR);
$logStmt->bindParam(':detalhes', $detalhes, PDO::PARAM_STR);
$logStmt->bindParam(':modulo', $modulo, PDO::PARAM_STR);
$logStmt->bindParam(':ip', $ip, PDO::PARAM_STR);
$logStmt->execute();

// Redirecionar para a página de sucesso
header('Location: debug_upload.php?success=1');
exit;
?>
