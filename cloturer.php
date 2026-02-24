<?php
include "../courrier/header.php"; // header.php démarre déjà la session
require_once "../courrier/conn.php"; // connexion à la base
$db_host="localhost";
$db_user="root";
$db_pass="";
$db_name="dbcourriers";
$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    echo '<div class="alert alert-danger">Vous devez être connecté pour clôturer un courrier.</div>';
    include "../courrier/footer.php";
    exit;
}

// Vérifier que l'utilisateur a le droit de clôturer (ex: admin ou directeur)
$roles_autorises = ['admin', 'directeur'];
$user_role = $_SESSION['user']['role'] ?? '';

if (!in_array($user_role, $roles_autorises)) {
    echo '<div class="alert alert-danger">Vous n’avez pas la permission de clôturer ce courrier.</div>';
    include "../courrier/footer.php";
    exit;
}

// Récupérer le code de tracking depuis l'URL
$tracking_code = $_GET['code'] ?? null;

if (!$tracking_code) {
    echo '<div class="alert alert-danger">Aucun courrier sélectionné.</div>';
    include "../courrier/footer.php";
    exit;
}

// Vérifier que le courrier existe
$stmt = $db->prepare("SELECT * FROM courriers WHERE tracking_code = ?");
$stmt->bind_param("s", $tracking_code);
$stmt->execute();
$result = $stmt->get_result();
$courrier = $result->fetch_assoc();

if (!$courrier) {
    echo '<div class="alert alert-danger">Courrier introuvable.</div>';
    include "../courrier/footer.php";
    exit;
}

// Vérifier que le courrier n'est pas déjà clôturé
if ($courrier['statut'] === 'clôturé') {
    echo '<div class="alert alert-info">Ce courrier est déjà clôturé.</div>';
    include "../courrier/footer.php";
    exit;
}

// Traitement de la clôture
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');

    // 1️⃣ Mettre à jour le statut du courrier
    $stmtUpdate = $db->prepare("UPDATE courriers SET statut = 'clôturé' WHERE id = ?");
    $stmtUpdate->bind_param("i", $courrier['id']);
    $stmtUpdate->execute();

    // 2️⃣ Ajouter une entrée dans la table suivis
    $stmtSuivi = $db->prepare("
        INSERT INTO suivis 
        (courrier_id, action, description, service_source_id, service_destination_id, utilisateur_id, date_action)
        VALUES (?, 'Clôture', ?, ?, ?, ?, NOW())
    ");
    $service_id = $courrier['service_actuel_id'];
    $user_id = $_SESSION['user']['id'];
    $stmtSuivi->bind_param("isiii", $courrier['id'], $description, $service_id, $service_id, $user_id);
    $stmtSuivi->execute();

    // 3️⃣ Ajouter un log dans journal_actions
    $stmtLog = $db->prepare("
        INSERT INTO journal_actions (utilisateur_id, courrier_id, action, date_action)
        VALUES (?, ?, 'Clôture', NOW())
    ");
    $stmtLog->bind_param("ii", $user_id, $courrier['id']);
    $stmtLog->execute();

    echo '<div class="alert alert-success">Le courrier a été clôturé avec succès.</div>';
}

?>

<h3 class="mb-4">Clôturer le courrier</h3>

<div class="card shadow p-4">
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Courrier</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($courrier['tracking_code']) ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Commentaire / raison de clôture</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <button class="btn btn-success w-100">Clôturer le courrier</button>
    </form>
</div>

<?php include "../courrier/footer.php"; ?>