<?php
/****************************************************
 * MISE À JOUR DE LA BASE DE DONNÉES
 * - Ajout documents_principaux
 * - Ajout documents_associes
 * - Extension des statuts de courriers
 ****************************************************/

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "dbcourriers";

try {
    // Connexion à la base
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (Exception $e) {
    die("❌ Erreur de connexion : " . $e->getMessage());
}

echo "<h2>🔧 Mise à jour de la base de données</h2>";

/****************************************************
 * TABLE : documents_principaux
 ****************************************************/
echo "<h3>📄 Création de documents_principaux</h3>";


               



/****************************************************
 * TABLE : documents_associes
 ****************************************************/
echo "<h3>📎 Création de documents_associes</h3>";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS documents_associes (
        id INT AUTO_INCREMENT PRIMARY KEY,

        courrier_id INT NOT NULL,
        chemin_fichier VARCHAR(255) NOT NULL,
        nom_original VARCHAR(255) NOT NULL,
        type_fichier VARCHAR(50) DEFAULT NULL,
        taille_fichier INT DEFAULT NULL,

        ajoute_par INT NOT NULL,
        date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,

        description TEXT DEFAULT NULL,

        INDEX idx_doc_associe_courrier (courrier_id),

        CONSTRAINT fk_doc_associe_courrier
            FOREIGN KEY (courrier_id)
            REFERENCES courriers(id)
            ON DELETE CASCADE,

        CONSTRAINT fk_doc_associe_user
            FOREIGN KEY (ajoute_par)
            REFERENCES utilisateurs(id)
            ON DELETE RESTRICT
    ) ENGINE=InnoDB;
");

echo "✔ documents_associes créée ou déjà existante<br>";

/****************************************************
 * EXTENSION DES STATUTS DE COURRIERS
 ****************************************************/
echo "<h3>🔄 Mise à jour des statuts de courriers</h3>";

$pdo->exec("
    ALTER TABLE courriers
    MODIFY statut ENUM(
        'reçu',
        'en traitement',
        'transféré',
        'clôturé',
        'brouillon_agent',
        'soumis_secretaire',
        'en_validation_directeur',
        'autorisation_transfert'
    ) DEFAULT 'reçu';
");

echo "✔ Statuts étendus avec succès<br>";

/****************************************************
 * FIN
 ****************************************************/
echo "<h3>🎉 Mise à jour terminée avec succès</h3>";
echo "<p>Aucune table existante n’a été supprimée ou modifiée dangereusement.</p>";
?>