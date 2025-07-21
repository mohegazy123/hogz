<?php

namespace App\Models\Finance;

use App\Models\BaseModel;

class JournalItem extends BaseModel {
    protected $table = 'journal_items';
    protected $primaryKey = 'id';
    protected $fillable = [
        'journal_entry_id', 'account_id', 'description',
        'debit_amount', 'credit_amount', 'reference'
    ];
    protected $timestamps = false;
    
    public function getItemsByJournalEntryId($entryId) {
        $sql = "SELECT ji.*, a.code as account_code, a.name as account_name, a.type as account_type
                FROM {$this->table} ji
                LEFT JOIN accounts a ON ji.account_id = a.id
                WHERE ji.journal_entry_id = ?
                ORDER BY ji.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $entryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTotalDebitByEntryId($entryId) {
        $sql = "SELECT SUM(debit_amount) as total FROM {$this->table} WHERE journal_entry_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $entryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (float) $row['total'];
    }
    
    public function getTotalCreditByEntryId($entryId) {
        $sql = "SELECT SUM(credit_amount) as total FROM {$this->table} WHERE journal_entry_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $entryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (float) $row['total'];
    }
    
    public function getAccountLedgerEntries($accountId, $startDate, $endDate) {
        $sql = "SELECT ji.*, je.entry_no, je.entry_date, je.reference as entry_reference, 
                je.description as entry_description, je.status
                FROM {$this->table} ji
                LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE ji.account_id = ? AND je.entry_date BETWEEN ? AND ?
                AND je.status != 'draft'
                ORDER BY je.entry_date ASC, je.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $accountId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getAccountBalance($accountId, $asOfDate = null) {
        $sql = "SELECT 
                    SUM(CASE 
                        WHEN a.type IN ('asset', 'expense') THEN (ji.debit_amount - ji.credit_amount)
                        ELSE (ji.credit_amount - ji.debit_amount)
                    END) as balance
                FROM {$this->table} ji
                LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id
                LEFT JOIN accounts a ON ji.account_id = a.id
                WHERE ji.account_id = ? AND je.status IN ('posted', 'approved')";
        
        $params = [$accountId];
        $types = "i";
        
        if ($asOfDate) {
            $sql .= " AND je.entry_date <= ?";
            $params[] = $asOfDate;
            $types .= "s";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (float) $row['balance'];
    }
    
    public function deleteByEntryId($entryId) {
        $sql = "DELETE FROM {$this->table} WHERE journal_entry_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $entryId);
        return $stmt->execute();
    }
}