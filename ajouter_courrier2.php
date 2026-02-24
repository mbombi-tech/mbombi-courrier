<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$courrier = null;
$piece_principale = null;
$documents = [];
$historique = [];
$commentaires = [];

/* RECHERCHE PAR CODE OU PAR OBJET */
if (!empty($_GET['tracking_code']) || !empty($_GET['objet'])) {

    if (!empty($_GET['tracking_code'])) {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                s.nom AS service_actuel,
                u.nom AS auteur_nom,
                r.nom AS auteur_role
            FROM courriers c
            LEFT JOIN services s ON c.service_actuel_id = s.id
            LEFT JOIN utilisateurs u ON c.auteur_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE c.tracking_code = ?
            LIMIT 1
        ");
        $stmt->execute([trim($_GET['tracking_code'])]);
    } else {
        $stmt = $db->prepare("
            SELECT *
            FROM courriers
            WHERE objet LIKE ?
            ORDER BY date_creation DESC
            LIMIT 1
        ");
        $stmt->execute(['%' . trim($_GET['objet']) . '%']);
    }

    $courrier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($courrier) {
        // Pièce principale
        $stmt = $db->prepare("
            SELECT *
            FROM pieces_jointes
            WHERE courrier_id = ?
            ORDER BY date_ajout ASC
            LIMIT 1
        ");
        $stmt->execute([$courrier['id']]);
        $piece_principale = $stmt->fetch(PDO::FETCH_ASSOC);

        // Documents associés
        $stmt = $db->prepare("
            SELECT d.*, u.nom AS auteur
            FROM documents_associes d
            LEFT JOIN utilisateurs u ON d.ajoute_par = u.id
            WHERE d.courrier_id = ?
            ORDER BY d.date_ajout DESC
        ");
        $stmt->execute([$courrier['id']]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Commentaires
        $stmt = $db->prepare("
            SELECT 
                s.description,
                s.date_action,
                u.nom AS auteur
            FROM suivis s
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            WHERE s.courrier_id = ?
              AND s.description IS NOT NULL
              AND s.description <> ''
            ORDER BY s.date_action DESC
        ");
        $stmt->execute([$courrier['id']]);
        $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Historique complet
        $stmt = $db->prepare("
            SELECT 
                s.*, 
                u.nom AS utilisateur,
                r.nom AS role
            FROM suivis s
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE s.courrier_id = ?
            ORDER BY s.date_action DESC
        ");
        $stmt->execute([$courrier['id']]);
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php include "../courrier/header.php"; ?>

<div class="container mt-4">

<h3>🔍 Suivi d’un courrier</h3>

<form method="GET" class="mb-3">
    <input type="text"
           name="tracking_code"
           class="form-control"
           placeholder="Numéro de suivi"
           value="<?= htmlspecialchars($_GET['tracking_code'] ?? '') ?>">
    <button class="btn btn-primary mt-2">Rechercher</button>
</form>

<?php if ($courrier): ?>

<div class="card mb-3">
    <div class="card-header bg-dark text-white">📄 Informations du courrier</div>
    <div class="card-body">
        <p><strong>Numéro :</strong> <?= $courrier['tracking_code'] ?></p>
        <p class="text-muted">
            Créé par <strong><?= $courrier['auteur_nom'] ?? '—' ?></strong>
            (<?= $courrier['auteur_role'] ?? '—' ?>) — <?= $courrier['service_actuel'] ?? '—' ?> •
            <?= date('d/m/Y H:i', strtotime($courrier['date_creation'])) ?>
        </p>
        <p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>
        <p><strong>Statut :</strong>
            <span id="statut-courrier" class="badge bg-secondary"><?= ucfirst($courrier['statut']) ?></span>
        </p>
    </div>
</div>

<?php if (!empty($historique)): ?>
<div class="alert alert-info text-center fw-bold">
📍 CE COURRIER SE TROUVE AU SERVICE
<span class="text-dark"><?= strtoupper(htmlspecialchars($courrier['service_actuel'])) ?></span>
<br>
DEPUIS LE <?= date('d/m/Y À H:i', strtotime($historique[0]['date_action'])) ?>
</div>
<?php endif; ?>

<!-- Action Affectation utilisateurs -->
<?php
// On récupère les ids des rôles autorisés
$stmt = $db->query("SELECT id FROM roles WHERE nom IN ('admin','agent','directeur')"); 
$rolesAutorises = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');

if (in_array($user['role_id'], $rolesAutorises)):
?>
<a href="affectation.php?courrier_id=<?= $courrier['id'] ?>" class="btn btn-success mb-3">
    🧑‍💼 Affecter des utilisateurs
</a>
<?php endif; ?>

<!-- Pièce principale -->
<div class="card mb-3">
    <div class="card-header">📌 Courrier (document principal)</div>
    <div class="card-body">
        <?php if ($piece_principale): ?>
            <p><?= htmlspecialchars(basename($piece_principale['chemin_fichier'])) ?></p>
            <a href="telecharger_document.php?id=<?= $piece_principale['id'] ?>" class="btn btn-success">
                Télécharger
            </a>
        <?php else: ?>
            <p class="text-muted">Aucun fichier pour ce courrier.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Documents associés -->
<div class="card mb-3">
    <div class="card-header">📎 Documents associés</div>
    <div class="card-body">
        <?php if (!empty($documents)): ?>
            <?php foreach ($documents as $doc): ?>
                <div class="border rounded p-2 mb-2">
                    <strong><?= htmlspecialchars($doc['nom_original']) ?></strong><br>
                    <small>Ajouté par <?= htmlspecialchars($doc['auteur']) ?> le <?= date('d/m/Y H:i', strtotime($doc['date_ajout'])) ?></small><br>
                    <a class="btn btn-success btn-sm mt-2" href="telecharger_document2.php?id=<?= $doc['id'] ?>">Télécharger</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Aucun document.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Commentaires -->
<div class="card mb-3">
    <div class="card-body">
        <h6>📝 Commentaires de transfert</h6>
        <?php if (!empty($commentaires)): ?>
            <?php foreach ($commentaires as $c): ?>
                <div class="mb-3 border rounded p-2">
                    <strong><?= htmlspecialchars($c['auteur']) ?></strong><br>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($c['date_action'])) ?></small>
                    <div><?= nl2br(htmlspecialchars($c['description'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted fst-italic">Aucun commentaire.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Actions -->
<div class="mb-5">
    <a href="ajouter_document.php?courrier_id=<?= $courrier['id'] ?>" class="btn btn-primary">➕ Ajouter un document</a>
    <a href="transfert.php?code=<?= urlencode($courrier['tracking_code']) ?>" class="btn btn-warning">🔁 Transférer</a>
    <a href="cloturer_courrier.php?id=<?= $courrier['id'] ?>" class="btn btn-danger" onclick="return confirm('⚠️ Clôturer ce courrier ?')">🔒 Clôturer</a>
</div>

<?php elseif (isset($_GET['tracking_code'])): ?>
<div class="alert alert-danger">
    Aucun courrier trouvé avec ce numéro.
</div>
<?php endif; ?>

</div>
<?php include "../courrier/footer.php"; ?>
