<?php
// ajouter_utilisateur.php
session_start();
require_once '../courrier/conn.php';
<?php
// ajouter_utilisateur.php
session_start();
require_once '../courrier/conn.php';
$db_host="localhost";
$db_user="root";
$db_pass="";
$db_name="dbcourriers";

$db=new mysqli($db_host,$db_user,$db_pass,$db_name);

// Vérifier que le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT); // Hash du mot de passe
    $role_id = intval($_POST['role_id']);
    $service_id = intval($_POST['service_id']);
    $actif = isset($_POST['actif']) ? 1 : 0;

    // Vérifier que les champs obligatoires sont remplis
    if ($nom && $email && $mot_de_passe && $role_id && $service_id) {
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "Cet email est déjà utilisé.";
            header("Location: utilisateurs.php");
            exit;
        }
        $stmt->close();

        // Insertion dans la table utilisateurs
        $stmt = $db->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role_id, service_id, actif) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $nom, $email, $mot_de_passe, $role_id, $service_id, $actif);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Utilisateur ajouté avec succès !";
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Retour à la page utilisateurs
header("Location: utilisateurs.php");
exit;
?>
// Vérifier que le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT); // Hash du mot de passe
    $role_id = intval($_POST['role_id']);
    $service_id = intval($_POST['service_id']);
    $actif = isset($_POST['actif']) ? 1 : 0;

    // Vérifier que les champs obligatoires sont remplis
    if ($nom && $email && $mot_de_passe && $role_id && $service_id) {
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "Cet email est déjà utilisé.";
            header("Location: utilisateurs.php");
            exit;
        }
        $stmt->close();

        // Insertion dans la table utilisateurs
        $stmt = $db->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role_id, service_id, actif) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiii", $nom, $email, $mot_de_passe, $role_id, $service_id, $actif);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Utilisateur ajouté avec succès !";
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Retour à la page utilisateurs
header("Location: utilisateurs.php");
exit;
?>