<?php
class Courrier {
    public $id;
    public $tracking_code;
    public $type;
    public $objet;
    public $expediteur;
    public $destinataire;
    public $service_actuel_id;
    public $statut;
    public $date_creation;

    private $conn; // Connexion PDO

    // Constructeur : attend un objet PDO
    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    // Génère un code de suivi unique
    public function genererTracking() {
        return "CR-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    }

    // Crée un courrier en base de données
    public function create() {
        $sql = "INSERT INTO courriers 
                (tracking_code, type, objet, expediteur, destinataire, service_actuel_id, statut)
                VALUES (:tracking_code, :type, :objet, :expediteur, :destinataire, :service_actuel_id, :statut)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':tracking_code' => $this->tracking_code,
            ':type' => $this->type,
            ':objet' => $this->objet,
            ':expediteur' => $this->expediteur,
            ':destinataire' => $this->destinataire,
            ':service_actuel_id' => $this->service_actuel_id,
            ':statut' => $this->statut
        ]);
    }

    // Récupère un courrier par son code de suivi
    public function getByTracking($tracking_code) {
        $sql = "SELECT c.*, s.nom AS service_actuel
                FROM courriers c
                LEFT JOIN services s ON c.service_actuel_id = s.id
                WHERE tracking_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$tracking_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Met à jour le service actuel du courrier
    public function updateService($new_service) {
        $sql = "UPDATE courriers SET service_actuel_id = :svc WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":svc" => $new_service,
            ":id" => $this->id
        ]);
    }
}