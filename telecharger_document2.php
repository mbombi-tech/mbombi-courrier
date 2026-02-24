<?php
session_start();
require_once "../courrier/conn.php";

// 🔐 Sécurité : utilisateur connecté
if (!isset($_SESSION['user'])) {
    die("Accès refusé.");
}

// 🔍 Vérification de l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Document invalide.");
}

$doc_id = (int) $_GET['id'];


// ================================
// 1️⃣ Récupération du document
// ================================
$stmt = $db->prepare("
    SELECT d.*, c.id AS courrier_id
    FROM documents_associes d
    INNER JOIN courriers c ON d.courrier_id = c.id
    WHERE d.id = ?
");
$stmt->execute([$doc_id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    die("Document introuvable.");
}


// ================================
// 2️⃣ Vérification du fichier
// ================================
$chemin = $document['chemin_fichier'];

if (!file_exists($chemin)) {
    die("Fichier introuvable sur le serveur.");
}


// ================================
// 3️⃣ Sécurité des headers
// ================================
$nom_fichier = basename($document['nom_original']);

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . $nom_fichier . "\"");
header("Content-Length: " . filesize($chemin));
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: public");
flush();


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

// ================================
// 4️⃣ Téléchargement réel
// ================================
readfile($chemin);
exit;