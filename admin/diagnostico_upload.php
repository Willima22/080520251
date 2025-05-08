<?php
/**
 * Ferramenta de diagnóstico para o sistema de upload de arquivos
 * Verifica permissões, caminhos e configurações
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

// Função para verificar permissões de diretório
function checkDirPermissions($dir) {
    if (!file_exists($dir)) {
        return [
            'exists' => false,
            'writable' => false,
            'permissions' => 'N/A',
            'owner' => 'N/A'
        ];
    }
    
    return [
        'exists' => true,
        'writable' => is_writable($dir),
        'permissions' => substr(sprintf('%o', fileperms($dir)), -4),
        'owner' => function_exists('posix_getpwuid') ? 
                  posix_getpwuid(fileowner($dir))['name'] : 
                  fileowner($dir)
    ];
}

// Testar a criação de um diretório de teste
function testDirectoryCreation($baseDir) {
    $testDir = $baseDir . 'test_' . time() . '/';
    $success = false;
    $error = '';
    
    try {
        if (mkdir($testDir, 0755, true)) {
            $success = true;
            // Limpar após o teste
            rmdir($testDir);
        } else {
            $error = 'Falha ao criar diretório de teste';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    
    return [
        'success' => $success,
        'error' => $error
    ];
}

// Obter informações do servidor
$serverInfo = [
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'php_version' => PHP_VERSION,
    'max_upload_size' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Verificar diretórios de upload
$uploadBaseDirInfo = checkDirPermissions(UPLOAD_BASE_DIR);
$uploadDirInfo = checkDirPermissions(UPLOAD_DIR);

// Testar criação de diretórios
$testBaseDir = testDirectoryCreation(UPLOAD_BASE_DIR);
$testUploadDir = testDirectoryCreation(UPLOAD_DIR);

// Testar cliente específico
$database = new Database();
$conn = $database->connect();
$query = "SELECT id, nome_cliente FROM clientes LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

$clienteUploadPath = null;
$testClienteDir = ['success' => false, 'error' => 'Nenhum cliente encontrado'];

if ($cliente) {
    $uploadPath = getUploadPath($cliente['id'], $cliente['nome_cliente'], 'imagem');
    $clienteUploadPath = $uploadPath['dir'];
    $clienteUploadPathInfo = checkDirPermissions($clienteUploadPath);
    $testClienteDir = testDirectoryCreation($uploadPath['dir']);
}

// Verificar constantes
$constantsInfo = [
    'UPLOAD_BASE_DIR' => UPLOAD_BASE_DIR,
    'UPLOAD_DIR' => UPLOAD_DIR,
    'FILES_BASE_URL' => FILES_BASE_URL,
    'APP_URL' => APP_URL
];

// Verificar se o diretório de arquivos está acessível via web
$testUrl = FILES_BASE_URL . '/arquivos/';
$webAccessible = false;
$webAccessError = '';

// Usar cURL para verificar se o diretório está acessível
if (function_exists('curl_init')) {
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $webAccessible = ($httpCode < 400);
    if (!$webAccessible) {
        $webAccessError = "HTTP Code: $httpCode";
    }
} else {
    $webAccessError = 'cURL não disponível';
}

// Cabeçalho
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Diagnóstico do Sistema de Upload</h1>
    <p class="text-muted">Esta ferramenta ajuda a identificar problemas com o sistema de upload de arquivos.</p>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informações do Servidor</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <?php foreach($serverInfo as $key => $value): ?>
                    <tr>
                        <th width="30%"><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                        <td><?= $value ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Constantes de Configuração</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <?php foreach($constantsInfo as $key => $value): ?>
                    <tr>
                        <th width="30%"><?= $key ?></th>
                        <td><?= $value ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Diretórios de Upload</h5>
        </div>
        <div class="card-body">
            <h6>Diretório Base (UPLOAD_BASE_DIR)</h6>
            <table class="table table-bordered mb-4">
                <tbody>
                    <tr>
                        <th width="30%">Caminho</th>
                        <td><?= UPLOAD_BASE_DIR ?></td>
                    </tr>
                    <tr>
                        <th>Existe</th>
                        <td>
                            <?php if($uploadBaseDirInfo['exists']): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Permissão de Escrita</th>
                        <td>
                            <?php if($uploadBaseDirInfo['writable']): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Permissões</th>
                        <td><?= $uploadBaseDirInfo['permissions'] ?></td>
                    </tr>
                    <tr>
                        <th>Proprietário</th>
                        <td><?= $uploadBaseDirInfo['owner'] ?></td>
                    </tr>
                    <tr>
                        <th>Teste de Criação</th>
                        <td>
                            <?php if($testBaseDir['success']): ?>
                                <span class="text-success">Sucesso</span>
                            <?php else: ?>
                                <span class="text-danger">Falha: <?= $testBaseDir['error'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h6>Diretório de Uploads Temporários (UPLOAD_DIR)</h6>
            <table class="table table-bordered mb-4">
                <tbody>
                    <tr>
                        <th width="30%">Caminho</th>
                        <td><?= UPLOAD_DIR ?></td>
                    </tr>
                    <tr>
                        <th>Existe</th>
                        <td>
                            <?php if($uploadDirInfo['exists']): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Permissão de Escrita</th>
                        <td>
                            <?php if($uploadDirInfo['writable']): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Permissões</th>
                        <td><?= $uploadDirInfo['permissions'] ?></td>
                    </tr>
                    <tr>
                        <th>Proprietário</th>
                        <td><?= $uploadDirInfo['owner'] ?></td>
                    </tr>
                    <tr>
                        <th>Teste de Criação</th>
                        <td>
                            <?php if($testUploadDir['success']): ?>
                                <span class="text-success">Sucesso</span>
                            <?php else: ?>
                                <span class="text-danger">Falha: <?= $testUploadDir['error'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php if($clienteUploadPath): ?>
            <h6>Diretório Específico do Cliente (<?= $cliente['nome_cliente'] ?>)</h6>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th width="30%">Caminho</th>
                        <td><?= $clienteUploadPath ?></td>
                    </tr>
                    <tr>
                        <th>Existe</th>
                        <td>
                            <?php if($clienteUploadPathInfo['exists']): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Permissão de Escrita</th>
                        <td>
                            <?php if($clienteUploadPathInfo['writable']): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Permissões</th>
                        <td><?= $clienteUploadPathInfo['permissions'] ?></td>
                    </tr>
                    <tr>
                        <th>Proprietário</th>
                        <td><?= $clienteUploadPathInfo['owner'] ?></td>
                    </tr>
                    <tr>
                        <th>Teste de Criação</th>
                        <td>
                            <?php if($testClienteDir['success']): ?>
                                <span class="text-success">Sucesso</span>
                            <?php else: ?>
                                <span class="text-danger">Falha: <?= $testClienteDir['error'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Acesso Web</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th width="30%">URL de Teste</th>
                        <td><?= $testUrl ?></td>
                    </tr>
                    <tr>
                        <th>Acessível</th>
                        <td>
                            <?php if($webAccessible): ?>
                                <span class="text-success">Sim</span>
                            <?php else: ?>
                                <span class="text-danger">Não: <?= $webAccessError ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Teste de Upload</h5>
        </div>
        <div class="card-body">
            <form action="diagnostico_upload.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="testFile">Arquivo de Teste</label>
                    <input type="file" class="form-control-file" id="testFile" name="testFile">
                </div>
                <button type="submit" class="btn btn-primary" name="testUpload">Testar Upload</button>
            </form>
            
            <?php
            // Processar upload de teste
            if (isset($_POST['testUpload']) && isset($_FILES['testFile'])) {
                $file = $_FILES['testFile'];
                
                if ($file['error'] === UPLOAD_ERR_OK) {
                    echo '<div class="alert alert-info mt-3">';
                    echo '<h6>Informações do Arquivo:</h6>';
                    echo '<ul>';
                    echo '<li>Nome: ' . $file['name'] . '</li>';
                    echo '<li>Tipo: ' . $file['type'] . '</li>';
                    echo '<li>Tamanho: ' . $file['size'] . ' bytes</li>';
                    echo '<li>Arquivo Temporário: ' . $file['tmp_name'] . '</li>';
                    echo '</ul>';
                    
                    // Tentar mover para o diretório de uploads
                    $targetPath = UPLOAD_DIR . 'test_' . time() . '_' . $file['name'];
                    
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        echo '<div class="alert alert-success">Upload bem-sucedido para: ' . $targetPath . '</div>';
                        
                        // Limpar após o teste
                        unlink($targetPath);
                    } else {
                        echo '<div class="alert alert-danger">Falha ao mover o arquivo para: ' . $targetPath . '</div>';
                    }
                    
                    echo '</div>';
                } else {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo PHP (upload_max_filesize)',
                        UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário',
                        UPLOAD_ERR_PARTIAL => 'O arquivo foi parcialmente carregado',
                        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                        UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
                        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco',
                        UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload'
                    ];
                    
                    $errorMessage = isset($errorMessages[$file['error']]) ? 
                                   $errorMessages[$file['error']] : 
                                   'Erro desconhecido: ' . $file['error'];
                    
                    echo '<div class="alert alert-danger mt-3">Erro no upload: ' . $errorMessage . '</div>';
                }
            }
            ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Recomendações</h5>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php if (!$uploadBaseDirInfo['exists']): ?>
                <li class="list-group-item list-group-item-warning">
                    O diretório base de arquivos não existe. Crie manualmente o diretório: <code><?= UPLOAD_BASE_DIR ?></code>
                </li>
                <?php endif; ?>
                
                <?php if ($uploadBaseDirInfo['exists'] && !$uploadBaseDirInfo['writable']): ?>
                <li class="list-group-item list-group-item-warning">
                    O diretório base de arquivos não tem permissão de escrita. Execute: <code>chmod 755 <?= UPLOAD_BASE_DIR ?></code>
                </li>
                <?php endif; ?>
                
                <?php if (!$uploadDirInfo['exists']): ?>
                <li class="list-group-item list-group-item-warning">
                    O diretório de uploads temporários não existe. Crie manualmente o diretório: <code><?= UPLOAD_DIR ?></code>
                </li>
                <?php endif; ?>
                
                <?php if ($uploadDirInfo['exists'] && !$uploadDirInfo['writable']): ?>
                <li class="list-group-item list-group-item-warning">
                    O diretório de uploads temporários não tem permissão de escrita. Execute: <code>chmod 755 <?= UPLOAD_DIR ?></code>
                </li>
                <?php endif; ?>
                
                <?php if (!$webAccessible): ?>
                <li class="list-group-item list-group-item-warning">
                    O diretório de arquivos não está acessível via web. Verifique se a URL <code><?= FILES_BASE_URL ?></code> está correta e se o diretório existe no servidor web.
                </li>
                <?php endif; ?>
                
                <li class="list-group-item list-group-item-info">
                    Certifique-se de que o usuário do servidor web (<?= function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($_SERVER['SCRIPT_FILENAME']))['name'] : 'www-data/apache' ?>) tenha permissão para escrever nos diretórios de upload.
                </li>
                
                <li class="list-group-item list-group-item-info">
                    Verifique se o valor de <code>FILES_BASE_URL</code> está correto e aponta para o domínio onde os arquivos serão acessíveis.
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
