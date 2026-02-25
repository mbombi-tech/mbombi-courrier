<?php
require 'conn.php';

try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Connexion réussie ✅</h2>";
    echo "Tables dans la base :<br><br>";

    foreach ($tables as $table) {
        echo $table . "<br>";
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
