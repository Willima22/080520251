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

// Get database connection
$database = new Database();
$conn = $database->connect();

// Get dashboard statistics
$stats = [];

// Count total clients
$query = "SELECT COUNT(*) as total FROM clientes";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['totalClients'] = $result['total'];

// Count total posts
$query = "SELECT COUNT(*) as total FROM postagens";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['totalPosts'] = $result['total'];

// Count total users (só mostrar para administradores)
$query = "SELECT COUNT(*) as total FROM usuarios";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['totalUsers'] = $result['total'];

// Obter ranking dos clientes mais ativos (com mais postagens)
$query = "SELECT c.id, c.nome_cliente, COUNT(p.id) as total_posts 
          FROM clientes c 
          LEFT JOIN postagens p ON c.id = p.cliente_id 
          GROUP BY c.id 
          ORDER BY total_posts DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$clientesAtivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Dashboard</h1>
            <p class="text-secondary">Bem-vindo ao sistema de agendamento de postagens da AW7 Comunicação e Marketing.</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stats-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Total de Postagens</h6>
                            <h2 class="card-title mb-0" id="totalPosts"><?= $stats['totalPosts'] ?></h2>
                        </div>
                        <div class="stats-icon bg-light rounded-circle p-3">
                            <i class="fas fa-calendar-check fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card stats-card clients h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Total de Clientes</h6>
                            <h2 class="card-title mb-0" id="totalClients"><?= $stats['totalClients'] ?></h2>
                        </div>
                        <div class="stats-icon bg-light rounded-circle p-3">
                            <i class="fas fa-users fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isAdmin()): ?>
        <div class="col-md-4 mb-3">
            <div class="card stats-card users h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Total de Usuários</h6>
                            <h2 class="card-title mb-0" id="totalUsers"><?= $stats['totalUsers'] ?></h2>
                        </div>
                        <div class="stats-icon bg-light rounded-circle p-3">
                            <i class="fas fa-user-cog fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Botões de Ação Rápida -->
    <div class="row mb-5">
        <div class="col-12">
            <h5 class="mb-3">Ações Rápidas</h5>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="clientes.php" class="card action-card h-100 border-0 shadow-sm text-decoration-none">
                <div class="card-body text-center py-4">
                    <div class="action-icon mb-3 bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-plus fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Novo Cliente</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="index.php" class="card action-card h-100 border-0 shadow-sm text-decoration-none">
                <div class="card-body text-center py-4">
                    <div class="action-icon mb-3 bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-calendar-plus fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title">Nova Postagem</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="clientes_visualizar.php" class="card action-card h-100 border-0 shadow-sm text-decoration-none">
                <div class="card-body text-center py-4">
                    <div class="action-icon mb-3 bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-search fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title">Ver Clientes</h5>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="postagens_agendadas.php" class="card action-card h-100 border-0 shadow-sm text-decoration-none">
                <div class="card-body text-center py-4">
                    <div class="action-icon mb-3 bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-clipboard-list fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title">Ver Postagens</h5>
                </div>
            </a>
        </div>
        
        <?php if (isAdmin()): ?>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="admin/backup_database.php" class="card action-card h-100 border-0 shadow-sm text-decoration-none">
                <div class="card-body text-center py-4">
                    <div class="action-icon mb-3 bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-download fa-3x text-primary" style="color: #0d6efd !important;"></i>
                    </div>
                    <h5 class="card-title">Backup do Banco de Dados</h5>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Ranking de Clientes Mais Ativos -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Ranking dos clientes mais ativos</h5>
                </div>
                <div class="card-body">
                    <?php if (count($clientesAtivos) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="70">#</th>
                                    <th>Cliente</th>
                                    <th width="200">Total de Postagens</th>
                                    <th width="150">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientesAtivos as $index => $cliente): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <div class="position-ranking position-<?= $index + 1 ?>">
                                                <?= $index + 1 ?>
                                            </div>
                                        <?php else: ?>
                                            <?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($cliente['nome_cliente'] ?? 'Cliente sem nome') ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <?php 
                                                $maxPosts = $clientesAtivos[0]['total_posts'] > 0 ? $clientesAtivos[0]['total_posts'] : 1;
                                                $percentage = ($cliente['total_posts'] / $maxPosts) * 100;
                                                ?>
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $cliente['total_posts'] ?>" aria-valuemin="0" aria-valuemax="<?= $maxPosts ?>"></div>
                                            </div>
                                            <span class="ms-2"><?= $cliente['total_posts'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="index.php?cliente_id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-primary" title="Agendar Postagem">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                        <a href="clientes.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar Cliente">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x mb-3 text-secondary"></i>
                        <p class="mb-0">Nenhum cliente com postagens registradas ainda.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para o dashboard */
.stats-card {
    transition: all 0.3s ease;
    border-radius: 10px;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.stats-icon {
    transition: all 0.3s ease;
}

.stats-card:hover .stats-icon {
    transform: scale(1.1);
}

.action-card {
    transition: all 0.3s ease;
    border-radius: 10px;
    overflow: hidden;
    color: #2F1847;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.action-icon {
    transition: all 0.3s ease;
}

.action-card:hover .action-icon {
    transform: scale(1.1);
}

.position-ranking {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
}

.position-1 {
    background-color: #FFD700; /* Ouro */
}

.position-2 {
    background-color: #C0C0C0; /* Prata */
}

.position-3 {
    background-color: #CD7F32; /* Bronze */
}
</style>

<?php require_once 'includes/footer.php'; ?>
