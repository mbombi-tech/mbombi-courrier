<?php
class JournalAction {
    private $conn;
    private $table = "journal_actions";

    public $id;
    public $utilisateur_id;
    public $courrier_id;
    public $action;
    public $date_action;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Ajouter une ligne dans le journal
    public function log($utilisateur_id, $courrier_id, $action) {
        $sql = "INSERT INTO {$this->table} 
                (utilisateur_id, courrier_id, action) 
                VALUES (:uid, :cid, :action)";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':uid' => $utilisateur_id,
            ':cid' => $courrier_id,
            ':action' => $action
        ]);
    }

    // Récupérer tout l'audit
    public function getAll() {
        $sql = "SELECT j.*, u.nom AS utilisateur, c.tracking_code
                FROM journal_actions j
                LEFT JOIN utilisateurs u ON j.utilisateur_id = u.id
                LEFT JOIN courriers c ON j.courrier_id = c.id
                ORDER BY j.date_action DESC";
                
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer l’historique d’un courrier
    public function getByCourrier($courrier_id) {
        $sql = "SELECT j.*, u.nom AS utilisateur
                FROM journal_actions j
                LEFT JOIN utilisateurs u ON j.utilisateur_id = u.id
                WHERE courrier_id = ?
                ORDER BY date_action DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courrier_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}