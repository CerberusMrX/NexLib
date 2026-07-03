<?php
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class TransactionController {
    private $db;
    private $transaction;

    public function __construct($db) {
        $this->db = $db;
        $this->transaction = new Transaction($db);
    }

    public function borrow($data) {
        $user = AuthMiddleware::authenticate();
        if (empty($data->book_id)) {
            Response::error("Book ID is required", 400);
        }

        if ($this->transaction->borrow($user['id'], $data->book_id)) {
            Response::success("Book borrowed successfully");
        } else {
            Response::error("Unable to borrow book. It might be out of stock.", 400);
        }
    }

    public function returnBook($data) {
        AuthMiddleware::authenticate();
        if (empty($data->transaction_id)) {
            Response::error("Transaction ID is required", 400);
        }

        if ($this->transaction->returnBook($data->transaction_id)) {
            Response::success("Book returned successfully");
        } else {
            Response::error("Unable to return book", 400);
        }
    }

    public function list() {
        $user = AuthMiddleware::authenticate();
        if ($user['role'] === 'admin') {
            $stmt = $this->transaction->getAll();
        } else {
            $stmt = $this->transaction->getUserTransactions($user['id']);
        }
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $today = date('Y-m-d');
        foreach ($transactions as &$t) {
            if ($t['status'] === 'borrowed') {
                $due_date = new DateTime($t['due_date']);
                $curr_date = new DateTime($today);
                if ($curr_date > $due_date) {
                    $diff = $curr_date->diff($due_date)->days;
                    $t['fine'] = number_format($diff * 5.00, 2);
                } else {
                    $t['fine'] = 0;
                }
            }
        }
        Response::json($transactions);
    }
}
?>
