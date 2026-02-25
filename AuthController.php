<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../courrier/conn.php";
require_once __DIR__ . "/../courrier/Utilisateurs.php";

class AuthController {

    private $db;

    public function __construct() {
        $database = new Conn(); // Assure-toi que ta classe s'appelle bien Conn
        $this->db = $database->getConnection();
    }

    public function login() {

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $utilisateur = new Utilisateur($this->db);

            $email = $_POST['email'] ?? '';
            $mot_de_passe = $_POST['mot_de_passe'] ?? '';

            $user = $utilisateur->login($email, $mot_de_passe);

            if ($user) {

                // Stockage structuré
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'nom' => $user['nom'],
                    'role' => $user['role_nom'],
                    'service_id' => $user['service_id']
                ];

                header("Location: /index.php"); // racine
                exit;

            } else {

                $_SESSION['error'] = "Email ou mot de passe incorrect";
                header("Location: /login.php");
                exit;
            }
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        header("Location: /login.php");
        exit;
    }
}

// Exécution automatique
$auth = new AuthController();
$auth->login();
