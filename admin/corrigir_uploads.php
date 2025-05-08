<?php
/**
 * Script para verificar e corrigir os diretórios de upload
 * Garante que todos os arquivos estejam no local correto
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

// Conectar ao banco de dados
$database = new Database();
$conn = $database->connect();

// Função para criar diretório com permissões corretas
function createDirectoryWithPermissions($dir) {
    if (!file_exists($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return false;
        }
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0755);
    }
    return true;
}

// Função para mover arquivo para o diretório correto
function moveFileToCorrectLocation($sourceFile, $targetFile) {
    // Criar diretório de destino se não existir
    $targetDir = dirname($targetFile);
    if (!createDirectoryWithPermissions($targetDir)) {
        return [
            'success' => false,
            'error' => "Não foi possível criar o diretório: $targetDir"
        ];
    }
    
    // Tentar copiar o arquivo
    if (file_exists($sourceFile)) {
        if (copy($sourceFile, $targetFile)) {
            // Definir permissões
            @chmod($targetFile, 0644);
            
            // Remover arquivo original apenas se a cópia foi bem-sucedida e os arquivos são diferentes
            if ($sourceFile !== $targetFile && file_exists($targetFile)) {
                @unlink($sourceFile);
            }
            
            return [
                'success' => true,
                'message' => "Arquivo movido com sucesso para: $targetFile"
            ];
        } else {
            return [
                'success' => false,
                'error' => "Falha ao copiar arquivo de $sourceFile para $targetFile"
            ];
        }
    } else {
        return [
            'success' => false,
            'error' => "Arquivo de origem não existe: $sourceFile"
        ];
    }
}

// Obter todos os clientes
$clientesQuery = "SELECT id, nome_cliente FROM clientes ORDER BY nome_cliente";
$clientesStmt = $conn->prepare($clientesQuery);
$clientesStmt->execute();
$clientes = $clientesStmt->fetchAll(PDO::FETCH_ASSOC);

// Obter todas as postagens com arquivos
$postagensQuery = "SELECT p.id, p.cliente_id, c.nome_cliente, p.arquivos 
                   FROM postagens p 
                   JOIN clientes c ON p.cliente_id = c.id 
                   WHERE p.arquivos IS NOT NULL AND p.arquivos != ''";
$postagensStmt = $conn->prepare($postagensQuery);
$postagensStmt->execute();
$postagens = $postagensStmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar contadores
$totalArquivos = 0;
$arquivosCorrigidos = 0;
$arquivosComErro = 0;
$logs = [];

// Verificar se o usuário confirmou a ação
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

// Cabeçalho
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Correção de Uploads de Arquivos</h1>
    <p class="text-muted">Esta ferramenta verifica e corrige os diretórios de upload, garantindo que todos os arquivos estejam no local correto.</p>
    
    <?php if (!$confirmed): ?>
    <div class="alert alert-warning">
        <h4 class="alert-heading">Atenção!</h4>
        <p>Esta ferramenta irá verificar todos os arquivos de upload e movê-los para os diretórios corretos seguindo o padrão:</p>
        <p><code>www.meusite.com.br/arquivos/[nomedo cliente sem espaço e minusculo]/[imagem ou video]/[nome do arquivo]</code></p>
        <p>Este processo pode levar algum tempo dependendo da quantidade de arquivos. Deseja continuar?</p>
        <form method="post">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="btn btn-primary">Sim, corrigir os uploads</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <?php else: ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Verificação de Diretórios Base</h5>
        </div>
        <div class="card-body">
            <?php
            // Verificar diretório base de arquivos
            $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/arquivos/';
            $baseDirAlt = dirname(dirname(__FILE__)) . '/arquivos/';
            
            echo '<h6>Diretório base principal</h6>';
            echo '<p><code>' . $baseDir . '</code></p>';
            
            if (createDirectoryWithPermissions($baseDir)) {
                echo '<div class="alert alert-success">Diretório base principal criado/verificado com sucesso.</div>';
            } else {
                echo '<div class="alert alert-danger">Falha ao criar/verificar diretório base principal.</div>';
                
                echo '<h6>Tentando diretório base alternativo</h6>';
                echo '<p><code>' . $baseDirAlt . '</code></p>';
                
                if (createDirectoryWithPermissions($baseDirAlt)) {
                    echo '<div class="alert alert-success">Diretório base alternativo criado/verificado com sucesso.</div>';
                    $baseDir = $baseDirAlt;
                } else {
                    echo '<div class="alert alert-danger">Falha ao criar/verificar diretório base alternativo.</div>';
                }
            }
            
            // Verificar diretório de uploads temporários
            $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            echo '<h6>Diretório de uploads temporários</h6>';
            echo '<p><code>' . $uploadsDir . '</code></p>';
            
            if (createDirectoryWithPermissions($uploadsDir)) {
                echo '<div class="alert alert-success">Diretório de uploads temporários criado/verificado com sucesso.</div>';
            } else {
                echo '<div class="alert alert-danger">Falha ao criar/verificar diretório de uploads temporários.</div>';
            }
            ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Criação de Diretórios por Cliente</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Diretório de Imagens</th>
                        <th>Diretório de Vídeos</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): 
                        $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente['nome_cliente']));
                        if (empty($cliente_slug)) {
                            $cliente_slug = 'cliente' . $cliente['id'];
                        }
                        
                        $imagemDir = $baseDir . $cliente_slug . '/imagem/';
                        $videoDir = $baseDir . $cliente_slug . '/video/';
                        
                        $imagemStatus = createDirectoryWithPermissions($imagemDir);
                        $videoStatus = createDirectoryWithPermissions($videoDir);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($cliente['nome_cliente']) ?></td>
                        <td><code><?= $imagemDir ?></code></td>
                        <td><code><?= $videoDir ?></code></td>
                        <td>
                            <?php if ($imagemStatus && $videoStatus): ?>
                                <span class="badge bg-success">Sucesso</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Falha</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Verificação e Correção de Arquivos</h5>
        </div>
        <div class="card-body">
            <?php
            if (count($postagens) === 0) {
                echo '<div class="alert alert-info">Nenhuma postagem com arquivos encontrada.</div>';
            } else {
                echo '<div class="alert alert-info">Processando ' . count($postagens) . ' postagens com arquivos...</div>';
                
                echo '<div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="progressBar"></div>
                </div>';
                
                echo '<div id="progressStatus" class="text-center mb-3">0/' . count($postagens) . '</div>';
                
                echo '<div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Postagem</th>
                                <th>Cliente</th>
                                <th>Arquivo</th>
                                <th>Caminho Original</th>
                                <th>Novo Caminho</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="resultTable">';
                
                // Processar cada postagem
                $counter = 0;
                foreach ($postagens as $postagem) {
                    $counter++;
                    $progress = round(($counter / count($postagens)) * 100);
                    
                    // Atualizar barra de progresso via JavaScript
                    echo '<script>
                        document.getElementById("progressBar").style.width = "' . $progress . '%";
                        document.getElementById("progressStatus").innerText = "' . $counter . '/' . count($postagens) . '";
                    </script>';
                    
                    // Forçar saída de buffer para mostrar o progresso
                    ob_flush();
                    flush();
                    
                    // Processar arquivos da postagem
                    $arquivos = json_decode($postagem['arquivos'], true);
                    if (is_array($arquivos)) {
                        foreach ($arquivos as $arquivo) {
                            $totalArquivos++;
                            
                            // Obter informações do arquivo
                            $nomeArquivo = $arquivo['name'] ?? '';
                            $caminhoOriginal = $arquivo['path'] ?? '';
                            $urlOriginal = $arquivo['url'] ?? '';
                            
                            if (empty($nomeArquivo) || empty($caminhoOriginal)) {
                                $arquivosComErro++;
                                echo '<tr class="table-danger">
                                    <td>' . $postagem['id'] . '</td>
                                    <td>' . htmlspecialchars($postagem['nome_cliente']) . '</td>
                                    <td>' . htmlspecialchars($nomeArquivo) . '</td>
                                    <td>' . htmlspecialchars($caminhoOriginal) . '</td>
                                    <td>-</td>
                                    <td><span class="badge bg-danger">Dados incompletos</span></td>
                                </tr>';
                                continue;
                            }
                            
                            // Determinar tipo de arquivo (imagem ou vídeo)
                            $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
                            $tipoArquivo = in_array($extensao, ['jpg', 'jpeg', 'png', 'gif']) ? 'imagem' : 'video';
                            
                            // Gerar caminho correto
                            $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($postagem['nome_cliente']));
                            if (empty($cliente_slug)) {
                                $cliente_slug = 'cliente' . $postagem['cliente_id'];
                            }
                            
                            $novoCaminho = $baseDir . $cliente_slug . '/' . $tipoArquivo . '/' . $nomeArquivo;
                            $novaUrl = rtrim(FILES_BASE_URL, '/') . '/arquivos/' . $cliente_slug . '/' . $tipoArquivo . '/' . $nomeArquivo;
                            
                            // Verificar se o arquivo já está no local correto
                            if ($caminhoOriginal === $novoCaminho) {
                                echo '<tr class="table-success">
                                    <td>' . $postagem['id'] . '</td>
                                    <td>' . htmlspecialchars($postagem['nome_cliente']) . '</td>
                                    <td>' . htmlspecialchars($nomeArquivo) . '</td>
                                    <td>' . htmlspecialchars($caminhoOriginal) . '</td>
                                    <td>' . htmlspecialchars($novoCaminho) . '</td>
                                    <td><span class="badge bg-success">Já correto</span></td>
                                </tr>';
                                continue;
                            }
                            
                            // Mover arquivo para o local correto
                            $resultado = moveFileToCorrectLocation($caminhoOriginal, $novoCaminho);
                            
                            if ($resultado['success']) {
                                $arquivosCorrigidos++;
                                
                                // Atualizar o registro no banco de dados
                                $arquivos[array_search($arquivo, $arquivos)] = [
                                    'name' => $nomeArquivo,
                                    'path' => $novoCaminho,
                                    'url' => $novaUrl
                                ];
                                
                                echo '<tr class="table-warning">
                                    <td>' . $postagem['id'] . '</td>
                                    <td>' . htmlspecialchars($postagem['nome_cliente']) . '</td>
                                    <td>' . htmlspecialchars($nomeArquivo) . '</td>
                                    <td>' . htmlspecialchars($caminhoOriginal) . '</td>
                                    <td>' . htmlspecialchars($novoCaminho) . '</td>
                                    <td><span class="badge bg-warning">Corrigido</span></td>
                                </tr>';
                            } else {
                                $arquivosComErro++;
                                echo '<tr class="table-danger">
                                    <td>' . $postagem['id'] . '</td>
                                    <td>' . htmlspecialchars($postagem['nome_cliente']) . '</td>
                                    <td>' . htmlspecialchars($nomeArquivo) . '</td>
                                    <td>' . htmlspecialchars($caminhoOriginal) . '</td>
                                    <td>' . htmlspecialchars($novoCaminho) . '</td>
                                    <td><span class="badge bg-danger">Erro: ' . $resultado['error'] . '</span></td>
                                </tr>';
                            }
                            
                            // Atualizar a postagem no banco de dados
                            $updateQuery = "UPDATE postagens SET arquivos = :arquivos WHERE id = :id";
                            $updateStmt = $conn->prepare($updateQuery);
                            $updateStmt->bindValue(':arquivos', json_encode($arquivos), PDO::PARAM_STR);
                            $updateStmt->bindValue(':id', $postagem['id'], PDO::PARAM_INT);
                            $updateStmt->execute();
                        }
                    }
                }
                
                echo '</tbody>
                    </table>
                </div>';
            }
            ?>
            
            <div class="alert alert-info mt-4">
                <h5>Resumo</h5>
                <ul>
                    <li>Total de arquivos processados: <?= $totalArquivos ?></li>
                    <li>Arquivos corrigidos: <?= $arquivosCorrigidos ?></li>
                    <li>Arquivos com erro: <?= $arquivosComErro ?></li>
                </ul>
            </div>
            
            <div class="mt-3">
                <a href="index.php" class="btn btn-primary">Voltar para o Dashboard</a>
                <a href="diagnostico_upload.php" class="btn btn-info">Executar Diagnóstico</a>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php
// Registrar a ação no log
$acao = "Correção de uploads de arquivos";
$detalhes = "Total: $totalArquivos, Corrigidos: $arquivosCorrigidos, Erros: $arquivosComErro";
$modulo = "Uploads";
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

include_once '../includes/footer.php';
?>
