<?php

$host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";
$port = 4000;
$dbname = "dbcourriers";
$user = "mbombi-tech";
$pass = "Richessen10?";

/*
====================================================
✅ Chemin du certificat CA (IMPORTANT)
====================================================
Télécharge le certificat TiDB ici :

https://docs.pingcap.com/tidbcloud/secure-connections-to-serverless-tier-clusters

Puis upload le fichier CA dans ton projet Render.

Exemple :
/var/www/html/cert/ca.pem
====================================================
*/

$caPath = "/var/www/html/cert/ca.pem";

try {

    $db = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::MYSQL_ATTR_SSL_CA => $caPath,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur connexion base de données : " . $e->getMessage());
}
