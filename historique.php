<?php
session_start();
include "../courrier/header.php";
require_once "../courrier/conn.php";

// 🔒 Sécurité
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$db = new mysqli("localhost", "root", "", "dbcourriers");
if ($db->connect_error) {
    die("Connexion échouée");
}

$tracking_code = trim($_GET['code'] ?? '');
?>

<h3 class="mb-4">📜 Historique complet du courrier</h3>

<?php if (empty($tracking_code)) : ?>

<div class="alert alert-info">Veuillez sélectionner un courrier.</div>

<?php else :

// ================== COURRIER ==================
$stmt = $db->prepare("
    SELECT c.*, s.nom AS service_actuel
    FROM courriers c
    LEFT JOIN services s ON c.service_actuel_id = s.id
    WHERE c.tracking_code = ?
    LIMIT 1
");
$stmt->bind_param("s", $tracking_code);
$stmt->execute();
$courrier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$courrier):
?>

<div class="alert alert-warning">Courrier introuvable.</div>

<?php else: ?>

<!-- ================= INFOS COURRIER ================= -->
<div class="card shadow mb-4 p-3">
    <h5>📌 Courrier : <?= htmlspecialchars($courrier['tracking_code']) ?></h5>

    <p>
        <strong>Statut :</strong>
        <span class="badge bg-info"><?= ucfirst($courrier['statut']) ?></span>
    </p>

    <p>
        <strong>Service actuel :</strong>
        <?= htmlspecialchars($courrier['service_actuel'] ?? '—') ?>
    </p>

    <!-- ✅ Lien vers suivi -->
    <a href="suivre_courrier.php?tracking_code=<?= urlencode($courrier['tracking_code']) ?>"
       class="btn btn-outline-primary btn-sm mt-2">
        🔍 Voir le suivi du courrier
    </a>
</div>

<!-- ================= HISTORIQUE ================= -->
<h4 class="mb-3">🕘 Historique des actions</h4>

<?php
$stmt = $db->prepare("
    SELECT 
        su.date_action,
        su.action,
        su.description,
        u.nom AS utilisateur,
        srv.nom AS service_utilisateur,
        ss.nom AS service_source,
        sd.nom AS service_destination
    FROM suivis su
    LEFT JOIN utilisateurs u ON su.utilisateur_id = u.id
    LEFT JOIN services srv ON u.service_id = srv.id
    LEFT JOIN services ss ON su.service_source_id = ss.id
    LEFT JOIN services sd ON su.service_destination_id = sd.id
    WHERE su.courrier_id = ?
    ORDER BY su.date_action DESC
");
$stmt->bind_param("i", $courrier['id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php if ($result->num_rows > 0): ?>
<ul class="list-unstyled">

<?php while ($row = $result->fetch_assoc()): ?>
    <li class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body">

                <small class="text-muted">
                    <?= date('d/m/Y H:i', strtotime($row['date_action'])) ?>
                </small>

                <p class="mb-1">
                    <strong><?= htmlspecialchars($row['action']) ?></strong>
                    — <?= htmlspecialchars($row['utilisateur'] ?? 'Système') ?>
                    <br>
                    <small class="text-muted">
                        Service :
                        <strong><?= htmlspecialchars($row['service_utilisateur'] ?? '—') ?></strong>
                    </small>
                </p>

                <?php if (!empty($row['service_source']) || !empty($row['service_destination'])): ?>
                    <p class="mb-1">
                        🔁 De <strong><?= htmlspecialchars($row['service_source'] ?? '—') ?></strong>
                        vers <strong><?= htmlspecialchars($row['service_destination'] ?? '—') ?></strong>
                    </p>
                <?php endif; ?>

                <?php if (!empty($row['description'])): ?>
                    <div class="bg-light p-2 rounded mt-2">
                        <?= nl2br(htmlspecialchars($row['description'])) ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </li>
<?php endwhile; ?>

</ul>
<?php else: ?>
    <p class="text-muted">Aucune action enregistrée.</p>
<?php endif; ?>

<?php endif; endif; ?>

<?php include "../courrier/footer.php"; ?>
