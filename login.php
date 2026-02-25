<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conn.php";

// 🔁 Si déjà connecté → dashboard
if (isset($_SESSION['user']['id'])) {
    header("Location: /dashboard.php");
    exit;
}

$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $mot_de_passe = $_POST["mot_de_passe"] ?? "";

    if (!empty($email) && !empty($mot_de_passe)) {

        // Connexion base
        $database = new Conn();
        $db = $database->getConnection();

        $sql = "
            SELECT 
                u.id,
                u.nom,
                u.email,
                u.mot_de_passe,
                u.service_id,
                u.role_id,
                r.nom AS role
            FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = ? AND u.actif = 1
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {

            // 🔐 Sécurité importante
            session_regenerate_id(true);

            /*
            ==================================================
            ✅ STRUCTURE MODERNE
            ==================================================
            */
            $_SESSION['user'] = [
                'id'         => (int) $user['id'],
                'nom'        => $user['nom'],
                'role'       => $user['role'],
                'role_id'    => (int) $user['role_id'],
                'service_id' => (int) $user['service_id']
            ];

            /*
            ==================================================
            ✅ COMPATIBILITÉ ANCIENNES PAGES
            ==================================================
            */
            $_SESSION['user_id']    = (int) $user['id'];
            $_SESSION['role_id']    = (int) $user['role_id'];
            $_SESSION['service_id'] = (int) $user['service_id'];

            header("Location: /dashboard.php");
            exit;

        } else {
            $error = true;
        }
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion | Gestion Courrier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">

            <div class="card shadow">
                <div class="card-header text-center">
                    <h4>Connexion</h4>
                </div>

                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center">
                            Email ou mot de passe incorrect
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="mot_de_passe" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Se connecter
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
