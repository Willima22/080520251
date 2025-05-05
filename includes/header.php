<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect('login.php');
}

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
</head>
<body>
    <!-- Header with gradient -->
    <header class="instagram-gradient py-3">
        <div class="container">
            <h1 class="text-white"><?= APP_NAME ?></h1>
        </div>
    </header>

    <!-- Navigation -->
    <?php if(isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-chart-bar"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-calendar-plus"></i> Agendar Postagem
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['clientes', 'clientes_visualizar']) ? 'active' : '' ?>" href="#" id="clientesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users"></i> Clientes
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="clientesDropdown">
                            <li><a class="dropdown-item" href="clientes.php">Cadastrar Cliente</a></li>
                            <li><a class="dropdown-item" href="clientes_visualizar.php">Visualizar Clientes</a></li>
                        </ul>
                    </li>
                    <?php if(isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'usuarios' ? 'active' : '' ?>" href="usuarios.php">
                            <i class="fas fa-user-cog"></i> Usuários
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'relatorios' ? 'active' : '' ?>" href="relatorios.php">
                            <i class="fas fa-file-alt"></i> Relatórios
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <i class="fas fa-user-circle"></i> <?= $_SESSION['user_nome'] ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

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
    <main class="container py-4">
