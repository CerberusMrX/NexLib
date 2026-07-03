<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, email=:email, password=:password, role=:role";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        $this->role = $this->role ?? 'user';

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT id, name, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    public function getAll() {
        $query = "SELECT id, name, email, role, created_at FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(1, $id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public $old_password;

    public function updateProfile() {
        if (!empty($this->password)) {
            // Verify old password
            $stmt = $this->conn->prepare("SELECT password FROM " . $this->table_name . " WHERE id = ?");
            $stmt->execute([$this->id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($this->old_password, $row['password'])) {
                throw new Exception("Incorrect current password.");
            }

            $query = "UPDATE " . $this->table_name . " SET name=:name, email=:email, password=:password WHERE id=:id";
            $stmt = $this->conn->prepare($query);
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $this->password);
        } else {
            $query = "UPDATE " . $this->table_name . " SET name=:name, email=:email WHERE id=:id";
            $stmt = $this->conn->prepare($query);
        }

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
}
?>
