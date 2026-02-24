<?php
// controllers/CourrierController.php
session_start();
require_once "../Courrier/Courrier.php";
require_once "../Courrier/Suivi.php";
require_once "../Courrier/JournalAction.php";

$action = $_GET['action'] ?? '';

if ($action === 'ajouter') {
    // Vérifier que le formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = $_POST['type'] ?? '';
        $objet = $_POST['objet'] ?? '';
        $expediteur = $_POST['expediteur'] ?? '';
        $destinataire = $_POST['destinataire'] ?? '';
        $date_creation = date('Y-m-d H:i:s');

        // Générer un code de suivi unique (exemple simple)
        $tracking_code = 'CR-' . rand(100000, 999999);

        // Création du courrier
        $courrier = new Courrier($type,$objet,$expediteur,$destinataire,$date_creation);
        $courrier->tracking_code = $tracking_code;
        $courrier->type = $type;
        $courrier->objet = $objet;
        $courrier->expediteur = $expediteur;
        $courrier->destinataire = $destinataire;
        $courrier->service_actuel_id = 1; // par défaut Service Accueil
        $courrier->statut = 'reçu';
        $courrier->date_creation = $date_creation;

        // Gestion des fichiers joints
        $fichiers_uploades = [];
        if (!empty($_FILES['fichiers']['name'][0])) {
            $upload_dir = '../uploads/';
            foreach ($_FILES['fichiers']['tmp_name'] as $index => $tmpName) {
                $nom_fichier = basename($_FILES['fichiers']['name'][$index]);
                $destination = $upload_dir . $tracking_code . '_' . $nom_fichier;
                if (move_uploaded_file($tmpName, $destination)) {
                    $fichiers_uploades[] = $destination;
                }
            }
        }

        // Sauvegarde du courrier
        if ($courrier->create()) {

            // Création d'une entrée dans suivis
            $suivi = new Suivi();
            $suivi->tracking_code = $tracking_code;
            $suivi->action = 'réception';
            $suivi->service_destination_id = $courrier->service_actuel_id;
            $suivi->utilisateur_id = $_SESSION['user']['id'] ?? null;
            $suivi->date_action = $date_creation;
            $suivi->save();

            // Optionnel : journal_actions pour audit
            $audit = new JournalAction();
            $audit->tracking_code = $tracking_code;
            $audit->action = 'enregistrement';
            $audit->utilisateur_id = $_SESSION['user']['id'] ?? null;
            $audit->date_action = $date_creation;
            $audit->save();

            $_SESSION['success'] = "Le courrier a été ajouté avec succès (Tracking: $tracking_code).";
            header("Location: ../public/liste.php");
            exit;
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout du courrier.";
            header("Location: ../public/ajouter_courrier.php");
            exit;
        }
    }
}