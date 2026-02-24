<?php
session_start();
require_once "../courrier/conn.php";

// 🔒 Vérification utilisateur connecté
if (!isset($_SESSION['user'])) {
    die("Accès refusé. Connectez-vous.");
}

// Connexion MySQL
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dbcourriers";

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    die("Erreur de connexion : " . $db->connect_error);
}

// 🔑 Récupérer le code de suivi
$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    die("Code courrier manquant.");
}

// 🔎 Récupérer le courrier
$stmt = $db->prepare("SELECT id, statut, service_actuel_id FROM courriers WHERE tracking_code = ? LIMIT 1");
$stmt->bind_param("s", $code);
$stmt->execute();
$courrier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$courrier) {
    die("Courrier introuvable.");
}

// 🚫 Vérifier si action autorisée
if (in_array($courrier['statut'], ['en_traitement', 'clôturé'])) {
    die("Impossible de commencer le traitement : courrier déjà en traitement ou clôturé.");
}

// 🔄 Mettre à jour le statut du courrier
$update = $db->prepare("UPDATE courriers SET statut = 'en_traitement' WHERE id = ?");
$update->bind_param("i", $courrier['id']);
$update->execute();
$update->close();

// 🧾 Ajouter une entrée dans l'historique
$suivi = $db->prepare("
    INSERT INTO suivis (
        courrier_id, action, description,
        service_source_id, service_destination_id,
        utilisateur_id, date_action
    ) VALUES (?, 'Traitement', 'Début du traitement du courrier', ?, ?, ?, NOW())
");
$suivi->bind_param(
    "iiii",
    $courrier['id'],
    $courrier['service_actuel_id'], // service_source
    $courrier['service_actuel_id'], // service_destination (même service)
    $_SESSION['user']['id']
);
$suivi->execute();
$suivi->close();

// ✅ Redirection vers la page de suivi pour afficher le statut mis à jour
header("Location: suivre_courrier.php?code=" . urlencode($code));
exit;