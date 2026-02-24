<?php
// Connexion existante conservée
require_once "../courrier/conn.php";

/*
    On s’attend à recevoir :
    $code = tracking_code
    depuis historique.php
*/

if (!isset($code) || empty($code)) {
    echo "<p class='text-muted'>Aucun courrier sélectionné.</p>";
    return;
}

// 1️⃣ Récupérer le courrier et son statut
$stmt = $db->prepare("
    SELECT id, statut, service_actuel_id
    FROM courriers 
    WHERE tracking_code = ?
    LIMIT 1
");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$courrier = $result->fetch_assoc();

if (!$courrier) {
    echo "<p class='text-muted'>Aucun courrier trouvé pour ce code.</p>";
    return;
}

$courrier_id = $courrier['id'];

// 2️⃣ Récupérer l’historique complet (table suivis)
$stmt2 = $db->prepare("
    SELECT s.*, 
           u.nom AS agent,
           ss.nom AS service_source,
           sd.nom AS service_destination
    FROM suivis s
    LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
    LEFT JOIN services ss ON s.service_source_id = ss.id
    LEFT JOIN services sd ON s.service_destination_id = sd.id
    WHERE s.courrier_id = ?
    ORDER BY s.date_action ASC
");
$stmt2->bind_param("i", $courrier_id);
$stmt2->execute();
$suivis = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// 3️⃣ Récupérer les pièces jointes
$stmt3 = $db->prepare("
    SELECT chemin_fichier, type_fichier
    FROM pieces_jointes
    WHERE courrier_id = ?
    ORDER BY date_ajout ASC
");
$stmt3->bind_param("i", $courrier_id);
$stmt3->execute();
$pieces = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// 4️⃣ Affichage de la timeline
if (empty($suivis)) {
    echo "<p class='text-muted'>Aucune action enregistrée pour ce courrier.</p>";
    return;
}
?>

<ul class="timeline list-unstyled">
    <?php foreach ($suivis as $s) : ?>
        <li class="mb-3">
            <div class="card shadow-sm">
                <div class="card-body">

                    <!-- Date et heure -->
                    <small class="text-muted">
                        <?= date('d/m/Y H:i', strtotime($s['date_action'])) ?>
                    </small>

                    <!-- Action et agent -->
                    <p class="mb-1">
                        <strong><?= htmlspecialchars($s['action']) ?></strong>
                        par <?= htmlspecialchars($s['agent'] ?? 'Système') ?>
                    </p>

                    <!-- Services source/destination -->
                    <?php if ($s['service_source_id'] || $s['service_destination_id']) : ?>
                        <p class="mb-1">
                            📍 De
                            <strong><?= htmlspecialchars($s['service_source'] ?? '-') ?></strong>
                            à
                            <strong><?= htmlspecialchars($s['service_destination'] ?? '-') ?></strong>
                        </p>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if (!empty($s['description'])) : ?>
                        <p class="mb-0"><?= htmlspecialchars($s['description']) ?></p>
                    <?php endif; ?>

                    <!-- Pièces jointes liées à cette action -->
                    <?php
                    foreach ($pieces as $p) {
                        // Optionnel : on peut filtrer par action si on ajoute un champ action_id dans pieces_jointes
                        echo "<p class='mt-1 mb-0'>📎 <a href='../uploads/".htmlspecialchars($p['chemin_fichier'])."' target='_blank'>".htmlspecialchars($p['chemin_fichier'])."</a></p>";
                    }
                    ?>

                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<!-- 5️⃣ Statut actuel du courrier (au bas de la timeline) -->
<div class="mt-3">
    <strong>Statut actuel :</strong>
    <?php
    switch ($courrier['statut']) {
        case 'clôturé':
            echo "<span class='badge bg-success'>Clôturé</span>";
            break;
        case 'en_traitement':
            echo "<span class='badge bg-warning text-dark'>En traitement</span>";
            break;
        case 'transféré':
            echo "<span class='badge bg-info text-dark'>Transféré</span>";
            break;
        default:
            echo "<span class='badge bg-secondary'>Reçu</span>";
            break;
    }
    ?>
</div>