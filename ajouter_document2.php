<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) exit;

$dossier = "../uploads/documents/";
if (!is_dir($dossier)) mkdir($dossier, 0777, true);

$nomOriginal = $_FILES['document']['name'];
$nomStocke = time() . "_" . $nomOriginal;
$chemin = $dossier . $nomStocke;

move_uploaded_file($_FILES['document']['tmp_name'], $chemin);

$stmt = $db->prepare("
    INSERT INTO documents_courrier
    (courrier_id, fichier, nom_original, utilisateur_id)
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $_POST['courrier_id'],
    "uploads/documents/" . $nomStocke,
    $nomOriginal,
    $_SESSION['user']['id']
]);

header("Location: suivre_courrier.php?tracking_code=" . urlencode($_POST['tracking_code']));
