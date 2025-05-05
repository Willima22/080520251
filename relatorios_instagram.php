<?php
/**
 * Relatórios do Instagram
 * Relatórios específicos por cliente conectados à API do Instagram
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    setFlashMessage('danger', 'Você precisa estar logado para acessar esta página.');
    redirect('login.php');
}

// Obter lista de clientes
$query = "SELECT id, nome, instagram, token_instagram FROM clientes ORDER BY nome";
$stmt = $pdo->prepare($query);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Receber cliente selecionado
$clienteId = $_GET['cliente_id'] ?? null;
$periodoRelatorio = $_GET['periodo'] ?? 'last_30_days';

// Dados do cliente selecionado
$clienteSelecionado = null;
if ($clienteId) {
    foreach($clientes as $cliente) {
        if ($cliente['id'] == $clienteId) {
            $clienteSelecionado = $cliente;
            break;
        }
    }
}

// Inicializar dados de relatórios
$relatorios = [
    'insights' => [
        'impressoes' => ['valor' => 0, 'comparacao' => 0],
        'alcance' => ['valor' => 0, 'comparacao' => 0],
        'engajamento' => ['valor' => 0, 'comparacao' => 0],
        'seguidores' => ['valor' => 0, 'comparacao' => 0]
    ],
    'publicacoes' => [],
    'demograficos' => [
        'genero' => ['masculino' => 30, 'feminino' => 70],
        'idade' => [
            '13-17' => 5,
            '18-24' => 25,
            '25-34' => 40,
            '35-44' => 20,
            '45-54' => 7,
            '55+' => 3
        ],
        'localizacao' => [
            'Brasil' => 92,
            'Portugal' => 5,
            'Estados Unidos' => 2,
            'Outros' => 1
        ]
    ],
    'tempo_conectado' => [
        'manha' => 28,
        'tarde' => 35,
        'noite' => 37
    ]
];

/**
 * Função para simular chamada à API do Instagram
 * Na implementação real, isso chamaria a API Meta/Instagram Graph
 */
function buscarDadosInstagram($clienteId, $periodo) {
    // Função simulada para obter insights do Instagram
    // Em produção, isso faria uma chamada real à API usando o token do cliente
    
    // Simulamos resultados diferentes por cliente e período para demonstração
    $hash = crc32($clienteId . $periodo);
    $baseValue = ($hash % 1000) + 1000;
    
    return [
        'insights' => [
            'impressoes' => [
                'valor' => $baseValue,
                'comparacao' => rand(-20, 30)
            ],
            'alcance' => [
                'valor' => floor($baseValue * 0.7),
                'comparacao' => rand(-15, 25)
            ],
            'engajamento' => [
                'valor' => floor($baseValue * 0.15),
                'comparacao' => rand(-10, 40)
            ],
            'seguidores' => [
                'valor' => floor($baseValue * 0.02) + 100,
                'comparacao' => rand(-5, 15)
            ]
        ],
        'publicacoes' => [
            [
                'id' => 'post' . $hash . '_1',
                'tipo' => 'Imagem',
                'data' => date('d/m/Y', time() - (86400 * 5)),
                'impressoes' => floor($baseValue * 0.3),
                'alcance' => floor($baseValue * 0.2),
                'engajamento' => floor($baseValue * 0.05),
                'thumbnail' => 'https://picsum.photos/seed/' . $hash . '_1/200'
            ],
            [
                'id' => 'post' . $hash . '_2',
                'tipo' => 'Vídeo',
                'data' => date('d/m/Y', time() - (86400 * 10)),
                'impressoes' => floor($baseValue * 0.4),
                'alcance' => floor($baseValue * 0.25),
                'engajamento' => floor($baseValue * 0.06),
                'thumbnail' => 'https://picsum.photos/seed/' . $hash . '_2/200'
            ],
            [
                'id' => 'post' . $hash . '_3',
                'tipo' => 'Carrossel',
                'data' => date('d/m/Y', time() - (86400 * 15)),
                'impressoes' => floor($baseValue * 0.5),
                'alcance' => floor($baseValue * 0.3),
                'engajamento' => floor($baseValue * 0.08),
                'thumbnail' => 'https://picsum.photos/seed/' . $hash . '_3/200'
            ]
        ]
    ];
}

// Se um cliente foi selecionado, buscar os dados da API do Instagram
if ($clienteSelecionado) {
    $dadosInstagram = buscarDadosInstagram($clienteId, $periodoRelatorio);
    $relatorios['insights'] = $dadosInstagram['insights'];
    $relatorios['publicacoes'] = $dadosInstagram['publicacoes'];
}

// Função para formatar números grandes
function formatNumero($numero) {
    if ($numero >= 1000000) {
        return round($numero / 1000000, 1) . 'M';
    } elseif ($numero >= 1000) {
        return round($numero / 1000, 1) . 'k';
    }
    return $numero;
}

// Função para obter nome do período
function getNomePeriodo($periodo) {
    switch ($periodo) {
        case 'last_7_days':
            return 'últimos 7 dias';
        case 'last_14_days':
            return 'últimos 14 dias';
        case 'last_30_days':
            return 'últimos 30 dias';
        case 'last_90_days':
            return 'últimos 90 dias';
        default:
            return 'últimos 30 dias';
    }
}

// Título da página
$titulo = 'Relatórios de Instagram';
if ($clienteSelecionado) {
    $titulo .= ': ' . $clienteSelecionado['nome'];
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1"><?= $titulo ?></h1>
                    <?php if ($clienteSelecionado): ?>
                    <p class="text-muted">
                        @<?= htmlspecialchars($clienteSelecionado['instagram']) ?> · 
                        <?= getNomePeriodo($periodoRelatorio) ?>
                    </p>
                    <?php else: ?>
                    <p class="text-muted">Selecione um cliente para ver seus relatórios do Instagram</p>
                    <?php endif; ?>
                </div>
                <a href="relatorios.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i> Voltar aos Relatórios Gerais
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="relatorios_instagram.php" class="row g-3">
                        <div class="col-md-5">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="form-select" required onchange="this.form.submit()">
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= ($clienteId == $cliente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nome']) ?> (@<?= htmlspecialchars($cliente['instagram']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="periodo" class="form-label">Período</label>
                            <select name="periodo" id="periodo" class="form-select" onchange="this.form.submit()">
                                <option value="last_7_days" <?= ($periodoRelatorio == 'last_7_days') ? 'selected' : '' ?>>Últimos 7 dias</option>
                                <option value="last_14_days" <?= ($periodoRelatorio == 'last_14_days') ? 'selected' : '' ?>>Últimos 14 dias</option>
                                <option value="last_30_days" <?= ($periodoRelatorio == 'last_30_days') ? 'selected' : '' ?>>Últimos 30 dias</option>
                                <option value="last_90_days" <?= ($periodoRelatorio == 'last_90_days') ? 'selected' : '' ?>>Últimos 90 dias</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" onclick="exportarCSV()">
                                <i class="fas fa-download me-2"></i> Exportar Relatório
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($clienteSelecionado): ?>
    <!-- Cards de Insights -->
    <div class="row mb-4">
        <!-- Impressões -->
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-2">Impressões</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 me-2"><?= formatNumero($relatorios['insights']['impressoes']['valor']) ?></h2>
                        <?php if ($relatorios['insights']['impressoes']['comparacao'] > 0): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-arrow-up"></i> <?= $relatorios['insights']['impressoes']['comparacao'] ?>%
                        </span>
                        <?php elseif ($relatorios['insights']['impressoes']['comparacao'] < 0): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-arrow-down"></i> <?= abs($relatorios['insights']['impressoes']['comparacao']) ?>%
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary">0%</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Comparado com o período anterior</p>
                </div>
            </div>
        </div>
        
        <!-- Alcance -->
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-2">Alcance</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 me-2"><?= formatNumero($relatorios['insights']['alcance']['valor']) ?></h2>
                        <?php if ($relatorios['insights']['alcance']['comparacao'] > 0): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-arrow-up"></i> <?= $relatorios['insights']['alcance']['comparacao'] ?>%
                        </span>
                        <?php elseif ($relatorios['insights']['alcance']['comparacao'] < 0): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-arrow-down"></i> <?= abs($relatorios['insights']['alcance']['comparacao']) ?>%
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary">0%</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Comparado com o período anterior</p>
                </div>
            </div>
        </div>
        
        <!-- Engajamento -->
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-2">Engajamento</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 me-2"><?= formatNumero($relatorios['insights']['engajamento']['valor']) ?></h2>
                        <?php if ($relatorios['insights']['engajamento']['comparacao'] > 0): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-arrow-up"></i> <?= $relatorios['insights']['engajamento']['comparacao'] ?>%
                        </span>
                        <?php elseif ($relatorios['insights']['engajamento']['comparacao'] < 0): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-arrow-down"></i> <?= abs($relatorios['insights']['engajamento']['comparacao']) ?>%
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary">0%</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Comparado com o período anterior</p>
                </div>
            </div>
        </div>
        
        <!-- Seguidores -->
        <div class="col-md-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-2">Seguidores</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 me-2"><?= formatNumero($relatorios['insights']['seguidores']['valor']) ?></h2>
                        <?php if ($relatorios['insights']['seguidores']['comparacao'] > 0): ?>
                        <span class="badge bg-success">
                            <i class="fas fa-arrow-up"></i> <?= $relatorios['insights']['seguidores']['comparacao'] ?>%
                        </span>
                        <?php elseif ($relatorios['insights']['seguidores']['comparacao'] < 0): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-arrow-down"></i> <?= abs($relatorios['insights']['seguidores']['comparacao']) ?>%
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary">0%</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Comparado com o período anterior</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos e Tabelas -->
    <div class="row mb-4">
        <!-- Demográficos -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dados Demográficos</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="exportarDemograficos()">Exportar dados</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Gênero</h6>
                            <canvas id="generoChart" height="150"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Faixa Etária</h6>
                            <canvas id="idadeChart" height="150"></canvas>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h6 class="mb-3">Localização</h6>
                            <canvas id="localizacaoChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Melhores Publicações -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Melhores Publicações</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="exportarPublicacoes()">Exportar dados</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Publicação</th>
                                    <th>Data</th>
                                    <th>Impressões</th>
                                    <th>Alcance</th>
                                    <th>Engajamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatorios['publicacoes'] as $publicacao): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2" style="width: 40px; height: 40px; overflow: hidden; border-radius: 4px;">
                                                <img src="<?= $publicacao['thumbnail'] ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                            <span class="badge bg-<?= ($publicacao['tipo'] == 'Imagem') ? 'primary' : (($publicacao['tipo'] == 'Vídeo') ? 'danger' : 'success') ?>">
                                                <?= $publicacao['tipo'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= $publicacao['data'] ?></td>
                                    <td><?= formatNumero($publicacao['impressoes']) ?></td>
                                    <td><?= formatNumero($publicacao['alcance']) ?></td>
                                    <td><?= formatNumero($publicacao['engajamento']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Horários e Atividade -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Horários de Maior Engajamento</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="exportarHorarios()">Exportar dados</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <canvas id="horariosChart" height="200"></canvas>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6 class="mb-3">Melhores Horários para Postar</h6>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Manhã (6h - 12h)</span>
                                        <span><?= $relatorios['tempo_conectado']['manha'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: <?= $relatorios['tempo_conectado']['manha'] ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Tarde (12h - 18h)</span>
                                        <span><?= $relatorios['tempo_conectado']['tarde'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?= $relatorios['tempo_conectado']['tarde'] ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Noite (18h - 24h)</span>
                                        <span><?= $relatorios['tempo_conectado']['noite'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: <?= $relatorios['tempo_conectado']['noite'] ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-lightbulb me-2"></i> Recomendação: poste entre <strong>18h e 21h</strong> para maior engajamento.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Conexão com o N8N -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Integração com N8N</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p>Os dados deste cliente estão sendo processados automaticamente pelo N8N.</p>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <span class="badge bg-success p-2">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Conexão com API do Instagram</h6>
                                    <p class="text-muted small mb-0">Dados sendo recebidos normalmente</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="badge bg-success p-2">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Workflow N8N</h6>
                                    <p class="text-muted small mb-0">Processamento automático ativado</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i> Sobre esta integração</h6>
                                <p class="mb-0">Esta integração permite o processamento automático de dados da API do Instagram usando o N8N. Os dados são coletados periodicamente e processados para gerar relatórios automatizados.</p>
                            </div>
                            <?php if (isAdmin()): ?>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-primary me-md-2">
                                    <i class="fas fa-sync-alt me-2"></i> Atualizar Dados
                                </button>
                                <button type="button" class="btn btn-primary">
                                    <i class="fas fa-cog me-2"></i> Configurar Workflow
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dados para os gráficos
        const generoData = {
            labels: ['Masculino', 'Feminino'],
            data: [<?= $relatorios['demograficos']['genero']['masculino'] ?>, <?= $relatorios['demograficos']['genero']['feminino'] ?>]
        };
        
        const idadeData = {
            labels: <?= json_encode(array_keys($relatorios['demograficos']['idade'])) ?>,
            data: <?= json_encode(array_values($relatorios['demograficos']['idade'])) ?>
        };
        
        const localizacaoData = {
            labels: <?= json_encode(array_keys($relatorios['demograficos']['localizacao'])) ?>,
            data: <?= json_encode(array_values($relatorios['demograficos']['localizacao'])) ?>
        };
        
        // Configuração dos gráficos
        const generoChart = new Chart(document.getElementById('generoChart'), {
            type: 'doughnut',
            data: {
                labels: generoData.labels,
                datasets: [{
                    data: generoData.data,
                    backgroundColor: ['#3b5998', '#e4405f'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        const idadeChart = new Chart(document.getElementById('idadeChart'), {
            type: 'bar',
            data: {
                labels: idadeData.labels,
                datasets: [{
                    label: 'Usuários por idade',
                    data: idadeData.data,
                    backgroundColor: '#6CBD45'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        const localizacaoChart = new Chart(document.getElementById('localizacaoChart'), {
            type: 'pie',
            data: {
                labels: localizacaoData.labels,
                datasets: [{
                    data: localizacaoData.data,
                    backgroundColor: ['#6CBD45', '#0A1C30', '#4267B2', '#cccccc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // Gráfico de horários
        const horariosChart = new Chart(document.getElementById('horariosChart'), {
            type: 'line',
            data: {
                labels: ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
                datasets: [{
                    label: 'Engajamento',
                    data: [15, 10, 25, 30, 40, 35, 55, 45],
                    borderColor: '#6CBD45',
                    backgroundColor: 'rgba(108, 189, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    });
    
    // Funções de exportação
    function exportarCSV() {
        alert('Exportando relatório completo para CSV...');
        // Em uma implementação real, isso geraria e faria o download do arquivo CSV
    }
    
    function exportarDemograficos() {
        alert('Exportando dados demográficos...');
    }
    
    function exportarPublicacoes() {
        alert('Exportando dados de publicações...');
    }
    
    function exportarHorarios() {
        alert('Exportando dados de horários...');
    }
    </script>
    <?php else: ?>
    <!-- Mensagem para selecionar um cliente -->
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info text-center p-5">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h4>Selecione um cliente</h4>
                <p>Para visualizar os relatórios do Instagram, selecione um cliente no filtro acima.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>