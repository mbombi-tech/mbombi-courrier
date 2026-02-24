<?php
// modifier_utilisateur.php
session_start();
$db_host="localhost";
$db_user="root";
$db_pass="";
$db_name="dbcourriers";

$db=new mysqli($db_host,$db_user,$db_pass,$db_name);

// Vérifier que l'ID est passé en GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: utilisateurs.php");
    exit;
}

$id = intval($_GET['id']);

// Récupérer les données de l'utilisateur
$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error'] = "Utilisateur introuvable.";
    header("Location: utilisateurs.php");
    exit;
}

// Récupérer les rôles et services pour les selects
$roles = $db->query("SELECT * FROM roles ORDER BY nom ASC");
$services = $db->query("SELECT * FROM services ORDER BY nom ASC");

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $role_id = intval($_POST['role_id']);
    $service_id = intval($_POST['service_id']);
    $actif = isset($_POST['actif']) ? 1 : 0;

    // Mot de passe optionnel
    $mot_de_passe = !empty($_POST['mot_de_passe']) ? password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT) : null;

    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Cet email est déjà utilisé par un autre utilisateur.";
        header("Location: modifier_utilisateur.php?id=$id");
        exit;
    }
    $stmt->close();

    // Construire la requête UPDATE
    if ($mot_de_passe) {
        $stmt = $db->prepare("UPDATE utilisateurs SET nom=?, email=?, mot_de_passe=?, role_id=?, service_id=?, actif=? WHERE id=?");
        $stmt->bind_param("sssiiii", $nom, $email, $mot_de_passe, $role_id, $service_id, $actif, $id);
    } else {
        $stmt = $db->prepare("UPDATE utilisateurs SET nom=?, email=?, role_id=?, service_id=?, actif=? WHERE id=?");
        $stmt->bind_param("siii", $nom, $email, $role_id, $service_id, $actif, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Utilisateur modifié avec succès !";
    } else {
        $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur.";
    }
    $stmt->close();

    header("Location: utilisateurs.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Utilisateur</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-5">
    <h2>Modifier l'utilisateur : <?= htmlspecialchars($user['nom']); ?></h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom</label>
            <input type="text" class="form-control" name="nom" id="nom" value="<?= htmlspecialchars($user['nom']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" class="form-control" name="mot_de_passe" id="mot_de_passe">
        </div>
        <div class="mb-3">
            <label for="role_id" class="form-label">Rôle</label>
            <select name="role_id" id="role_id" class="form-select" required>
                <?php while($role = $roles->fetch_assoc()): ?>
                    <option value="<?= $role['id']; ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($role['nom']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="service_id" class="form-label">Service</label>
            <select name="service_id" id="service_id" class="form-select" required>
                <?php while($service = $services->fetch_assoc()): ?>
                    <option value="<?= $service['id']; ?>" <?= $service['id'] == $user['service_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($service['nom']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="actif" id="actif" <?= $user['actif'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="actif">Actif</label>
        </div>
        <button type="submit" class="btn btn-primary">Modifier</button>
        <a href="utilisateurs.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>