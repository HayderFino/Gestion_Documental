<?php

namespace app\models;

class Usuario extends Model {
    protected $table = 'usuarios';

    public function getByUsuario($usuario) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.usuario = ? AND u.estado = 'activo'
        ");
        $stmt->execute([$usuario]);
        return $stmt->fetch();
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
