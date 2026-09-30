<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.I.A. - Gestor de Inventario ASTI</title>
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- SheetJS for Excel compatibility (Local for Offline support) -->
    <script src="js/xlsx.full.min.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <img src="img/Icono.png" alt="GIA Logo">
            <h1>G.I.A<span>.</span></h1>
        </a>

        <div class="nav-links">
            <a href="inventario.php?cat=computo" title="Equipos de Cómputo" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'computo' ? 'active' : '' ?>">
                <i class="ph ph-laptop"></i>
            </a>
            <a href="inventario.php?cat=monitores" title="Monitores" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'monitores' ? 'active' : '' ?>">
                <i class="ph ph-monitor"></i>
            </a>
            <a href="inventario.php?cat=impresoras" title="Impresoras" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'impresoras' ? 'active' : '' ?>">
                <i class="ph ph-printer"></i>
            </a>
            <a href="inventario.php?cat=televisiones" title="Televisiones" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'televisiones' ? 'active' : '' ?>">
                <i class="ph ph-monitor-play"></i>
            </a>
            <a href="inventario.php?cat=redes" title="Infraestructura de Red" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'redes' ? 'active' : '' ?>">
                <i class="ph ph-hard-drives"></i>
            </a>
            <a href="inventario.php?cat=telefonia" title="Telefonía" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'telefonia' ? 'active' : '' ?>">
                <i class="ph ph-phone"></i>
            </a>
            <a href="inventario.php?cat=consumibles" title="Consumibles y Cables" class="<?= isset($_GET['cat']) && $_GET['cat'] == 'consumibles' ? 'active' : '' ?>">
                <i class="ph ph-battery-full"></i>
            </a>
            <a href="historial_general.php" title="Historial y Auditoría" class="<?= basename($_SERVER['PHP_SELF']) == 'historial_general.php' ? 'active' : '' ?>">
                <i class="ph ph-clock-counter-clockwise"></i>
            </a>
            <a href="informes.php" title="Informes" class="<?= basename($_SERVER['PHP_SELF']) == 'informes.php' ? 'active' : '' ?>">
                <i class="ph ph-file-text"></i>
            </a>
            <a href="gestion_administrativa.php" title="Gestión Administrativa" class="<?= basename($_SERVER['PHP_SELF']) == 'gestion_administrativa.php' ? 'active' : '' ?>">
                <i class="ph ph-gear"></i>
            </a>
        </div>

        <div class="nav-actions">
        </div>
    </nav>

    <main class="main-content">