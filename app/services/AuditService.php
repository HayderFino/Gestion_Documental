<?php

namespace app\services;

use app\config\Database;
use PDO;

class AuditService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($userId, $action, $table = null, $recordId = null, $oldValue = null, $newValue = null) {
        if (!$this->db) return false; // Ignorar si no hay base de datos
        
        $sql = "INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, valor_anterior, valor_nuevo, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $action,
            $table,
            $recordId,
            $oldValue ? json_encode($oldValue) : null,
            $newValue ? json_encode($newValue) : null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
}
