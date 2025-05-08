<?php
// Iniciar output buffering
ob_start();

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect('login.php');
}

// Define tempo de login se não estiver setado
if (isset($_SESSION['user_id']) && !isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
}

// Verifica inatividade (5 minutos)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
    session_unset();
    session_destroy();
    redirect('login.php?reason=inactivity');
}
$_SESSION['last_activity'] = time();

// Calcula o tempo logado
$loginTime = $_SESSION['login_time'] ?? time();
$timeLoggedIn = time() - $loginTime;
$hours = floor($timeLoggedIn / 3600);
$minutes = floor(($timeLoggedIn % 3600) / 60);
$seconds = $timeLoggedIn % 60;
$timeLoggedInString = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

// Agora carrega o HTML
require_once 'includes/header.php';

// Check if user has permission
requirePermission('Editor');

// Get database connection
$database = new Database();
$conn = $database->connect();

// Handle delete request
if (isset($_POST['delete']) && isset($_POST['client_id'])) {
    try {
        $query = "DELETE FROM clientes WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_POST['client_id']);
        $stmt->execute();
        
        setFlashMessage('success', 'Cliente excluído com sucesso!');
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        
        if ($e->getCode() == '23000') { // Foreign key constraint error
            setFlashMessage('danger', 'Não é possível excluir este cliente porque existem postagens associadas a ele.');
        } else {
            setFlashMessage('danger', 'Erro ao excluir cliente: ' . $e->getMessage());
        }
    }
    
    // Redirect to refresh the page
    redirect('clientes_visualizar.php');
}

// Handle toggle active status
if (isset($_POST['toggle_status']) && isset($_POST['client_id'])) {
    try {
        // First get current status
        $query = "SELECT ativo FROM clientes WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_POST['client_id']);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Toggle status
        $newStatus = ($client && isset($client['ativo'])) ? !$client['ativo'] : 1;
        
        $query = "UPDATE clientes SET ativo = :ativo WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_POST['client_id']);
        $stmt->bindParam(':ativo', $newStatus, PDO::PARAM_INT);
        $stmt->execute();
        
        $statusText = $newStatus ? 'ativado' : 'desativado';
        setFlashMessage('success', "Cliente {$statusText} com sucesso!");
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        setFlashMessage('danger', 'Erro ao alterar status do cliente: ' . $e->getMessage());
    }
    
    // Redirect to refresh the page
    redirect('clientes_visualizar.php');
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 100; // Alterado para 100 clientes por página
$offset = ($page - 1) * $perPage;

// Get search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get sort parameters
$sortColumn = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'nome_cliente';
$sortDirection = isset($_GET['direction']) && strtolower($_GET['direction']) === 'desc' ? 'DESC' : 'ASC';

// Valid columns for sorting
$validColumns = ['nome_cliente', 'instagram', 'id_grupo', 'id_instagram', 'conta_anuncio'];
if (!in_array($sortColumn, $validColumns)) {
    $sortColumn = 'nome_cliente';
}

// Build the query
$whereClause = "";
$queryParams = [];

// Add search condition if search parameter exists
if (!empty($search)) {
    $whereClause = " WHERE nome_cliente LIKE ? OR instagram LIKE ?";
    $searchParam = "%{$search}%";
    $queryParams[] = $searchParam;
    $queryParams[] = $searchParam;
}

// Base queries
$countQuery = "SELECT COUNT(*) as total FROM clientes" . $whereClause;
$query = "SELECT * FROM clientes" . $whereClause;

// Add order by
$query .= " ORDER BY {$sortColumn} {$sortDirection}";

// Execute count query
$countStmt = $conn->prepare($countQuery);
if (!empty($queryParams)) {
    $countStmt->execute($queryParams);
} else {
    $countStmt->execute();
}
$totalClients = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$totalPages = ceil($totalClients / $perPage);

// Add limit for pagination
$query .= " LIMIT ?, ?";
$queryParams[] = $offset;
$queryParams[] = $perPage;

// Execute main query
$stmt = $conn->prepare($query);
$stmt->execute($queryParams);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get post count for each client
$clientPostCounts = [];
foreach ($clients as $client) {
    $query = "SELECT COUNT(*) as count FROM postagens WHERE cliente_id = :cliente_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cliente_id', $client['id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $clientPostCounts[$client['id']] = $result['count'];
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">Clientes</h1>
            <p class="text-secondary">Visualize e gerencie todos os clientes cadastrados no sistema.</p>
        </div>
        <div class="col-md-4 text-end d-flex align-items-center justify-content-end">
            <a href="clientes.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Novo Cliente
            </a>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="searchForm" action="clientes_visualizar.php" method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Buscar por nome ou Instagram..." name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!empty($search)): ?>
                            <a href="clientes_visualizar.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Limpar Filtros
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Lista de Clientes</strong>
                        <span class="badge bg-primary"><?= $totalClients ?> clientes</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($clients) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="clientsTable">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="clientes_visualizar.php?sort=nome_cliente&direction=<?= $sortColumn === 'nome_cliente' && $sortDirection === 'ASC' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-dark">
                                            Nome
                                            <?php if ($sortColumn === 'nome_cliente'): ?>
                                                <i class="fas fa-sort-<?= $sortDirection === 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="clientes_visualizar.php?sort=instagram&direction=<?= $sortColumn === 'instagram' && $sortDirection === 'ASC' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-dark">
                                            Instagram
                                            <?php if ($sortColumn === 'instagram'): ?>
                                                <i class="fas fa-sort-<?= $sortDirection === 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="clientes_visualizar.php?sort=id_grupo&direction=<?= $sortColumn === 'id_grupo' && $sortDirection === 'ASC' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-dark">
                                            Grupo
                                            <?php if ($sortColumn === 'id_grupo'): ?>
                                                <i class="fas fa-sort-<?= $sortDirection === 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="clientes_visualizar.php?sort=id_instagram&direction=<?= $sortColumn === 'id_instagram' && $sortDirection === 'ASC' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-dark">
                                            ID IG
                                            <?php if ($sortColumn === 'id_instagram'): ?>
                                                <i class="fas fa-sort-<?= $sortDirection === 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="clientes_visualizar.php?sort=conta_anuncio&direction=<?= $sortColumn === 'conta_anuncio' && $sortDirection === 'ASC' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="text-decoration-none text-dark">
                                            Anúncio
                                            <?php if ($sortColumn === 'conta_anuncio'): ?>
                                                <i class="fas fa-sort-<?= $sortDirection === 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>Postagens</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <?php $isActive = isset($client['ativo']) ? (bool)$client['ativo'] : true; ?>
                                <tr class="<?= $isActive ? '' : 'client-inactive' ?>">
                                    <td><?= htmlspecialchars($client['nome_cliente']) ?></td>
                                    <td>
                                        <a href="https://instagram.com/<?= htmlspecialchars($client['instagram']) ?>" target="_blank" class="text-decoration-none">
                                            @<?= htmlspecialchars($client['instagram']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($client['id_grupo']) ?></td>
                                    <td><?= htmlspecialchars($client['id_instagram']) ?></td>
                                    <td><?= htmlspecialchars($client['conta_anuncio']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= $clientPostCounts[$client['id']] ?> postagens
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <!-- Visualizar -->
                                            <button type="button" class="btn btn-view btn-sm me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal" 
                                                    data-client-id="<?= $client['id'] ?>"
                                                    data-client-name="<?= htmlspecialchars($client['nome_cliente']) ?>"
                                                    data-client-instagram="<?= htmlspecialchars($client['instagram']) ?>"
                                                    data-client-grupo="<?= htmlspecialchars($client['id_grupo']) ?>"
                                                    data-client-instagram-id="<?= htmlspecialchars($client['id_instagram']) ?>"
                                                    data-client-anuncio="<?= htmlspecialchars($client['conta_anuncio']) ?>"
                                                    title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Editar -->
                                            <a href="clientes.php?id=<?= $client['id'] ?>" class="btn btn-edit btn-sm me-2" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Excluir -->
                                            <button type="button" class="btn btn-danger btn-sm me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal" 
                                                    data-client-id="<?= $client['id'] ?>" 
                                                    data-client-name="<?= htmlspecialchars($client['nome_cliente']) ?>"
                                                    title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            
                                            <!-- Agendar Postagem -->
                                            <a href="index.php?cliente_id=<?= $client['id'] ?>" class="btn btn-schedule btn-sm me-2 <?= $isActive ? '' : 'disabled' ?>" 
                                               title="<?= $isActive ? 'Agendar Postagem' : 'Cliente inativo' ?>">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            
                                            <!-- Ativar/Desativar -->
                                            <form method="POST" action="clientes_visualizar.php" class="d-inline">
                                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <button type="submit" class="btn <?= $isActive ? 'btn-toggle-on' : 'btn-toggle-off' ?> btn-sm me-2" 
                                                        title="<?= $isActive ? 'Desativar Cliente' : 'Ativar Cliente' ?>">
                                                    <i class="fas fa-toggle-<?= $isActive ? 'on' : 'off' ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Histórico -->
                                            <button type="button" class="btn btn-history btn-sm me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#historyModal" 
                                                    data-client-id="<?= $client['id'] ?>"
                                                    data-client-name="<?= htmlspecialchars($client['nome_cliente']) ?>"
                                                    data-client-instagram="<?= htmlspecialchars($client['instagram']) ?>"
                                                    title="Histórico de Postagens">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            
                                            <!-- Copiar Dados -->
                                            <button type="button" class="btn btn-copy btn-sm" 
                                                    onclick="copyClientData('<?= htmlspecialchars($client['nome_cliente']) ?>', '<?= htmlspecialchars($client['instagram']) ?>', '<?= htmlspecialchars($client['id_instagram']) ?>', '<?= htmlspecialchars($client['id_grupo']) ?>')"
                                                    title="Copiar Dados">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Navegação de páginas" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="clientes_visualizar.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($sortColumn) ? '&sort=' . $sortColumn . '&direction=' . $sortDirection : '' ?>" aria-label="Anterior">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="clientes_visualizar.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($sortColumn) ? '&sort=' . $sortColumn . '&direction=' . $sortDirection : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="clientes_visualizar.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($sortColumn) ? '&sort=' . $sortColumn . '&direction=' . $sortDirection : '' ?>" aria-label="Próximo">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x mb-3 text-secondary"></i>
                        <p class="mb-0">Nenhum cliente cadastrado ainda.</p>
                        <a href="clientes.php" class="btn btn-primary mt-3">Cadastrar Primeiro Cliente</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Visualização -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="viewModalLabel">Visualizar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-user me-2"></i> Informações Básicas
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Nome:</strong> <span id="view-name"></span></p>
                                <p class="mb-2"><strong>Instagram:</strong> @<span id="view-instagram"></span></p>
                                <p class="mb-0"><strong>Grupo:</strong> <span id="view-grupo"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3 border-success">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-cog me-2"></i> Informações Técnicas
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>ID Instagram:</strong> <span id="view-instagram-id"></span></p>
                                <p class="mb-0"><strong>Conta Anúncio:</strong> <span id="view-anuncio"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="view-edit-link" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i> Editar
                </a>
                <a href="#" id="view-schedule-link" class="btn btn-success">
                    <i class="fas fa-calendar-plus me-2"></i> Agendar Postagem
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este cliente?</p>
            </div>
            <div class="modal-footer">
                <form action="clientes_visualizar.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" id="clientId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para histórico de postagens do cliente -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="historyModalLabel">Histórico de Postagens</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="client-history-header mb-3">
                    <h4 id="historyClientName" class="mb-2"></h4>
                    <p class="text-muted">@<span id="historyClientInstagram"></span></p>
                </div>
                
                <div id="historyContent" class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Data da Postagem</th>
                                <th>Tipo</th>
                                <th>Formato</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- Conteúdo será preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div id="noHistoryMessage" class="alert alert-info d-none">
                    <i class="fas fa-info-circle me-2"></i> Nenhuma postagem registrada para este cliente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="#" id="history-schedule-link" class="btn btn-primary">Agendar Nova Postagem</a>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar modais e tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar modal de visualização
    var viewModal = document.getElementById('viewModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var clientId = button.getAttribute('data-client-id');
            var clientName = button.getAttribute('data-client-name');
            var clientInstagram = button.getAttribute('data-client-instagram');
            var clientGrupo = button.getAttribute('data-client-grupo');
            var clientInstagramId = button.getAttribute('data-client-instagram-id');
            var clientAnuncio = button.getAttribute('data-client-anuncio');
            
            var modal = this;
            modal.querySelector('.modal-title').textContent = 'Visualizar Cliente: ' + clientName;
            modal.querySelector('#view-name').textContent = clientName;
            modal.querySelector('#view-instagram').textContent = clientInstagram;
            modal.querySelector('#view-grupo').textContent = clientGrupo;
            modal.querySelector('#view-instagram-id').textContent = clientInstagramId;
            modal.querySelector('#view-anuncio').textContent = clientAnuncio;
            
            modal.querySelector('#view-edit-link').href = 'clientes.php?id=' + clientId;
            modal.querySelector('#view-schedule-link').href = 'index.php?cliente_id=' + clientId;
        });
    }
    
    // Configurar modal de exclusão
    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var clientId = button.getAttribute('data-client-id');
            var clientName = button.getAttribute('data-client-name');
            
            var modal = this;
            modal.querySelector('.modal-body p').textContent = 'Tem certeza que deseja excluir o cliente "' + clientName + '"? Esta ação não pode ser desfeita.';
            modal.querySelector('#clientId').value = clientId;
        });
    }
    
    // Configurar modal de histórico
    var historyModal = document.getElementById('historyModal');
    if (historyModal) {
        historyModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var clientId = button.getAttribute('data-client-id');
            var clientName = button.getAttribute('data-client-name');
            var clientInstagram = button.getAttribute('data-client-instagram');
            
            document.getElementById('historyClientName').textContent = clientName;
            document.getElementById('historyClientInstagram').textContent = clientInstagram;
            document.getElementById('history-schedule-link').href = 'index.php?cliente_id=' + clientId;
            
            // Mostrar indicador de carregamento
            var historyTableBody = document.getElementById('historyTableBody');
            historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></td></tr>';
            
            // Fazer requisição AJAX para buscar o histórico
            fetch('ajax/get_client_history.php?client_id=' + clientId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao carregar histórico');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.posts && data.posts.length > 0) {
                        // Preencher a tabela com os dados
                        historyTableBody.innerHTML = '';
                        data.posts.forEach(post => {
                            var row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${formatDate(post.data_postagem)}</td>
                                <td>${post.tipo_postagem || '-'}</td>
                                <td>${post.formato || '-'}</td>
                                <td><span class="badge ${getBadgeClass(post.status)}">${post.status || 'Pendente'}</span></td>
                                <td>
                                    <a href="visualizar_postagem.php?id=${post.id}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            `;
                            historyTableBody.appendChild(row);
                        });
                        
                        document.getElementById('historyContent').classList.remove('d-none');
                        document.getElementById('noHistoryMessage').classList.add('d-none');
                    } else {
                        // Mostrar mensagem de nenhum histórico
                        document.getElementById('historyContent').classList.add('d-none');
                        document.getElementById('noHistoryMessage').classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar histórico. Tente novamente mais tarde.</td></tr>';
                });
        });
    }
    
    // Função para formatar data
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString('pt-BR') + ' ' + 
               date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    }
    
    // Função para determinar a classe do badge baseado no status
    function getBadgeClass(status) {
        if (!status) return 'bg-secondary';
        
        status = status.toLowerCase();
        if (status.includes('agendado') || status === 'pendente') return 'bg-warning';
        if (status.includes('publicado') || status === 'concluído') return 'bg-success';
        if (status.includes('cancelado') || status === 'erro') return 'bg-danger';
        return 'bg-secondary';
    }
    
    // Configurar filtro de busca em tempo real
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterClients(searchTerm);
        });
    }
    
    // Função para filtrar clientes em tempo real
    function filterClients(searchTerm) {
        const rows = document.querySelectorAll('#clientsTable tbody tr');
        
        rows.forEach(row => {
            const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const instagram = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            
            if (name.includes(searchTerm) || instagram.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Atualizar contador de resultados
        const visibleRows = document.querySelectorAll('#clientsTable tbody tr:not([style*="display: none"])').length;
        const totalRows = rows.length;
        
        const resultsCounter = document.getElementById('resultsCounter');
        if (resultsCounter) {
            resultsCounter.textContent = `Mostrando ${visibleRows} de ${totalRows} clientes`;
        }
    }
    
    // Atualizar a sessão a cada 5 minutos para mantê-la ativa
    setInterval(function() {
        fetch('ajax/update_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Sessão atualizada:', data.timestamp);
                }
            })
            .catch(error => console.error('Erro ao atualizar sessão:', error));
    }, 300000); // 5 minutos
});
</script>

<?php require_once 'includes/footer.php'; ?>
