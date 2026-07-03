<?php
class Transaction {
    private $conn;
    private $table_name = "transactions";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function borrow($user_id, $book_id) {
        // First check availability
        $check = $this->conn->prepare("SELECT available FROM books WHERE id = ?");
        $check->execute([$book_id]);
        $book = $check->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book['available'] <= 0) return false;

        $issue_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+2 months'));

        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO " . $this->table_name . " (user_id, book_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $book_id, $issue_date, $due_date]);

            $update = $this->conn->prepare("UPDATE books SET available = available - 1 WHERE id = ?");
            $update->execute([$book_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function returnBook($transaction_id) {
        $check = $this->conn->prepare("SELECT book_id, due_date FROM " . $this->table_name . " WHERE id = ? AND status = 'borrowed'");
        $check->execute([$transaction_id]);
        $trans = $check->fetch(PDO::FETCH_ASSOC);

        if (!$trans) return false;

        $return_date = date('Y-m-d');
        $fine = $this->calculateFine($trans['due_date'], $return_date);

        $this->conn->beginTransaction();
        try {
            $query = "UPDATE " . $this->table_name . " SET return_date = ?, fine = ?, status = 'returned' WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$return_date, $fine, $transaction_id]);

            $update = $this->conn->prepare("UPDATE books SET available = available + 1 WHERE id = ?");
            $update->execute([$trans['book_id']]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    private function calculateFine($due_date, $return_date) {
        $due = new DateTime($due_date);
        $ret = new DateTime($return_date);
        if ($ret <= $due) return 0.00;

        $diff = $ret->diff($due)->days;
        return $diff * 5.00; // $5 per day fine
    }

    public function getAll() {
        $query = "SELECT t.*, u.name as user_name, b.title as book_title 
                  FROM " . $this->table_name . " t 
                  JOIN users u ON t.user_id = u.id 
                  JOIN books b ON t.book_id = b.id 
                  ORDER BY t.issue_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getUserTransactions($user_id) {
        $query = "SELECT t.*, b.title as book_title 
                  FROM " . $this->table_name . " t 
                  JOIN books b ON t.book_id = b.id 
                  WHERE t.user_id = ? 
                  ORDER BY t.issue_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt;
    }
}
?>
