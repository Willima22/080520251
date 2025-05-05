<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// Check if user has permission
requirePermission('Editor');

// Get database connection
$database = new Database();
$conn = $database->connect();

// Get report data
$months = [];
$currentYear = date('Y');
$currentMonth = date('n');

// Get last 6 months for chart
for ($i = 5; $i >= 0; $i--) {
    $monthNum = $currentMonth - $i;
    $yearNum = $currentYear;
    
    if ($monthNum <= 0) {
        $monthNum += 12;
        $yearNum -= 1;
    }
    
    $monthName = date('M', mktime(0, 0, 0, $monthNum, 1, $yearNum));
    $months[] = $monthName;
}

// Get post statistics by month for the last 6 months
$postStats = [];
for ($i = 5; $i >= 0; $i--) {
    $monthNum = $currentMonth - $i;
    $yearNum = $currentYear;
    
    if ($monthNum <= 0) {
        $monthNum += 12;
        $yearNum -= 1;
    }
    
    $startDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
    $endDate = date('Y-m-t', strtotime($startDate));
    
    // Total posts by month
    $query = "SELECT COUNT(*) as total FROM postagens WHERE created_at BETWEEN :start AND :end";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start', $startDate);
    $stmt->bindParam(':end', $endDate);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $postStats[] = (int)$result['total'];
}

// Get post counts by type
$typeStats = [];
$query = "SELECT tipo_postagem, COUNT(*) as total FROM postagens GROUP BY tipo_postagem";
$stmt = $conn->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $result) {
    $typeStats[$result['tipo_postagem']] = (int)$result['total'];
}

// Get post counts by format
$formatStats = [];
$query = "SELECT formato, COUNT(*) as total FROM postagens GROUP BY formato";
$stmt = $conn->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $result) {
    $formatStats[$result['formato']] = (int)$result['total'];
}

// Get top clients by post count
$topClients = [];
$query = "SELECT c.nome, COUNT(p.id) as total 
          FROM postagens p 
          JOIN clientes c ON p.cliente_id = c.id 
          GROUP BY p.cliente_id 
          ORDER BY total DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$topClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent posts
$query = "SELECT p.*, c.nome as cliente_nome 
          FROM postagens p 
          JOIN clientes c ON p.cliente_id = c.id 
          ORDER BY p.data_postagem DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Relatórios</h1>
            <p class="text-secondary">Visualize estatísticas e relatórios de postagens agendadas.</p>
        </div>
    </div>
    
    <!-- Report Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-secondary">Postagens do Mês</h6>
                            <h2 class="card-title mb-0">
                                <?= $postStats[count($postStats) - 1] ?>
                            </h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-calendar-alt"></i>
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
                            <h6 class="card-subtitle mb-2 text-secondary">Tipo Mais Comum</h6>
                            <h2 class="card-title mb-0">
                                <?php
                                $maxType = 'N/A';
                                $maxCount = 0;
                                foreach ($typeStats as $type => $count) {
                                    if ($count > $maxCount) {
                                        $maxType = $type;
                                        $maxCount = $count;
                                    }
                                }
                                echo htmlspecialchars($maxType);
                                ?>
                            </h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-hashtag"></i>
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
                            <h6 class="card-subtitle mb-2 text-secondary">Formato Mais Usado</h6>
                            <h2 class="card-title mb-0">
                                <?php
                                $maxFormat = 'N/A';
                                $maxCount = 0;
                                foreach ($formatStats as $format => $count) {
                                    if ($count > $maxCount) {
                                        $maxFormat = $format;
                                        $maxCount = $count;
                                    }
                                }
                                echo htmlspecialchars($maxFormat);
                                ?>
                            </h2>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-image"></i>
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
                    Postagens por Mês
                </div>
                <div class="card-body">
                    <canvas id="monthlyPostsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    Tipos de Postagem
                </div>
                <div class="card-body">
                    <canvas id="postTypesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    Top 5 Clientes
                </div>
                <div class="card-body">
                    <?php if (count($topClients) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Total de Postagens</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topClients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['nome']) ?></td>
                                    <td><?= $client['total'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <p class="mb-0">Nenhum cliente com postagens.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    Formatos de Postagem
                </div>
                <div class="card-body">
                    <canvas id="postFormatsChart"></canvas>
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
document.addEventListener('DOMContentLoaded', function() {
    // Months data
    const months = <?= json_encode($months) ?>;
    const postsData = <?= json_encode($postStats) ?>;
    
    // Post types data
    const postTypes = <?= json_encode(array_keys($typeStats)) ?>;
    const postTypeCounts = <?= json_encode(array_values($typeStats)) ?>;
    
    // Post formats data
    const postFormats = <?= json_encode(array_keys($formatStats)) ?>;
    const postFormatCounts = <?= json_encode(array_values($formatStats)) ?>;
    
    // Monthly posts chart
    const monthlyPostsChartEl = document.getElementById('monthlyPostsChart');
    if (monthlyPostsChartEl) {
        new Chart(monthlyPostsChartEl, {
            type: 'line',
            data: {
                labels: months,
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
                        text: 'Postagens por Mês'
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
    
    // Post types chart
    const postTypesChartEl = document.getElementById('postTypesChart');
    if (postTypesChartEl) {
        new Chart(postTypesChartEl, {
            type: 'doughnut',
            data: {
                labels: postTypes,
                datasets: [{
                    data: postTypeCounts,
                    backgroundColor: [
                        '#405DE6',
                        '#5851DB',
                        '#833AB4',
                        '#C13584',
                        '#E1306C',
                        '#FD1D1D'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Tipos de Postagem'
                    }
                }
            }
        });
    }
    
    // Post formats chart
    const postFormatsChartEl = document.getElementById('postFormatsChart');
    if (postFormatsChartEl) {
        new Chart(postFormatsChartEl, {
            type: 'pie',
            data: {
                labels: postFormats,
                datasets: [{
                    data: postFormatCounts,
                    backgroundColor: [
                        '#F77737',
                        '#FCAF45',
                        '#FFDC80',
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Formatos de Postagem'
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
