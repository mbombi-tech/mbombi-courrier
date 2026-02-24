<?php
session_start();
require_once "conn.php";

$user_id = $_SESSION['user']['id'] ?? 0;

$stmt = $db->prepare("
    SELECT * 
    FROM notifications
    WHERE utilisateur_id = ? AND lu = 0
    ORDER BY date_creation DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notifications);
