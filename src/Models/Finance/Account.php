<?php

namespace App\Models\Finance;

use App\Models\BaseModel;

class Account extends BaseModel {
    protected $table = 'accounts';
    protected $primaryKey = 'id';
    protected $fillable = [
        'code', 'name', 'description', 'type', 'parent_id', 
        'level', 'balance', 'is_active', 'created_by'
    ];

    // Account types
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSE = 'expense';

    public function getAccountsByType($type) {
        $sql = "SELECT * FROM {$this->table} WHERE type = ? ORDER BY code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getChildAccounts($parentId) {
        $sql = "SELECT * FROM {$this->table} WHERE parent_id = ? ORDER BY code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $parentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAccountTree($parentId = 0, $level = 0) {
        $accounts = $this->getChildAccounts($parentId);
        $tree = [];
        
        foreach ($accounts as $account) {
            $account['level'] = $level;
            $children = $this->getAccountTree($account['id'], $level + 1);
            $account['children'] = $children;
            $tree[] = $account;
        }
        
        return $tree;
    }

    public function getAccountPath($id) {
        $path = [];
        $current = $this->findById($id);
        
        if ($current) {
            $path[] = $current;
            
            while ($current['parent_id'] > 0) {
                $current = $this->findById($current['parent_id']);
                if ($current) {
                    array_unshift($path, $current);
                } else {
                    break;
                }
            }
        }
        
        return $path;
    }

    public function updateBalance($id, $amount, $operation = 'add') {
        $account = $this->findById($id);
        
        if (!$account) {
            return false;
        }
        
        $currentBalance = $account['balance'];
        $newBalance = $operation === 'add' ? $currentBalance + $amount : $currentBalance - $amount;
        
        return $this->update($id, ['balance' => $newBalance]);
    }

    public function getAccountsBySearch($search) {
        $searchTerm = "%{$search}%";
        $sql = "SELECT * FROM {$this->table} WHERE code LIKE ? OR name LIKE ? ORDER BY code ASC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function calculateAccountLevels() {
        // Start with root level accounts (parent_id = 0)
        $rootAccounts = $this->getChildAccounts(0);
        
        foreach ($rootAccounts as $account) {
            $this->_updateAccountLevel($account['id'], 1);
        }
        
        return true;
    }
    
    private function _updateAccountLevel($accountId, $level) {
        // Update the current account's level
        $this->update($accountId, ['level' => $level]);
        
        // Get child accounts
        $children = $this->getChildAccounts($accountId);
        
        // Update each child's level
        foreach ($children as $child) {
            $this->_updateAccountLevel($child['id'], $level + 1);
        }
    }

    public function isAccountCodeUnique($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE code = ?";
        $params = [$code];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (count($params) === 1) {
            $stmt->bind_param("s", $params[0]);
        } else {
            $stmt->bind_param("si", $params[0], $params[1]);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['count'] === 0;
    }
}