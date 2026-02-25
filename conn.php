<?php

$host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";
$port = 4000;
$dbname = "dbcourriers";
$user = "mbombi-tech";
$pass = "TON_MOT_DE_PASSE_TIDB";

try {

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $options = [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false
    ];

    $db = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    die("Erreur connexion base de données : " . $e->getMessage());
}
?>
