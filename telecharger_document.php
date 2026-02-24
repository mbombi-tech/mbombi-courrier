<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    die("Accès refusé.");
}

if (empty($_GET['id'])) {
    die("Paramètre manquant.");
}

$id = intval($_GET['id']);

/* ================================
   1️⃣ RÉCUPÉRATION DU FICHIER
================================ */
$stmt = $db->prepare("
    SELECT id, chemin_fichier, courrier_id
    FROM pieces_jointes
    WHERE id = ?
");
$stmt->execute([$id]);
$fichier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fichier) {
    die("Fichier introuvable en base.");
}

/* ================================
   2️⃣ ENREGISTRER L'ACTION DE TÉLÉCHARGEMENT
================================ */
$courrier_id = $fichier['courrier_id'];
$user_id = $_SESSION['user']['id'];

// 2a. Dans journal_actions
$action_desc = "Téléchargement du fichier : " . basename($fichier['chemin_fichier']);
$stmt_journal = $db->prepare("
    INSERT INTO journal_actions (utilisateur_id, courrier_id, action, date_action)
    VALUES (?, ?, ?, NOW())
");
$stmt_journal->execute([$user_id, $courrier_id, $action_desc]);

// 2b. Dans suivis
$stmt_suivis = $db->prepare("
    INSERT INTO suivis (
        courrier_id, action, description, utilisateur_id, date_action
    )
    VALUES (?, 'Téléchargement', ?, ?, NOW())
");
$description = "Utilisateur ID $user_id a téléchargé le fichier " . basename($fichier['chemin_fichier']);
$stmt_suivis->execute([$courrier_id, $description, $user_id]);

/* ================================
   3️⃣ TÉLÉCHARGEMENT DU FICHIER
================================ */
$chemin = __DIR__ . "/uploads/" . basename($fichier['chemin_fichier']);

if (!file_exists($chemin)) {
    die("Le fichier n'existe pas sur le serveur : " . $chemin);
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fichier['chemin_fichier']) . '"');
header('Content-Length: ' . filesize($chemin));

readfile($chemin);
exit;
