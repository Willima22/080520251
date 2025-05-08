<?php
// Iniciar output buffering
ob_start();

/**
 * Perfil do Usuário
 * Permite ao usuário visualizar e editar seus dados pessoais
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

// Inicializa a conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Obter dados do usuário logado
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    // Atualizar perfil
    if ($acao === 'perfil') {
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        
        // Inicializar variáveis para a foto
        $foto_perfil = $usuario['foto_perfil'] ?? '';
        $foto_atualizada = false;
        
        // Processar upload de foto, se houver
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $arquivo = $_FILES['foto_perfil'];
            
            // Verificar tipo de arquivo
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            $extensoes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif'
            ];
            
            if (in_array($arquivo['type'], $tipos_permitidos)) {
                // Verificar tamanho (máximo 2MB)
                if ($arquivo['size'] <= 2 * 1024 * 1024) {
                    // Criar diretório se não existir
                    $diretorio_fotos = 'arquivos/fotos_perfil/';
                    if (!file_exists($diretorio_fotos)) {
                        mkdir($diretorio_fotos, 0755, true);
                    }
                    
                    // Gerar nome único para o arquivo
                    $extensao = $extensoes[$arquivo['type']];
                    $novo_nome = 'user_' . $user_id . '_' . time() . '.' . $extensao;
                    $caminho_completo = $diretorio_fotos . $novo_nome;
                    
                    // Mover o arquivo para o diretório de destino
                    if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                        // Apagar foto antiga se existir
                        if (!empty($foto_perfil) && file_exists($diretorio_fotos . $foto_perfil)) {
                            unlink($diretorio_fotos . $foto_perfil);
                        }
                        
                        $foto_perfil = $novo_nome;
                        $foto_atualizada = true;
                    } else {
                        setFlashMessage('warning', 'Erro ao salvar a foto. Tente novamente.');
                    }
                } else {
                    setFlashMessage('warning', 'A foto deve ter no máximo 2MB.');
                }
            } else {
                setFlashMessage('warning', 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.');
            }
        }
        
        if (!empty($nome) && !empty($email)) {
            // SQL para atualizar o perfil, incluindo a foto se foi atualizada
            if ($foto_atualizada) {
                $sql = "UPDATE usuarios SET nome = ?, email = ?, foto_perfil = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$nome, $email, $foto_perfil, $user_id]);
            } else {
                $sql = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$nome, $email, $user_id]);
            }
            
            if ($result) {
                // Atualizar sessão
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_foto'] = $foto_perfil;
                setFlashMessage('success', 'Perfil atualizado com sucesso!');
                
                // Registrar no log
                registrarLog('Atualização de Perfil', 'Usuário atualizou seus dados de perfil');
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
        $stmt = $conn->prepare($sql);
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
                    $stmt = $conn->prepare($sql);
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
                <form method="POST" action="perfil.php" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="perfil">
                    
                    <!-- Foto de Perfil -->
                    <div class="mb-4 text-center">
                        <?php
                        $foto_perfil = $usuario['foto_perfil'] ?? '';
                        $foto_url = !empty($foto_perfil) ? 'arquivos/fotos_perfil/' . $foto_perfil : 'https://img.freepik.com/vetores-premium/icone-de-perfil-de-avatar-padrao-imagem-de-usuario-de-midia-social-icone-de-avatar-cinza-silhueta-de-perfil-em-branco-ilustracao-vetorial_561158-3383.jpg?semt=ais_hybrid&w=740';
                        ?>
                        <div class="mb-3">
                            <img src="<?= htmlspecialchars($foto_url) ?>" alt="Foto de Perfil" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <div class="mb-3">
                            <label for="foto_perfil" class="form-label">Alterar Foto de Perfil</label>
                            <input type="file" class="form-control" id="foto_perfil" name="foto_perfil" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuário</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['tipo'] ?? '') ?>" readonly>
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