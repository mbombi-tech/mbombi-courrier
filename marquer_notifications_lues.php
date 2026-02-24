<?php
session_start();
require_once "conn.php";

$user_id = $_SESSION['user']['id'] ?? 0;

$stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?");
$stmt->execute([$user_id]);
