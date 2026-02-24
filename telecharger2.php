<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    die("Accès refusé");
}

$type = $_GET['type'] ?? '';
$id   = (int) ($_GET['id'] ?? 0);

if ($type === 'principal') {

    $stmt = $db->prepare("SELECT document_principal FROM courriers WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetchColumn();

    if (!$doc || !file_exists("../" . $doc)) {
        die("Fichier introuvable");
    }

    $file = "../" . $doc;

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . basename($file));
    readfile($file);
    exit;
}
