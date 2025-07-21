<?php

namespace App\Models\Finance;

use App\Models\BaseModel;

class VoucherItem extends BaseModel {
    protected $table = 'voucher_items';
    protected $primaryKey = 'id';
    protected $fillable = [
        'voucher_id', 'account_id', 'description', 
        'amount', 'tax_rate', 'tax_amount'
    ];
    protected $timestamps = false;
    
    public function getItemsByVoucherId($voucherId) {
        $sql = "SELECT vi.*, a.code as account_code, a.name as account_name
                FROM {$this->table} vi
                LEFT JOIN accounts a ON vi.account_id = a.id
                WHERE vi.voucher_id = ?
                ORDER BY vi.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $voucherId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function deleteByVoucherId($voucherId) {
        $sql = "DELETE FROM {$this->table} WHERE voucher_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $voucherId);
        return $stmt->execute();
    }
}