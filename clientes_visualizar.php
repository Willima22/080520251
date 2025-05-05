<?php
require_once 'config/config.php';
require_once 'config/db.php';
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

// Get all clients
$query = "SELECT * FROM clientes ORDER BY nome ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
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
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Lista de Clientes</strong>
                        <span class="badge bg-primary"><?= count($clients) ?> clientes</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($clients) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Instagram</th>
                                    <th>ID do Grupo</th>
                                    <th>ID do Instagram</th>
                                    <th>Conta de Anúncio</th>
                                    <th>Postagens</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['nome']) ?></td>
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
                                        <div class="btn-group" role="group">
                                            <a href="clientes.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal" 
                                                    data-client-id="<?= $client['id'] ?>" 
                                                    data-client-name="<?= htmlspecialchars($client['nome']) ?>"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o cliente <strong id="clientName"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="clientes_visualizar.php">
                    <input type="hidden" name="client_id" id="clientId">
                    <button type="submit" name="delete" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set client data in delete modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button.getAttribute('data-client-id');
            const clientName = button.getAttribute('data-client-name');
            
            document.getElementById('clientId').value = clientId;
            document.getElementById('clientName').textContent = clientName;
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
