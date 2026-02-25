<?php

$host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";
$port = 4000;
$dbname = "dbcourriers";
$user = "3CcRhwJGS9Zx9di.root";   // ⚠️ prends le username EXACT TiDB
$pass = "TON_MOT_DE_PASSE_TIDB";

try {

    $db = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt'
        ]
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Erreur connexion base de données : " . $e->getMessage());

}
