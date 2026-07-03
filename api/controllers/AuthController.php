<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function register($data) {
        if (empty($data->name) || empty($data->email) || empty($data->password)) {
            Response::error("Missing required fields", 400);
        }

        $this->user->name = $data->name;
        $this->user->email = $data->email;
        $this->user->password = $data->password;
        // Strictly enforce user role, ignore any payload role for best security principles
        $this->user->role = 'user';

        if ($this->user->emailExists()) {
            Response::error("Email already exists", 400);
        }

        if ($this->user->register()) {
            Response::success("User registered successfully", [], 201);
        } else {
            Response::error("Unable to register user", 500);
        }
    }

    public function login($data) {
        if (empty($data->email) || empty($data->password)) {
            Response::error("Email and password are required", 400);
        }

        $this->user->email = $data->email;
        if ($this->user->emailExists() && password_verify($data->password, $this->user->password)) {
            $token = JWT::encode([
                "id" => $this->user->id,
                "name" => $this->user->name,
                "email" => $this->user->email,
                "role" => $this->user->role
            ]);

            Response::success("Login successful", [
                "token" => $token,
                "user" => [
                    "id" => $this->user->id,
                    "name" => $this->user->name,
                    "email" => $this->user->email,
                    "role" => $this->user->role
                ]
            ]);
        } else {
            Response::error("Invalid email or password", 401);
        }
    }
}
?>
