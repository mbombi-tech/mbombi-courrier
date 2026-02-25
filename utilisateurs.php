<?php
// utilisateurs.php
session_start();
require_once __DIR__ . "/conn.php";
$db_host="localhost";
$db_user="root";
$db_pass="";
$db_name="dbcourriers";

$db=new mysqli($db_host,$db_user,$db_pass,$db_name);

// Récupération de tous les utilisateurs avec leur rôle et service
$sql = "SELECT u.id, u.nom, u.email, r.nom AS role, s.nom AS service, u.actif
        FROM utilisateurs u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN services s ON u.service_id = s.id
        ORDER BY u.nom ASC";
$result = $db->query($sql);

// Récupération des rôles pour le formulaire d'ajout
$roles = $db->query("SELECT * FROM roles ORDER BY nom ASC");

// Récupération des services pour le formulaire d'ajout
$services = $db->query("SELECT * FROM services ORDER BY nom ASC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-5">
    <h2>Liste des utilisateurs</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#ajoutUtilisateurModal">Ajouter un utilisateur</button>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Service</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nom']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['role']); ?></td>
                    <td><?= htmlspecialchars($row['service']); ?></td>
                    <td><?= $row['actif'] ? 'Actif' : 'Inactif'; ?></td>
                    <td>
                        <a href="modifier_utilisateur.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="supprimer_utilisateur.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center">Aucun utilisateur trouvé</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ajout Utilisateur -->
<div class="modal fade" id="ajoutUtilisateurModal" tabindex="-1" aria-labelledby="ajoutUtilisateurModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="ajouter_utilisateur.php" method="POST">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ajoutUtilisateurModalLabel">Ajouter un utilisateur</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" name="nom" id="nom" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" name="mot_de_passe" id="mot_de_passe" required>
                </div>
                <div class="mb-3">
                    <label for="role_id" class="form-label">Rôle</label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <option value="">Sélectionner un rôle</option>
                        <?php while($role = $roles->fetch_assoc()): ?>
                            <option value="<?= $role['id']; ?>"><?= htmlspecialchars($role['nom']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="service_id" class="form-label">Service</label>
                    <select name="service_id" id="service_id" class="form-select" required>
                        <option value="">Sélectionner un service</option>
                        <?php while($service = $services->fetch_assoc()): ?>
                            <option value="<?= $service['id']; ?>"><?= htmlspecialchars($service['nom']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="actif" id="actif" checked>
                    <label class="form-check-label" for="actif">Actif</label>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-primary">Ajouter</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script src="assets/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
✅ Fonctionnalités incluses :

Liste complète des utilisateurs avec rôle, service et statut.

Boutons Modifier / Supprimer.

Formulaire modal pour ajouter un nouvel utilisateur.

Protection basique avec htmlspecialchars() pour éviter les injections XSS.

Si tu veux, je peux te créer les scripts ajouter_utilisateur.php, modifier_utilisateur.php et supprimer_utilisateur.php complets pour que la page soit entièrement fonctionnelle avec la base de données.

Veux‑tu que je fasse ça tout de suite ?



Des répons
