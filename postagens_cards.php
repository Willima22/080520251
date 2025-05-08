<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Consulta para obter as postagens com informações do cliente
$query = "SELECT p.*, c.nome_cliente
          FROM postagens p
          JOIN clientes c ON p.cliente_id = c.id
          ORDER BY p.data_postagem DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$postagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar as datas para exibição correta
foreach ($postagens as &$postagem) {
    $data_br = $postagem['data_postagem'] ?? '';
    $data_utc = $postagem['data_postagem_utc'] ?? '';
    
    // Inicializar com valores padrão
    $postagem['data_formatada'] = 'Data não informada';
    $postagem['hora_formatada'] = '';
    
    if (!empty($data_br) && $data_br !== '0000-00-00 00:00:00') {
        $timestamp = strtotime($data_br);
        if ($timestamp !== false) {
            $postagem['data_formatada'] = date('d/m/Y', $timestamp);
            $postagem['hora_formatada'] = date('H:i', $timestamp);
        }
    } elseif (!empty($data_utc)) {
        $data_convertida = str_replace(['T', 'Z'], [' ', ''], $data_utc);
        $timestamp = strtotime($data_convertida);
        if ($timestamp !== false) {
            // Ajustar para horário de Brasília (UTC-3)
            $timestamp_brasilia = $timestamp - (3 * 3600);
            $postagem['data_formatada'] = date('d/m/Y', $timestamp_brasilia);
            $postagem['hora_formatada'] = date('H:i', $timestamp_brasilia);
        }
    }
}

// Função para obter a primeira imagem de uma postagem
function getFirstImage($arquivos) {
    if (empty($arquivos)) {
        return 'assets/img/placeholder.jpg';
    }
    
    // Tenta decodificar o JSON
    $files = json_decode($arquivos, true);
    
    // Se não for um JSON válido ou estiver vazio, retorna placeholder
    if (!$files || empty($files)) {
        return 'assets/img/placeholder.jpg';
    }
    
    // Retorna a URL da primeira imagem
    return isset($files[0]['url']) ? $files[0]['url'] : 'assets/img/placeholder.jpg';
}

// Função para obter a cor do status
function getStatusColor($status) {
    switch ($status) {
        case 'Publicado':
            return 'success';
        case 'Agendado':
            return 'warning';
        case 'Cancelado':
            return 'danger';
        case 'Falha':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Incluir o cabeçalho
require_once 'includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Postagens Agendadas</h1>
            <p class="text-secondary mb-0">Visualize todas as postagens agendadas e publicadas.</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nova Postagem
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filters-container mb-4">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros</h5>
        <form id="filtroForm" class="row g-3">
                <div class="col-md-4 col-lg-3">
                    <label for="filtroCliente" class="form-label">Cliente</label>
                    <select class="form-select" id="filtroCliente">
                        <option value="">Todos</option>
                        <?php 
                        // Obter lista de clientes
                        $clientes = $conn->query("SELECT id, nome_cliente as nome FROM clientes ORDER BY nome_cliente")->fetchAll();
                        foreach ($clientes as $cliente) {
                            echo "<option value=\"{$cliente['id']}\">{$cliente['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="filtroTipoPostagem" class="form-label">Tipo de Postagem</label>
                    <select class="form-select" id="filtroTipoPostagem">
                        <option value="">Todos</option>
                        <option value="Feed">Feed</option>
                        <option value="Story">Story</option>
                        <option value="Feed e Story">Feed e Story</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="filtroStatus" class="form-label">Status</label>
                    <select class="form-select" id="filtroStatus">
                        <option value="">Todos</option>
                        <option value="Agendado">Agendado</option>
                        <option value="Publicado">Publicado</option>
                        <option value="Cancelado">Cancelado</option>
                        <option value="Falha">Falha</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="filtroDataInicial" class="form-label">Data Inicial</label>
                    <div class="input-group">
                        <input type="text" class="form-control datepicker" id="filtroDataInicial" placeholder="dd/mm/aaaa">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="filtroDataFinal" class="form-label">Data Final</label>
                    <div class="input-group">
                        <input type="text" class="form-control datepicker" id="filtroDataFinal" placeholder="dd/mm/aaaa">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                    </div>
                </div>
                <div class="col-12 filter-buttons">
                    <button type="button" id="limparFiltros" class="btn btn-outline-secondary">
                        <i class="fas fa-eraser me-1"></i> Limpar Filtros
                    </button>
                    <button type="button" id="aplicarFiltros" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4" id="postagens-container">
        <?php if (empty($postagens)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma postagem encontrada.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($postagens as $postagem): ?>
                <div class="col-lg-4 col-md-6 col-sm-12 mb-4 postagem-card" 
                     data-cliente="<?= $postagem['cliente_id'] ?>" 
                     data-tipo="<?= $postagem['tipo_postagem'] ?>" 
                     data-status="<?= $postagem['status'] ?>" 
                     data-data="<?= !empty($postagem['data_postagem']) ? date('Y-m-d', strtotime($postagem['data_postagem'])) : '' ?>">
                    <div class="card shadow-sm h-100 cursor-pointer" data-bs-toggle="modal" data-bs-target="#postagemModal<?= $postagem['id'] ?>">
                        <div class="ratio ratio-4x5" style="background-color: white;">
                            <img src="<?= htmlspecialchars(getFirstImage($postagem['arquivos'])) ?>" 
                                 class="img-fluid" style="object-fit: contain; background-color: white;" alt="Imagem da Postagem">
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title mb-2"><?= htmlspecialchars($postagem['nome_cliente']) ?></h5>
                            <p class="card-text mb-1 text-muted">
                                <i class="far fa-calendar-alt me-1"></i> <?= htmlspecialchars($postagem['data_formatada']) ?> <?= htmlspecialchars($postagem['hora_formatada']) ?>
                            </p>
                            <span class="badge bg-<?= getStatusColor($postagem['status'] ?? 'Agendado') ?>">
                                <?= htmlspecialchars($postagem['status'] ?? 'Agendado') ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Modal para a postagem -->
                <div class="modal fade" id="postagemModal<?= $postagem['id'] ?>" tabindex="-1" aria-labelledby="postagemModalLabel<?= $postagem['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="postagemModalLabel<?= $postagem['id'] ?>">
                                    Postagem de <?= htmlspecialchars($postagem['nome_cliente']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <img src="<?= htmlspecialchars(getFirstImage($postagem['arquivos'])) ?>" 
                                             class="img-fluid rounded mb-3" alt="Imagem da Postagem">
                                    </div>
                                    <div class="col-md-6">
                                        <h4>Informações da Postagem</h4>
                                        <ul class="list-group list-group-flush mb-3">
                                            <li class="list-group-item"><strong>Cliente:</strong> <?= htmlspecialchars($postagem['nome_cliente']) ?></li>
                                            <li class="list-group-item"><strong>Data:</strong> <?= htmlspecialchars($postagem['data_formatada']) ?></li>
                                            <li class="list-group-item"><strong>Hora:</strong> <?= htmlspecialchars($postagem['hora_formatada']) ?></li>
                                            <li class="list-group-item"><strong>Tipo:</strong> <?= htmlspecialchars($postagem['tipo_postagem']) ?></li>
                                            <li class="list-group-item"><strong>Formato:</strong> <?= htmlspecialchars($postagem['formato']) ?></li>
                                            <li class="list-group-item"><strong>Status:</strong> 
                                                <span class="badge bg-<?= $postagem['webhook_status'] ? 'success' : 'warning' ?>">
                                                    <?= $postagem['webhook_status'] ? 'Publicado' : 'Agendado' ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <?php if (!empty($postagem['legenda'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h5>Legenda</h5>
                                        <div class="p-3 bg-light rounded">
                                            <?= nl2br(htmlspecialchars($postagem['legenda'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <a href="visualizar_postagem.php?id=<?= $postagem['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i>Ver Detalhes Completos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar datepickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.datepicker', {
            dateFormat: "d/m/Y",
            locale: "pt",
            allowInput: true,
            altInput: true,
            altFormat: "d/m/Y",
            disableMobile: true
        });
    }
    
    // Aplicar filtros
    document.getElementById('aplicarFiltros').addEventListener('click', function() {
        aplicarFiltros();
    });
    
    // Limpar filtros
    document.getElementById('limparFiltros').addEventListener('click', function() {
        document.getElementById('filtroCliente').value = '';
        document.getElementById('filtroTipoPostagem').value = '';
        document.getElementById('filtroStatus').value = '';
        
        // Limpar datepickers
        var dataInicialEl = document.getElementById('filtroDataInicial');
        var dataFinalEl = document.getElementById('filtroDataFinal');
        
        if (dataInicialEl._flatpickr) {
            dataInicialEl._flatpickr.clear();
        } else {
            dataInicialEl.value = '';
        }
        
        if (dataFinalEl._flatpickr) {
            dataFinalEl._flatpickr.clear();
        } else {
            dataFinalEl.value = '';
        }
        
        aplicarFiltros();
        
        // Mostrar mensagem de filtros limpos
        mostrarNotificacao('Filtros limpos com sucesso!', 'success');
    });
    
    function mostrarNotificacao(mensagem, tipo) {
        // Criar elemento de notificação
        var notificacao = document.createElement('div');
        notificacao.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
        notificacao.setAttribute('role', 'alert');
        notificacao.innerHTML = mensagem + 
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        
        // Adicionar ao topo da página
        var container = document.querySelector('.container');
        container.insertBefore(notificacao, container.firstChild);
        
        // Auto-remover após 3 segundos
        setTimeout(function() {
            notificacao.remove();
        }, 3000);
    }
    
    function aplicarFiltros() {
        var cliente = document.getElementById('filtroCliente').value;
        var tipo = document.getElementById('filtroTipoPostagem').value;
        var status = document.getElementById('filtroStatus').value;
        var dataInicial = document.getElementById('filtroDataInicial').value;
        var dataFinal = document.getElementById('filtroDataFinal').value;
        
        // Converter datas para formato yyyy-mm-dd para comparação
        if (dataInicial) {
            var partes = dataInicial.split('/');
            dataInicial = partes[2] + '-' + partes[1] + '-' + partes[0];
        }
        
        if (dataFinal) {
            var partes = dataFinal.split('/');
            dataFinal = partes[2] + '-' + partes[1] + '-' + partes[0];
        }
        
        var cardsVisiveis = 0;
        var cards = document.querySelectorAll('.postagem-card');
        
        cards.forEach(function(card) {
            var mostrar = true;
            
            // Filtro por cliente
            if (cliente && card.dataset.cliente != cliente) {
                mostrar = false;
            }
            
            // Filtro por tipo de postagem
            if (tipo && card.dataset.tipo != tipo) {
                mostrar = false;
            }
            
            // Filtro por status
            if (status && card.dataset.status != status) {
                mostrar = false;
            }
            
            // Filtro por data inicial
            if (dataInicial && card.dataset.data) {
                if (card.dataset.data < dataInicial) {
                    mostrar = false;
                }
            }
            
            // Filtro por data final
            if (dataFinal && card.dataset.data) {
                if (card.dataset.data > dataFinal) {
                    mostrar = false;
                }
            }
            
            // Mostrar ou esconder o card
            if (mostrar) {
                card.style.display = '';
                cardsVisiveis++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Verificar se há resultados
        var semResultados = document.getElementById('sem-resultados');
        if (cardsVisiveis === 0) {
            // Se não houver cards visíveis, mostrar mensagem
            if (!semResultados) {
                semResultados = document.createElement('div');
                semResultados.id = 'sem-resultados';
                semResultados.className = 'col-12 text-center py-5';
                semResultados.innerHTML = 
                    '<div class="alert alert-info">' +
                    '<i class="fas fa-info-circle me-2"></i>Nenhuma postagem encontrada com os filtros selecionados.' +
                    '</div>';
                document.getElementById('postagens-container').appendChild(semResultados);
            }
        } else {
            // Se houver cards visíveis, remover mensagem
            const semResultados = document.getElementById('sem-resultados');
            if (semResultados) {
                semResultados.remove();
            }
        }
    });
    
    limparFiltros.addEventListener('click', function() {
        filtroCliente.value = '';
        filtroTipoPostagem.value = '';
        filtroStatus.value = '';
        filtroDataInicial.value = '';
        filtroDataFinal.value = '';
        
        postagemCards.forEach(function(card) {
            card.style.display = '';
        });
        
        // Remover mensagem de sem resultados
        const semResultados = document.getElementById('sem-resultados');
        if (semResultados) {
            semResultados.remove();
        }
    });
});
</script>
