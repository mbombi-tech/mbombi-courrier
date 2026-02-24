<?php
session_start();

require_once "../courrier/Auth.php";
require_once "../courrier/conn.php";

requireLogin();

$user = $_SESSION["user"];

$userId    = $user['id'];
$serviceId = $user['service_id'];
$role      = $user['role'];


/* ================================
   STATS TYPES
================================ */

$totalEntrant = 0;
$totalSortant = 0;
$totalInterne = 0;

if (in_array($role, ['Coordon','SG','AP','Directeur'])) {

    $stmt = $db->prepare("
        SELECT type, COUNT(*) total
        FROM courriers
        WHERE service_actuel_id = ?
        GROUP BY type
    ");
    $stmt->execute([$serviceId]);

    while ($row = $stmt->fetch()) {
        if ($row['type'] === 'entrant')  $totalEntrant = $row['total'];
        if ($row['type'] === 'sortant')  $totalSortant = $row['total'];
        if ($row['type'] === 'interne')  $totalInterne = $row['total'];
    }
}


/* ================================
   SERVICE
================================ */

$stmt = $db->prepare("
    SELECT nom FROM services WHERE id = ?
");
$stmt->execute([$serviceId]);

$serviceName = $stmt->fetchColumn() ?? "Service inconnu";


/* ================================
   INIT STATS
================================ */

$totalService          = 0;
$totalRecuService      = 0;

$totalAssignesAgent    = 0;
$totalRecuAgent        = 0;

$totalSoumis           = 0;
$totalNonTraitesPerso  = 0;
$totalDansService      = 0;


/* ================================
   STATS AGENT
================================ */

if ($role === 'agent') {

    // Assignés
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM courrier_affectations
        WHERE utilisateur_id = ?
    ");
    $stmt->execute([$userId]);

    $totalAssignesAgent = $stmt->fetchColumn();


    // A traiter
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM courrier_affectations ca
        JOIN courriers c ON ca.courrier_id = c.id
        WHERE ca.utilisateur_id = ?
        AND ca.etat IN ('non_lu','en_cours')
    ");
    $stmt->execute([$userId]);

    $totalRecuAgent = $stmt->fetchColumn();
}


/* ================================
  

/* ================================
   STATS AP / DIRECTEUR
================================ */

if (in_array($role, ['AP','Directeur','Coordon','agent'])) {

    // Courriers soumis à cet utilisateur
    $stmt = $db->prepare("
          SELECT COUNT(*)
    FROM courriers c
    JOIN utilisateurs u ON c.service_actuel_id  = u.service_id
    WHERE u.id = ?
      AND c.statut != 'reçu'
    ");
    $stmt->execute([$userId]);
    $totalSoumis = $stmt->fetchColumn();


    // Courriers non traités (en cours ou non lus)
  $stmt = $db->prepare("
    SELECT c.*
    FROM courriers c
    WHERE c.service_actuel_id = ?
      AND c.statut NOT IN ('recu', 'reçu')
      AND NOT EXISTS (
          SELECT 1
          FROM suivis s
          WHERE s.courrier_id = c.id
            AND s.action NOT IN ('reception', 'assignation', 'soumission')
      )
    ORDER BY c.date_creation DESC
");
$stmt->execute([$serviceId]);
$courriersNonTraites = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalNonTraites = count($courriersNonTraites);








    // Tous les courriers dans le service
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM courriers
        WHERE service_actuel_id = ?
    ");
    $stmt->execute([$serviceId]);
    $totalDansService = $stmt->fetchColumn();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<title>Dashboard | Gestion Courrier</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f4f6f9; }

.sidebar {
    min-height:100vh;
    background:#0d6efd;
    color:white;
}

.sidebar a {
    color:white;
    text-decoration:none;
    padding:12px;
    display:block;
    border-radius:8px;
}

.sidebar a:hover {
    background:rgba(255,255,255,0.2);
}

.card-icon { font-size:2rem; }
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

<?php if ($role === "agent"): ?>
<a href="ajouter_courrier.php"><i class="bi bi-plus-circle"></i> Ajouter</a>
<a href="liste_courriers.php"><i class="bi bi-list-ul"></i> Mes courriers</a>
<?php endif; ?>

<?php if (in_array($role, ['Coordon','SG','AP','Directeur'])): ?>
<a href="liste_courriers.php"><i class="bi bi-eye"></i> Voir courriers</a>
<a href="statistiques.php"><i class="bi bi-graph-up"></i> Statistiques</a>
<?php endif; ?>

<?php if ($role === "admin"): ?>
<a href="utilisateurs.php"><i class="bi bi-people"></i> Utilisateurs</a>
<a href="services.php"><i class="bi bi-building"></i> Services</a>
<?php endif; ?>

<hr>
<a href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
</div>

<!-- CONTENT -->
<div class="col-md-10 p-4">

<h2 class="mb-4">Bonjour <?= htmlspecialchars($user['nom']) ?> 👋</h2>

<div class="row g-4">

<!-- ROLE -->
<div class="col-md-4">
<div class="card shadow-sm"><div class="card-body">
<div class="d-flex justify-content-between">
<div><h6>Rôle</h6><h4><?= ucfirst($role) ?></h4></div>
<div class="card-icon text-primary"><i class="bi bi-person-badge"></i></div>
</div></div></div></div>

<!-- SERVICE -->
<div class="col-md-4">
<div class="card shadow-sm"><div class="card-body">
<div class="d-flex justify-content-between">
<div><h6>Service</h6><h4><?= htmlspecialchars($serviceName) ?></h4></div>
<div class="card-icon text-success"><i class="bi bi-building"></i></div>
</div></div></div></div>

<!-- STATS -->
<div class="col-md-4">
<div class="card shadow-sm"><div class="card-body">
<h6>Statistiques</h6>

<?php if ($role === 'Coordon'): ?>
<p>🏢 Stock dans le service : <strong><?= $totalDansService ?></strong></p>
<?php endif; ?>


<?php if ($role === 'agent'): ?>
<p>📌 Assignés : <strong><?= $totalAssignesAgent ?></strong></p>
<p>📩 En attente : <strong><?= $totalNonTraitesPerso ?></strong></p>
<?php endif; ?>



<?php if (in_array($role,['AP','Directeur'])): ?>
<hr>
<p>📨 Courriers soumis à vous : <strong><?= $totalSoumis ?></strong></p>
<p>⏳ En attente  : <strong><?= $totalNonTraitesPerso ?></strong></p>
<p>🏢 Stock dans le service : <strong><?= $totalDansService ?></strong></p>
<?php endif; ?>

<?php if ($role === 'admin'): ?>
<p class="text-muted">Administration système</p>
<?php endif; ?>

</div></div></div>

</div>

<!-- ACTIONS -->
<div class="mt-5">
<div class="card shadow-sm"><div class="card-body">
<h5>Actions rapides</h5>

<?php if ($role === 'agent'): ?>
<a href="ajouter_courrier.php" class="btn btn-primary btn-sm w-100 mb-2">
<i class="bi bi-plus-circle"></i> Nouveau courrier
</a>
<?php endif; ?>

<a href="suivre_courrier.php" class="btn btn-outline-primary btn-sm w-100">
<i class="bi bi-search"></i> Suivre un courrier
</a>

</div></div>

<br>

<!-- TYPES -->
<?php if (in_array($role,['AP','Directeur'])): ?>
<div class="text-center mt-3">
<h5 class="mb-3">📊 Répartition des courriers</h5>
<div class="row">
<div class="col-4"><div class="border rounded p-2 bg-light">
<strong class="text-primary fs-4"><?= $totalSoumis  ?></strong><br>Courriers s
</div></div>
<div class="col-4"><div class="border rounded p-2 bg-light">
<strong class="text-success fs-4"><?= $totalNonTraitesPerso ?></strong><br>En attente
</div></div>
<div class="col-4"><div class="border rounded p-2 bg-light">
<strong class="text-warning fs-4"><?=  $totalDansService ?></strong><br>Stockés dans le service
</div></div>
</div>
</div>
<?php endif; ?>

</div>
</div>
</div>
</body>
</html>
<!-- ================= NOTIFICATIONS ================= -->

<script>

function fetchNotifications() {

    fetch('notifications_ajax.php')

    .then(response => response.json())

    .then(data => {

        if (data.length > 0) {

            data.forEach(n => {
                alert(n.message);
            });

            fetch('marquer_notifications_lues.php', {
                method: 'POST'
            });

        }

    })

    .catch(err => console.error(err));

}

setInterval(fetchNotifications, 5000);

</script>
 