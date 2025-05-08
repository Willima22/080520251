<?php
/**
 * Script para gerar backup do banco de dados
 * Disponível apenas para administradores
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se o usuário está logado e é administrador
// Não precisamos chamar session_start() pois já é chamado em config.php
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Obter conexão com o banco de dados usando a classe Database
$database = new Database();
$conn = $database->connect();

// Definir as credenciais do banco de dados diretamente
// Usando as mesmas credenciais que estão na classe Database
$db_host = 'localhost';
$db_name = 'opapopol_02052025';
$db_user = 'opapopol_02052025';
$db_pass = 'Aroma19@';

// Nome do arquivo de backup
$backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

// Cabeçalho para download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $backup_file . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Iniciar o output buffer
ob_start();

// Cabeçalho do arquivo SQL
echo "-- Backup do banco de dados {$db_name}\n";
echo "-- Data: " . date('Y-m-d H:i:s') . "\n";
echo "-- Gerado por: " . ($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Administrador') . "\n\n";

// Obter todas as tabelas do banco de dados
$tables_query = "SHOW TABLES";
$tables_stmt = $conn->prepare($tables_query);
$tables_stmt->execute();
$tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

// Para cada tabela, gerar o código SQL para criar e popular
foreach ($tables as $table) {
    // Obter a estrutura da tabela
    $create_query = "SHOW CREATE TABLE `{$table}`";
    $create_stmt = $conn->prepare($create_query);
    $create_stmt->execute();
    $create_row = $create_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "-- Estrutura da tabela `{$table}`\n";
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $create_row['Create Table'] . ";\n\n";
    
    // Obter os dados da tabela
    $data_query = "SELECT * FROM `{$table}`";
    $data_stmt = $conn->prepare($data_query);
    $data_stmt->execute();
    $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "-- Dados da tabela `{$table}`\n";
        
        // Obter os nomes das colunas
        $columns = array_keys($rows[0]);
        $column_list = '`' . implode('`, `', $columns) . '`';
        
        // Iniciar o comando INSERT
        echo "INSERT INTO `{$table}` ({$column_list}) VALUES\n";
        
        $row_count = count($rows);
        foreach ($rows as $i => $row) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            
            $value_list = implode(', ', $values);
            echo "({$value_list})";
            
            // Adicionar vírgula se não for o último registro
            if ($i < $row_count - 1) {
                echo ",\n";
            } else {
                echo ";\n\n";
            }
        }
    }
}

// Registrar no histórico
$acao = "Gerou backup do banco de dados";
$detalhes = "Gerou backup completo do banco de dados {$db_name}";
$modulo = "backup_database.php";
$ip = $_SERVER['REMOTE_ADDR'];

$logQuery = "INSERT INTO historico (usuario_id, usuario_nome, acao, detalhes, modulo, ip, data_hora) 
            VALUES (:usuario_id, :usuario_nome, :acao, :detalhes, :modulo, :ip, NOW())";
$logStmt = $conn->prepare($logQuery);
$logStmt->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
$logStmt->bindParam(':usuario_nome', ($_SESSION['user_name'] ?? $_SESSION['username']), PDO::PARAM_STR);
$logStmt->bindParam(':acao', $acao, PDO::PARAM_STR);
$logStmt->bindParam(':detalhes', $detalhes, PDO::PARAM_STR);
$logStmt->bindParam(':modulo', $modulo, PDO::PARAM_STR);
$logStmt->bindParam(':ip', $ip, PDO::PARAM_STR);
$logStmt->execute();

// Enviar o conteúdo do buffer para o navegador
ob_end_flush();
exit;
?>
