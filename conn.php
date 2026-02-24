<?php
// Connexion TiDB Cloud avec PDO
cloud.com
$host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";      // ex: gateway01.eu-central-1.prod.aws.tidb
$port = 4000;
$dbname = "dbcourriers";
$user = "mbombi-tech";
$pass = "Richessen10?";

try {
    $db = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
