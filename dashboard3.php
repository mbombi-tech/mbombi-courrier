<?php
require_once "../courrier/Auth.php";
require_once "../courrier/conn.php";
requireLogin();

$user = $_SESSION["user"];
$serviceId = $user['service_id'];
$userId    = $user['id'];

// ✅ Fonction pour vérifier les rôles
function hasRole($user, $roles = []) {
    return in_array($user['role'], $roles);
}

// =========================
// 1️⃣ Comptage des courriers non lus pour cet utilisateur
// =========================
$stmt = $db->prepare("
    SELECT COUNT(*) AS total
    FROM courriers c
    JOIN courrier_affectations ca ON ca.courrier_id = c.id
    WHERE ca.utilisateur_id = ?
      AND ca.etat = 'non_lu'
");
$stmt->execute([$userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$nbCourriersNonLus = $result['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Gestion Courrier</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }
        .sidebar {
            min-height: 100vh;
            background: #0d6efd;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px;
            display: block;
            border-radius: 8px;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .card-icon {
            font-size: 2rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-2 sidebar p-3">
            <h4 class="text-center mb-4">
                <i class="bi bi-envelope-paper"></i> Courrier
            </h4>

            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Tableau de bord</a>

            <!-- LIENS PAR RÔLE -->
            <?php if(hasRole($user, ['agent','CB','CD'])): ?>
                <a href="ajouter_courrier.php"><i class="bi bi-plus-circle"></i> Ajouter courrier</a>
                <a href="liste_courriers.php"><i class="bi bi-list-ul"></i> Mes courriers</a>
            <?php endif; ?>

            <?php if(hasRole($user, ['admin','AP','Directeur','SG','Coordon','Chef_pool'])): ?>
                <a href="liste_courriers.php"><i class="bi bi-eye"></i> Tous les courriers</a>
                <a href="statistiques.php"><i class="bi bi-graph-up"></i> Statistiques</a>
            <?php endif; ?>

            <?php if(hasRole($user, ['admin'])): ?>
                <a href="utilisateurs.php"><i class="bi bi-people"></i> Utilisateurs</a>
                <a href="services.php"><i class="bi bi-building"></i> Services</a>
                <a href="audit.php"><i class="bi bi-shield-check"></i> Journal d’audit</a>
            <?php endif; ?>

            <!-- ALERTES COURRIERS NON LUS -->
            <?php if ($nbCourriersNonLus > 0): ?>
                <div class="alert alert-warning mt-3">
                    📩 Vous avez <strong><?= $nbCourriersNonLus ?></strong>
                    nouveau(x) courrier(s) à traiter.
                </div>
            <?php else: ?>
                <div class="alert alert-success mt-3">
                    ✅ Aucun nouveau courrier en attente.
                </div>
            <?php endif; ?>

            <hr>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </div>

        <!-- CONTENT -->
        <div class="col-md-10 p-4">

            <h2 class="mb-4">
                Bonjour <?= htmlspecialchars($user["nom"]) ?> 👋
            </h2>

            <div class="row g-4">

                <!-- CARD ROLE -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>Rôle</h6>
                                    <h4><?= ucfirst($user["role"]) ?></h4>
                                </div>
                                <div class="card-icon text-primary">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD SERVICE -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>Service</h6>
                                    <h4><?= htmlspecialchars($user["service_id"]) ?></h4>
                                </div>
                                <div class="card-icon text-success">
                                    <i class="bi bi-building"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD ACTIONS -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6>Actions rapides</h6>

                            <!-- Pour tous les agents/CB/CD : ajouter un courrier -->
                            <?php if(hasRole($user, ['agent','CB','CD'])): ?>
                                <a href="ajouter_courrier.php" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="bi bi-plus-circle"></i> Nouveau courrier
                                </a>
                            <?php endif; ?>

                            <a href="suivre_courrier.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                <i class="bi bi-search"></i> Suivre un courrier
                            </a>

                            <!-- Pour les rôles autorisés à transférer/affecter -->
                            <?php if(hasRole($user, ['admin','AP','Directeur','SG','Coordon','Chef_pool'])): ?>
                                <a href="transfert.php" class="btn btn-warning btn-sm w-100 mb-2">
                                    🔁 Transférer un courrier
                                </a>
                                <a href="affectation.php" class="btn btn-primary btn-sm w-100 mb-2">
                                    👥 Affecter des utilisateurs
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>

            <!-- PLACE POUR STATISTIQUES -->
            <div class="mt-5">
                <div class="alert alert-info">
                    📌 Ici viendront :
                    <ul class="mb-0">
                        <li>Nombre de courriers par statut</li>
                        <li>Derniers transferts</li>
                        <li>Activité récente</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
