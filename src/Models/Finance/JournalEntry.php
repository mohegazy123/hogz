<?php

namespace App\Models\Finance;

use App\Models\BaseModel;

class JournalEntry extends BaseModel {
    protected $table = 'journal_entries';
    protected $primaryKey = 'id';
    protected $fillable = [
        'entry_no', 'reference', 'entry_date', 'description', 
        'total_debit', 'total_credit', 'status', 'notes',
        'created_by', 'approved_by', 'approved_at'
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_APPROVED = 'approved';
    const STATUS_VOIDED = 'voided';

    public function create(array $data) {
        $this->db->beginTransaction();
        
        try {
            // Generate entry number if not provided
            if (empty($data['entry_no'])) {
                $data['entry_no'] = $this->generateEntryNumber();
            }
            
            // Create the journal entry
            $entryId = parent::create($data);
            
            // Process journal items
            if ($entryId && !empty($data['items'])) {
                $journalItemModel = new JournalItem();
                
                foreach ($data['items'] as $item) {
                    $item['journal_entry_id'] = $entryId;
                    $journalItemModel->create($item);
                }
            }
            
            $this->db->commit();
            return $entryId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getJournalEntryWithItems($entryId) {
        $entry = $this->findById($entryId);
        
        if (!$entry) {
            return null;
        }
        
        $journalItemModel = new JournalItem();
        $entry['items'] = $journalItemModel->getItemsByJournalEntryId($entryId);
        
        return $entry;
    }
    
    public function generateEntryNumber() {
        // Generate a unique entry number with format JE-YYYYMMDD-XXXX
        $prefix = 'JE-' . date('Ymd') . '-';
        
        $sql = "SELECT MAX(entry_no) as max_entry FROM {$this->table} WHERE entry_no LIKE ?";
        $pattern = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $maxEntry = $row['max_entry'];
        
        if ($maxEntry) {
            // Extract the sequential number part
            $maxNum = (int) substr($maxEntry, -4);
            $nextNum = $maxNum + 1;
        } else {
            $nextNum = 1;
        }
        
        // Format with leading zeros
        $nextNumStr = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $nextNumStr;
    }
    
    public function postJournalEntry($entryId) {
        $this->db->beginTransaction();
        
        try {
            $entry = $this->getJournalEntryWithItems($entryId);
            
            if (!$entry) {
                throw new \Exception('Journal entry not found');
            }
            
            if ($entry['status'] !== self::STATUS_DRAFT) {
                throw new \Exception('Journal entry is not in draft status');
            }
            
            // Verify debits = credits
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($entry['items'] as $item) {
                $totalDebit += $item['debit_amount'];
                $totalCredit += $item['credit_amount'];
            }
            
            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \Exception('Journal entry is not balanced');
            }
            
            // Update account balances
            $accountModel = new Account();
            
            foreach ($entry['items'] as $item) {
                $account = $accountModel->findById($item['account_id']);
                
                if (!$account) {
                    throw new \Exception('Account not found: ' . $item['account_id']);
                }
                
                // Update account balance based on account type and debit/credit
                if ($item['debit_amount'] > 0) {
                    $this->updateAccountBalanceByDebit($account, $item['debit_amount']);
                }
                
                if ($item['credit_amount'] > 0) {
                    $this->updateAccountBalanceByCredit($account, $item['credit_amount']);
                }
            }
            
            // Update journal entry status
            $this->update($entryId, [
                'status' => self::STATUS_POSTED,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit
            ]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function updateAccountBalanceByDebit($account, $amount) {
        $accountModel = new Account();
        
        switch ($account['type']) {
            case Account::TYPE_ASSET:
            case Account::TYPE_EXPENSE:
                // Debit increases asset and expense accounts
                $accountModel->updateBalance($account['id'], $amount, 'add');
                break;
                
            case Account::TYPE_LIABILITY:
            case Account::TYPE_EQUITY:
            case Account::TYPE_REVENUE:
                // Debit decreases liability, equity and revenue accounts
                $accountModel->updateBalance($account['id'], $amount, 'subtract');
                break;
        }
    }
    
    private function updateAccountBalanceByCredit($account, $amount) {
        $accountModel = new Account();
        
        switch ($account['type']) {
            case Account::TYPE_ASSET:
            case Account::TYPE_EXPENSE:
                // Credit decreases asset and expense accounts
                $accountModel->updateBalance($account['id'], $amount, 'subtract');
                break;
                
            case Account::TYPE_LIABILITY:
            case Account::TYPE_EQUITY:
            case Account::TYPE_REVENUE:
                // Credit increases liability, equity and revenue accounts
                $accountModel->updateBalance($account['id'], $amount, 'add');
                break;
        }
    }
    
    public function approveJournalEntry($entryId, $approverId) {
        $entry = $this->findById($entryId);
        
        if (!$entry) {
            return false;
        }
        
        if ($entry['status'] !== self::STATUS_POSTED) {
            return false;
        }
        
        return $this->update($entryId, [
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function voidJournalEntry($entryId) {
        $this->db->beginTransaction();
        
        try {
            $entry = $this->getJournalEntryWithItems($entryId);
            
            if (!$entry) {
                throw new \Exception('Journal entry not found');
            }
            
            if ($entry['status'] !== self::STATUS_POSTED && $entry['status'] !== self::STATUS_APPROVED) {
                throw new \Exception('Journal entry cannot be voided');
            }
            
            // Reverse account balances
            $accountModel = new Account();
            
            foreach ($entry['items'] as $item) {
                $account = $accountModel->findById($item['account_id']);
                
                if (!$account) {
                    throw new \Exception('Account not found: ' . $item['account_id']);
                }
                
                // Reverse account balance based on account type and debit/credit
                if ($item['debit_amount'] > 0) {
                    $this->updateAccountBalanceByCredit($account, $item['debit_amount']);
                }
                
                if ($item['credit_amount'] > 0) {
                    $this->updateAccountBalanceByDebit($account, $item['credit_amount']);
                }
            }
            
            // Update journal entry status
            $this->update($entryId, [
                'status' => self::STATUS_VOIDED
            ]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getJournalEntriesByDate($startDate, $endDate, $status = null) {
        $sql = "SELECT * FROM {$this->table} WHERE entry_date BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        $types = "ss";
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY entry_date DESC, id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}