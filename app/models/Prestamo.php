<?php

namespace app\models;

class Prestamo extends Model {
    protected $table = 'prestamos';

    public function getActiveLoansCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM prestamos 
            WHERE usuario_solicitante_id = ? AND estado IN ('entregado', 'vencido')
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row['count'];
    }

    public function getTodayLoansCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM prestamos 
            WHERE usuario_solicitante_id = ? AND DATE(fecha_prestamo) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row['count'];
    }

    public function getAllDetailed() {
        $stmt = $this->db->prepare("
            SELECT p.*, e.numero_expediente, e.titulo as expediente_titulo, 
                   u1.nombre_completo as solicitante_nombre, u2.nombre_completo as autorizador_nombre,
                   m.nombre as motivo_nombre
            FROM prestamos p
            JOIN expedientes e ON p.expediente_id = e.id
            JOIN usuarios u1 ON p.usuario_solicitante_id = u1.id
            JOIN usuarios u2 ON p.usuario_archivo_id = u2.id
            JOIN motivos_consulta m ON p.motivo_id = m.id
            ORDER BY p.fecha_prestamo DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
