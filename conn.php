<?php
// Connexion TiDB Cloud avec PDO

$host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";
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
    die("Erreur connexion base de données : " . $e->getMessage());
}

?>
