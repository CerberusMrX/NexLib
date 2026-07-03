<?php
class Book {
    private $conn;
    private $table_name = "books";

    public $id;
    public $title;
    public $author;
    public $category_id;
    public $isbn;
    public $quantity;
    public $available;
    public $image_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT b.id, b.title, b.author, b.isbn, b.quantity, b.available, b.image_url, c.name as category_name 
                  FROM " . $this->table_name . " b 
                  LEFT JOIN categories c ON b.category_id = c.id 
                  ORDER BY b.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    private function fetchCoverImage() {
        $query = !empty($this->isbn) ? "isbn:" . urlencode($this->isbn) : "intitle:" . urlencode($this->title);
        $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&maxResults=1";
        
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['items'][0]['volumeInfo']['imageLinks']['thumbnail'])) {
                return str_replace('http:', 'https:', $data['items'][0]['volumeInfo']['imageLinks']['thumbnail']);
            }
        }
        return 'https://via.placeholder.com/150x225/2a2a35/ffffff?text=No+Cover';
    }

    public function create() {
        if (empty($this->image_url)) {
            $this->image_url = $this->fetchCoverImage();
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET title=:title, author=:author, category_id=:category_id, isbn=:isbn, quantity=:quantity, available=:available, image_url=:image_url";
        $stmt = $this->conn->prepare($query);

        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->author = htmlspecialchars(strip_tags($this->author));
        $this->isbn = htmlspecialchars(strip_tags($this->isbn));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":author", $this->author);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":available", $this->quantity); // Initial available is same as quantity
        $stmt->bindParam(":image_url", $this->image_url);

        return $stmt->execute();
    }

    public function update() {
        // Fetch old quantity and availability to calculate difference
        $stmt_old = $this->conn->prepare("SELECT quantity, available FROM " . $this->table_name . " WHERE id = ?");
        $stmt_old->execute([$this->id]);
        $old_data = $stmt_old->fetch(PDO::FETCH_ASSOC);
        
        if ($old_data) {
            $diff = $this->quantity - $old_data['quantity'];
            $new_available = $old_data['available'] + $diff;
            if ($new_available < 0) { $new_available = 0; } // Exception handling for negative availability
        } else {
            $new_available = $this->quantity;
        }

        if (empty($this->image_url)) {
            $this->image_url = $this->fetchCoverImage();
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET title=:title, author=:author, category_id=:category_id, isbn=:isbn, quantity=:quantity, available=:available, image_url=:image_url
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->author = htmlspecialchars(strip_tags($this->author));
        $this->isbn = htmlspecialchars(strip_tags($this->isbn));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":author", $this->author);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":isbn", $this->isbn);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":available", $new_available);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    public function search($keyword) {
        $query = "SELECT b.id, b.title, b.author, b.isbn, b.available, b.image_url, c.name as category_name 
                  FROM " . $this->table_name . " b 
                  LEFT JOIN categories c ON b.category_id = c.id 
                  WHERE b.title LIKE ? OR b.author LIKE ? OR c.name LIKE ? OR b.isbn LIKE ?";
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->execute([$keyword, $keyword, $keyword, $keyword]);
        return $stmt;
    }
}
?>
