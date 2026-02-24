<?php
session_start();
require_once "../courrier/conn.php";
include "../courrier/header.php";

// 🔐 Sécurité
if (!isset($_SESSION['user'])) {
    echo "<div class='alert alert-danger'>Accès refusé.</div>";
    include "../courrier/footer.php";
    exit;
}

// 📌 Code courrier
$tracking_code = $_GET['code'] ?? null;
if (!$tracking_code) {
    echo "<div class='alert alert-danger'>Aucun courrier sélectionné.</div>";
    include "../courrier/footer.php";
    exit;
}

// 📦 Récupération du courrier
$stmt = $db->prepare("
    SELECT id, tracking_code, statut, service_actuel_id
    FROM courriers
    WHERE tracking_code = ?
");
$stmt->execute([$tracking_code]);
$courrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$courrier) {
    echo "<div class='alert alert-danger'>Courrier introuvable.</div>";
    include "../courrier/footer.php";
    exit;
}

// ⛔ Si clôturé → blocage
if ($courrier['statut'] === 'clôturé') {
    echo "<div class='alert alert-warning'>Courrier clôturé — transfert impossible.</div>";
    include "../courrier/footer.php";
    exit;
}

// 🔁 TRAITEMENT DU TRANSFERT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $service_destination = (int)($_POST['service_destination'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $user_id = $_SESSION['user']['id'];

    if ($service_destination <= 0) {
        echo "<div class='alert alert-danger'>Service invalide.</div>";
    } elseif ($service_destination == $courrier['service_actuel_id']) {
        echo "<div class='alert alert-warning'>Le courrier est déjà dans ce service.</div>";
    } else {

        try {
            $db->beginTransaction();

            // 1️⃣ Historique du transfert
            $stmt = $db->prepare("
                INSERT INTO suivis (
                    courrier_id,
                    action,
                    description,
                    service_source_id,
                    service_destination_id,
                    utilisateur_id,
                    date_action
                ) VALUES (
                    ?, 'transfert', ?, ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $courrier['id'],
                $description,
                $courrier['service_actuel_id'],
                $service_destination,
                $user_id
            ]);

            // 2️⃣ Mise à jour du courrier (IMPORTANT)
            $stmt = $db->prepare("
                UPDATE courriers
                SET 
                    service_actuel_id = ?,
                    statut = 'reçu'
                WHERE id = ?
            ");

            $stmt->execute([
                $service_destination,
                $courrier['id']
            ]);

            $db->commit();

            header("Location: liste.php?transfert=success");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            echo "<div class='alert alert-danger'>Erreur lors du transfert.</div>";
        }
    }
}

// 📋 Liste des services
$services = $db->query("SELECT id, nom FROM services")->fetchAll(PDO::FETCH_ASSOC);
?>

<h3 class="mb-4">🔁 Transférer le courrier</h3>

<div class="card shadow p-4">
    <form method="POST">

        <div class="mb-3">
            <label>Courrier</label>
            <input type="text" class="form-control"
                   value="<?= htmlspecialchars($courrier['tracking_code']) ?>"
                   disabled>
        </div>

        <div class="mb-3">
            <label>Service destination</label>
            <select name="service_destination" class="form-select" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= $s['id'] == $courrier['service_actuel_id'] ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($s['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Commentaire</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <button class="btn btn-warning w-100">
            🔁 Transférer le courrier
        </button>
    </form>
</div>

<?php include "../courrier/footer.php"; ?>
