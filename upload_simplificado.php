<?php
/**
 * Script de upload simplificado e robusto
 * Funciona independentemente das configurações do servidor
 */

// Incluir arquivos necessários
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Função para criar diretório com permissões corretas
function criarDiretorio($caminho) {
    if (file_exists($caminho)) {
        return true;
    }
    
    return @mkdir($caminho, 0755, true);
}

// Função para upload de arquivo com múltiplas tentativas
function uploadArquivo($arquivo_tmp, $destino) {
    // Garantir que o diretório de destino existe
    $diretorio = dirname($destino);
    if (!criarDiretorio($diretorio)) {
        return [
            'sucesso' => false,
            'erro' => "Não foi possível criar o diretório: $diretorio"
        ];
    }
    
    // Método 1: move_uploaded_file
    if (@move_uploaded_file($arquivo_tmp, $destino)) {
        @chmod($destino, 0644);
        return [
            'sucesso' => true,
            'metodo' => 'move_uploaded_file'
        ];
    }
    
    // Método 2: copy
    if (@copy($arquivo_tmp, $destino)) {
        @chmod($destino, 0644);
        return [
            'sucesso' => true,
            'metodo' => 'copy'
        ];
    }
    
    // Método 3: file_put_contents
    $conteudo = @file_get_contents($arquivo_tmp);
    if ($conteudo !== false && @file_put_contents($destino, $conteudo) !== false) {
        @chmod($destino, 0644);
        return [
            'sucesso' => true,
            'metodo' => 'file_put_contents'
        ];
    }
    
    return [
        'sucesso' => false,
        'erro' => "Falha em todos os métodos de upload"
    ];
}

// Função para determinar o melhor caminho de upload
function obterMelhorCaminho() {
    $caminhos = [];
    
    // Opção 1: DOCUMENT_ROOT
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $caminhos[] = [
            'arquivos' => $_SERVER['DOCUMENT_ROOT'] . '/arquivos/',
            'uploads' => $_SERVER['DOCUMENT_ROOT'] . '/uploads/'
        ];
    }
    
    // Opção 2: Diretório do script
    $caminhos[] = [
        'arquivos' => dirname(__FILE__) . '/arquivos/',
        'uploads' => dirname(__FILE__) . '/uploads/'
    ];
    
    // Opção 3: Diretório atual
    $caminhos[] = [
        'arquivos' => getcwd() . '/arquivos/',
        'uploads' => getcwd() . '/uploads/'
    ];
    
    // Testar cada caminho
    foreach ($caminhos as $caminho) {
        // Tentar criar os diretórios
        $arquivos_ok = criarDiretorio($caminho['arquivos']);
        $uploads_ok = criarDiretorio($caminho['uploads']);
        
        if ($arquivos_ok && $uploads_ok) {
            return $caminho;
        }
    }
    
    // Se nenhum caminho funcionar, usar o diretório temporário do sistema
    $temp_dir = sys_get_temp_dir();
    return [
        'arquivos' => $temp_dir . '/arquivos/',
        'uploads' => $temp_dir . '/uploads/'
    ];
}

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';
$arquivos_enviados = [];

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se o cliente foi selecionado
    if (empty($_POST['cliente_id'])) {
        $mensagem = 'Por favor, selecione um cliente.';
        $tipo_mensagem = 'danger';
    } else {
        $cliente_id = $_POST['cliente_id'];
        
        // Obter informações do cliente
        $query = "SELECT id, nome_cliente FROM clientes WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $cliente_id, PDO::PARAM_INT);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            $mensagem = 'Cliente não encontrado.';
            $tipo_mensagem = 'danger';
        } else {
            // Obter o melhor caminho para upload
            $melhor_caminho = obterMelhorCaminho();
            
            // Processar upload de arquivo único
            if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $arquivo = $_FILES['arquivo'];
                
                // Determinar o tipo de arquivo
                $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $tipo_arquivo = in_array($extensao, ['jpg', 'jpeg', 'png', 'gif']) ? 'imagem' : 'video';
                
                // Gerar nome de arquivo único
                $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente['nome_cliente']));
                if (empty($cliente_slug)) {
                    $cliente_slug = 'cliente' . $cliente['id'];
                }
                
                $timestamp = date('mdYHis') . substr(microtime(), 2, 3);
                $nome_arquivo = $cliente_slug . '_' . $timestamp . '.' . $extensao;
                
                // Definir caminho de destino
                $diretorio_destino = $melhor_caminho['arquivos'] . $cliente_slug . '/' . $tipo_arquivo . '/';
                $caminho_destino = $diretorio_destino . $nome_arquivo;
                
                // Fazer upload do arquivo
                $resultado = uploadArquivo($arquivo['tmp_name'], $caminho_destino);
                
                if ($resultado['sucesso']) {
                    // Gerar URL do arquivo
                    $url_arquivo = rtrim(FILES_BASE_URL, '/') . '/arquivos/' . $cliente_slug . '/' . $tipo_arquivo . '/' . $nome_arquivo;
                    
                    $arquivos_enviados[] = [
                        'nome' => $nome_arquivo,
                        'caminho' => $caminho_destino,
                        'url' => $url_arquivo,
                        'tipo' => $tipo_arquivo
                    ];
                    
                    $mensagem = 'Arquivo enviado com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = 'Falha ao enviar o arquivo: ' . ($resultado['erro'] ?? 'Erro desconhecido');
                    $tipo_mensagem = 'danger';
                }
            } else {
                $mensagem = 'Por favor, selecione um arquivo para upload.';
                $tipo_mensagem = 'danger';
            }
        }
    }
}

// Obter lista de clientes
$query = "SELECT id, nome_cliente FROM clientes ORDER BY nome_cliente";
$stmt = $conn->prepare($query);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cabeçalho
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <h1>Upload Simplificado</h1>
    <p class="text-muted">Use este formulário para testar o upload de arquivos de forma simplificada.</p>
    
    <?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?= $tipo_mensagem ?>">
        <?= $mensagem ?>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Formulário de Upload</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="cliente_id" class="form-label">Cliente</label>
                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome_cliente']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="arquivo" class="form-label">Arquivo</label>
                    <input type="file" class="form-control" id="arquivo" name="arquivo" required>
                    <div class="form-text">Selecione uma imagem ou vídeo para upload.</div>
                </div>
                
                <button type="submit" class="btn btn-primary">Enviar Arquivo</button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($arquivos_enviados)): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Arquivos Enviados</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Caminho</th>
                        <th>URL</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arquivos_enviados as $arquivo): ?>
                    <tr>
                        <td><?= htmlspecialchars($arquivo['nome']) ?></td>
                        <td><?= ucfirst($arquivo['tipo']) ?></td>
                        <td><code><?= htmlspecialchars($arquivo['caminho']) ?></code></td>
                        <td><code><?= htmlspecialchars($arquivo['url']) ?></code></td>
                        <td>
                            <a href="<?= htmlspecialchars($arquivo['url']) ?>" target="_blank" class="btn btn-sm btn-primary">Visualizar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mt-3 mb-5">
        <a href="index.php" class="btn btn-primary">Voltar para o Formulário Principal</a>
        <a href="admin/debug_upload.php" class="btn btn-info">Depuração Avançada</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
