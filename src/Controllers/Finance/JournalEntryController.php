<?php

namespace App\Controllers\Finance;

use App\Controllers\BaseController;
use App\Models\Finance\Account;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalItem;

class JournalEntryController extends BaseController {
    private $journalEntryModel;
    private $journalItemModel;
    private $accountModel;
    
    public function __construct() {
        $this->journalEntryModel = new JournalEntry();
        $this->journalItemModel = new JournalItem();
        $this->accountModel = new Account();
    }
    
    public function index() {
        $this->requireAuth();
        
        $queryData = $this->getQueryData();
        $page = isset($queryData['page']) ? (int)$queryData['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Get date range from query or use current month
        $startDate = $queryData['start_date'] ?? date('Y-m-01'); // First day of current month
        $endDate = $queryData['end_date'] ?? date('Y-m-t'); // Last day of current month
        
        // Get status filter
        $status = $queryData['status'] ?? null;
        
        // Get journal entries
        $entries = $this->journalEntryModel->getJournalEntriesByDate($startDate, $endDate, $status);
        
        // Pagination
        $totalEntries = count($entries);
        $entries = array_slice($entries, $offset, $limit);
        $totalPages = ceil($totalEntries / $limit);
        
        $this->render('finance/journal/index', [
            'title' => 'Journal Entries',
            'entries' => $entries,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        
        // Get active accounts for selection
        $accounts = $this->accountModel->getAll(null, 0, 'code ASC', 'is_active = 1');
        
        $errors = [];
        $entry = [
            'entry_date' => date('Y-m-d'),
            'reference' => '',
            'description' => '',
            'items' => [
                ['account_id' => '', 'description' => '', 'debit_amount' => '', 'credit_amount' => '']
            ]
        ];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['entry_date', 'description'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate items
            $totalDebit = 0;
            $totalCredit = 0;
            $itemErrors = [];
            
            if (empty($postData['items']) || !is_array($postData['items'])) {
                $errors['items'] = 'At least one journal item is required';
            } else {
                foreach ($postData['items'] as $index => $item) {
                    if (empty($item['account_id'])) {
                        $itemErrors["item_{$index}_account"] = 'Account is required';
                    }
                    
                    if (empty($item['debit_amount']) && empty($item['credit_amount'])) {
                        $itemErrors["item_{$index}_amount"] = 'Debit or credit amount is required';
                    }
                    
                    if (!empty($item['debit_amount']) && !empty($item['credit_amount'])) {
                        $itemErrors["item_{$index}_amount"] = 'Only one of debit or credit amount should be entered';
                    }
                    
                    if (!empty($item['debit_amount']) && (!is_numeric($item['debit_amount']) || $item['debit_amount'] < 0)) {
                        $itemErrors["item_{$index}_debit"] = 'Debit amount must be a positive number';
                    }
                    
                    if (!empty($item['credit_amount']) && (!is_numeric($item['credit_amount']) || $item['credit_amount'] < 0)) {
                        $itemErrors["item_{$index}_credit"] = 'Credit amount must be a positive number';
                    }
                    
                    $totalDebit += !empty($item['debit_amount']) ? (float)$item['debit_amount'] : 0;
                    $totalCredit += !empty($item['credit_amount']) ? (float)$item['credit_amount'] : 0;
                }
                
                // Check if debits equal credits
                if (abs($totalDebit - $totalCredit) > 0.01) {
                    $errors['balance'] = 'Journal entry must be balanced (total debits must equal total credits)';
                }
            }
            
            $errors = array_merge($errors, $itemErrors);
            
            if (empty($errors)) {
                // Prepare journal entry data
                $entryData = [
                    'entry_date' => $postData['entry_date'],
                    'reference' => $postData['reference'] ?? '',
                    'description' => $postData['description'],
                    'status' => JournalEntry::STATUS_DRAFT,
                    'created_by' => $this->getUserId(),
                    'items' => []
                ];
                
                // Prepare journal items
                foreach ($postData['items'] as $item) {
                    if (empty($item['account_id'])) {
                        continue;
                    }
                    
                    $entryData['items'][] = [
                        'account_id' => $item['account_id'],
                        'description' => $item['description'] ?? '',
                        'debit_amount' => !empty($item['debit_amount']) ? (float)$item['debit_amount'] : 0,
                        'credit_amount' => !empty($item['credit_amount']) ? (float)$item['credit_amount'] : 0
                    ];
                }
                
                // Create journal entry
                $entryId = $this->journalEntryModel->create($entryData);
                
                if ($entryId) {
                    $this->setFlash('success', 'Journal entry created successfully!');
                    $this->redirect('/finance/journal/view/' . $entryId);
                } else {
                    $errors['create'] = 'Failed to create journal entry. Please try again.';
                }
            }
            
            // Repopulate form data
            $entry = $postData;
        }
        
        $this->render('finance/journal/create', [
            'title' => 'Create Journal Entry',
            'entry' => $entry,
            'accounts' => $accounts,
            'errors' => $errors
        ]);
    }
    
    public function view($id) {
        $this->requireAuth();
        
        $entry = $this->journalEntryModel->getJournalEntryWithItems($id);
        
        if (!$entry) {
            $this->setFlash('error', 'Journal entry not found');
            $this->redirect('/finance/journal');
        }
        
        $this->render('finance/journal/view', [
            'title' => 'Journal Entry Details',
            'entry' => $entry
        ]);
    }
    
    public function post($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $entry = $this->journalEntryModel->findById($id);
            
            if (!$entry) {
                $this->setFlash('error', 'Journal entry not found');
                $this->redirect('/finance/journal');
            }
            
            if ($entry['status'] !== JournalEntry::STATUS_DRAFT) {
                $this->setFlash('error', 'Journal entry is not in draft status');
                $this->redirect('/finance/journal/view/' . $id);
                return;
            }
            
            try {
                $success = $this->journalEntryModel->postJournalEntry($id);
                
                if ($success) {
                    $this->setFlash('success', 'Journal entry posted successfully!');
                } else {
                    $this->setFlash('error', 'Failed to post journal entry');
                }
            } catch (\Exception $e) {
                $this->setFlash('error', 'Error: ' . $e->getMessage());
            }
            
            $this->redirect('/finance/journal/view/' . $id);
        } else {
            $this->redirect('/finance/journal');
        }
    }
    
    public function approve($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $entry = $this->journalEntryModel->findById($id);
            
            if (!$entry) {
                $this->setFlash('error', 'Journal entry not found');
                $this->redirect('/finance/journal');
            }
            
            if ($entry['status'] !== JournalEntry::STATUS_POSTED) {
                $this->setFlash('error', 'Journal entry must be posted before approval');
                $this->redirect('/finance/journal/view/' . $id);
                return;
            }
            
            $success = $this->journalEntryModel->approveJournalEntry($id, $this->getUserId());
            
            if ($success) {
                $this->setFlash('success', 'Journal entry approved successfully!');
            } else {
                $this->setFlash('error', 'Failed to approve journal entry');
            }
            
            $this->redirect('/finance/journal/view/' . $id);
        } else {
            $this->redirect('/finance/journal');
        }
    }
    
    public function void($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $entry = $this->journalEntryModel->findById($id);
            
            if (!$entry) {
                $this->setFlash('error', 'Journal entry not found');
                $this->redirect('/finance/journal');
            }
            
            if ($entry['status'] !== JournalEntry::STATUS_POSTED && $entry['status'] !== JournalEntry::STATUS_APPROVED) {
                $this->setFlash('error', 'Journal entry cannot be voided');
                $this->redirect('/finance/journal/view/' . $id);
                return;
            }
            
            try {
                $success = $this->journalEntryModel->voidJournalEntry($id);
                
                if ($success) {
                    $this->setFlash('success', 'Journal entry voided successfully!');
                } else {
                    $this->setFlash('error', 'Failed to void journal entry');
                }
            } catch (\Exception $e) {
                $this->setFlash('error', 'Error: ' . $e->getMessage());
            }
            
            $this->redirect('/finance/journal/view/' . $id);
        } else {
            $this->redirect('/finance/journal');
        }
    }
}