<?php
// Arquivo de diagnóstico para identificar problemas no sistema

// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico do Sistema</h1>";

// Verificar versão do PHP
echo "<h2>Informações do PHP</h2>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";
echo "<p>Extensões carregadas: </p><ul>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";

// Verificar configurações do PHP
echo "<h2>Configurações do PHP</h2>";
echo "<p>display_errors: " . ini_get('display_errors') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";

// Verificar conexão com o banco de dados
echo "<h2>Teste de Conexão com o Banco de Dados</h2>";
try {
    require_once 'config/config.php';
    require_once 'config/db.php';
    
    $database = new Database();
    $conn = $database->connect();
    
    echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida com sucesso!</p>";
    
    // Verificar tabelas
    echo "<h3>Verificação de Tabelas</h3>";
    $tables = ['usuarios', 'clientes', 'postagens'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            echo "<p style='color:green'>✓ Tabela '$table' existe</p>";
            
            // Verificar estrutura da tabela
            $query = "DESCRIBE $table";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<details><summary>Estrutura da tabela '$table'</summary><table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table></details>";
            
            // Verificar registros
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Número de registros na tabela '$table': " . $count['count'] . "</p>";
        } else {
            echo "<p style='color:red'>✗ Tabela '$table' não existe!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na conexão com o banco de dados: " . $e->getMessage() . "</p>";
}

// Verificar diretórios e permissões
echo "<h2>Verificação de Diretórios</h2>";
$directories = [
    'uploads' => __DIR__ . '/uploads',
    'assets' => __DIR__ . '/assets',
    'config' => __DIR__ . '/config',
    'includes' => __DIR__ . '/includes'
];

foreach ($directories as $name => $path) {
    if (file_exists($path)) {
        echo "<p>Diretório '$name': Existe";
        if (is_writable($path)) {
            echo " <span style='color:green'>(Permissão de escrita: OK)</span>";
        } else {
            echo " <span style='color:red'>(Permissão de escrita: NEGADA)</span>";
        }
        echo "</p>";
    } else {
        echo "<p style='color:red'>Diretório '$name': Não existe!</p>";
    }
}

// Verificar arquivos principais
echo "<h2>Verificação de Arquivos Principais</h2>";
$files = [
    'index.php', 
    'login.php', 
    'dashboard.php', 
    'config/config.php', 
    'config/db.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/auth.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green'>✓ Arquivo '$file' existe</p>";
    } else {
        echo "<p style='color:red'>✗ Arquivo '$file' não existe!</p>";
    }
}

// Verificar sessão
echo "<h2>Informações de Sessão</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";
echo "<p>Conteúdo da sessão:</p><pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar variáveis de servidor
echo "<h2>Variáveis de Servidor</h2>";
echo "<p>SERVER_SOFTWARE: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . "</p>";

echo "<h2>Recomendações</h2>";
echo "<ol>";
echo "<li>Se você vir erros de conexão com o banco de dados, verifique as credenciais em config/db.php</li>";
echo "<li>Se as tabelas não existirem, execute o script SQL para criá-las</li>";
echo "<li>Se houver problemas de permissão, certifique-se de que os diretórios tenham permissão de escrita (chmod 755 ou 775)</li>";
echo "<li>Se os arquivos principais estiverem faltando, faça o upload deles novamente</li>";
echo "<li>Se houver problemas com a sessão, verifique as configurações do PHP</li>";
echo "</ol>";
?>
