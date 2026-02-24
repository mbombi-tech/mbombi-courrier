<?php
// 🔐 Démarrage sécurisé de la session (UNE SEULE FOIS)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiploCourrier 2.0</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            📨 DiploCourrier 2.0
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menu"
            aria-controls="menu"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div id="menu" class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'liste.php' ? 'active' : '' ?>" href="liste.php">Courriers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'ajouter_courrier.php' ? 'active' : '' ?>" href="ajouter_courrier.php">Ajouter</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'suivre_courrier.php' ? 'active' : '' ?>" href="suivre_courrier.php">Suivi</a>
                </li>
				 <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'suivre_courrier.php' ? 'active' : '' ?>" href="statistiques.php">Statiqtiques</a>
                </li>
            </ul>

            <?php if (isset($_SESSION['user'])) : ?>
                <span class="navbar-text me-3">
                    👤 <?= htmlspecialchars($_SESSION['user']['nom'] ?? '') ?>
                    <small class="text-muted">
                        (<?= htmlspecialchars($_SESSION['user']['role'] ?? '') ?>)
                    </small>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Déconnexion</a>
            <?php else : ?>
                <a href="login.php" class="btn btn-outline-light btn-sm">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">