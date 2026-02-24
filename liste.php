<?php
session_start();
require_once "../courrier/conn.php";

/* =======================
   SÉCURITÉ
======================= */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user']['id'];
$role      = $_SESSION['user']['role'] ?? '';
$serviceId = $_SESSION['user']['service_id'] ?? 0;

/* =======================
   NOM DU SERVICE
======================= */
$stmt = $db->prepare("SELECT nom FROM services WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

$nom_service = $service['nom'] ?? '—';

/* =======================
   RECHERCHE
======================= */
$search = trim($_GET['search'] ?? '');

/* =======================
   REQUÊTE DE BASE
======================= */

$params = [];

$sql = "
SELECT 
    c.*,

    (
        SELECT s2.nom
        FROM suivis sv
        JOIN services s2 ON sv.service_source_id = s2.id
        WHERE sv.courrier_id = c.id
        AND sv.action = 'transfert'
        ORDER BY sv.date_action DESC
        LIMIT 1
    ) AS service_source,

    (
        SELECT sv.date_action
        FROM suivis sv
        WHERE sv.courrier_id = c.id
        AND sv.action = 'transfert'
        ORDER BY sv.date_action DESC
        LIMIT 1
    ) AS date_reception

FROM courriers c
WHERE c.service_actuel_id = ?
";

$params[] = $serviceId;

/* =======================
   FILTRE PAR RÔLE
======================= */

if ($role === 'agent') {

    // Agent → seulement ses courriers
    $sql .= "
        AND c.id IN (
            SELECT courrier_id
            FROM courrier_affectations
            WHERE utilisateur_id = ?
        )
    ";

    $params[] = $user_id;

}

/* AP / Directeur / SG → pas de 'reçu' */
elseif (in_array($role, ['AP', 'Directeur', 'SG'])) {

    $sql .= "
        AND c.statut NOT IN ('reçu', 'recu')
    ";

}

/* Coordon → voit tout (aucun filtre) */


/* =======================
   RECHERCHE TEXTE
======================= */

if ($search !== '') {

    $sql .= "
        AND (
            c.tracking_code LIKE ?
            OR c.type LIKE ?
            OR c.objet LIKE ?
            OR c.expediteur LIKE ?
            OR c.destinataire LIKE ?
        )
    ";

    for ($i = 0; $i < 5; $i++) {
        $params[] = "%$search%";
    }
}


/* =======================
   TRI
======================= */

$sql .= " 
ORDER BY COALESCE(date_reception, c.date_creation) DESC
";


/* =======================
   EXECUTION
======================= */

$stmt = $db->prepare($sql);
$stmt->execute($params);

$courriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nbCourriers = count($courriers);

include "../courrier/header.php";
?>

<div class="container mt-4">

<h3>📂 Liste des courriers — <?= htmlspecialchars($nom_service) ?></h3>


<!-- ================= RECHERCHE ================= -->

<form method="GET" class="mb-3 d-flex">

    <input type="text"
           name="search"
           class="form-control me-2"
           placeholder="Rechercher..."
           value="<?= htmlspecialchars($search) ?>">

    <button class="btn btn-primary">🔍</button>

</form>


<!-- ================= TOTAL ================= -->

<div class="mb-3">
    <span class="badge bg-primary fs-6">
        Total : <?= $nbCourriers ?> courrier<?= $nbCourriers > 1 ? 's' : '' ?>
    </span>
</div>


<!-- ================= TITRE PAR RÔLE ================= -->

<?php if ($role === 'agent'): ?>
<p class="text-muted">📌 Vos courriers assignés</p>

<?php elseif (in_array($role, ['AP', 'Directeur', 'SG'])): ?>
<p class="text-muted">📌 Courriers en traitement</p>

<?php elseif ($role === 'Coordon'): ?>
<p class="text-muted">📌 Tous les courriers du service</p>
<?php endif; ?>


<!-- ================= BOUTON AJOUT ================= -->

<?php if ($role === 'Coordon'): ?>
<a href="ajouter_courrier.php" class="btn btn-success mb-3">
➕ Nouveau courrier
</a>
<?php endif; ?>


<!-- ================= TABLE ================= -->

<?php if ($nbCourriers > 0): ?>

<div class="table-responsive">

<table class="table table-striped table-hover align-middle">

<thead class="table-light">
<tr>
<th>#</th>
<th>Tracking</th>
<th>Type</th>
<th>Objet</th>
<th>Expéditeur</th>
<th>Destinataire</th>
<th>Statut</th>
<th>Service source</th>
<th>Date</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach ($courriers as $i => $row): ?>

<?php
$badgeClass = 'bg-dark';

switch ($row['statut']) {

    case 'reçu':
    case 'recu':
        $badgeClass = 'bg-success';
        break;

    case 'en traitement':
        $badgeClass = 'bg-warning text-dark';
        break;

    case 'transféré':
        $badgeClass = 'bg-info text-dark';
        break;

    case 'clôturé':
        $badgeClass = 'bg-secondary';
        break;
}
?>

<tr>

<td><?= $i + 1 ?></td>

<td><?= htmlspecialchars($row['tracking_code']) ?></td>

<td><?= htmlspecialchars($row['type']) ?></td>

<td><?= htmlspecialchars($row['objet']) ?></td>

<td><?= htmlspecialchars($row['expediteur']) ?></td>

<td><?= htmlspecialchars($row['destinataire']) ?></td>

<td>
<span class="badge <?= $badgeClass ?>">
<?= ucfirst($row['statut']) ?>
</span>
</td>

<td><?= htmlspecialchars($row['service_source'] ?? '—') ?></td>

<td>
<?= date('d/m/Y H:i', strtotime($row['date_reception'] ?? $row['date_creation'])) ?>
</td>

<td>

<?php
$statut_row = strtolower(trim($row['statut'] ?? ''));

/* ===========================
   COURRIER CLÔTURÉ
=========================== */

if ($statut_row === 'clôturé') :
?>

<!-- ✅ Bouton ARCHIVER -->
<a class="btn btn-sm btn-dark"
   href="archiver_courrier.php?id=<?= intval($row['id']) ?>"
   onclick="return confirm('🗄 Voulez-vous archiver ce courrier ?')">
🗄 Archiver
</a>

<?php else : ?>

<!-- ✅ Bouton SUIVI -->
<a class="btn btn-sm btn-info"
   href="suivre_courrier.php?tracking_code=<?= urlencode($row['tracking_code']) ?>">
Suivre
</a>

<?php endif; ?>


<!-- ===========================
   AUTRES ACTIONS (inchangées)
=========================== -->

<?php if ($role === 'Coordon' && $row['statut'] !== 'clôturé'): ?>

<a class="btn btn-sm btn-warning"
   href="suivre_courrier6.php?tracking_code=<?= urlencode($row['tracking_code']) ?>">
Soumettre
</a>

<?php endif; ?>


<?php if ($role === 'AP'): ?>

<a class="btn btn-sm btn-warning"
   href="Transfert.php?tracking_code=<?= urlencode($row['tracking_code']) ?>">
Transférer
</a>

<?php endif; ?>


<!-- Historique toujours visible -->
<a class="btn btn-sm btn-secondary"
   href="historique.php?code=<?= urlencode($row['tracking_code']) ?>">
Historique
</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<?php else: ?>

<p class="text-center text-muted">
Aucun courrier disponible.
</p>

<?php endif; ?>

</div>

<?php include "../courrier/footer.php"; ?>


<!-- ================= NOTIFICATIONS ================= -->

<script>

function fetchNotifications() {

    fetch('notifications_ajax.php')

    .then(r => r.json())

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
