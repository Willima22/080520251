<?php
/**
 * Script para verificar e corrigir problemas no sistema de upload de arquivos
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id'])) {
    die("Acesso negado. Você precisa estar logado para executar este script.");
}

// Função para verificar e criar diretórios
function checkAndCreateDirectory($dir, $relativePath = '') {
    $result = [
        'path' => $dir,
        'relative_path' => $relativePath,
        'exists' => file_exists($dir),
        'writable' => is_writable($dir),
        'created' => false,
        'error' => null
    ];
    
    if (!$result['exists']) {
        try {
            if (mkdir($dir, 0755, true)) {
                $result['created'] = true;
                $result['exists'] = true;
                $result['writable'] = is_writable($dir);
            } else {
                $result['error'] = "Não foi possível criar o diretório.";
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
    }
    
    return $result;
}

// Verificar diretórios de upload
$uploadDirs = [
    'base' => [
        'path' => UPLOAD_BASE_DIR,
        'relative' => 'arquivos/'
    ],
    'uploads' => [
        'path' => __DIR__ . '/../uploads/',
        'relative' => 'uploads/'
    ]
];

$results = [];
foreach ($uploadDirs as $key => $dir) {
    $results[$key] = checkAndCreateDirectory($dir['path'], $dir['relative']);
}

// Verificar permissões do PHP para upload
$phpSettings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

// Verificar se o PHP pode fazer upload de arquivos
$canUpload = true;
$uploadTestFile = __DIR__ . '/../uploads/test_upload.txt';
$testContent = 'This is a test file to verify upload functionality. ' . date('Y-m-d H:i:s');

try {
    file_put_contents($uploadTestFile, $testContent);
    $testSuccess = file_exists($uploadTestFile) && file_get_contents($uploadTestFile) === $testContent;
    
    if ($testSuccess) {
        unlink($uploadTestFile); // Remover arquivo de teste
    }
} catch (Exception $e) {
    $testSuccess = false;
    $testError = $e->getMessage();
}

// Verificar definições no config.php
$configSettings = [
    'UPLOAD_BASE_DIR' => defined('UPLOAD_BASE_DIR') ? UPLOAD_BASE_DIR : 'Não definido',
    'UPLOAD_DIR' => defined('UPLOAD_DIR') ? UPLOAD_DIR : 'Não definido',
    'MAX_FILE_SIZE' => defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 'Não definido',
    'ALLOWED_IMAGE_TYPES' => defined('ALLOWED_IMAGE_TYPES') ? implode(', ', ALLOWED_IMAGE_TYPES) : 'Não definido',
    'ALLOWED_VIDEO_TYPES' => defined('ALLOWED_VIDEO_TYPES') ? implode(', ', ALLOWED_VIDEO_TYPES) : 'Não definido'
];

// Verificar funções de upload
$functionExists = [
    'getUploadPath' => function_exists('getUploadPath'),
    'generateUniqueFilename' => function_exists('generateUniqueFilename')
];

// Incluir cabeçalho
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Verificação do Sistema de Upload</h1>
    
    <?php if (isset($_GET['test']) && $_GET['test'] === 'upload'): ?>
        <div class="alert alert-info">
            <h4 class="alert-heading">Teste de Upload</h4>
            <p>Esta página permite testar o upload de arquivos para verificar se o sistema está funcionando corretamente.</p>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Teste de Upload de Arquivo</h5>
            </div>
            <div class="card-body">
                <form action="fix_upload_system.php?test=upload" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="testFile" class="form-label">Selecione um arquivo para upload</label>
                        <input type="file" class="form-control" id="testFile" name="testFile">
                    </div>
                    <button type="submit" class="btn btn-primary">Fazer Upload</button>
                </form>
                
                <?php
                // Processar upload de teste
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['testFile'])) {
                    $file = $_FILES['testFile'];
                    
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../uploads/';
                        $fileName = 'test_' . time() . '_' . basename($file['name']);
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            echo '<div class="alert alert-success mt-3">';
                            echo '<h5 class="alert-heading">Upload realizado com sucesso!</h5>';
                            echo '<p>O arquivo foi enviado para: ' . $targetPath . '</p>';
                            echo '<p>URL do arquivo: <a href="../uploads/' . $fileName . '" target="_blank">Ver arquivo</a></p>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-danger mt-3">';
                            echo '<h5 class="alert-heading">Falha no upload!</h5>';
                            echo '<p>Não foi possível mover o arquivo para o diretório de destino.</p>';
                            echo '<p>Verifique as permissões do diretório: ' . $uploadDir . '</p>';
                            echo '</div>';
                        }
                    } else {
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo PHP (upload_max_filesize).',
                            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário.',
                            UPLOAD_ERR_PARTIAL => 'O arquivo foi parcialmente enviado.',
                            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
                            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado.',
                            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo no disco.',
                            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload.'
                        ];
                        
                        $errorMessage = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : 'Erro desconhecido.';
                        
                        echo '<div class="alert alert-danger mt-3">';
                        echo '<h5 class="alert-heading">Erro no upload!</h5>';
                        echo '<p>Código do erro: ' . $file['error'] . '</p>';
                        echo '<p>Mensagem: ' . $errorMessage . '</p>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <a href="fix_upload_system.php" class="btn btn-secondary">Voltar para Diagnóstico</a>
    <?php else: ?>
        <!-- Resultados da verificação -->
        <div class="alert <?php echo ($testSuccess) ? 'alert-success' : 'alert-danger'; ?>">
            <h4 class="alert-heading"><?php echo ($testSuccess) ? 'Sistema de Upload Funcionando!' : 'Problemas no Sistema de Upload!'; ?></h4>
            <p><?php echo ($testSuccess) ? 'O teste de escrita de arquivo foi bem-sucedido.' : 'O teste de escrita de arquivo falhou.'; ?></p>
            <?php if (!$testSuccess && isset($testError)): ?>
                <p>Erro: <?php echo $testError; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <!-- Diretórios de Upload -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Diretórios de Upload</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Diretório</th>
                                    <th>Status</th>
                                    <th>Permissões</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $key => $result): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($result['relative_path']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($result['path']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($result['exists']): ?>
                                            <span class="badge bg-success">Existe</span>
                                            <?php if ($result['created']): ?>
                                                <span class="badge bg-info">Criado agora</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Não existe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($result['writable']): ?>
                                            <span class="badge bg-success">Gravável</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Não gravável</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($result['error']): ?>
                                            <div class="text-danger mt-1"><?php echo htmlspecialchars($result['error']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Configurações do PHP -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Configurações do PHP</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Configuração</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($phpSettings as $setting => $value): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($setting); ?></td>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Configurações do Sistema -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Configurações do Sistema</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Configuração</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($configSettings as $setting => $value): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($setting); ?></td>
                                    <td><?php echo htmlspecialchars($value); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Funções de Upload -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Funções de Upload</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Função</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($functionExists as $function => $exists): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($function); ?></td>
                                    <td>
                                        <?php if ($exists): ?>
                                            <span class="badge bg-success">Disponível</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Não disponível</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="fix_upload_system.php?test=upload" class="btn btn-primary">Testar Upload de Arquivo</a>
            <a href="../index.php" class="btn btn-secondary">Voltar para o Sistema</a>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
