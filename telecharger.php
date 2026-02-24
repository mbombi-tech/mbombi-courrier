<?php
session_start();
require_once "../courrier/conn.php"; // connexion existante

// 🔁 Connexion directe conservée (NE PAS TOUCHER)
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dbcourriers";

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    die("Connexion échouée: " . $db->connect_error);
}

// 🔒 Vérification utilisateur connecté
if (!isset($_SESSION['user'])) {
    die("Accès refusé");
}

$user_id = $_SESSION['user']['id'];

// 🔎 Vérification ID pièce jointe
$piece_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($piece_id <= 0) {
    die("Paramètre invalide");
}

/* ===============================
   1️⃣ Récupérer la pièce jointe
================================= */
$stmt = $db->prepare("
    SELECT id, courrier_id, chemin_fichier
    FROM pieces_jointes
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $piece_id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$file) {
    die("Fichier introuvable");
}

$filepath = $file['chemin_fichier'];
$filename = basename($filepath);

if (!file_exists($filepath)) {
    die("Le fichier n'existe pas sur le serveur");
}

/* ===============================
   2️⃣ Mise à jour du statut
   → seulement pour les décideurs
================================= */

$role = $_SESSION['user']['role'] ?? '';

$roles_autorises = ['AP', 'Directeur', 'SG']; // adapte si besoin

if (in_array($role, $roles_autorises)) {

    $update = $db->prepare("
        UPDATE courriers
        SET statut = 'en traitement'
        WHERE id = ?
        AND statut = 'reçu'
    ");

    $update->bind_param("i", $file['courrier_id']);
    $update->execute();
    $update->close();

}

/* ===============================
   3️⃣ Historique (table suivis)
================================= */
$suivi = $db->prepare("
    INSERT INTO suivis (
        courrier_id,
        action,
        description,
        utilisateur_id,
        date_action
    ) VALUES (?, 'Téléchargement pièce jointe', 'Passage en traitement', ?, NOW())
");
$suivi->bind_param("ii", $file['courrier_id'], $user_id);
$suivi->execute();
$suivi->close();

/* ===============================
   4️⃣ Journal d’audit
================================= */
$log = $db->prepare("
    INSERT INTO journal_actions (
        utilisateur_id,
        courrier_id,
        action,
        date_action
    ) VALUES (?, ?, 'Téléchargement pièce jointe', NOW())
");
$log->bind_param("ii", $user_id, $file['courrier_id']);
$log->execute();
$log->close();

/* ===============================
   5️⃣ Forcer le téléchargement
================================= */
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;