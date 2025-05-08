<?php
require_once 'config/config.php';
require_once 'config/db.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Verificar se o usuário é administrador
if (!isAdmin()) {
    setFlashMessage('danger', 'Você não tem permissão para acessar esta página.');
    redirect('dashboard.php');
}

// Definir página atual para o menu
$currentPage = 'logs';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Verificar se a tabela historico existe
$tabela_existe = false;
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'historico'");
    $tabela_existe = $check_table->rowCount() > 0;
} catch (PDOException $e) {
    setFlashMessage('danger', 'Erro ao verificar tabela de histórico: ' . $e->getMessage());
}

// Se a tabela não existir, exibir mensagem e criar link para o script de criação
if (!$tabela_existe) {
    include_once 'includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="alert alert-warning">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Tabela de Logs não encontrada!</h4>
            <p>A tabela de histórico (logs) não foi encontrada no banco de dados. É necessário executar o script de criação da tabela.</p>
            <hr>
            <p class="mb-0">
                <a href="admin/create_historico_table.php" class="btn btn-primary">Criar Tabela de Logs</a>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Voltar para o Dashboard</a>
            </p>
        </div>
    </div>
    <?php
    include_once 'includes/footer.php';
    exit;
}

// Parâmetros de paginação
$registros_por_pagina = 50;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Filtros
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
$filtro_acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$filtro_modulo = isset($_GET['modulo']) ? $_GET['modulo'] : '';

// Construir a consulta SQL com filtros
$sql_where = [];
$params = [];

if (!empty($filtro_usuario)) {
    $sql_where[] = "h.usuario_id = ?";
    $params[] = $filtro_usuario;
}

if (!empty($filtro_acao)) {
    $sql_where[] = "h.acao = ?";
    $params[] = $filtro_acao;
}

if (!empty($filtro_data_inicio)) {
    $sql_where[] = "h.data_hora >= ?";
    $params[] = $filtro_data_inicio . ' 00:00:00';
}

if (!empty($filtro_data_fim)) {
    $sql_where[] = "h.data_hora <= ?";
    $params[] = $filtro_data_fim . ' 23:59:59';
}

if (!empty($filtro_modulo)) {
    $sql_where[] = "h.modulo = ?";
    $params[] = $filtro_modulo;
}

$where_clause = !empty($sql_where) ? " WHERE " . implode(" AND ", $sql_where) : "";

// Consulta para obter o total de registros
$sql_count = "SELECT COUNT(*) FROM historico h" . $where_clause;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->execute($params);
} else {
    $stmt_count->execute();
}
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta para obter os logs com paginação
$sql = "SELECT h.*, u.nome as usuario_nome 
        FROM historico h
        LEFT JOIN usuarios u ON h.usuario_id = u.id
        $where_clause
        ORDER BY h.data_hora DESC
        LIMIT $registros_por_pagina OFFSET $offset";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlashMessage('danger', 'Erro ao buscar logs: ' . $e->getMessage());
    $logs = [];
}

// Obter lista de usuários para o filtro
try {
    $sql_usuarios = "SELECT id, nome FROM usuarios ORDER BY nome";
    $stmt_usuarios = $conn->prepare($sql_usuarios);
    $stmt_usuarios->execute();
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
}

// Obter lista de ações para o filtro
try {
    $sql_acoes = "SELECT DISTINCT acao FROM historico ORDER BY acao";
    $stmt_acoes = $conn->prepare($sql_acoes);
    $stmt_acoes->execute();
    $acoes = $stmt_acoes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $acoes = [];
}

// Obter lista de módulos para o filtro
try {
    $sql_modulos = "SELECT DISTINCT modulo FROM historico WHERE modulo IS NOT NULL ORDER BY modulo";
    $stmt_modulos = $conn->prepare($sql_modulos);
    $stmt_modulos->execute();
    $modulos = $stmt_modulos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $modulos = [];
}

// Incluir o cabeçalho
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Logs do Sistema</h1>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="logs.php" class="row g-3">
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <select class="form-select" id="usuario" name="usuario">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id'] ?>" <?= $filtro_usuario == $usuario['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usuario['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="acao" class="form-label">Ação</label>
                    <select class="form-select" id="acao" name="acao">
                        <option value="">Todas</option>
                        <?php foreach ($acoes as $acao): ?>
                            <option value="<?= $acao['acao'] ?>" <?= $filtro_acao == $acao['acao'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acao['acao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="modulo" class="form-label">Módulo</label>
                    <select class="form-select" id="modulo" name="modulo">
                        <option value="">Todos</option>
                        <?php foreach ($modulos as $modulo): ?>
                            <option value="<?= $modulo['modulo'] ?>" <?= $filtro_modulo == $modulo['modulo'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($modulo['modulo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $filtro_data_inicio ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $filtro_data_fim ?>">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="logs.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Logs -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Logs</h5>
                <span class="badge bg-primary"><?= $total_registros ?> registros encontrados</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (count($logs) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Módulo</th>
                                <th>Detalhes</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                                    <td><?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($log['acao'] ?? 'N/A') ?></span></td>
                                    <td><?= htmlspecialchars($log['modulo'] ?? 'N/A') ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                data-bs-toggle="modal" data-bs-target="#logModal<?= $log['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td><?= htmlspecialchars($log['ip'] ?? 'N/A') ?></td>
                                </tr>
                                
                                <!-- Modal para exibir detalhes do log -->
                                <div class="modal fade" id="logModal<?= $log['id'] ?>" tabindex="-1" aria-labelledby="logModalLabel<?= $log['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="logModalLabel<?= $log['id'] ?>">Detalhes do Log #<?= $log['id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Data/Hora:</strong> <?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></p>
                                                        <p><strong>Usuário:</strong> <?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?></p>
                                                        <p><strong>Ação:</strong> <?= htmlspecialchars($log['acao'] ?? 'N/A') ?></p>
                                                        <p><strong>Módulo:</strong> <?= htmlspecialchars($log['modulo'] ?? 'N/A') ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>IP:</strong> <?= htmlspecialchars($log['ip'] ?? 'N/A') ?></p>
                                                        <p><strong>ID do Usuário:</strong> <?= $log['usuario_id'] ?? 'N/A' ?></p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6>Detalhes:</h6>
                                                        <?php if (!empty($log['detalhes'])): ?>
                                                            <div class="p-3 bg-light rounded">
                                                                <?php 
                                                                // Verificar se é um JSON válido
                                                                $json = json_decode($log['detalhes'], true);
                                                                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                                                    echo '<pre class="mb-0">' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                                                } else {
                                                                    echo '<p class="mb-0">' . nl2br(htmlspecialchars($log['detalhes'])) . '</p>';
                                                                }
                                                                ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted">Nenhum detalhe disponível</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginação de logs">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?>&usuario=<?= $filtro_usuario ?>&acao=<?= $filtro_acao ?>&modulo=<?= $filtro_modulo ?>&data_inicio=<?= $filtro_data_inicio ?>&data_fim=<?= $filtro_data_fim ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                                <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?>&usuario=<?= $filtro_usuario ?>&acao=<?= $filtro_acao ?>&modulo=<?= $filtro_modulo ?>&data_inicio=<?= $filtro_data_inicio ?>&data_fim=<?= $filtro_data_fim ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?>&usuario=<?= $filtro_usuario ?>&acao=<?= $filtro_acao ?>&modulo=<?= $filtro_modulo ?>&data_inicio=<?= $filtro_data_inicio ?>&data_fim=<?= $filtro_data_fim ?>" aria-label="Próximo">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Nenhum registro de log encontrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
