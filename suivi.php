<?php

class Suivi
{
    private $conn;
    private $table = "suivis";

    public $id;
    public $courrier_id;
    public $action;
    public $description;
    public $service_source_id;
    public $service_destination_id;
    public $utilisateur_id;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* =========================================================
       AJOUT D’UNE ACTION (transfert, validation, commentaire…)
       ========================================================= */
    public function ajouter()
    {
        $sql = "INSERT INTO suivis (
                    courrier_id,
                    action,
                    description,
                    service_source_id,
                    service_destination_id,
                    utilisateur_id,
                    date_action
                ) VALUES (
                    :cid, :action, :descr, :src, :dst, :uid, NOW()
                )";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':cid'   => $this->courrier_id,
            ':action'=> $this->action,
            ':descr' => $this->description,
            ':src'   => $this->service_source_id,
            ':dst'   => $this->service_destination_id,
            ':uid'   => $this->utilisateur_id
        ]);
    }

    /* =========================================================
       HISTORIQUE COMPLET D’UN COURRIER
       ========================================================= */
    public function getHistorique($courrier_id)
    {
        $sql = "
            SELECT 
                s.*,
                ss.nom AS service_source,
                sd.nom AS service_destination,
                u.nom AS utilisateur
            FROM suivis s
            LEFT JOIN services ss ON s.service_source_id = ss.id
            LEFT JOIN services sd ON s.service_destination_id = sd.id
            LEFT JOIN utilisateurs u ON s.utilisateur_id = u.id
            WHERE s.courrier_id = ?
            ORDER BY s.date_action ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courrier_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
       DERNIÈRE ACTION EFFECTUÉE SUR LE COURRIER
       ========================================================= */
    public function getDerniereAction($courrier_id)
    {
        $sql = "
            SELECT *
            FROM suivis
            WHERE courrier_id = ?
            ORDER BY date_action DESC
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courrier_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================================================
       TRANSFERT COMPLET (ACTION + MAJ COURRIER)
       ========================================================= */
    public function transfererCourrier($courrier_id, $service_destination, $utilisateur_id)
    {
        // 1️⃣ Récupérer le service actuel
        $sql = "SELECT service_actuel_id FROM courriers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courrier_id]);
        $service_source = $stmt->fetchColumn();

        // 2️⃣ Historique du transfert
        $sql = "
            INSERT INTO suivis (
                courrier_id,
                action,
                description,
                service_source_id,
                service_destination_id,
                utilisateur_id,
                date_action
            ) VALUES (
                ?, 'transfert',
                'Transfert du courrier',
                ?, ?, ?, NOW()
            )
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $courrier_id,
            $service_source,
            $service_destination,
            $utilisateur_id
        ]);

        // 3️⃣ Mise à jour du courrier (POINT CLÉ)
        $sql = "
            UPDATE courriers
            SET 
                service_actuel_id = ?,
                statut = 'reçu'
            WHERE id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$service_destination, $courrier_id]);
    }
}
