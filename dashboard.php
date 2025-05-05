<?php
require_once 'config/config.php';
require_once 'config/db.php';
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

// Count total users
$query = "SELECT COUNT(*) as total FROM usuarios";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['totalUsers'] = $result['total'];

// Count posts by status
$query = "SELECT status, COUNT(*) as total FROM postagens GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->execute();
$postsByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
$statusData = [0, 0, 0]; // [Agendado, Publicado, Falha]

foreach ($postsByStatus as $status) {
    switch ($status['status']) {
        case 'Agendado':
            $statusData[0] = (int)$status['total'];
            break;
        case 'Publicado':
            $statusData[1] = (int)$status['total'];
            break;
        case 'Falha':
            $statusData[2] = (int)$status['total'];
            break;
    }
}

// Get posts from last 7 days
$postsData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    
    $query = "SELECT COUNT(*) as total FROM postagens WHERE DATE(created_at) = :date";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $postsData[] = (int)$result['total'];
}

// Get recent posts
$query = "SELECT p.*, c.nome as cliente_nome 
          FROM postagens p 
          JOIN clientes c ON p.cliente_id = c.id 
          ORDER BY p.created_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Dashboard</h1>
            <p class="text-secondary">Bem-vindo ao sistema de agendamento de postagens.</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Total de Postagens</h6>
                            <h2 class="card-title mb-0" id="totalPosts"><?= $stats['totalPosts'] ?></h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card stats-card clients h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Total de Clientes</h6>
                            <h2 class="card-title mb-0" id="totalClients"><?= $stats['totalClients'] ?></h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card stats-card users h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Total de Usuários</h6>
                            <h2 class="card-title mb-0" id="totalUsers"><?= $stats['totalUsers'] ?></h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-8 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    Atividade Recente
                </div>
                <div class="card-body">
                    <canvas id="postsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    Status das Postagens
                </div>
                <div class="card-body">
                    <canvas id="clientsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Posts -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Postagens Recentes
                </div>
                <div class="card-body">
                    <?php if (count($recentPosts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Tipo</th>
                                    <th>Formato</th>
                                    <th>Data da Postagem</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPosts as $post): ?>
                                <tr>
                                    <td><?= htmlspecialchars($post['cliente_nome']) ?></td>
                                    <td><?= htmlspecialchars($post['tipo_postagem']) ?></td>
                                    <td><?= htmlspecialchars($post['formato']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($post['data_postagem'])) ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($post['status']) {
                                            case 'Agendado':
                                                $statusClass = 'primary';
                                                break;
                                            case 'Publicado':
                                                $statusClass = 'success';
                                                break;
                                            case 'Falha':
                                                $statusClass = 'danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($post['status']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-day fa-3x mb-3 text-secondary"></i>
                        <p class="mb-0">Nenhuma postagem registrada ainda.</p>
                        <a href="index.php" class="btn btn-primary mt-3">Agendar Primeira Postagem</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass data to charts
document.addEventListener('DOMContentLoaded', function() {
    const postsData = <?= json_encode($postsData) ?>;
    const statusData = <?= json_encode($statusData) ?>;
    
    // Update the charts with the data
    const postsChartEl = document.getElementById('postsChart');
    if (postsChartEl) {
        window.postsChart = new Chart(postsChartEl, {
            type: 'line',
            data: {
                labels: getLastSevenDays(),
                datasets: [{
                    label: 'Postagens',
                    data: postsData,
                    borderColor: '#E1306C',
                    backgroundColor: 'rgba(225, 48, 108, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Postagens nos Últimos 7 Dias'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    const clientsChartEl = document.getElementById('clientsChart');
    if (clientsChartEl) {
        window.clientsChart = new Chart(clientsChartEl, {
            type: 'doughnut',
            data: {
                labels: ['Agendadas', 'Publicadas', 'Falhas'],
                datasets: [{
                    data: statusData,
                    backgroundColor: [
                        '#405DE6',
                        '#58CF86',
                        '#ED4956'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Status de Postagens'
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
