<?php
session_start();

/* ============================================================
   🔒 Sécurité : utilisateur connecté
   ============================================================ */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

require_once "../courrier/conn.php"; // PDO existant (NE PAS TOUCHER)

/* ============================================================
   🔁 Connexion MySQLi directe (NE PAS TOUCHER)
   ============================================================ */
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "dbcourriers";

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    die("Erreur de connexion : " . $db->connect_error);
}

/* ============================================================
   🔎 Vérification de l’ID courrier
   ============================================================ */
$courrier_id = intval($_GET['id'] ?? 0);

if ($courrier_id <= 0) {
    die("Accès invalide.");
}

/* ============================================================
   📌 Récupération du courrier
   ============================================================ */
$stmt = $db->prepare("
    SELECT c.*, s.nom AS service_actuel
    FROM courriers c
    LEFT JOIN services s ON c.service_actuel_id = s.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $courrier_id);
$stmt->execute();
$courrier = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$courrier) {
    die("Courrier introuvable.");
}

/* ============================================================
   🔐 Contrôle d’accès (simple et cohérent)
   ============================================================ */
$rolesAutorises = ['admin', 'directeur', 'chef_service', 'agent'];
if (!in_array($_SESSION['user']['role'], $rolesAutorises)) {
    die("Accès non autorisé.");
}

/* ============================================================
   📄 Génération du contenu du courrier (TXT)
   ============================================================ */
$contenu = "";
$contenu .= "COURRIER ADMINISTRATIF\n";
$contenu .= "======================\n\n";
$contenu .= "Numéro de suivi : " . $courrier['tracking_code'] . "\n";
$contenu .= "Objet : " . $courrier['objet'] . "\n";
$contenu .= "Statut : " . ucfirst($courrier['statut']) . "\n";
$contenu .= "Service actuel : " . ($courrier['service_actuel'] ?? '-') . "\n";
$contenu .= "Date de création : " . $courrier['date_creation'] . "\n\n";

$contenu .= "----------------------\n";
$contenu .= "CONTENU DU COURRIER\n";
$contenu .= "----------------------\n\n";

$contenu .= $courrier['contenu'] ?? "Contenu non renseigné.\n";

$contenu .= "\n\n----------------------\n";
$contenu .= "Document généré automatiquement par le système.\n";
$contenu .= "Date : " . date('d/m/Y H:i') . "\n";

/* ============================================================
   ⬇ Téléchargement forcé
   ============================================================ */
$nomFichier = "courrier_" . $courrier['tracking_code'] . ".txt";

header("Content-Type: text/plain; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$nomFichier\"");
header("Content-Length: " . strlen($contenu));
header("Pragma: no-cache");
header("Expires: 0");

echo $contenu;
exit;



//doc associé


<?php
session_start();
require_once "../courrier/conn.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM document_associes WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
echo "ID: "; var_dump($id);
echo "Données DB:\n";
print_r($doc);

$basePath = realpath(__DIR__ . '/../uploads');
echo "\nBase path:\n";
var_dump($basePath);

$filePath = $basePath . DIRECTORY_SEPARATOR . $doc['chemin_fichier'];
echo "\nChemin final:\n";
var_dump($filePath);

echo "\nExiste ? ";
var_dump(file_exists($filePath));
echo "</pre>";

exit;