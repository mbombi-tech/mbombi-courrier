
<?php
// db.php — Connexion MySQL avec PDO

$host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";
$port = 4000;
$dbname = "dbcourriers";
$user = "3CcRhwJGS9Zx9di.root";
$pass = "agj9cdG5scfUtrzC";

try {
    // DSN = Data Source Name
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    // Option pour les erreurs PDO
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
