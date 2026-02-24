<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$role_id = $_SESSION['user']['role_id'];

$courrier_id  = $_GET['courrier_id'] ?? null;
$tracking_code = $_GET['tracking_code'] ?? '';

if (!$courrier_id) {
    die("Courrier non spécifié.");
}

/* ===========================
   1. Vérifier existence courrier
=========================== */
$stmt = $db->prepare("
    SELECT *
    FROM courriers
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$courrier_id]);
$courrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$courrier) {
    die("Courrier introuvable.");
}

/* ===========================
   2. Vérifier autorisation
   (ex : Directeur, SG, Coord)
=========================== */
if (
    !isset($_SESSION['user']['role']) ||
    strtoupper($_SESSION['user']['role']) !== 'AP'
) {
    die("⛔ Vous n’êtes pas autorisé à effectuer la validation finale.");
}
/* ===========================
   3. Traitement POST
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Mise à jour statut
    $stmt = $db->prepare("
        UPDATE courriers
        SET statut = 'Validé'
        WHERE id = ?
    ");
    $stmt->execute([$courrier_id]);

    /* ===========================
       Historique
    =========================== */
    $stmt = $db->prepare("
        INSERT INTO suivis (
            courrier_id,
            action,
            description,
            utilisateur_id,
            date_action
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $courrier_id,
        'Validation finale',
        "Validation finale avant clôture effectuée.",
        $user_id
    ]);

    /* ===========================
       Journal
    =========================== */
    $stmt = $db->prepare("
        INSERT INTO journal_actions (
            utilisateur_id,
            courrier_id,
            action
        ) VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $courrier_id,
        "Validation finale du courrier"
    ]);

    header("Location: liste.php?tracking_code=" . urlencode($tracking_code) . "&validation_finale=success");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Validation finale</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
</head>
<body class="container mt-5">

    <div class="card">
        <div class="card-header bg-danger text-white">
            ⚠ Confirmation de validation 
        </div>
        <div class="card-body">

            <p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>
            <p><strong>Code :</strong> <?= htmlspecialchars($courrier['tracking_code']) ?></p>
            <p><strong>Statut actuel :</strong> <?= htmlspecialchars($courrier['statut']) ?></p>

            <div class="alert alert-warning mt-3">
                Cette action va valider définitivement le courrier avant clôture.
            </div>

            <form method="POST">
                <button type="submit" class="btn btn-success">
                    ✔ Confirmer la validation 
                </button>

                <a href="suivre_courrier5.php?tracking_code=<?= urlencode($tracking_code) ?>"
                   class="btn btn-secondary">
                   Annuler
                </a>
            </form>

        </div>
    </div>

</body>
</html>