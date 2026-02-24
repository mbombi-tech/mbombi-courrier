<?php
session_start();
require_once "../courrier/conn.php";

/* ================== SÉCURITÉ ================== */

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user']['id'];
$role_name = $_SESSION['user']['role'];

/* Seuls AP, Directeur, SG peuvent refuser */
$roles_autorises = ['AP', 'Directeur', 'SG'];

if (!in_array($role_name, $roles_autorises)) {
    die("⛔ Accès refusé.");
}

/* ================== PARAMÈTRE ================== */

if (!isset($_GET['courrier_id'])) {
    die("Courrier invalide.");
}

$courrier_id = intval($_GET['courrier_id']);


/* ================== VÉRIFIER COURRIER ================== */

$stmt = $db->prepare("
    SELECT 
        id,
        tracking_code,
        statut,
        cree_par
    FROM courriers
    WHERE id = ?
");
$stmt->execute([$courrier_id]);

$courrier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$courrier) {
    die("Courrier introuvable.");
}


/* ================== DÉJÀ TRAITÉ ? ================== */

if ($courrier['statut'] === 'refuse') {
    die("⚠️ Ce courrier est déjà refusé.");
}


/* ================== DÉJÀ ASSIGNÉ ? ================== */

$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM courrier_affectations
    WHERE courrier_id = ?
");
$stmt->execute([$courrier_id]);

$nb = $stmt->fetchColumn();

if ($nb > 0) {
    die("⚠️ Impossible : ce courrier est déjà assigné.");
}


/* ================== TRANSACTION ================== */

$db->beginTransaction();

try {

    /* 1️⃣ Mettre statut = refusé */
    $stmt = $db->prepare("
        UPDATE courriers
        SET statut = 'refuse'
        WHERE id = ?
    ");
    $stmt->execute([$courrier_id]);


    /* 2️⃣ Historique */
    $stmt = $db->prepare("
        INSERT INTO suivis
        (courrier_id, action, description, utilisateur_id)
        VALUES (?, 'refus', ?, ?)
    ");

    $desc = "Courrier refusé par $role_name";

    $stmt->execute([
        $courrier_id,
        $desc,
        $user_id
    ]);


    /* 3️⃣ Notification coordon */

    if (!empty($courrier['cree_par'])) {

        $msg = "❌ Votre courrier {$courrier['tracking_code']} a été refusé par $role_name";

        $stmt = $db->prepare("
            INSERT INTO notifications
            (utilisateur_id, courrier_id, message)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $courrier['cree_par'],
            $courrier_id,
            $msg
        ]);
    }


    /* 4️⃣ Valider */
    $db->commit();


    /* ================== REDIRECTION ================== */

    header("Location: suivre_courrier.php?tracking_code=".$courrier['tracking_code']."&refus=ok");
    exit;


} catch (Exception $e) {

    $db->rollBack();

    die("❌ Erreur : ".$e->getMessage());
}
