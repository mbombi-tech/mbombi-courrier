<?php
session_start();
require_once "../courrier/conn.php";

// 🔒 Sécurité utilisateur
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$db = new mysqli("localhost", "root", "", "dbcourriers");
if ($db->connect_error) {
    die("Erreur DB");
}

$user_id = $_SESSION['user']['id'] ?? null;
$service_id = $_SESSION['user']['service_id'] ?? null;

if (!$user_id || !$service_id) {
    die("Utilisateur invalide.");
}

/* ===============================
   TRAITEMENT DU FORMULAIRE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $db->begin_transaction();

        $type         = trim($_POST['type']);
        $objet        = trim($_POST['objet']);
        $expediteur   = trim($_POST['expediteur']);
        $destinataire = trim($_POST['destinataire']);

        if (!$type || !$objet || !$expediteur || !$destinataire) {
            throw new Exception("Tous les champs sont obligatoires.");
        }

        // 🔹 Génération tracking
        $tracking_code = 'CR-' . strtoupper(bin2hex(random_bytes(3)));

      // 🔹 Insertion courrier
$stmt = $db->prepare("
    INSERT INTO courriers
    (tracking_code, type, objet, expediteur, destinataire,
     service_actuel_id, date_creation, statut, cree_par)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'recu', ?)
");

$stmt->bind_param(
    "sssssii",
    $tracking_code,
    $type,
    $objet,
    $expediteur,
    $destinataire,
    $service_id,
    $user_id   // 👈 cree_par = coordon connecté
);

$stmt->execute();
$courrier_id = $stmt->insert_id;
$stmt->close();

        // 🔹 Suivi
        $stmt = $db->prepare("
            INSERT INTO suivis
            (courrier_id, action, description, service_source_id, utilisateur_id, date_action)
            VALUES (?, 'réception', ?, ?, ?, NOW())
        ");
        $desc = "Courrier enregistré";
        $stmt->bind_param("isii", $courrier_id, $desc, $service_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 🔹 Pièces jointes
        if (!empty($_FILES['pieces_jointes']['name'][0])) {
            $dir = __DIR__ . "/uploads/";
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            foreach ($_FILES['pieces_jointes']['tmp_name'] as $i => $tmp) {
                if ($_FILES['pieces_jointes']['error'][$i] === 0) {
                    $name = time() . "_" . basename($_FILES['pieces_jointes']['name'][$i]);
                    move_uploaded_file($tmp, $dir . $name);

                    $stmt = $db->prepare("
                        INSERT INTO pieces_jointes
                        (courrier_id, chemin_fichier, type_fichier, date_ajout)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $mime = mime_content_type($dir . $name);
                    $stmt->bind_param("iss", $courrier_id, $name, $mime);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        $db->commit();

        // ✅ REDIRECTION PROPRE


header("Location: suivre_courrier.php?tracking_code=" . urlencode($tracking_code));
exit;
    } catch (Exception $e) {
        $db->rollback();
        die("Erreur : " . $e->getMessage());
    }
}
?>

<?php include "../courrier/header.php"; ?>

<h3>Ajouter un courrier</h3>

<form method="POST" enctype="multipart/form-data" class="card p-4 shadow">

    <div class="mb-3">
        <label>Type</label>
        <select name="type" class="form-control" required>
            <option value="">-- Choisir --</option>
            <option value="entrant">Entrant</option>
            <option value="sortant">Sortant</option>
            <option value="interne">Interne</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Objet</label>
        <input type="text" name="objet" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Expéditeur</label>
        <input type="text" name="expediteur" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Destinataire</label>
        <input type="text" name="destinataire" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Pièces jointes</label>
        <input type="file" name="pieces_jointes[]" multiple class="form-control">
    </div>

    <button class="btn btn-primary">Enregistrer</button>
</form>



<?php include "../courrier/footer.php"; ?>