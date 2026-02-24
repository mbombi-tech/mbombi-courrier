<?php
session_start();
require_once "../courrier/conn.php";

/* =====================
   SÉCURITÉ
===================== */

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user']['id'];
$role      = $_SESSION['user']['role'] ?? '';
$role_id   = $_SESSION['user']['role_id'] ?? 0;


/* =====================
   VARIABLES
===================== */

$courrier = null;
$piece_principale = null;
$documents = [];
$historique = [];
$commentaires = [];
$assignations = [];

$message_erreur = '';
$message_succes = '';


/* =====================
   RECHERCHE COURRIER
===================== */

if (!empty($_GET['tracking_code'])) {

    $tracking = trim($_GET['tracking_code']);

    /* Courrier */
    $stmt = $db->prepare("
        SELECT 
            c.*,
            s.nom AS service_actuel,
            u.nom AS auteur_nom,
            r.nom AS auteur_role
        FROM courriers c
        LEFT JOIN services s ON c.service_actuel_id = s.id
        LEFT JOIN suivis su ON su.courrier_id = c.id
        LEFT JOIN utilisateurs u ON su.utilisateur_id = u.id
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE c.tracking_code = ?
        ORDER BY su.date_action ASC
        LIMIT 1
    ");

    $stmt->execute([$tracking]);
    $courrier = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($courrier) {

        $courrier_id = $courrier['id'];


        /* =====================
           DOCUMENT PRINCIPAL
        ===================== */

        $stmt = $db->prepare("
            SELECT *
            FROM pieces_jointes
            WHERE courrier_id = ?
            ORDER BY date_ajout ASC
            LIMIT 1
        ");

        $stmt->execute([$courrier_id]);
        $piece_principale = $stmt->fetch(PDO::FETCH_ASSOC);


        /* =====================
           DOCUMENTS ASSOCIÉS
        ===================== */

        $stmt = $db->prepare("
            SELECT d.*, u.nom AS auteur
            FROM documents_associes d
            LEFT JOIN utilisateurs u ON d.ajoute_par = u.id
            WHERE d.courrier_id = ?
            ORDER BY d.date_ajout DESC
        ");

        $stmt->execute([$courrier_id]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);


        /* =====================
           COMMENTAIRES
        ===================== */

        $stmt = $db->prepare("
            SELECT s.description, s.date_action, u.nom AS auteur
            FROM suivis s
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            WHERE s.courrier_id = ?
              AND s.description IS NOT NULL
            ORDER BY s.date_action DESC
        ");

        $stmt->execute([$courrier_id]);
        $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);


        /* =====================
           HISTORIQUE
        ===================== */

        $stmt = $db->prepare("
            SELECT s.*, u.nom AS utilisateur
            FROM suivis s
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            WHERE s.courrier_id = ?
            ORDER BY s.date_action DESC
        ");

        $stmt->execute([$courrier_id]);
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);


        /* =====================
           ASSIGNATIONS
        ===================== */

        $stmt = $db->prepare("
            SELECT 
                ca.utilisateur_id,
                ca.etat,
                u.nom
            FROM courrier_affectations ca
            JOIN utilisateurs u ON ca.utilisateur_id = u.id
            WHERE ca.courrier_id = ?
            ORDER BY u.nom
        ");

        $stmt->execute([$courrier_id]);
        $assignations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include "../courrier/header.php";
?>


<div class="container mt-4">

<h3>🔍 Suivi d’un courrier</h3>


<!-- ================= FORM RECHERCHE ================= -->

<form method="GET" class="mb-3">

    <input type="text"
           name="tracking_code"
           class="form-control"
           placeholder="Numéro de suivi"
           value="<?= htmlspecialchars($_GET['tracking_code'] ?? '') ?>">

    <button class="btn btn-primary mt-2">
        Rechercher
    </button>

</form>


<?php if ($courrier): ?>


<!-- ================= INFOS ================= -->

<div class="card mb-3">

<div class="card-header bg-dark text-white">
📄 Informations
</div>

<div class="card-body">

<p><strong>Numéro :</strong> <?= htmlspecialchars($courrier['tracking_code']) ?></p>

<p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>

<p class="text-muted">
Créé par <?= htmlspecialchars($courrier['auteur_nom']) ?>
(<?= htmlspecialchars($courrier['auteur_role']) ?>)
— <?= htmlspecialchars($courrier['service_actuel']) ?>
</p>

<p>
<strong>Statut :</strong>
<span class="badge bg-info">
<?= strtoupper(htmlspecialchars($courrier['statut'])) ?>
</span>
</p>


<?php if (!empty($historique)): ?>

<p>
<strong>Position :</strong>
<?= strtoupper(htmlspecialchars($courrier['service_actuel'])) ?>

depuis le
<?= date('d/m/Y H:i', strtotime($historique[0]['date_action'])) ?>
</p>

<?php endif; ?>

</div>
</div>


<!-- ================= DOCUMENT PRINCIPAL ================= -->

<div class="card mb-3">

<div class="card-header">
📌 Document principal
</div>

<div class="card-body">

<?php if ($piece_principale): ?>

<p><?= htmlspecialchars(basename($piece_principale['chemin_fichier'])) ?></p>

<a href="telecharger_document.php?id=<?= $piece_principale['id'] ?>"
   class="btn btn-success">

📥 Télécharger

</a>

<?php else: ?>

<p class="text-muted">Aucun document.</p>

<?php endif; ?>

</div>
</div>


<!-- ================= DOCUMENTS ================= -->

<div class="card mb-3">

<div class="card-header">
📎 Documents associés
</div>

<div class="card-body">


<?php if ($documents): ?>

<?php foreach ($documents as $doc): ?>

<div class="border rounded p-2 mb-2">

<strong><?= htmlspecialchars($doc['nom_original']) ?></strong><br>

<small>
Ajouté par <?= htmlspecialchars($doc['auteur']) ?>
le <?= date('d/m/Y H:i', strtotime($doc['date_ajout'])) ?>
</small><br>

<a class="btn btn-success btn-sm mt-2"
   href="telecharger_document2.php?id=<?= $doc['id'] ?>">
Télécharger
</a>

</div>

<?php endforeach; ?>


<?php else: ?>

<p class="text-muted">Aucun document.</p>

<?php endif; ?>

</div>
</div>


<!-- ================= COMMENTAIRES ================= -->

<div class="card mb-3">

<div class="card-body">

<h6>📝 Commentaires</h6>

<?php if ($commentaires): ?>

<?php foreach ($commentaires as $c): ?>

<div class="border p-2 mb-2">

<strong><?= htmlspecialchars($c['auteur']) ?></strong><br>

<small>
<?= date('d/m/Y H:i', strtotime($c['date_action'])) ?>
</small>

<div class="mt-2">
<?= nl2br(htmlspecialchars($c['description'])) ?>
</div>

</div>

<?php endforeach; ?>

<?php else: ?>

<p class="text-muted">Aucun commentaire.</p>

<?php endif; ?>

</div>
</div>


<!-- ================= ASSIGNATIONS ================= -->

<?php
$assignes = array_column($assignations,'utilisateur_id');
$roles_ok = [3,7,12,13];
?>

<?php if (in_array($role_id,$roles_ok) || in_array($user_id,$assignes)): ?>

<div class="card mb-3">

<div class="card-header">
👥 Assignations
</div>

<div class="card-body">


<?php if ($assignations): ?>

<?php foreach ($assignations as $a): ?>

<div class="d-flex justify-content-between border p-2 mb-2">

<?= htmlspecialchars($a['nom']) ?>

<?php if ($a['etat']=='termine'): ?>

<span class="badge bg-success">Validé</span>

<?php else: ?>

<span class="badge bg-warning">En attente</span>

<?php endif; ?>

</div>


<?php if ($a['utilisateur_id']==$user_id && $a['etat']!='termine'): ?>

<a href="validation_courrier.php?courrier_id=<?= $courrier['id'] ?>&tracking_code=<?= urlencode($courrier['tracking_code']) ?>"
   class="btn btn-success btn-sm mb-3"
   onclick="return confirm('Valider votre traitement ?')">

✔ Valider

</a>

<?php endif; ?>


<?php endforeach; ?>

<?php else: ?>

<p class="text-muted">Aucune assignation.</p>

<?php endif; ?>

</div>
</div>

<?php endif; ?>


<!-- ================= ACTIONS ================= -->

<div class="mb-5">


<?php if (in_array($role,['Directeur','AP','SG','Coordon'])): ?>

<a href="assigner_courrier.php?code=<?= urlencode($courrier['tracking_code']) ?>"
   class="btn btn-primary">

📝 Assigner

</a>

<?php endif; ?>


<?php if (in_array($role,['AP','agent','Directeur','SG'])): ?>

<a href="ajouter_document.php?courrier_id=<?= $courrier['id'] ?>"
   class="btn btn-primary">

➕ Ajouter document

</a>

<?php endif; ?>


<?php if (in_array($role,['AP','Directeur','SG'])): ?>

<a href="transfert.php?code=<?= urlencode($courrier['tracking_code']) ?>"
   class="btn btn-warning"
   onclick="return confirm('Transférer ce courrier ?')">

🔁 Transférer

</a>

<?php endif; ?>


<?php if ($role=='Coordon'): ?>

<a href="suivre_courrier6.php?tracking_code=<?= urlencode($courrier['tracking_code']) ?>"
   class="btn btn-warning">

📤 Soumettre

</a>

<?php endif; ?>


<?php if (in_array($role,['AP','Directeur','SG'])): ?>

<a href="cloturer_courrier.php?id=<?= $courrier['id'] ?>"
   class="btn btn-danger"
   onclick="return confirm('Clôturer définitivement ?')">

🔒 Clôturer

</a>

<?php endif; ?>


</div>


<?php elseif (isset($_GET['tracking_code'])): ?>

<div class="alert alert-danger">
Aucun courrier trouvé.
</div>

<?php endif; ?>


</div>

<?php include "../courrier/footer.php"; ?>
