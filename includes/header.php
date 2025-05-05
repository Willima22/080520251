<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect('login.php');
}

// Set login time if not set
if (isset($_SESSION['user_id']) && !isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
}

// Calculate time logged in
$loginTime = isset($_SESSION['login_time']) ? $_SESSION['login_time'] : time();
$timeLoggedIn = time() - $loginTime;
$hours = floor($timeLoggedIn / 3600);
$minutes = floor(($timeLoggedIn % 3600) / 60);
$seconds = $timeLoggedIn % 60;
$timeLoggedInString = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

// Check for inactivity timeout (5 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
    // Last activity was more than 5 minutes ago
    session_unset();     // unset $_SESSION variable
    session_destroy();   // destroy session data
    redirect('login.php?reason=inactivity');
}
$_SESSION['last_activity'] = time(); // update last activity time stamp

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= ucfirst($currentPage) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Flatpickr for Date/Time -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* Sidebar styles */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            width: 250px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            background-color: #0a1c30;
            transition: all 0.3s;
            overflow-y: auto;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            padding-top: 1rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: #e9ecef;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 0;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(108, 189, 69, 0.2);
            color: #6cbd45;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #6cbd45;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding-top: 56px;
            flex: 1;
            width: calc(100% - 250px);
            transition: all 0.3s;
        }
        
        .navbar-aw7 {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            height: 56px;
        }
        
        .brand-logo img {
            height: 40px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .nav-text {
                display: none;
            }
        }
        
        /* Mobile menu toggle */
        #sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 576px) {
            #sidebar-toggle {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar.show .nav-link span {
                display: inline;
            }
            
            .sidebar.show .nav-link i {
                margin-right: 0.75rem;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-aw7">
        <div class="container-fluid">
            <button id="sidebar-toggle" class="btn btn-sm d-md-none me-2">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand brand-logo" href="dashboard.php">
                <img src="assets/img/logo.png" alt="AW7 Postagens">
            </a>
            
            <div class="d-flex ms-auto align-items-center">
                <div class="text-end me-3 d-none d-md-block">
                    <div class="small text-muted"><?= date('d/m/Y H:i') ?> (Palmas-TO)</div>
                    <div class="small">
                        <strong><?= $_SESSION['user_nome'] ?? 'Usuário' ?></strong> | 
                        IP: <?= $_SESSION['user_ip'] ?? $_SERVER['REMOTE_ADDR'] ?> | 
                        Tempo: <?= $timeLoggedInString ?>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user me-2"></i>Meu Perfil</a></li>
                        <li><a class="dropdown-item" href="configuracoes.php"><i class="fas fa-cog me-2"></i>Configurações</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Agendar Postagem</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'postagens_agendadas' ? 'active' : '' ?>" href="postagens_agendadas.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Postagens Agendadas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($currentPage, ['clientes', 'clientes_visualizar']) ? 'active' : '' ?>" href="clientes_visualizar.php">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <?php if(isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'usuarios' ? 'active' : '' ?>" href="usuarios.php">
                        <i class="fas fa-user-cog"></i>
                        <span>Usuários</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'relatorios' ? 'active' : '' ?>" href="relatorios.php">
                        <i class="fas fa-chart-pie"></i>
                        <span>Relatórios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'configuracoes' ? 'active' : '' ?>" href="configuracoes.php">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'perfil' ? 'active' : '' ?>" href="perfil.php">
                        <i class="fas fa-user"></i>
                        <span>Meu Perfil</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="<?= isset($_SESSION['user_id']) ? 'main-content' : '' ?>">
        <!-- Flash Messages -->
        <?php $flash = getFlashMessage(); ?>
        <?php if($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main content container -->
        <main class="container-fluid py-4">
