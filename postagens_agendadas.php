<?php
/**
 * Postagens Agendadas
 * Visualização das postagens que foram agendadas
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Obter postagens agendadas
$sql = "SELECT p.id, p.cliente_id, p.titulo, p.descricao, p.data_agendamento, p.hora_agendamento, 
        p.data_postagem, p.status, p.tipo_midia, p.arquivos, p.webhook_enviado, p.criado_em, 
        c.nome as cliente_nome
        FROM postagens p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        ORDER BY p.data_agendamento DESC, p.hora_agendamento ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$postagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ação de cancelamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancelar') {
    $postagem_id = $_POST['postagem_id'];
    
    // Atualizar status para cancelado
    $sql = "UPDATE postagens SET status = 'Cancelado' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
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
                    $clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll();
                    foreach ($clientes as $cliente) {
                        echo "<option value=\"{$cliente['id']}\">{$cliente['nome']}</option>";
                    }
                    ?>
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
                        <th>Título</th>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Webhook</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postagens as $postagem): ?>
                    <tr class="postagem-row" 
                        data-cliente="<?= $postagem['cliente_id'] ?>" 
                        data-status="<?= $postagem['status'] ?>" 
                        data-data="<?= $postagem['data_agendamento'] ?>">
                        <td><?= $postagem['id'] ?></td>
                        <td><?= htmlspecialchars($postagem['cliente_nome']) ?></td>
                        <td><?= htmlspecialchars($postagem['titulo']) ?></td>
                        <td>
                            <?= date('d/m/Y', strtotime($postagem['data_agendamento'])) ?>
                            às
                            <?= $postagem['hora_agendamento'] ?>
                        </td>
                        <td>
                            <?php if ($postagem['tipo_midia'] == 'imagem'): ?>
                                <span class="badge bg-primary"><i class="fas fa-image"></i> Imagem</span>
                            <?php elseif ($postagem['tipo_midia'] == 'video'): ?>
                                <span class="badge bg-danger"><i class="fas fa-video"></i> Vídeo</span>
                            <?php elseif ($postagem['tipo_midia'] == 'carrossel'): ?>
                                <span class="badge bg-success"><i class="fas fa-images"></i> Carrossel</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $postagem['tipo_midia'] ?></span>
                            <?php endif; ?>
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
                        <td>
                            <?php if ($postagem['webhook_enviado']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Enviado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-times"></i> Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($postagem['criado_em'])) ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="visualizar_postagem.php?id=<?= $postagem['id'] ?>">
                                            <i class="fas fa-eye"></i> Visualizar
                                        </a>
                                    </li>
                                    <?php if ($postagem['status'] == 'Agendado'): ?>
                                    <li>
                                        <a class="dropdown-item" href="index.php?editar=<?= $postagem['id'] ?>">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                    </li>
                                    <li>
                                        <form action="postagens_agendadas.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja cancelar esta postagem?');">
                                            <input type="hidden" name="action" value="cancelar">
                                            <input type="hidden" name="postagem_id" value="<?= $postagem['id'] ?>">
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-ban"></i> Cancelar
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
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
    const filtroStatus = document.getElementById('filtroStatus');
    const filtroDataInicial = document.getElementById('filtroDataInicial');
    const filtroDataFinal = document.getElementById('filtroDataFinal');
    const aplicarFiltros = document.getElementById('aplicarFiltros');
    const limparFiltros = document.getElementById('limparFiltros');
    const postagemRows = document.querySelectorAll('.postagem-row');
    
    // Aplicar filtros
    aplicarFiltros.addEventListener('click', function() {
        const clienteValue = filtroCliente.value;
        const statusValue = filtroStatus.value;
        const dataInicialValue = filtroDataInicial.value;
        const dataFinalValue = filtroDataFinal.value;
        
        postagemRows.forEach(row => {
            let mostrar = true;
            
            // Filtro de cliente
            if (clienteValue && row.dataset.cliente !== clienteValue) {
                mostrar = false;
            }
            
            // Filtro de status
            if (statusValue && row.dataset.status !== statusValue) {
                mostrar = false;
            }
            
            // Filtro de data
            if (dataInicialValue && row.dataset.data < dataInicialValue) {
                mostrar = false;
            }
            
            if (dataFinalValue && row.dataset.data > dataFinalValue) {
                mostrar = false;
            }
            
            // Mostrar ou esconder a linha
            row.style.display = mostrar ? '' : 'none';
        });
    });
    
    // Limpar filtros
    limparFiltros.addEventListener('click', function() {
        filtroCliente.value = '';
        filtroStatus.value = '';
        filtroDataInicial.value = '';
        filtroDataFinal.value = '';
        
        postagemRows.forEach(row => {
            row.style.display = '';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>