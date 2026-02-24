<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user']['id'];
$role_id = $_SESSION['user']['role_id'] ?? null;

$courrier = null;
$piece_principale = null;
$documents = [];
$historique = [];
$commentaires = [];
$assignations = [];

/* ================================
   🔍 RECHERCHE DU COURRIER
================================ */
if (!empty($_GET['tracking_code'])) {

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
    $stmt->execute([trim($_GET['tracking_code'])]);
    $courrier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($courrier) {

        /* ==========================
           📌 DOCUMENT PRINCIPAL
        ========================== */
        $stmt = $db->prepare("
            SELECT *
            FROM pieces_jointes
            WHERE courrier_id = ?
            ORDER BY date_ajout ASC
            LIMIT 1
        ");
        $stmt->execute([$courrier['id']]);
        $piece_principale = $stmt->fetch(PDO::FETCH_ASSOC);

        /* ==========================
           📎 DOCUMENTS ASSOCIÉS
        ========================== */
        $stmt = $db->prepare("
            SELECT d.*, u.nom AS auteur
            FROM documents_associes d
            LEFT JOIN utilisateurs u ON d.ajoute_par = u.id
            WHERE d.courrier_id = ?
            ORDER BY d.date_ajout DESC
        ");
        $stmt->execute([$courrier['id']]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ==========================
           📝 COMMENTAIRES
        ========================== */
        $stmt = $db->prepare("
            SELECT s.description, s.date_action, u.nom AS auteur
            FROM suivis s
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            WHERE s.courrier_id = ?
              AND s.description IS NOT NULL
            ORDER BY s.date_action DESC
        ");
        $stmt->execute([$courrier['id']]);
        $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		/* ================= REFUS (AP / DIRECTEUR) ================= */

if (
    isset($_POST['refuser']) &&
    in_array($role_name, ['AP','Directeur'])
) {

    $courrier_id = intval($_POST['courrier_id']);

    /* Vérifier affectation */
    $check = $db->prepare("
        SELECT COUNT(*)
        FROM courrier_affectations
        WHERE courrier_id=? AND utilisateur_id=?
    ");
    $check->execute([
        $courrier_id,
        $user_id
    ]);

    $nb = $check->fetchColumn();


    if ($nb == 0) {

        $message_erreur = "⚠️ Vous n’êtes pas concerné.";

    } else {

        /* Historique */
        $stmt = $db->prepare("
            INSERT INTO suivis
            (
                courrier_id,
                action,
                description,
                utilisateur_id
            )
            VALUES (?,?,?,?)
        ");

        $stmt->execute([
            $courrier_id,
            'refus',
            'Courrier refusé',
            $user_id
        ]);


        /* Statut */
        $stmt = $db->prepare("
            UPDATE courriers
            SET statut='refuse'
            WHERE id=?
        ");
        $stmt->execute([$courrier_id]);


        $message_succes = "❌ Courrier refusé.";
    }
}



        /* ==========================
           📜 HISTORIQUE
        ========================== */
        $stmt = $db->prepare("
            SELECT s.*, u.nom AS utilisateur
            FROM suivis s
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            WHERE s.courrier_id = ?
            ORDER BY s.date_action DESC
        ");
        $stmt->execute([$courrier['id']]);
        $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ==========================
           👥 ASSIGNATIONS
        ========================== */
        $stmt = $db->prepare("
            SELECT 
                ca.utilisateur_id,
                ca.etat,
                ca.date_affectation,
                u.nom
            FROM courrier_affectations ca
            JOIN utilisateurs u ON ca.utilisateur_id = u.id
            WHERE ca.courrier_id = ?
            ORDER BY u.nom
        ");
        $stmt->execute([$courrier['id']]);
        $assignations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php include "../courrier/header.php"; ?>

<div class="container mt-4">

<h3>🔍 Suivi d’un courrier</h3>

<form method="GET" class="mb-3">
    <input type="text" name="tracking_code" class="form-control"
           placeholder="Numéro de suivi"
           value="<?= htmlspecialchars($_GET['tracking_code'] ?? '') ?>">
    <button class="btn btn-primary mt-2">Rechercher</button>
</form>

<?php if ($courrier): ?>

<!-- ================= INFO COURRIER ================= -->
<div class="card mb-3">
    <div class="card-header bg-dark text-white">📄 Informations</div>
    <div class="card-body">
        <p><strong>Numéro :</strong> <?= $courrier['tracking_code'] ?></p>
        <p><strong>Objet :</strong> <?= htmlspecialchars($courrier['objet']) ?></p>
        <p class="text-muted">
            Créé par <?= $courrier['auteur_nom'] ?> (<?= $courrier['auteur_role'] ?>)
            — <?= $courrier['service_actuel'] ?>
        </p>
        <p>
            <strong>Statut :</strong>
            <span class="badge bg-info"><?= strtoupper($courrier['statut']) ?></span>
        </p>
		<!-- ================= PHRASE D'ÉTAT ================= -->
<?php if (!empty($historique)): ?>
   <p><strong>
        Position actuelle:</strong>
        
            <?= strtoupper(htmlspecialchars($courrier['service_actuel'])) ?>
       
        
        DEPUIS LE
        <?= date('d/m/Y À H:i', strtotime($historique[0]['date_action'])) ?>
    </p>
<?php endif; ?>
    </div>
</div>



 <!-- COURRIER PRINCIPAL -->
    <div class="card mb-3">
        <div class="card-header">📌 Courrier (document principal)</div>
        <div class="card-body">
            <?php if ($piece_principale): ?>
                <p><?= htmlspecialchars(basename($piece_principale['chemin_fichier'])) ?></p>
				
             <a href="telecharger_document.php?id=<?= $piece_principale['id'] ?>"
               class="btn btn-success"
               onclick="changerStatutVisuel()">
                Télécharger
            </a>
            <?php else: ?>
                <p class="text-muted">Aucun fichier pour ce courrier.</p>
            <?php endif; ?>
        </div>
    </div>

<!-- DOCUMENTS ASSOCIÉS -->
<div class="card mb-3">
    <div class="card-header">📎 Documents associés</div>
    <div class="card-body">

        <?php if (!empty($documents)): ?>

            <?php 
            // Premier document
            $premier = $documents[0]; 
            ?>

            <!-- 📄 Document principal affiché -->
            <div class="border rounded p-2 mb-2">
                <strong><?= htmlspecialchars($premier['nom_original']) ?></strong><br>
                <small>
                    Ajouté par <?= htmlspecialchars($premier['auteur']) ?>
                    le <?= date('d/m/Y H:i', strtotime($premier['date_ajout'])) ?>
                </small><br>

                <a class="btn btn-success btn-sm mt-2"
                   href="telecharger_document2.php?id=<?= $premier['id'] ?>">
                    Télécharger
                </a>
            </div>

            <!-- 📂 Autres documents (repliables) -->
            <?php if (count($documents) > 1): ?>
                <details class="mt-3">
                    <summary class="text-primary" style="cursor:pointer;">
                        📂 Voir les autres documents associés
                    </summary>

                    <?php foreach (array_slice($documents, 1) as $doc): ?>
                        <div class="border rounded p-2 mt-2">
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
                </details>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-muted">Aucun document.</p>
        <?php endif; ?>

    </div>
</div>

  <!-- COMMENTAIRES -->
<div class="card shadow mb-3">
    <div class="card-body">
        <h6>📝 Commentaires de transfert</h6>

        <?php if (!empty($commentaires)): ?>

            <!-- Commentaire le plus récent -->
            <?php $dernier = $commentaires[0]; ?>

            <div class="mb-3">
                <strong><?= htmlspecialchars($dernier['auteur'] ?? 'Utilisateur') ?></strong><br>
                <small class="text-muted">
                    <?= date('d/m/Y H:i', strtotime($dernier['date_action'])) ?>
                </small>

                <div class="mt-2 bg-light p-2 rounded">
                    <?= nl2br(htmlspecialchars($dernier['description'])) ?>
                </div>
            </div>

            <!-- Autres commentaires -->
            <?php if (count($commentaires) > 1): ?>
                <details>
                    <summary class="text-primary" style="cursor:pointer;">
                        💬 Voir les autres commentaires
                    </summary>

                    <?php foreach (array_slice($commentaires, 1) as $c): ?>
                        <div class="border rounded p-2 mt-2">
                            <strong><?= htmlspecialchars($c['auteur'] ?? 'Utilisateur') ?></strong><br>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($c['date_action'])) ?>
                            </small>

                            <div class="mt-2">
                                <?= nl2br(htmlspecialchars($c['description'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </details>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-muted fst-italic">Aucun commentaire.</p>
        <?php endif; ?>
    </div>
</div>


    <div class="card mb-3">
    <a href="historique.php?code=<?= urlencode($courrier['tracking_code']) ?>"
   class="btn btn-outline-primary btn-sm">
   📜 Voir l’historique complet
   </a>
    </div>



<!-- ================= ASSIGNATIONS ================= -->
<?php
$roles_autorises = [3,7,12,13]; // AP, Directeur, SG, Coordon
$est_assigné = array_column($assignations, 'utilisateur_id');
?>

<?php if (in_array($role_id, $roles_autorises) || in_array($user_id, $est_assigné)): ?>
<div class="card mb-3">
    <div class="card-header">👥 Utilisateurs assignés</div>
    <div class="card-body">

        <?php if ($assignations): ?>
            <?php foreach ($assignations as $a): ?>
                <div class="border p-2 mb-2 d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($a['nom']) ?></strong>

                    <?php if ($a['etat'] === 'termine'): ?>
                        <span class="badge bg-success">✅ Validé</span>
                    <?php else: ?>
                        <span class="badge bg-warning">⏳ En attente</span>
                    <?php endif; ?>
                </div>

                <?php if ($a['utilisateur_id'] == $user_id && $a['etat'] !== 'termine'): ?>
                    <a href="validation_courrier.php?courrier_id=<?= $courrier['id'] ?>&tracking_code=<?= urlencode($courrier['tracking_code']) ?>"
                       class="btn btn-success btn-sm mb-3"
                       onclick="return confirm('Valider votre traitement ?')">
                       ✔ Valider mon traitement
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Aucune assignation pour ce courrier.</p>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>




    <!-- ACTIONS -->
	
	 

	<br/>
	<br/>
    <div class="mb-5">
	
	

	
	  <?php if (in_array($_SESSION['user']['role'], ['AP','agent','Directeur','SG'])): ?> 
        <a href="ajouter_document.php?courrier_id=<?= $courrier['id'] ?>"
           class="btn btn-primary">
            ➕ Ajouter un document
        </a>
        <?php endif; ?>
		
	  <?php if (in_array($_SESSION['user']['role'], ['Directeur','AP','SG','Coordon','Agent_courrier'])): ?>
    <a href="assigner_courrier.php?code=<?= urlencode($courrier['tracking_code']) ?>"
       class="btn btn-primary">📝 Assigner courrier</a>
    <?php endif; ?>	

	<?php if (in_array($_SESSION['user']['role'], ['AP','Directeur','SG'])): ?> 
	 <a href="transfert.php?code=<?= urlencode($courrier['tracking_code']) ?>"
           class="btn btn-warning
	 onclick="return confirm('⚠️ Voulez-vous vraiment transferer ce courrier ?')">	   
        🔁 Transférer
     </a>
   <?php endif; ?>
   
   	<?php if (in_array($_SESSION['user']['role'], ['Coordon'])): ?> 
 <a class="btn btn-warning"  href="soumettre_courrier.php?tracking_code=<?= urlencode($courrier['tracking_code']) ?>">
   📤 Soumettre
</a>
   <?php endif; ?>
   
<?php if (in_array($_SESSION['user']['role'], ['AP','Directeur','SG'])): ?>
<a href="cloturer_courrier.php?id=<?= intval($courrier['id']) ?>"
   class="btn btn-danger"
   onclick="return confirm('⚠️ Voulez-vous vraiment clôturer ce courrier ?\n\nCette action est définitive.')">
   🔒 Clôturer
</a>
<?php endif; ?>

    </div>

<?php elseif (isset($_GET['tracking_code'])): ?>

    <div class="alert alert-danger">
        Aucun courrier trouvé avec ce numéro.
    </div>

<?php endif; ?>

</div>
<script>
function changerStatutVisuel() {
    const badge = document.getElementById("statut-courrier");
    if (badge) {
        badge.innerText = "En traitement";
        badge.className = "badge bg-warning";
    }
}
</script>
<?php include "../courrier/footer.php"; ?>a
