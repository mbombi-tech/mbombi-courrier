<?php
session_start();
require_once "../courrier/conn.php";
include "../courrier/header.php";

/* ==========================
   SÉCURITÉ
========================== */

if (!isset($_SESSION['user'])) {
    echo "<div class='alert alert-danger'>Accès refusé.</div>";
    exit;
}

$user_id   = $_SESSION['user']['id'];
$user_nom  = $_SESSION['user']['nom'];
$user_role = $_SESSION['user']['role'];

/* Rôles autorisés */
$roles_autorises = ['Directeur','AP','SG','Coordon'];

if (!in_array($user_role, $roles_autorises)) {
    echo "<div class='alert alert-danger'>Vous n’êtes pas autorisé.</div>";
    exit;
}


/* ==========================
   CODE COURRIER
========================== */

$tracking_code = $_GET['code'] ?? '';

if (!$tracking_code) {
    echo "<div class='alert alert-danger'>Code courrier manquant.</div>";
    exit;
}


/* ==========================
   COURRIER
========================== */

$stmt = $db->prepare("
    SELECT *
    FROM courriers
    WHERE tracking_code = ?
");
$stmt->execute([$tracking_code]);

$courrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$courrier) {
    echo "<div class='alert alert-danger'>Courrier introuvable.</div>";
    exit;
}

$courrier_id = $courrier['id'];


/* Blocage si clôturé */
if ($courrier['statut'] === 'clôturé') {
    echo "<div class='alert alert-warning'>Courrier clôturé.</div>";
    exit;
}


/* ==========================
   UTILISATEURS DU SERVICE
========================== */

$stmt = $db->prepare("
    SELECT u.id, u.nom, r.nom AS role
    FROM utilisateurs u
    JOIN roles r ON u.role_id = r.id
    WHERE u.service_id = ?
      AND u.actif = 1
      AND u.id != ?
      AND u.nom IN ('Stel', 'Theresia')
    ORDER BY u.nom

");

$stmt->execute([
    $courrier['service_actuel_id'],
    $user_id
]);

$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ==========================
   RESPONSABLES (AP / DIRECTEUR)
========================== */

$stmt = $db->prepare("
    SELECT u.id
    FROM utilisateurs u
    JOIN roles r ON u.role_id = r.id
    WHERE r.nom IN ('AP','Directeur')
      AND u.actif = 1
");

$stmt->execute();

$responsables = $stmt->fetchAll(PDO::FETCH_COLUMN);


/* ==========================
   TRAITEMENT FORMULAIRE
========================== */

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $utilisateurs_ids = $_POST['utilisateurs'] ?? [];

    if (empty($utilisateurs_ids)) {

        $error = "Veuillez sélectionner au moins un utilisateur.";

    } else {

        try {

            $db->beginTransaction();


            /* Requête affectation */
            $stmtAffect = $db->prepare("
                INSERT INTO courrier_affectations
                (courrier_id, utilisateur_id, affecte_par, etat, date_affectation)
                VALUES (?, ?, ?, 'en_cours', NOW())
            ");


            /* Historique */
            $stmtSuivi = $db->prepare("
                INSERT INTO suivis
                (courrier_id, action, description, utilisateur_id)
                VALUES (?, 'assignation', ?, ?)
            ");


            /* Notification */
            $stmtNotif = $db->prepare("
                INSERT INTO notifications
                (utilisateur_id, courrier_id, message, date_creation, lu)
                VALUES (?, ?, ?, NOW(), 0)
            ");


            foreach ($utilisateurs_ids as $uid) {

                /* Anti-doublon */
                $check = $db->prepare("
                    SELECT COUNT(*) 
                    FROM courrier_affectations
                    WHERE courrier_id = ?
                      AND utilisateur_id = ?
                ");

                $check->execute([$courrier_id, $uid]);

                if ($check->fetchColumn() > 0) {
                    continue;
                }


                /* Affectation */
                $stmtAffect->execute([
                    $courrier_id,
                    $uid,
                    $user_id
                ]);


                /* Historique */
                $desc = "$user_nom a assigné le courrier à l'utilisateur ID $uid";

                $stmtSuivi->execute([
                    $courrier_id,
                    $desc,
                    $user_id
                ]);


                /* Message selon rôle */
                if ($user_role === 'Coordon') {

                    $message = "📌 $user_nom (Coordon) vous a assigné le courrier \"$tracking_code\".";

                } else {

                    $message = "📌 $user_nom ($user_role) vous a assigné le courrier \"$tracking_code\".";
                }


                /* Notification agent */
                $stmtNotif->execute([
                    $uid,
                    $courrier_id,
                    $message
                ]);


               
              /* Récupérer le nom de l’agent */
$stmtAgent = $db->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
$stmtAgent->execute([$uid]);
$agent = $stmtAgent->fetch(PDO::FETCH_ASSOC);

$nom_agent = $agent ? $agent['nom'] : 'Utilisateur inconnu';


/* Notification AP / Directeur */
foreach ($responsables as $resp_id) {

    $msgResp = "ℹ️ $user_nom a assigné le courrier \"$tracking_code\" à $nom_agent.";

    $stmtNotif->execute([
        $resp_id,
        $courrier_id,
        $msgResp
    ]);
}

            }


            /* Mise à jour statut */
            $stmt = $db->prepare("
                UPDATE courriers
                SET statut = 'à orienter'
                WHERE id = ?
            ");

            $stmt->execute([$courrier_id]);


            $db->commit();

            $success = "Le courrier a été orienté avec succès.";


        } catch (Exception $e) {

            $db->rollBack();
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

?>



<div class="container mt-4">

<h3>📝 Assigner le courrier</h3>

<hr>


<!-- ==========================
   INFOS COURRIER
========================== -->

<div class="card mb-3">
<div class="card-body">

<p><strong>Tracking :</strong> <?= htmlspecialchars($tracking_code) ?></p>
<p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>
<p><strong>Statut :</strong> <?= htmlspecialchars($courrier['statut']) ?></p>

</div>
</div>


<!-- ==========================
   MESSAGES
========================== -->

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>


<!-- ==========================
   FORMULAIRE
========================== -->

<div class="card shadow">
<div class="card-body">

<form method="POST">

<h5>👥 Sélectionnez les utilisateurs</h5>
<hr>

<?php if ($utilisateurs): ?>

    <?php foreach ($utilisateurs as $u): ?>

        <div class="form-check mb-2">

            <input
                class="form-check-input"
                type="checkbox"
                name="utilisateurs[]"
                value="<?= $u['id'] ?>"
                id="user<?= $u['id'] ?>"
            >

            <label
                class="form-check-label"
                for="user<?= $u['id'] ?>"
            >
                <?= htmlspecialchars($u['nom']) ?> (<?= htmlspecialchars($u['role']) ?>)
            </label>

        </div>

    <?php endforeach; ?>

<?php else: ?>

<p class="text-muted">Aucun utilisateur disponible.</p>

<?php endif; ?>


<hr>

<button class="btn btn-success w-100">
    ✅ Envoyer le courrier pour orientation
</button>

<a href="suivre_courrier.php?tracking_code=<?= urlencode($tracking_code) ?>"
   class="btn btn-secondary w-100 mt-2">
   ⬅ Retour
</a>

</form>

</div>
</div>

</div>

<?php include "../courrier/footer.php"; ?>
