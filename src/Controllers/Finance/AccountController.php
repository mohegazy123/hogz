<?php

namespace App\Controllers\Finance;

use App\Controllers\BaseController;
use App\Models\Finance\Account;
use App\Models\Finance\JournalItem;

class AccountController extends BaseController {
    private $accountModel;
    private $journalItemModel;
    
    public function __construct() {
        $this->accountModel = new Account();
        $this->journalItemModel = new JournalItem();
    }
    
    public function index() {
        $this->requireAuth();
        
        // Get all accounts organized in tree structure
        $accountTree = $this->accountModel->getAccountTree();
        
        $this->render('finance/account/index', [
            'title' => 'Chart of Accounts',
            'accountTree' => $accountTree
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        
        // Get all accounts for parent selection
        $accounts = $this->accountModel->getAll(null, 0, 'code ASC');
        
        $errors = [];
        $account = [
            'code' => '',
            'name' => '',
            'description' => '',
            'type' => '',
            'parent_id' => 0,
            'is_active' => 1
        ];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['code', 'name', 'type'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate account code uniqueness
            if (!empty($postData['code']) && !$this->accountModel->isAccountCodeUnique($postData['code'])) {
                $errors['code'] = 'Account code must be unique';
            }
            
            // Validate account type
            $validTypes = [
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_REVENUE,
                Account::TYPE_EXPENSE
            ];
            
            if (!empty($postData['type']) && !in_array($postData['type'], $validTypes)) {
                $errors['type'] = 'Invalid account type';
            }
            
            if (empty($errors)) {
                $accountData = [
                    'code' => $postData['code'],
                    'name' => $postData['name'],
                    'description' => $postData['description'] ?? '',
                    'type' => $postData['type'],
                    'parent_id' => !empty($postData['parent_id']) ? (int)$postData['parent_id'] : 0,
                    'balance' => 0,
                    'is_active' => isset($postData['is_active']) ? 1 : 0,
                    'created_by' => $this->getUserId()
                ];
                
                // Create account
                $accountId = $this->accountModel->create($accountData);
                
                if ($accountId) {
                    // Recalculate account levels
                    $this->accountModel->calculateAccountLevels();
                    
                    $this->setFlash('success', 'Account created successfully!');
                    $this->redirect('/finance/accounts');
                } else {
                    $errors['create'] = 'Failed to create account. Please try again.';
                }
            }
            
            // Repopulate form data
            $account = $postData;
        }
        
        $this->render('finance/account/create', [
            'title' => 'Create Account',
            'account' => $account,
            'accounts' => $accounts,
            'errors' => $errors
        ]);
    }
    
    public function edit($id) {
        $this->requireAuth();
        
        $account = $this->accountModel->findById($id);
        
        if (!$account) {
            $this->setFlash('error', 'Account not found');
            $this->redirect('/finance/accounts');
        }
        
        // Get all accounts for parent selection (excluding current account and its children)
        $accounts = $this->accountModel->getAll(null, 0, 'code ASC');
        
        $errors = [];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['code', 'name', 'type'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate account code uniqueness
            if (!empty($postData['code']) && !$this->accountModel->isAccountCodeUnique($postData['code'], $id)) {
                $errors['code'] = 'Account code must be unique';
            }
            
            // Validate account type
            $validTypes = [
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_REVENUE,
                Account::TYPE_EXPENSE
            ];
            
            if (!empty($postData['type']) && !in_array($postData['type'], $validTypes)) {
                $errors['type'] = 'Invalid account type';
            }
            
            // Prevent selection of self or child accounts as parent
            if (!empty($postData['parent_id']) && $postData['parent_id'] == $id) {
                $errors['parent_id'] = 'An account cannot be its own parent';
            }
            
            // TODO: Add validation to prevent selection of child accounts as parent
            
            if (empty($errors)) {
                $accountData = [
                    'code' => $postData['code'],
                    'name' => $postData['name'],
                    'description' => $postData['description'] ?? '',
                    'type' => $postData['type'],
                    'parent_id' => !empty($postData['parent_id']) ? (int)$postData['parent_id'] : 0,
                    'is_active' => isset($postData['is_active']) ? 1 : 0
                ];
                
                // Update account
                $success = $this->accountModel->update($id, $accountData);
                
                if ($success) {
                    // Recalculate account levels
                    $this->accountModel->calculateAccountLevels();
                    
                    $this->setFlash('success', 'Account updated successfully!');
                    $this->redirect('/finance/accounts');
                } else {
                    $errors['update'] = 'Failed to update account. Please try again.';
                }
            }
            
            // Repopulate form data
            $account = $postData;
        }
        
        $this->render('finance/account/edit', [
            'title' => 'Edit Account',
            'account' => $account,
            'accounts' => $accounts,
            'errors' => $errors
        ]);
    }
    
    public function view($id) {
        $this->requireAuth();
        
        $account = $this->accountModel->findById($id);
        
        if (!$account) {
            $this->setFlash('error', 'Account not found');
            $this->redirect('/finance/accounts');
        }
        
        // Get account path (from root to current)
        $accountPath = $this->accountModel->getAccountPath($id);
        
        // Get child accounts if any
        $childAccounts = $this->accountModel->getChildAccounts($id);
        
        // Get query parameters
        $queryData = $this->getQueryData();
        $startDate = $queryData['start_date'] ?? date('Y-m-01'); // First day of current month
        $endDate = $queryData['end_date'] ?? date('Y-m-t'); // Last day of current month
        
        // Get account ledger entries
        $ledgerEntries = $this->journalItemModel->getAccountLedgerEntries($id, $startDate, $endDate);
        
        // Calculate opening balance (as of start date)
        $openingBalance = $this->journalItemModel->getAccountBalance($id, date('Y-m-d', strtotime($startDate . ' -1 day')));
        
        $this->render('finance/account/view', [
            'title' => 'Account Details',
            'account' => $account,
            'accountPath' => $accountPath,
            'childAccounts' => $childAccounts,
            'ledgerEntries' => $ledgerEntries,
            'openingBalance' => $openingBalance,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $account = $this->accountModel->findById($id);
            
            if (!$account) {
                $this->setFlash('error', 'Account not found');
                $this->redirect('/finance/accounts');
            }
            
            // Check if account has child accounts
            $childAccounts = $this->accountModel->getChildAccounts($id);
            if (!empty($childAccounts)) {
                $this->setFlash('error', 'Cannot delete account with child accounts. Delete child accounts first.');
                $this->redirect('/finance/accounts/view/' . $id);
                return;
            }
            
            // Check if account has transactions
            $startDate = '2000-01-01'; // A very old date
            $endDate = date('Y-m-d'); // Today
            $ledgerEntries = $this->journalItemModel->getAccountLedgerEntries($id, $startDate, $endDate);
            
            if (!empty($ledgerEntries)) {
                $this->setFlash('error', 'Cannot delete account with transactions.');
                $this->redirect('/finance/accounts/view/' . $id);
                return;
            }
            
            // Delete account
            $success = $this->accountModel->delete($id);
            
            if ($success) {
                $this->setFlash('success', 'Account deleted successfully!');
                $this->redirect('/finance/accounts');
            } else {
                $this->setFlash('error', 'Failed to delete account');
                $this->redirect('/finance/accounts/view/' . $id);
            }
        } else {
            $this->redirect('/finance/accounts');
        }
    }
}