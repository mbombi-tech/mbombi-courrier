<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId    = $_SESSION['user']['id'];
$serviceId = $_SESSION['user']['service_id'];

// Compter les courriers non lus
$stmt = $db->prepare("
    SELECT COUNT(*) AS nbNonLus
    FROM courriers c
    LEFT JOIN suivis s 
        ON s.courrier_id = c.id 
        AND s.utilisateur_id = :userId
        AND s.action NOT IN ('reception', 'assignation', 'soumission')
    WHERE c.service_actuel_id = :serviceId
      AND c.statut != 'reçu'
    GROUP BY c.id
    HAVING COUNT(s.id) = 0
");

$stmt->execute([
    ':userId'    => $userId,
    ':serviceId' => $serviceId
]);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul du total
$totalNonLus = count($result);

echo "📩 Courriers non lus : $totalNonLus";
