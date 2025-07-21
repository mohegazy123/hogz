<?php

namespace App\Models;

class User extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name',
        'role', 'department_id', 'status', 'last_login'
    ];

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            // Update last login time
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }
        
        return false;
    }

    public function register($userData) {
        // Hash password
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($userData);
    }

    public function getUsersByDepartment($departmentId) {
        $sql = "SELECT * FROM {$this->table} WHERE department_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}