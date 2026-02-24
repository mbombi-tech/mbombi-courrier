<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$courrier_id = $_GET['courrier_id'] ?? null;
$tracking_code = $_GET['tracking_code'] ?? '';

if (!$courrier_id) {
    die("Courrier non spécifié.");
}


/* ===========================
   1. Vérifier l’affectation
=========================== */
$stmt = $db->prepare("
    SELECT id
    FROM courrier_affectations
    WHERE courrier_id = ?
      AND utilisateur_id = ?
      AND etat != 'termine'
    LIMIT 1
");
$stmt->execute([$courrier_id, $user_id]);

$affectation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$affectation) {
    die("Action non autorisée ou déjà validée.");
}


/* ===========================
   2. Valider l’affectation
=========================== */
$stmt = $db->prepare("
    UPDATE courrier_affectations
    SET etat = 'termine'
    WHERE id = ?
");
$stmt->execute([$affectation['id']]);


/* ===========================
   3. Vérifier s’il reste
      des validations
=========================== */
$stmt = $db->prepare("
    SELECT COUNT(*) AS total_restant
    FROM courrier_affectations
    WHERE courrier_id = ?
      AND etat != 'termine'
");
$stmt->execute([$courrier_id]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

$reste = (int)$result['total_restant'];


/* ===========================
   4. Déterminer le statut
=========================== */
if ($reste === 0) {
    $nouveau_statut = 'Traité';
} else {
    $nouveau_statut = 'Traité_ partiellement';
}


/* ===========================
   5. Mise à jour courrier
=========================== */
$stmt = $db->prepare("
    UPDATE courriers
    SET statut = ?
    WHERE id = ?
");
$stmt->execute([$nouveau_statut, $courrier_id]);


/* ===========================
   6. Historique
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
    'Validation traitement',
    "Validation effectuée. Statut : $nouveau_statut",
    $user_id
]);


/* ===========================
   7. Journal
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
    "Validation courrier ($nouveau_statut)"
]);



/* ===========================
   5. Redirection
   =========================== */
header("Location: suivre_courrier5.php?tracking_code=" . urlencode($_GET['tracking_code'] ?? '') . "&validation=success");
exit;