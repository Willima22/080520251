<?php
// header.php - Cabeçalho completo atualizado

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
date_default_timezone_set('America/Araguaina');

// Formatador de data/hora em português
$formatter = new IntlDateFormatter(
    'pt_BR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::SHORT,
    'America/Araguaina',
    IntlDateFormatter::GREGORIAN,
    "d 'de' MMMM 'de' yyyy - HH'h'mm"
);
$dataHoraFormatada = $formatter->format(new DateTime());

// Dados do usuário
$usuarioNome = $_SESSION['user_name'] ?? 'Usuário';
$cidadeEstado = 'Palmas, Tocantins';
$ipUsuario = $_SERVER['REMOTE_ADDR'] ?? 'IP desconhecido';

// Cálculo do tempo logado
if (isset($_SESSION['login_time'])) {
    $segundosLogado = time() - $_SESSION['login_time'];
    $horas = floor($segundosLogado / 3600);
    $minutos = floor(($segundosLogado % 3600) / 60);
    $tempoAtivo = sprintf('%02d:%02d', $horas, $minutos);
} else {
    $tempoAtivo = '00:00';
}

// Foto de perfil
$fotoPerfil = $_SESSION['foto_perfil'] ?? 'assets/img/default_user.png';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Agendamento</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/agendamento.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/cards-enhanced.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/light.css">

  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f9f9f9;
      color: #2F1847;
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 240px;
      background-color: #FAF6F1;
      padding-top: 20px;
      overflow-y: auto;
      border-right: 1px solid #ddd;
      z-index: 1000;
    }
    .sidebar a {
      color: #2F1847;
      text-decoration: none;
      display: block;
      padding: 14px 20px;
      transition: background-color 0.3s;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 13px;
    }
    .sidebar a:hover {
      background-color: rgba(122, 199, 79, 0.5);
      color: #2F1847;
    }
    .sidebar a.active {
      background-color: #7AC74F;
      color: #ffffff;
    }
    .sidebar-logo {
      padding: 1rem;
      text-align: center;
      border-bottom: 1px solid rgba(47, 24, 71, 0.1);
    }
    .sidebar-logo img {
      max-width: 80%;
      height: auto;
    }
    .navbar-custom {
      margin-left: 240px;
      background-color: #ffffff;
      height: 70px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      border-bottom: 1px solid #ddd;
      z-index: 999;
      position: fixed;
      top: 0;
      right: 0;
      left: 0;
    }
    .navbar-custom .info-bar {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
      font-size: 14px;
    }
    .navbar-custom .info-bar span {
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
    }
    .navbar-custom .user-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .user-photo-container {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      overflow: hidden;
      border: 2px solid #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .user-photo {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }
    .navbar-custom .btn-danger {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 14px;
      padding: 6px 12px;
      background-color: #dc3545;
      border: none;
    }
    .navbar-custom .btn-danger:hover {
      background-color: #c82333;
    }
    main {
      margin-left: 240px;
      padding: 20px;
      margin-top: 70px;
    }
    h1 {
      color: #2F1847;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    hr {
      border-top: 1px solid #ccc;
      margin: 10px 0;
    }
  </style>
</head>

<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-logo mb-4">
    <img src="assets/img/logo.png" alt="Logo">
  </div>

  <a href="dashboard.php" class="<?= ($currentPage == 'dashboard') ? 'active' : '' ?>">
    <i class="fas fa-tachometer-alt me-2"></i> DASHBOARD
  </a>

  <hr>

  <a href="clientes_visualizar.php" class="<?= ($currentPage == 'clientes_visualizar') ? 'active' : '' ?>">
    <i class="fas fa-users me-2"></i> CLIENTES
  </a>

  <hr>

  <a href="index.php" class="<?= ($currentPage == 'index') ? 'active' : '' ?>">
    <i class="fas fa-calendar-plus me-2"></i> AGENDAR POSTAGEM
  </a>

  <a href="postagens_cards.php" class="<?= ($currentPage == 'postagens_cards' || $currentPage == 'postagens_agendadas') ? 'active' : '' ?>">
    <i class="fas fa-calendar-check me-2"></i> POSTAGENS AGENDADAS
  </a>

  <hr>

  <a href="usuarios.php" class="<?= ($currentPage == 'usuarios') ? 'active' : '' ?>">
    <i class="fas fa-users-cog me-2"></i> USUÁRIOS
  </a>

  <hr>

  <a href="relatorios.php" class="<?= ($currentPage == 'relatorios') ? 'active' : '' ?>">
    <i class="fas fa-chart-line me-2"></i> RELATÓRIOS
  </a>

  <hr>

  <a href="logs.php" class="<?= ($currentPage == 'logs') ? 'active' : '' ?>">
    <i class="fas fa-file-alt me-2"></i> LOGS DO SISTEMA
  </a>

  <a href="configuracoes.php" class="<?= ($currentPage == 'configuracoes') ? 'active' : '' ?>">
    <i class="fas fa-cogs me-2"></i> CONFIGURAÇÕES
  </a>

  <a href="webhooks.php" class="<?= ($currentPage == 'webhooks') ? 'active' : '' ?>">
    <i class="fas fa-random me-2"></i> WEBHOOKS
  </a>

  <hr>

  <a href="meu_perfil.php" class="<?= ($currentPage == 'meu_perfil') ? 'active' : '' ?>">
    <i class="fas fa-user-circle me-2"></i> MEU PERFIL
  </a>
</nav>

<!-- Barra Superior -->
<header class="navbar-custom">
  <div class="info-bar">
    <span><i class="fas fa-user"></i> <?= htmlspecialchars($usuarioNome) ?></span> |
    <span><i class="fas fa-globe"></i> <?= $cidadeEstado ?></span> |
    <span><i class="fas fa-clock"></i> <?= $dataHoraFormatada ?></span> |
    <span><i class="fas fa-desktop"></i> IP: <?= $ipUsuario ?></span> |
    <span><i class="fas fa-hourglass-half"></i> <span id="tempo-atividade"><?= $tempoAtivo ?></span></span>
  </div>

  <div class="user-actions">
    <div class="user-photo-container">
      <?php
      $foto_perfil = $_SESSION['user_foto'] ?? '';
      $foto_url = !empty($foto_perfil) && file_exists('arquivos/fotos_perfil/' . $foto_perfil)
          ? 'arquivos/fotos_perfil/' . $foto_perfil
          : 'assets/img/semfoto.png';
      ?>
      <img src="<?= htmlspecialchars($foto_url) ?>" alt="Foto de Perfil" class="user-photo">
    </div>
    <a href="logout.php" class="btn btn-danger">
      <i class="fas fa-sign-out-alt"></i> Sair
    </a>
  </div>
</header>

<!-- Script tempo de atividade -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  let tempoInicial = '<?= $tempoAtivo ?>';
  let [horas, minutos] = tempoInicial.split(':').map(Number);
  let totalSegundos = (horas * 3600) + (minutos * 60);

  setInterval(function() {
    totalSegundos++;
    let h = Math.floor(totalSegundos / 3600);
    let m = Math.floor((totalSegundos % 3600) / 60);
    let s = totalSegundos % 60;
    document.getElementById('tempo-atividade').textContent = 
        String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }, 1000);
});
</script>

<main>
<!-- Aqui começa o conteúdo da página -->
