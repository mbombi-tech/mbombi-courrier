<?php
session_start();
require_once "../courrier/conn.php";

// ==========================
// SÉCURITÉ
// ==========================
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID du courrier manquant.");
}

$courrier_id = (int) $_GET['id'];
$utilisateur_id = $_SESSION['user']['id'];

try {
    // ==========================
    // VÉRIFIER QUE LE COURRIER EXISTE
    // ==========================
    $stmt = $db->prepare("SELECT * FROM courriers WHERE id = ?");
    $stmt->execute([$courrier_id]);
    $courrier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$courrier) {
        die("Courrier introuvable.");
    }

   // ==========================
// RÈGLE MÉTIER : SEUL "VALIDÉ" EST CLÔTURABLE
// ==========================

// Normalisation du statut
$statut = strtolower(trim($courrier['statut'] ?? ''));

if ($statut !== 'validé') {
    die("⛔ Seuls les courriers ayant le statut 'validé' peuvent être clôturés.");
}

// Vérifier déjà clôturé
if ($statut === 'clôturé') {
    header("Location: suivre_courrier.php?cloture=deja");
    exit;
}

    // ==========================
    // TRANSACTION
    // ==========================
    $db->beginTransaction();

    // 1️⃣ Mise à jour du statut
    $stmt = $db->prepare("
        UPDATE courriers
        SET statut = 'clôturé'
        WHERE id = ?
    ");
    $stmt->execute([$courrier_id]);

    // 2️⃣ Historique
    $stmt = $db->prepare("
        INSERT INTO suivis (
            courrier_id,
            utilisateur_id,
            action,
            description,
            date_action
        ) VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $courrier_id,
        $utilisateur_id,
        'Clôture',
        'Le courrier a été clôturé'
    ]);

    // ==========================
    // VALIDATION
    // ==========================
    $db->commit();

    header("Location: suivre_courrier.php?cloture=success");
    exit;

} catch (Exception $e) {
    $db->rollBack();
    die("Erreur lors de la clôture : " . $e->getMessage());
}
