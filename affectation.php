<?php
require_once "../courrier/Auth.php";
require_once "../courrier/conn.php";
requireLogin();

$user = $_SESSION["user"];
$serviceId = $user['service_id'];
$userId    = $user['id'];

// ========================
// 1️⃣ Vérifier que l'ID du courrier est passé en GET
// ========================
if (empty($_GET['courrier_id'])) {
    die("❌ Aucun courrier sélectionné.");
}
$courrierId = intval($_GET['courrier_id']);

// ========================
// 2️⃣ Vérifier que le courrier existe
// ========================
$stmt = $db->prepare("SELECT * FROM courriers WHERE id = ?");
$stmt->execute([$courrierId]);
$courrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$courrier) {
    die("❌ Courrier introuvable.");
}

// ========================
// 3️⃣ Vérifier que l'utilisateur a le droit d'affecter
// ========================
$rolesAutorises = ['admin','AP','Directeur','SG','Coordon','Chef_pool'];
if (!in_array($user['role'], $rolesAutorises)) {
    die("❌ Accès refusé : vous n'avez pas la permission d'affecter des utilisateurs.");
}

// ========================
// 4️⃣ Récupérer les utilisateurs du même service que le courrier
// ========================
$stmt = $db->prepare("
    SELECT id, nom, role 
    FROM utilisateurs 
    WHERE service_id = ? 
      AND id != ?
");
$stmt->execute([$courrier['service_actuel_id'], $user['id']]);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========================
// 5️⃣ Traitement du formulaire d'affectation
// ========================
$successMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['utilisateurs'])) {
    $selectedUsers = $_POST['utilisateurs'];

    // Supprimer les affectations précédentes pour ce courrier
    $db->prepare("DELETE FROM courrier_affectations WHERE courrier_id = ?")->execute([$courrierId]);

    // Ajouter les nouvelles affectations
    $stmt = $db->prepare("INSERT INTO courrier_affectations (courrier_id, utilisateur_id, date_affectation) VALUES (?, ?, NOW())");
    foreach ($selectedUsers as $uid) {
        $stmt->execute([$courrierId, intval($uid)]);
    }

    $successMessage = "✅ Utilisateurs affectés avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Affectation des utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">

    <h3>📌 Affecter des utilisateurs au courrier <?= htmlspecialchars($courrier['tracking_code']) ?></h3>
    <p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= $successMessage ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="utilisateurs" class="form-label">Sélectionner les utilisateurs :</label>
            <select name="utilisateurs[]" id="utilisateurs" class="form-select" multiple required>
                <?php foreach ($utilisateurs as $u): ?>
                    <option value="<?= $u['id'] ?>">
                        <?= htmlspecialchars($u['nom']) ?> (<?= $u['role'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Maintenez Ctrl/Cmd pour sélectionner plusieurs utilisateurs.</small>
        </div>

        <button type="submit" class="btn btn-primary">Affecter</button>
        <a href="suivre_courrier.php?tracking_code=<?= urlencode($courrier['tracking_code']) ?>" class="btn btn-secondary">Retour</a>
    </form>

</div>
</body>
</html>
