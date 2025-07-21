<?php

namespace App\Models;

class Department extends BaseModel {
    protected $table = 'departments';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'code', 'manager_id', 'description', 'status'];
    
    public function getUserCount($departmentId) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE department_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int) $row['count'];
    }
}