<?php
session_start();
require_once "../courrier/conn.php"; // connexion existante (à ne pas toucher)

// 🔁 Connexion directe conservée
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dbcourriers";

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    die("Connexion échouée: " . $db->connect_error);
}

// 🔒 Vérification utilisateur connecté
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$role = $user['role'];
$service_id = $user['service_id'];

// 🔹 Récupération du nom du service utilisateur
$nomService = null;
if ($role !== 'admin') {
    $stmtService = $db->prepare("SELECT nom FROM services WHERE id = ?");
    $stmtService->bind_param("i", $service_id);
    $stmtService->execute();
    $stmtService->bind_result($nomService);
    $stmtService->fetch();
    $stmtService->close();
}

// 🔹 Construction de la requête principale
$sql = "
SELECT 
    c.id,
    c.tracking_code,
    c.type,
    c.objet,
    c.expediteur,
	c.destinataire,
    c.date_creation,
    c.statut,
    s.nom AS service_actuel
FROM courriers c
LEFT JOIN services s ON c.service_actuel_id = s.id
";

$params = [];
$types  = "";

// 🔐 Si pas admin → limiter au service
if ($role !== 'admin') {
    $sql .= " WHERE c.service_actuel_id = ?";
    $params[] = $service_id;
    $types .= "i";
}

$sql .= " ORDER BY c.date_creation DESC";

// ▶ Exécution
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 🔢 Nombre de courriers
$nbCourriers = $result ? $result->num_rows : 0;

include "../courrier/header.php";



?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_GET['transfert']) && $_GET['transfert'] === 'success'): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Transfert réussi',
        text: 'Le courrier a été transféré avec succès.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#198754'
    });

    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>
<?php endif; ?>

<?php if (isset($_GET['transfert']) && $_GET['transfert'] === 'success'): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Transfert réussi',
        text: 'Le courrier a été transféré avec succès.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#198754'
    });
</script>
<?php endif; 
?>

<style>
.text-truncate-hover {
    max-width: 240px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: help;
    display: inline-block;
}
</style>

<h3 class="mb-2">📂 Liste des courriers</h3>

<!-- 🔢 Compteur avec nom du service -->
<div class="mb-3">
    <span class="badge bg-primary fs-6">
        Total :
        <?= $nbCourriers ?>
        courrier<?= $nbCourriers > 1 ? 's' : '' ?>
        (
        <?= ($role === 'admin')
            ? 'tous services'
            : htmlspecialchars($nomService ?? 'Service inconnu') ?>
        )
    </span>
</div>

<a href="ajouter_courrier.php" class="btn btn-primary mb-3">
    ➕ Nouveau courrier
</a>

<div class="card shadow">
    <div class="card-body">

        <?php if ($nbCourriers > 0): ?>

        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Tracking</th>
                    <th>Type</th>
                    <th>Objet</th>
                    <th>Expéditeur</th>
					<th>Destnataire</th>
                    <th>Service actuel</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php
            $i = 1;
            while ($row = $result->fetch_assoc()):
                // 🎨 Badge couleur selon statut
                $badgeClass = 'bg-dark';
                switch ($row['statut']) {
                    case 'reçu':
                        $badgeClass = 'bg-success';
                        break;
                    case 'en_traitement':
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
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['tracking_code']) ?></td>
                    <td><?= htmlspecialchars($row['type']) ?></td>

                    <td>
                        <span class="text-truncate-hover"
                              title="<?= htmlspecialchars($row['objet']) ?>">
                            <?= htmlspecialchars($row['objet']) ?>
                        </span>
                    </td>

                    <td>
                        <span class="text-truncate-hover"
                              title="<?= htmlspecialchars($row['expediteur']) ?>">
                            <?= htmlspecialchars($row['expediteur']) ?>
                        </span>
                    </td>
					
					<td>
                        <span class="text-truncate-hover"
                              title="<?= htmlspecialchars($row['destinataire']) ?>">
                            <?= htmlspecialchars($row['destinataire']) ?>
                        </span>
                    </td>

                    <td><?= htmlspecialchars($row['service_actuel'] ?? 'Non attribué') ?></td>

                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <?= ucfirst(htmlspecialchars($row['statut'])) ?>
                        </span>
                    </td>

                    <td><?= date('d/m/Y H:i', strtotime($row['date_creation'])) ?></td>

                    <td>
                       <a class="btn btn-sm btn-info text-white"
                            href="suivre_courrier.php?tracking_code=<?= urlencode($row['tracking_code']) ?>">
                           Suivre
                        </a> 
                        

                        <?php if ($row['statut'] !== 'clôturé'): ?>
                            <a class="btn btn-sm btn-warning"
                               href="transfert.php?code=<?= urlencode($row['tracking_code']) ?>">
                               Transférer
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary">Clôturé</span>
                        <?php endif; ?>

                        <a class="btn btn-sm btn-secondary"
                           href="historique.php?code=<?= urlencode($row['tracking_code']) ?>">
                           Historique
                        </a>
                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

        <?php else: ?>

            <p class="text-center text-muted mb-0">
                Aucun courrier disponible pour ce service.
            </p>

        <?php endif; ?>

    </div>
</div>

<?php
$stmt->close();
$db->close();
include "../courrier/footer.php";
?> 