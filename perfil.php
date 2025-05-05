<?php
/**
 * Perfil do Usuário
 * Permite ao usuário visualizar e editar seus dados pessoais
 */

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Obter dados do usuário logado
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Atualizar perfil
    if ($acao === 'perfil') {
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if (!empty($nome) && !empty($email)) {
            $sql = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$nome, $email, $user_id]);
            
            if ($result) {
                // Atualizar sessão
                $_SESSION['user_nome'] = $nome;
                setFlashMessage('success', 'Perfil atualizado com sucesso!');
            } else {
                setFlashMessage('danger', 'Erro ao atualizar perfil. Tente novamente.');
            }
        } else {
            setFlashMessage('danger', 'Todos os campos são obrigatórios.');
        }
        
        redirect('perfil.php');
    }
    
    // Atualizar senha
    if ($acao === 'senha') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        
        // Verificar se a senha atual está correta
        $sql = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $senha_hash = $stmt->fetchColumn();
        
        // Aqui estamos verificando se a senha está no formato hash ou texto plano
        $senha_correta = password_verify($senha_atual, $senha_hash) || $senha_atual === $senha_hash;
        
        if ($senha_correta) {
            if ($nova_senha === $confirmar_senha) {
                if (strlen($nova_senha) >= 6) {
                    // Hash da nova senha
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute([$nova_senha_hash, $user_id]);
                    
                    if ($result) {
                        setFlashMessage('success', 'Senha atualizada com sucesso!');
                    } else {
                        setFlashMessage('danger', 'Erro ao atualizar senha. Tente novamente.');
                    }
                } else {
                    setFlashMessage('danger', 'A nova senha deve ter pelo menos 6 caracteres.');
                }
            } else {
                setFlashMessage('danger', 'As senhas não coincidem.');
            }
        } else {
            setFlashMessage('danger', 'Senha atual incorreta.');
        }
        
        redirect('perfil.php');
    }
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Meu Perfil</h1>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Dados do Perfil -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Dados Pessoais</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="perfil.php">
                    <input type="hidden" name="acao" value="perfil">
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuário</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['tipo']) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Último Login</label>
                        <input type="text" class="form-control" value="<?= isset($usuario['ultimo_login']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'N/A' ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Atualizar Perfil
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Alterar Senha -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Alterar Senha</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="perfil.php">
                    <input type="hidden" name="acao" value="senha">
                    
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
                        <div class="form-text">
                            A senha deve ter pelo menos 6 caracteres.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Alterar Senha
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Informações da Sessão -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações da Sessão</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Endereço IP</label>
                    <input type="text" class="form-control" value="<?= $_SESSION['user_ip'] ?? $_SERVER['REMOTE_ADDR'] ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tempo Logado</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="tempo_logado" value="<?= $timeLoggedInString ?? '00:00:00' ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="window.location.reload();">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Último Login</label>
                    <input type="text" class="form-control" value="<?= isset($usuario['ultimo_login']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'N/A' ?>" readonly>
                </div>
                
                <div class="text-center mt-4">
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Encerrar Sessão
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>