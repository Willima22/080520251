<?php
// Iniciar output buffering
ob_start();

/**
 * Postagens Agendadas
 * Visualização das postagens que foram agendadas
 */

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

// Obter postagens agendadas
$sql = "SELECT p.id, p.cliente_id, p.tipo_postagem, p.formato, p.data_postagem, p.data_postagem_utc,
        p.webhook_status as status, p.post_id_unique as arquivos, p.data_criacao, 
        c.nome_cliente as cliente_nome, u.nome as usuario_nome, u.id as usuario_id
        FROM postagens AS p
        LEFT JOIN clientes AS c ON p.cliente_id = c.id
        LEFT JOIN usuarios AS u ON p.usuario_id = u.id
        ORDER BY p.data_postagem DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$postagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ação de cancelamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancelar') {
    $postagem_id = $_POST['postagem_id'];
    
    // Atualizar status para cancelado
    $sql = "UPDATE postagens SET webhook_status = 'Cancelado' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$postagem_id]);
    
    if ($result) {
        setFlashMessage('success', 'Postagem cancelada com sucesso!');
    } else {
        setFlashMessage('danger', 'Erro ao cancelar a postagem. Tente novamente.');
    }
    
    redirect('postagens_agendadas.php');
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Postagens Agendadas</h1>
    <a href="index.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova Postagem
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form id="filtroForm" class="row g-3">
            <div class="col-md-3">
                <label for="filtroCliente" class="form-label">Cliente</label>
                <select class="form-select" id="filtroCliente">
                    <option value="">Todos</option>
                    <?php 
                    // Obter lista de clientes
                    $clientes = $conn->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll();
                    foreach ($clientes as $cliente) {
                        echo "<option value=\"{$cliente['id']}\">{$cliente['nome']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filtroTipoPostagem" class="form-label">Tipo de Postagem</label>
                <select class="form-select" id="filtroTipoPostagem">
                    <option value="">Todos</option>
                    <option value="Feed">Feed</option>
                    <option value="Story">Story</option>
                    <option value="Feed e Story">Feed e Story</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filtroStatus" class="form-label">Status</label>
                <select class="form-select" id="filtroStatus">
                    <option value="">Todos</option>
                    <option value="Agendado">Agendado</option>
                    <option value="Publicado">Publicado</option>
                    <option value="Cancelado">Cancelado</option>
                    <option value="Falha">Falha</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filtroData" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="filtroDataInicial">
            </div>
            <div class="col-md-3">
                <label for="filtroData" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="filtroDataFinal">
            </div>
            <div class="col-12 text-end">
                <button type="button" id="limparFiltros" class="btn btn-secondary me-2">Limpar Filtros</button>
                <button type="button" id="aplicarFiltros" class="btn btn-primary">Aplicar Filtros</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Postagens -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Lista de Postagens</h5>
    </div>
    <div class="card-body">
        <?php if (count($postagens) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Formato</th>
                        <th>Data/Hora</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Criado por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postagens as $postagem): ?>
                    <tr class="postagem-row" 
                        data-cliente="<?= $postagem['cliente_id'] ?>" 
                        data-tipo="<?= $postagem['tipo_postagem'] ?>" 
                        data-status="<?= $postagem['status'] ?>" 
                        data-data="<?= date('Y-m-d', strtotime($postagem['data_postagem'])) ?>">
                        <td><?= $postagem['id'] ?></td>
                        <td><?= htmlspecialchars($postagem['cliente_nome'] ?? '') ?></td>
                        <td><?= htmlspecialchars($postagem['tipo_postagem'] ?? '') ?></td>
                        <td><?= htmlspecialchars($postagem['formato'] ?? '') ?></td>
                        <td>
                            <?php
                            $data_br = $postagem['data_postagem'] ?? '';
                            $data_utc = $postagem['data_postagem_utc'] ?? '';
                            $data_formatada = 'Data não definida';
                            
                            if (!empty($data_br) && $data_br !== '0000-00-00 00:00:00') {
                                $timestamp = strtotime($data_br);
                                if ($timestamp !== false) {
                                    $data_formatada = date('d/m/Y H:i', $timestamp);
                                }
                            } elseif (!empty($data_utc)) {
                                $data_convertida = str_replace(['T', 'Z'], [' ', ''], $data_utc);
                                $timestamp = strtotime($data_convertida);
                                if ($timestamp !== false) {
                                    $timestamp_brasilia = $timestamp - (3 * 3600);
                                    $data_formatada = date('d/m/Y H:i', $timestamp_brasilia);
                                }
                            }
                            
                            echo $data_formatada;
                            ?>
                        </td>
                        <td>
                            <?php if ($postagem['status'] == 'Agendado'): ?>
                                <span class="badge bg-warning"><i class="fas fa-clock"></i> Agendado</span>
                            <?php elseif ($postagem['status'] == 'Publicado'): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Publicado</span>
                            <?php elseif ($postagem['status'] == 'Cancelado'): ?>
                                <span class="badge bg-danger"><i class="fas fa-ban"></i> Cancelado</span>
                            <?php elseif ($postagem['status'] == 'Falha'): ?>
                                <span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Falha</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $postagem['status'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($postagem['data_criacao'])) ?></td>
                        <td><?= htmlspecialchars($postagem['usuario_nome'] ?? '') ?></td>
                        <td>
                            <a href="visualizar_postagem.php?id=<?= $postagem['id'] ?>" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            Nenhuma postagem agendada encontrada.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros
    const filtroCliente = document.getElementById('filtroCliente');
    const filtroTipoPostagem = document.getElementById('filtroTipoPostagem');
    const filtroStatus = document.getElementById('filtroStatus');
    const filtroDataInicial = document.getElementById('filtroDataInicial');
    const filtroDataFinal = document.getElementById('filtroDataFinal');
    const aplicarFiltros = document.getElementById('aplicarFiltros');
    const limparFiltros = document.getElementById('limparFiltros');
    const postagens = document.querySelectorAll('.postagem-row');
    
    aplicarFiltros.addEventListener('click', function() {
        const clienteValue = filtroCliente.value;
        const tipoPostagemValue = filtroTipoPostagem.value;
        const statusValue = filtroStatus.value;
        const dataInicialValue = filtroDataInicial.value;
        const dataFinalValue = filtroDataFinal.value;
        
        postagens.forEach(function(postagem) {
            let mostrar = true;
            
            // Filtro de cliente
            if (clienteValue && postagem.dataset.cliente !== clienteValue) {
                mostrar = false;
            }
            
            // Filtro de tipo de postagem
            if (tipoPostagemValue && postagem.dataset.tipo !== tipoPostagemValue) {
                mostrar = false;
            }
            
            // Filtro de status
            if (statusValue && postagem.dataset.status !== statusValue) {
                mostrar = false;
            }
            
            // Filtro de data inicial
            if (dataInicialValue && postagem.dataset.data < dataInicialValue) {
                mostrar = false;
            }
            
            // Filtro de data final
            if (dataFinalValue && postagem.dataset.data > dataFinalValue) {
                mostrar = false;
            }
            
            postagem.style.display = mostrar ? '' : 'none';
        });
    });
    
    limparFiltros.addEventListener('click', function() {
        filtroCliente.value = '';
        filtroTipoPostagem.value = '';
        filtroStatus.value = '';
        filtroDataInicial.value = '';
        filtroDataFinal.value = '';
        
        postagens.forEach(function(postagem) {
            postagem.style.display = '';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>