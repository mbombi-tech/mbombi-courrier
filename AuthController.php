<?php
session_start();
require_once "../courrier/conn.php";
require_once "../courrier/Utilisateurs.php";

class AuthController {

    public function login() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $conn = new conn();
            $db = $database->getConnection();

            $utilisateur = new Utilisateur($db);

            $email = $_POST['email'];
            $mot_de_passe = $_POST['mot_de_passe'];

            $user = $utilisateur->login($email, $mot_de_passe);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['role'] = $user['role_nom'];
                $_SESSION['service_id'] = $user['service_id'];

                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error'] = "Email ou mot de passe incorrect";
                header("Location: login.php");
                exit;
            }
        }
    }

    public function logout() {
        session_destroy();
        header("Location: login.php");
    }
}

$auth = new AuthController();
$auth->login();