<?php
session_start();
include "../courrier/header.php";
require_once "../courrier/conn.php";

// 🔁 Connexion MySQLi (conservée)
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dbcourriers";

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    die("Erreur de connexion : " . $db->connect_error);
}

// 🔒 Sécurité utilisateur
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$rolesAutorises = ['admin', 'Directeur', 'chef_service', 'secretaire','AP','agent'];
if (!in_array($_SESSION['user']['role'], $rolesAutorises)) {
    die("⛔ Accès refusé.");
}

// 📌 Courrier concerné
$courrier_id = (int)($_GET['courrier_id'] ?? 0);
if ($courrier_id <= 0) {
    die("Courrier invalide.");
}

// 🔎 Récupération du tracking_code
$stmt = $db->prepare("SELECT tracking_code FROM courriers WHERE id = ?");
$stmt->bind_param("i", $courrier_id);
$stmt->execute();
$stmt->bind_result($tracking_code);
$stmt->fetch();
$stmt->close();

if (!$tracking_code) {
    die("Impossible de retrouver le courrier.");
}

// =========================
// 📥 TRAITEMENT DU FORMULAIRE
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $erreur = "Aucun fichier valide sélectionné.";
    } else {

        $description = trim($_POST['description'] ?? '');

        // 📁 Dossier de stockage
        $uploadDir = "../uploads/courriers/$courrier_id/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $nomOriginal = $_FILES['document']['name'];
        $extension   = pathinfo($nomOriginal, PATHINFO_EXTENSION);
        $nomFichier  = uniqid("doc_") . "." . $extension;
        $chemin      = $uploadDir . $nomFichier;

        if (move_uploaded_file($_FILES['document']['tmp_name'], $chemin)) {

            // 📎 Insertion document associé
            $stmt = $db->prepare("
                INSERT INTO documents_associes
                (courrier_id, chemin_fichier, nom_original, type_fichier, taille_fichier, ajoute_par, description, date_ajout)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param(
                "isssiis",
                $courrier_id,
                $chemin,
                $nomOriginal,
                $_FILES['document']['type'],
                $_FILES['document']['size'],
                $_SESSION['user']['id'],
                $description
            );
            $stmt->execute();
            $stmt->close();

            // 🧾 Historique
            $action = "AJOUT_DOCUMENT";
            $descAction = "Ajout d’un document associé : $nomOriginal";

            $stmt = $db->prepare("
                INSERT INTO suivis
                (courrier_id, utilisateur_id, action, description, date_action)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param(
                "iiss",
                $courrier_id,
                $_SESSION['user']['id'],
                $action,
                $descAction
            );
            $stmt->execute();
            $stmt->close();

            // ✅ REDIRECTION CORRECTE
            header("Location: suivre_courrier.php?tracking_code=" . urlencode($tracking_code));
            exit;

        } else {
            $erreur = "Erreur lors de l’enregistrement du fichier.";
        }
    }
}
?>

<h4 class="mb-4">📎 Ajouter un document au courrier</h4>

<?php if (!empty($erreur)) : ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<<form method="post" enctype="multipart/form-data" class="card p-4 shadow">

    <!-- Fichier -->
    <div class="mb-3">
        <label class="form-label">📄 Fichier</label>
        <input type="file" name="document" class="form-control" required>
    </div>

    <!-- Description / Commentaire -->
    <div class="mb-3">
        <label class="form-label">💬 Commentaire / Description</label>

        <textarea name="description"
                  class="form-control"
                  rows="3"
                  placeholder="Ex : Version corrigée du courrier, pièce justificative, note explicative..."
        ></textarea>
    </div>

    <!-- Bouton -->
    <button type="submit" class="btn btn-primary">
        ➕ Ajouter le document
    </button>

</form>


<?php
$db->close();
include "../courrier/footer.php";
?>