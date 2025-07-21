<?php

namespace App\Models\Finance;

use App\Models\BaseModel;

class Voucher extends BaseModel {
    protected $table = 'vouchers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'voucher_no', 'voucher_type', 'voucher_date', 'due_date',
        'reference_no', 'party_type', 'party_id', 'amount', 
        'description', 'status', 'journal_entry_id',
        'created_by', 'approved_by', 'approved_at'
    ];
    
    const TYPE_RECEIVABLE = 'receivable';
    const TYPE_PAYABLE = 'payable';
    
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_VOIDED = 'voided';
    
    const PARTY_TYPE_CUSTOMER = 'customer';
    const PARTY_TYPE_SUPPLIER = 'supplier';
    const PARTY_TYPE_EMPLOYEE = 'employee';
    const PARTY_TYPE_OTHER = 'other';

    public function create(array $data) {
        $this->db->beginTransaction();
        
        try {
            // Generate voucher number if not provided
            if (empty($data['voucher_no'])) {
                $prefix = $data['voucher_type'] === self::TYPE_RECEIVABLE ? 'AR-' : 'AP-';
                $data['voucher_no'] = $this->generateVoucherNumber($prefix);
            }
            
            // Create voucher
            $voucherId = parent::create($data);
            
            // Process voucher items if provided
            if ($voucherId && !empty($data['items'])) {
                $voucherItemModel = new VoucherItem();
                
                foreach ($data['items'] as $item) {
                    $item['voucher_id'] = $voucherId;
                    $voucherItemModel->create($item);
                }
            }
            
            $this->db->commit();
            return $voucherId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function generateVoucherNumber($prefix = 'V-') {
        // Generate a unique voucher number with format V-YYYYMMDD-XXXX
        $prefix = $prefix . date('Ymd') . '-';
        
        $sql = "SELECT MAX(voucher_no) as max_voucher FROM {$this->table} WHERE voucher_no LIKE ?";
        $pattern = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $maxVoucher = $row['max_voucher'];
        
        if ($maxVoucher) {
            // Extract the sequential number part
            $maxNum = (int) substr($maxVoucher, -4);
            $nextNum = $maxNum + 1;
        } else {
            $nextNum = 1;
        }
        
        // Format with leading zeros
        $nextNumStr = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $nextNumStr;
    }
    
    public function getVoucherWithItems($voucherId) {
        $voucher = $this->findById($voucherId);
        
        if (!$voucher) {
            return null;
        }
        
        // Get party details
        if ($voucher['party_type'] === self::PARTY_TYPE_CUSTOMER) {
            $partyModel = new \App\Models\Customer();
        } elseif ($voucher['party_type'] === self::PARTY_TYPE_SUPPLIER) {
            $partyModel = new \App\Models\Supplier();
        } else {
            $partyModel = null;
        }
        
        if ($partyModel) {
            $party = $partyModel->findById($voucher['party_id']);
            $voucher['party_name'] = $party ? $party['name'] : 'Unknown';
        } else {
            $voucher['party_name'] = 'Other';
        }
        
        // Get voucher items
        $voucherItemModel = new VoucherItem();
        $voucher['items'] = $voucherItemModel->getItemsByVoucherId($voucherId);
        
        // Get payments
        $paymentModel = new Payment();
        $voucher['payments'] = $paymentModel->getPaymentsByVoucherId($voucherId);
        $voucher['total_paid'] = array_sum(array_column($voucher['payments'], 'amount'));
        $voucher['balance'] = $voucher['amount'] - $voucher['total_paid'];
        
        return $voucher;
    }
    
    public function approveVoucher($voucherId, $approverId) {
        $this->db->beginTransaction();
        
        try {
            $voucher = $this->getVoucherWithItems($voucherId);
            
            if (!$voucher) {
                throw new \Exception('Voucher not found');
            }
            
            if ($voucher['status'] !== self::STATUS_DRAFT) {
                throw new \Exception('Voucher is not in draft status');
            }
            
            // Create journal entry for the voucher
            $journalEntryModel = new JournalEntry();
            $journalItems = [];
            
            // Set up journal entry data
            $journalEntryData = [
                'entry_date' => $voucher['voucher_date'],
                'reference' => $voucher['voucher_no'],
                'description' => $voucher['description'],
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => $approverId,
                'items' => []
            ];
            
            // Add journal items based on voucher type
            if ($voucher['voucher_type'] === self::TYPE_RECEIVABLE) {
                // Accounts Receivable (Debit) / Revenue or Sales Account (Credit)
                
                // First get the AR account (should be configured in settings)
                $arAccountId = $this->getSettingValue('ar_account_id');
                if (!$arAccountId) {
                    throw new \Exception('Accounts Receivable account not configured');
                }
                
                // Debit AR account
                $journalEntryData['items'][] = [
                    'account_id' => $arAccountId,
                    'description' => 'Accounts Receivable - ' . $voucher['reference_no'],
                    'debit_amount' => $voucher['amount'],
                    'credit_amount' => 0
                ];
                
                // Credit the revenue accounts from voucher items
                foreach ($voucher['items'] as $item) {
                    $journalEntryData['items'][] = [
                        'account_id' => $item['account_id'],
                        'description' => $item['description'],
                        'debit_amount' => 0,
                        'credit_amount' => $item['amount']
                    ];
                }
            } else {
                // Expense or Asset Account (Debit) / Accounts Payable (Credit)
                
                // First get the AP account (should be configured in settings)
                $apAccountId = $this->getSettingValue('ap_account_id');
                if (!$apAccountId) {
                    throw new \Exception('Accounts Payable account not configured');
                }
                
                // Credit AP account
                $journalEntryData['items'][] = [
                    'account_id' => $apAccountId,
                    'description' => 'Accounts Payable - ' . $voucher['reference_no'],
                    'debit_amount' => 0,
                    'credit_amount' => $voucher['amount']
                ];
                
                // Debit the expense accounts from voucher items
                foreach ($voucher['items'] as $item) {
                    $journalEntryData['items'][] = [
                        'account_id' => $item['account_id'],
                        'description' => $item['description'],
                        'debit_amount' => $item['amount'],
                        'credit_amount' => 0
                    ];
                }
            }
            
            // Create the journal entry
            $entryId = $journalEntryModel->create($journalEntryData);
            
            // Post the journal entry
            $journalEntryModel->postJournalEntry($entryId);
            
            // Update voucher
            $this->update($voucherId, [
                'status' => self::STATUS_APPROVED,
                'journal_entry_id' => $entryId,
                'approved_by' => $approverId,
                'approved_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function getSettingValue($key) {
        $sql = "SELECT value FROM settings WHERE `key` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row ? $row['value'] : null;
    }
    
    public function recordPayment($voucherId, $paymentData) {
        $this->db->beginTransaction();
        
        try {
            $voucher = $this->findById($voucherId);
            
            if (!$voucher) {
                throw new \Exception('Voucher not found');
            }
            
            if ($voucher['status'] !== self::STATUS_APPROVED && 
                $voucher['status'] !== self::STATUS_PARTIALLY_PAID) {
                throw new \Exception('Voucher cannot be paid');
            }
            
            // Get current payments
            $paymentModel = new Payment();
            $payments = $paymentModel->getPaymentsByVoucherId($voucherId);
            $totalPaid = array_sum(array_column($payments, 'amount'));
            
            // Check if amount is valid
            $remainingAmount = $voucher['amount'] - $totalPaid;
            
            if ($paymentData['amount'] <= 0 || $paymentData['amount'] > $remainingAmount) {
                throw new \Exception('Invalid payment amount');
            }
            
            // Record the payment
            $paymentData['voucher_id'] = $voucherId;
            $paymentId = $paymentModel->create($paymentData);
            
            // Create journal entry for the payment
            $journalEntryModel = new JournalEntry();
            
            $journalEntryData = [
                'entry_date' => $paymentData['payment_date'],
                'reference' => $paymentData['reference_no'],
                'description' => 'Payment for ' . $voucher['voucher_no'],
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => $paymentData['created_by'],
                'items' => []
            ];
            
            // Add journal items based on voucher type
            if ($voucher['voucher_type'] === self::TYPE_RECEIVABLE) {
                // Cash or Bank Account (Debit) / Accounts Receivable (Credit)
                
                // Get the AR account
                $arAccountId = $this->getSettingValue('ar_account_id');
                if (!$arAccountId) {
                    throw new \Exception('Accounts Receivable account not configured');
                }
                
                // Credit AR account
                $journalEntryData['items'][] = [
                    'account_id' => $arAccountId,
                    'description' => 'Payment received for ' . $voucher['voucher_no'],
                    'debit_amount' => 0,
                    'credit_amount' => $paymentData['amount']
                ];
                
                // Debit cash or bank account
                $journalEntryData['items'][] = [
                    'account_id' => $paymentData['account_id'],
                    'description' => 'Payment received for ' . $voucher['voucher_no'],
                    'debit_amount' => $paymentData['amount'],
                    'credit_amount' => 0
                ];
            } else {
                // Accounts Payable (Debit) / Cash or Bank Account (Credit)
                
                // Get the AP account
                $apAccountId = $this->getSettingValue('ap_account_id');
                if (!$apAccountId) {
                    throw new \Exception('Accounts Payable account not configured');
                }
                
                // Debit AP account
                $journalEntryData['items'][] = [
                    'account_id' => $apAccountId,
                    'description' => 'Payment made for ' . $voucher['voucher_no'],
                    'debit_amount' => $paymentData['amount'],
                    'credit_amount' => 0
                ];
                
                // Credit cash or bank account
                $journalEntryData['items'][] = [
                    'account_id' => $paymentData['account_id'],
                    'description' => 'Payment made for ' . $voucher['voucher_no'],
                    'debit_amount' => 0,
                    'credit_amount' => $paymentData['amount']
                ];
            }
            
            // Create and post the journal entry
            $entryId = $journalEntryModel->create($journalEntryData);
            $journalEntryModel->postJournalEntry($entryId);
            
            // Update payment with journal entry ID
            $paymentModel->update($paymentId, ['journal_entry_id' => $entryId]);
            
            // Update voucher status
            $newTotalPaid = $totalPaid + $paymentData['amount'];
            $newStatus = $newTotalPaid >= $voucher['amount'] ? 
                self::STATUS_PAID : self::STATUS_PARTIALLY_PAID;
            
            $this->update($voucherId, ['status' => $newStatus]);
            
            $this->db->commit();
            return $paymentId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function voidVoucher($voucherId) {
        $this->db->beginTransaction();
        
        try {
            $voucher = $this->getVoucherWithItems($voucherId);
            
            if (!$voucher) {
                throw new \Exception('Voucher not found');
            }
            
            if ($voucher['status'] === self::STATUS_VOIDED) {
                throw new \Exception('Voucher is already voided');
            }
            
            if (!empty($voucher['payments'])) {
                throw new \Exception('Cannot void a voucher with payments');
            }
            
            // If voucher has associated journal entry, void it
            if ($voucher['journal_entry_id']) {
                $journalEntryModel = new JournalEntry();
                $journalEntryModel->voidJournalEntry($voucher['journal_entry_id']);
            }
            
            // Update voucher status
            $this->update($voucherId, ['status' => self::STATUS_VOIDED]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getVouchersByType($type, $status = null, $limit = null, $offset = 0) {
        $sql = "SELECT v.*, 
                CASE 
                    WHEN v.party_type = 'customer' THEN c.first_name || ' ' || c.last_name
                    WHEN v.party_type = 'supplier' THEN s.name
                    ELSE 'Other'
                END as party_name
                FROM {$this->table} v
                LEFT JOIN customers c ON v.party_type = 'customer' AND v.party_id = c.id
                LEFT JOIN suppliers s ON v.party_type = 'supplier' AND v.party_id = s.id
                WHERE v.voucher_type = ?";
        
        $params = [$type];
        $types = "s";
        
        if ($status) {
            $sql .= " AND v.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $sql .= " ORDER BY v.voucher_date DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $limit;
            $types .= "ii";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getReceivablesByCustomer($customerId) {
        $sql = "SELECT v.*, 
                (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.voucher_id = v.id) as total_paid,
                (v.amount - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.voucher_id = v.id), 0)) as balance
                FROM {$this->table} v
                WHERE v.party_type = ? 
                AND v.party_id = ? 
                AND v.voucher_type = ?
                AND v.status IN (?, ?)
                ORDER BY v.due_date ASC";
        
        $partyType = self::PARTY_TYPE_CUSTOMER;
        $voucherType = self::TYPE_RECEIVABLE;
        $statusApproved = self::STATUS_APPROVED;
        $statusPartiallyPaid = self::STATUS_PARTIALLY_PAID;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisss", $partyType, $customerId, $voucherType, $statusApproved, $statusPartiallyPaid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPayablesBySupplier($supplierId) {
        $sql = "SELECT v.*, 
                (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.voucher_id = v.id) as total_paid,
                (v.amount - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.voucher_id = v.id), 0)) as balance
                FROM {$this->table} v
                WHERE v.party_type = ? 
                AND v.party_id = ? 
                AND v.voucher_type = ?
                AND v.status IN (?, ?)
                ORDER BY v.due_date ASC";
        
        $partyType = self::PARTY_TYPE_SUPPLIER;
        $voucherType = self::TYPE_PAYABLE;
        $statusApproved = self::STATUS_APPROVED;
        $statusPartiallyPaid = self::STATUS_PARTIALLY_PAID;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisss", $partyType, $supplierId, $voucherType, $statusApproved, $statusPartiallyPaid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getOverdueVouchers($voucherType, $daysOverdue = 30) {
        $today = date('Y-m-d');
        $cutoffDate = date('Y-m-d', strtotime("-{$daysOverdue} days"));
        
        $sql = "SELECT v.*, 
                (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.voucher_id = v.id) as total_paid,
                (v.amount - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.voucher_id = v.id), 0)) as balance
                FROM {$this->table} v
                WHERE v.voucher_type = ?
                AND v.status IN (?, ?)
                AND v.due_date < ?
                ORDER BY v.due_date ASC";
        
        $statusApproved = self::STATUS_APPROVED;
        $statusPartiallyPaid = self::STATUS_PARTIALLY_PAID;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssss", $voucherType, $statusApproved, $statusPartiallyPaid, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}