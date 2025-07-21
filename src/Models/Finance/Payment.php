<?php

namespace App\Models\Finance;

use App\Models\BaseModel;

class Payment extends BaseModel {
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'voucher_id', 'payment_date', 'payment_method', 
        'account_id', 'reference_no', 'amount', 
        'notes', 'journal_entry_id', 'created_by'
    ];
    
    const METHOD_CASH = 'cash';
    const METHOD_CHECK = 'check';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_ONLINE = 'online';
    const METHOD_OTHER = 'other';
    
    public function getPaymentsByVoucherId($voucherId) {
        $sql = "SELECT p.*, a.code as account_code, a.name as account_name,
                u.first_name as created_by_first_name, u.last_name as created_by_last_name
                FROM {$this->table} p
                LEFT JOIN accounts a ON p.account_id = a.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.voucher_id = ?
                ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $voucherId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getRecentPayments($limit = 10) {
        $sql = "SELECT p.*, v.voucher_no, v.voucher_type,
                CASE 
                    WHEN v.party_type = 'customer' THEN c.first_name || ' ' || c.last_name
                    WHEN v.party_type = 'supplier' THEN s.name
                    ELSE 'Other'
                END as party_name,
                a.code as account_code, a.name as account_name
                FROM {$this->table} p
                LEFT JOIN vouchers v ON p.voucher_id = v.id
                LEFT JOIN customers c ON v.party_type = 'customer' AND v.party_id = c.id
                LEFT JOIN suppliers s ON v.party_type = 'supplier' AND v.party_id = s.id
                LEFT JOIN accounts a ON p.account_id = a.id
                ORDER BY p.payment_date DESC, p.id DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPaymentsByDateRange($startDate, $endDate, $accountId = null) {
        $sql = "SELECT p.*, v.voucher_no, v.voucher_type,
                CASE 
                    WHEN v.party_type = 'customer' THEN c.first_name || ' ' || c.last_name
                    WHEN v.party_type = 'supplier' THEN s.name
                    ELSE 'Other'
                END as party_name,
                a.code as account_code, a.name as account_name
                FROM {$this->table} p
                LEFT JOIN vouchers v ON p.voucher_id = v.id
                LEFT JOIN customers c ON v.party_type = 'customer' AND v.party_id = c.id
                LEFT JOIN suppliers s ON v.party_type = 'supplier' AND v.party_id = s.id
                LEFT JOIN accounts a ON p.account_id = a.id
                WHERE p.payment_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        $types = "ss";
        
        if ($accountId) {
            $sql .= " AND p.account_id = ?";
            $params[] = $accountId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY p.payment_date ASC, p.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}