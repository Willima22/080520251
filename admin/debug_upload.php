<?php
/**
 * Ferramenta de depuração avançada para uploads
 * Identifica e corrige problemas específicos de upload
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

// Função para testar permissões e criar diretórios
function testDirectoryCreation($path) {
    $result = [
        'path' => $path,
        'exists' => false,
        'writable' => false,
        'created' => false,
        'error' => null
    ];
    
    // Verificar se o diretório existe
    if (file_exists($path)) {
        $result['exists'] = true;
        $result['writable'] = is_writable($path);
        return $result;
    }
    
    // Tentar criar o diretório
    try {
        if (@mkdir($path, 0755, true)) {
            $result['created'] = true;
            $result['exists'] = true;
            $result['writable'] = is_writable($path);
        } else {
            $error = error_get_last();
            $result['error'] = $error['message'] ?? 'Erro desconhecido';
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

// Função para testar upload de arquivo
function testFileUpload($file, $targetPath) {
    $result = [
        'source' => $file['tmp_name'],
        'target' => $targetPath,
        'success' => false,
        'method' => null,
        'error' => null
    ];
    
    // Verificar se o arquivo temporário existe
    if (!file_exists($file['tmp_name'])) {
        $result['error'] = 'Arquivo temporário não existe';
        return $result;
    }
    
    // Verificar se o diretório de destino existe
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        if (!@mkdir($targetDir, 0755, true)) {
            $result['error'] = 'Não foi possível criar o diretório de destino';
            return $result;
        }
    }
    
    // Método 1: move_uploaded_file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $result['success'] = true;
        $result['method'] = 'move_uploaded_file';
        return $result;
    }
    
    // Se falhou, verificar o erro
    $error = error_get_last();
    $result['error'] = $error['message'] ?? 'Erro desconhecido em move_uploaded_file';
    
    // Método 2: copy
    if (copy($file['tmp_name'], $targetPath)) {
        $result['success'] = true;
        $result['method'] = 'copy';
        return $result;
    }
    
    // Se falhou, verificar o erro
    $error = error_get_last();
    $result['error'] = $error['message'] ?? 'Erro desconhecido em copy';
    
    // Método 3: file_put_contents
    $content = file_get_contents($file['tmp_name']);
    if ($content !== false && file_put_contents($targetPath, $content) !== false) {
        $result['success'] = true;
        $result['method'] = 'file_put_contents';
        return $result;
    }
    
    // Se falhou, verificar o erro
    $error = error_get_last();
    $result['error'] = $error['message'] ?? 'Erro desconhecido em file_put_contents';
    
    return $result;
}

// Função para obter todos os caminhos possíveis
function getAllPossiblePaths() {
    $paths = [];
    
    // Caminho 1: DOCUMENT_ROOT
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $paths['document_root'] = [
            'base' => $_SERVER['DOCUMENT_ROOT'],
            'arquivos' => $_SERVER['DOCUMENT_ROOT'] . '/arquivos/',
            'uploads' => $_SERVER['DOCUMENT_ROOT'] . '/uploads/'
        ];
    }
    
    // Caminho 2: Diretório do script atual
    $scriptDir = dirname(dirname(__FILE__));
    $paths['script_dir'] = [
        'base' => $scriptDir,
        'arquivos' => $scriptDir . '/arquivos/',
        'uploads' => $scriptDir . '/uploads/'
    ];
    
    // Caminho 3: Diretório pai do script atual
    $parentDir = dirname($scriptDir);
    $paths['parent_dir'] = [
        'base' => $parentDir,
        'arquivos' => $parentDir . '/arquivos/',
        'uploads' => $parentDir . '/uploads/'
    ];
    
    // Caminho 4: Diretório atual
    $currentDir = getcwd();
    $paths['current_dir'] = [
        'base' => $currentDir,
        'arquivos' => $currentDir . '/arquivos/',
        'uploads' => $currentDir . '/uploads/'
    ];
    
    return $paths;
}

// Processar upload de teste
$uploadResult = null;
$directoryTests = [];
$possiblePaths = getAllPossiblePaths();

// Testar todos os caminhos possíveis
foreach ($possiblePaths as $key => $paths) {
    $directoryTests[$key] = [
        'base' => testDirectoryCreation($paths['base']),
        'arquivos' => testDirectoryCreation($paths['arquivos']),
        'uploads' => testDirectoryCreation($paths['uploads'])
    ];
}

if (isset($_POST['test_upload']) && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Testar upload em todos os caminhos possíveis
        $uploadResults = [];
        
        foreach ($possiblePaths as $key => $paths) {
            $targetDir = $paths['arquivos'] . 'teste/';
            if (!file_exists($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            
            $targetPath = $targetDir . 'teste_' . time() . '_' . basename($file['name']);
            $uploadResults[$key] = testFileUpload($file, $targetPath);
        }
        
        // Encontrar o primeiro upload bem-sucedido
        $successfulUpload = null;
        foreach ($uploadResults as $key => $result) {
            if ($result['success']) {
                $successfulUpload = [
                    'path_key' => $key,
                    'result' => $result
                ];
                break;
            }
        }
        
        $uploadResult = [
            'file' => $file,
            'results' => $uploadResults,
            'successful' => $successfulUpload
        ];
    }
}

// Obter configurações PHP relevantes
$phpSettings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'file_uploads' => ini_get('file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'open_basedir' => ini_get('open_basedir')
];

// Cabeçalho
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Depuração Avançada de Uploads</h1>
    <p class="text-muted">Esta ferramenta ajuda a identificar e corrigir problemas específicos de upload de arquivos.</p>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Configurações PHP</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <?php foreach ($phpSettings as $key => $value): ?>
                    <tr>
                        <th><?= $key ?></th>
                        <td><?= $value ?: 'Não definido' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Variáveis de Servidor</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th>DOCUMENT_ROOT</th>
                        <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Não definido' ?></td>
                    </tr>
                    <tr>
                        <th>SCRIPT_FILENAME</th>
                        <td><?= $_SERVER['SCRIPT_FILENAME'] ?? 'Não definido' ?></td>
                    </tr>
                    <tr>
                        <th>PHP_SELF</th>
                        <td><?= $_SERVER['PHP_SELF'] ?? 'Não definido' ?></td>
                    </tr>
                    <tr>
                        <th>REQUEST_URI</th>
                        <td><?= $_SERVER['REQUEST_URI'] ?? 'Não definido' ?></td>
                    </tr>
                    <tr>
                        <th>SERVER_SOFTWARE</th>
                        <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Não definido' ?></td>
                    </tr>
                    <tr>
                        <th>HTTP_HOST</th>
                        <td><?= $_SERVER['HTTP_HOST'] ?? 'Não definido' ?></td>
                    </tr>
                    <tr>
                        <th>Diretório atual (getcwd)</th>
                        <td><?= getcwd() ?></td>
                    </tr>
                    <tr>
                        <th>Diretório do script (__FILE__)</th>
                        <td><?= __FILE__ ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Teste de Caminhos</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="pathsAccordion">
                <?php foreach ($directoryTests as $key => $tests): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $key ?>">
                        <button class="accordion-button <?= $tests['arquivos']['created'] || $tests['arquivos']['writable'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $key ?>" aria-expanded="<?= $tests['arquivos']['created'] || $tests['arquivos']['writable'] ? 'true' : 'false' ?>" aria-controls="collapse<?= $key ?>">
                            <?= ucfirst(str_replace('_', ' ', $key)) ?>
                            <?php if ($tests['arquivos']['created'] || $tests['arquivos']['writable']): ?>
                                <span class="badge bg-success ms-2">Viável</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">Problema</span>
                            <?php endif; ?>
                        </button>
                    </h2>
                    <div id="collapse<?= $key ?>" class="accordion-collapse collapse <?= $tests['arquivos']['created'] || $tests['arquivos']['writable'] ? 'show' : '' ?>" aria-labelledby="heading<?= $key ?>" data-bs-parent="#pathsAccordion">
                        <div class="accordion-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Diretório</th>
                                        <th>Caminho</th>
                                        <th>Existe</th>
                                        <th>Permissão de Escrita</th>
                                        <th>Criado</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tests as $dirType => $test): ?>
                                    <tr>
                                        <td><?= $dirType ?></td>
                                        <td><code><?= $test['path'] ?></code></td>
                                        <td><?= $test['exists'] ? '<span class="text-success">Sim</span>' : '<span class="text-danger">Não</span>' ?></td>
                                        <td><?= $test['writable'] ? '<span class="text-success">Sim</span>' : '<span class="text-danger">Não</span>' ?></td>
                                        <td><?= $test['created'] ? '<span class="text-success">Sim</span>' : '<span class="text-danger">Não</span>' ?></td>
                                        <td><?= $test['error'] ? '<span class="text-danger">' . $test['error'] . '</span>' : '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Teste de Upload</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="test_file" class="form-label">Selecione um arquivo para teste</label>
                    <input type="file" class="form-control" id="test_file" name="test_file" required>
                </div>
                <button type="submit" name="test_upload" class="btn btn-primary">Testar Upload</button>
            </form>
            
            <?php if ($uploadResult): ?>
            <div class="mt-4">
                <h5>Resultado do Teste de Upload</h5>
                
                <div class="alert <?= $uploadResult['successful'] ? 'alert-success' : 'alert-danger' ?>">
                    <?php if ($uploadResult['successful']): ?>
                        <p><strong>Upload bem-sucedido!</strong></p>
                        <p>Método utilizado: <code><?= $uploadResult['successful']['result']['method'] ?></code></p>
                        <p>Caminho: <code><?= $uploadResult['successful']['result']['target'] ?></code></p>
                        <p>Tipo de caminho: <strong><?= ucfirst(str_replace('_', ' ', $uploadResult['successful']['path_key'])) ?></strong></p>
                    <?php else: ?>
                        <p><strong>Falha no upload em todos os caminhos testados!</strong></p>
                    <?php endif; ?>
                </div>
                
                <h6>Detalhes do Arquivo</h6>
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>Nome</th>
                            <td><?= $uploadResult['file']['name'] ?></td>
                        </tr>
                        <tr>
                            <th>Tipo</th>
                            <td><?= $uploadResult['file']['type'] ?></td>
                        </tr>
                        <tr>
                            <th>Tamanho</th>
                            <td><?= $uploadResult['file']['size'] ?> bytes</td>
                        </tr>
                        <tr>
                            <th>Arquivo Temporário</th>
                            <td><?= $uploadResult['file']['tmp_name'] ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <h6>Resultados por Caminho</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Caminho</th>
                            <th>Destino</th>
                            <th>Sucesso</th>
                            <th>Método</th>
                            <th>Erro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploadResult['results'] as $key => $result): ?>
                        <tr class="<?= $result['success'] ? 'table-success' : 'table-danger' ?>">
                            <td><?= ucfirst(str_replace('_', ' ', $key)) ?></td>
                            <td><code><?= $result['target'] ?></code></td>
                            <td><?= $result['success'] ? '<span class="text-success">Sim</span>' : '<span class="text-danger">Não</span>' ?></td>
                            <td><?= $result['method'] ?: '-' ?></td>
                            <td><?= $result['error'] ?: '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Solução Recomendada</h5>
        </div>
        <div class="card-body">
            <?php
            // Encontrar o melhor caminho para uploads
            $bestPath = null;
            $bestPathKey = null;
            
            foreach ($directoryTests as $key => $tests) {
                if ($tests['arquivos']['writable'] || $tests['arquivos']['created']) {
                    $bestPath = $possiblePaths[$key];
                    $bestPathKey = $key;
                    break;
                }
            }
            
            if ($bestPath):
            ?>
                <div class="alert alert-success">
                    <h5>Caminho Recomendado</h5>
                    <p>Com base nos testes, o melhor caminho para uploads é: <strong><?= ucfirst(str_replace('_', ' ', $bestPathKey)) ?></strong></p>
                    <p>Diretório base: <code><?= $bestPath['base'] ?></code></p>
                    <p>Diretório de arquivos: <code><?= $bestPath['arquivos'] ?></code></p>
                    <p>Diretório de uploads temporários: <code><?= $bestPath['uploads'] ?></code></p>
                </div>
                
                <h5>Código de Configuração Recomendado</h5>
                <p>Adicione o seguinte código ao arquivo <code>config/config.php</code>:</p>
                
                <pre class="bg-light p-3"><code>// File upload settings
define('UPLOAD_BASE_DIR', '<?= addslashes($bestPath['arquivos']) ?>');
define('UPLOAD_DIR', '<?= addslashes($bestPath['uploads']) ?>');</code></pre>
                
                <form method="post" action="fix_config.php" class="mt-3">
                    <input type="hidden" name="upload_base_dir" value="<?= htmlspecialchars($bestPath['arquivos']) ?>">
                    <input type="hidden" name="upload_dir" value="<?= htmlspecialchars($bestPath['uploads']) ?>">
                    <button type="submit" name="fix_config" class="btn btn-success">Aplicar Esta Configuração</button>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h5>Problema Crítico</h5>
                    <p>Não foi possível encontrar um caminho viável para uploads. Verifique as permissões do servidor.</p>
                </div>
                
                <h5>Recomendações</h5>
                <ol>
                    <li>Verifique se o usuário do servidor web (www-data, apache, etc.) tem permissões para escrever nos diretórios.</li>
                    <li>Crie manualmente os diretórios <code>/arquivos</code> e <code>/uploads</code> na raiz do site.</li>
                    <li>Defina permissões 755 para os diretórios: <code>chmod -R 755 /arquivos /uploads</code></li>
                    <li>Verifique se há restrições no <code>open_basedir</code> que possam estar bloqueando o acesso.</li>
                </ol>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-3 mb-5">
        <a href="index.php" class="btn btn-primary">Voltar para o Dashboard</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
