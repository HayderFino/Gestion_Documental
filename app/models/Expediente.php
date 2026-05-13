<?php

namespace app\models;

class Expediente extends Model {
    protected $table = 'expedientes';

    public function search($query) {
        $stmt = $this->db->prepare("
            SELECT e.*, t.nombre as tramite_nombre 
            FROM expedientes e 
            LEFT JOIN tramites t ON e.tramite_id = t.id 
            WHERE e.numero_expediente LIKE ? OR e.titulo LIKE ?
        ");
        $stmt->execute(["%$query%", "%$query%"]);
        return $stmt->fetchAll();
    }

    public function getDetailed($id) {
        $stmt = $this->db->prepare("
            SELECT e.*, t.nombre as tramite_nombre 
            FROM expedientes e 
            LEFT JOIN tramites t ON e.tramite_id = t.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
