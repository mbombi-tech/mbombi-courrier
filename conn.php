<?php
// db.php — Connexion MySQL avec PDO

$host = "localhost";
$dbname = "dbcourriers";
$user = "root";
$pass = "";

try {
    // DSN = Data Source Name
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    // Option pour les erreurs PDO
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}