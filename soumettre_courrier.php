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

$user_id = $_SESSION['user']['id'];
$nom_expediteur = $_SESSION['user']['nom'];

/* =======================
   CODE COURRIER
======================= */
$tracking_code = $_GET['tracking_code'] ?? '';

if (!$tracking_code) {
    die("Code courrier manquant.");
}

/* =======================
   INFOS COURRIER
======================= */
$stmt = $db->prepare("SELECT * FROM courriers WHERE tracking_code = ?");
$stmt->execute([$tracking_code]);
$courrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$courrier) {
    die("Courrier introuvable.");
}

$courrier_id = $courrier['id'];

/* =======================
   LISTE UTILISATEURS (DEST)
======================= */
$stmt = $db->prepare("
    SELECT u.id, u.nom, s.nom AS service, r.nom AS role
    FROM utilisateurs u
    JOIN services s ON u.service_id = s.id
    JOIN roles r ON u.role_id = r.id
    WHERE u.actif = 1
      AND u.id != ?
      AND r.nom IN ('AP')
	  AND s.nom='DANTIC'
    ORDER BY u.nom
");
$stmt->execute([$user_id]);

$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   TRAITEMENT SOUMISSION
======================= */
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dest_id = $_POST['destinataire'] ?? '';

    if ($dest_id) {

        // Vérifier que le courrier n’a pas déjà été soumis à ce destinataire
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM suivis
            WHERE courrier_id = ? 
              AND action = 'soumission'
              AND utilisateur_id = ?
        ");
        $stmt->execute([$courrier_id, $user_id]);
        $already_submitted = $stmt->fetchColumn();

        if ($already_submitted) {
            $error_message = "Ce courrier a déjà été soumis à cet utilisateur.";
        } else {

            // Nom du destinataire
            $stmt = $db->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
            $stmt->execute([$dest_id]);
            $dest = $stmt->fetch();

            if ($dest) {
                $desc = "Soumis à " . $dest['nom'];

                // Enregistrer dans suivis
                $stmt = $db->prepare("
                    INSERT INTO suivis
                    (courrier_id, action, description, utilisateur_id, utilisateur_destination_id)
                    VALUES (?, 'soumission', ?, ?, ?)
                ");
                $stmt->execute([
                    $courrier_id,
                    $desc,
                    $user_id,
                    $dest_id
                ]);
// ===============================
// 1️⃣ Récupérer le statut actuel
// ===============================

$stmt = $db->prepare("
    SELECT statut
    FROM courriers
    WHERE id = ?
");

$stmt->execute([$courrier_id]);

$current = $stmt->fetch(PDO::FETCH_ASSOC);

$statutActuel = $current['statut'] ?? '';


// ===============================
// 2️⃣ Déterminer le nouveau statut
// ===============================

if ($statutActuel === 'soumis à AP') {

    $nouveauStatut = 'soumis à SG';

} else {

    $nouveauStatut = 'soumis à AP';

}


// ===============================
// 3️⃣ Mise à jour
// ===============================

$stmt = $db->prepare("
    UPDATE courriers
    SET statut = ?
    WHERE id = ?
");

$stmt->execute([
    $nouveauStatut,
    $courrier_id
]);

                // Notification destinataire
                $message = "📩 Courrier $tracking_code soumis par $nom_expediteur";
                $stmt = $db->prepare("
                    INSERT INTO notifications
                    (utilisateur_id, courrier_id, message)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$dest_id, $courrier_id, $message]);

                $success_message = "La soumission du courrier \"$tracking_code\" auprès de \"{$dest['nom']}\" a réussi.";
            }
        }
    }
}

include "../courrier/header.php";
?>

<div class="container mt-4">

<h3>📤 Soumission du courrier</h3>
<hr>

<div class="card mb-4">
<div class="card-body">

<p><strong>Tracking :</strong> <?= htmlspecialchars($tracking_code) ?></p>
<p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>
<p><strong>Type :</strong> <?= htmlspecialchars($courrier['type']) ?></p>

</div>
</div>

<!-- ================= MESSAGES ================= -->
<?php if ($error_message): ?>
<script>alert("<?= addslashes($error_message) ?>");</script>
<?php endif; ?>

<?php if ($success_message): ?>
<script>alert("<?= addslashes($success_message) ?>");</script>
<?php endif; ?>

<!-- ================= FORMULAIRE ================= -->
<form method="POST" id="formSoumission">
<div class="mb-3">
<label class="form-label">👤 Soumettre à :</label>
<select name="destinataire" id="destinataire" class="form-select" required>
<option value="">-- Choisir un utilisateur --</option>
<?php foreach ($utilisateurs as $u): ?>
<option value="<?= $u['id'] ?>">
<?= htmlspecialchars($u['nom']) ?> (<?= $u['service'] ?>)
</option>
<?php endforeach; ?>
</select>
</div>

<button type="submit" class="btn btn-warning">📨 Soumettre</button>
<a href="liste_courriers.php" class="btn btn-secondary">⬅ Retour</a>
</form>

</div>

<!-- ================= CONFIRMATION JS ================= -->
<script>
document.getElementById('formSoumission')
.addEventListener('submit', function(e){
    e.preventDefault();

    let select = document.getElementById('destinataire');
    let nom = select.options[select.selectedIndex].text;

    if(!select.value){
        alert("Veuillez choisir un destinataire.");
        return;
    }

    let tracking = "<?= addslashes($tracking_code) ?>";
    let msg = "Voulez-vous soumettre le courrier \"" + tracking + "\" à \"" + nom + "\" ?";

    if(confirm(msg)){
        this.submit();
    }
});
</script>

<?php include "../courrier/footer.php"; ?>
