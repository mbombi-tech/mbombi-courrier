<?php
session_start();
require_once "../courrier/conn.php";

/* Sécurité */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user']['role'] !== 'Coordon') {
    die("Accès refusé.");
}

if (empty($_POST['courrier_id'])) {
    die("ID manquant.");
}

function normaliserStatut($texte) {
    $texte = strtolower(trim($texte));
    $texte = iconv('UTF-8', 'ASCII//TRANSLIT', $texte);
    return $texte;
}

$courrier_id = intval($_POST['courrier_id']);


/* Récupération courrier + assignations */
$stmt = $db->prepare("
    SELECT 
        c.statut,
        COUNT(ca.id) AS nbAssign
    FROM courriers c
    LEFT JOIN courrier_affectations ca
        ON ca.courrier_id = c.id
    WHERE c.id = ?
    GROUP BY c.id
");

$stmt->execute([$courrier_id]);

$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    die("Courrier introuvable.");
}


/* Vérifier si déjà soumis */
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM suivis
    WHERE courrier_id = ?
    AND description LIKE '%soumi%'
");

$stmt->execute([$courrier_id]);

$nbSoumissions = $stmt->fetchColumn();


$statut = normaliserStatut($c['statut']);


/* Vérification métier */
if (
    $statut !== 'recu' ||
    $c['nbAssign'] > 0 ||
    $nbSoumissions > 0
) {
    die("Suppression interdite.");
}


/* Suppression sécurisée */
$db->beginTransaction();

try {

    $db->prepare("
        DELETE FROM pieces_jointes
        WHERE courrier_id=?
    ")->execute([$courrier_id]);

    $db->prepare("
        DELETE FROM documents_associes
        WHERE courrier_id=?
    ")->execute([$courrier_id]);

    $db->prepare("
        DELETE FROM suivis
        WHERE courrier_id=?
    ")->execute([$courrier_id]);

    $db->prepare("
        DELETE FROM courriers
        WHERE id=?
    ")->execute([$courrier_id]);

    $db->commit();

    header("Location: liste.php?msg=deleted");
    exit;

} catch (Exception $e) {

    $db->rollBack();
    die("Erreur suppression.");

}
