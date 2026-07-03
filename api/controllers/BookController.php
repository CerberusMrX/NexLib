<?php
require_once __DIR__ . '/../models/Book.php';
require_once __DIR__ . '/../utils/Response.php';

class BookController {
    private $db;
    private $book;

    public function __construct($db) {
        $this->db = $db;
        $this->book = new Book($db);
    }

    public function getAll() {
        $stmt = $this->book->getAll();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json($books);
    }

    public function create($data) {
        if (empty($data->title) || empty($data->author)) {
            Response::error("Title and Author are required", 400);
        }

        $this->book->title = $data->title;
        $this->book->author = $data->author;
        $this->book->category_id = $data->category_id ?? null;
        $this->book->isbn = $data->isbn ?? null;
        $this->book->quantity = $data->quantity ?? 1;
        $this->book->image_url = $data->image_url ?? null;

        if ($this->book->create()) {
            Response::success("Book created successfully", [], 201);
        } else {
            Response::error("Unable to create book", 500);
        }
    }

    public function update($data) {
        if (empty($data->id) || empty($data->title)) {
            Response::error("ID and Title are required", 400);
        }

        $this->book->id = $data->id;
        $this->book->title = $data->title;
        $this->book->author = $data->author;
        $this->book->category_id = $data->category_id ?? null;
        $this->book->isbn = $data->isbn ?? null;
        $this->book->quantity = $data->quantity ?? 1;
        $this->book->image_url = $data->image_url ?? null;

        if ($this->book->update()) {
            Response::success("Book updated successfully");
        } else {
            Response::error("Unable to update book", 500);
        }
    }

    public function delete($id) {
        if (!$id) {
            Response::error("ID is required", 400);
        }

        $this->book->id = $id;
        if ($this->book->delete()) {
            Response::success("Book deleted successfully");
        } else {
            Response::error("Unable to delete book", 500);
        }
    }
}
?>
