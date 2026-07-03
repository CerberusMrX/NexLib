<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BookController.php';
require_once __DIR__ . '/controllers/TransactionController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

$database = new Database();
$db = $database->getConnection();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$requestMethod = $_SERVER["REQUEST_METHOD"];
$data = json_decode(file_get_contents("php://input"));

// Extract resource name more robustly (handle subfolders and /api/index.php/resource)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = array_values(array_filter(explode('/', $uri)));
$resource = end($parts) ?: 'home';

// If the last part is index.php, it's the home or we need the part before it
if ($resource === 'index.php') {
    $resource = 'home';
}

switch ($resource) {
    case 'register':
        if ($requestMethod == 'POST') (new AuthController($db))->register($data);
        break;
    case 'login':
        if ($requestMethod == 'POST') (new AuthController($db))->login($data);
        break;
    case 'books':
        $controller = new BookController($db);
        if ($requestMethod == 'GET') {
            $controller->getAll();
        } elseif ($requestMethod == 'POST') {
            AuthMiddleware::isAdmin();
            $controller->create($data);
        } elseif ($requestMethod == 'PUT') {
            AuthMiddleware::isAdmin();
            $controller->update($data);
        } elseif ($requestMethod == 'DELETE') {
            AuthMiddleware::isAdmin();
            $id = $_GET['id'] ?? null;
            $controller->delete($id);
        }
        break;
    case 'borrow':
        if ($requestMethod == 'POST') (new TransactionController($db))->borrow($data);
        break;
    case 'return':
        if ($requestMethod == 'POST') (new TransactionController($db))->returnBook($data);
        break;
    case 'transactions':
        if ($requestMethod == 'GET') (new TransactionController($db))->list();
        break;
    case 'users':
        if ($requestMethod == 'GET') {
            AuthMiddleware::isAdmin();
            $stmt = (new User($db))->getAll();
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } elseif ($requestMethod == 'DELETE') {
            AuthMiddleware::isAdmin();
            $id = $_GET['id'] ?? null;
            if (!$id) {
                Response::error("User ID is required", 400);
            }
            $user = new User($db);
            if ($user->delete($id)) {
                Response::json(["message" => "User deleted successfully."]);
            } else {
                Response::error("Failed to delete user.", 503);
            }
        } elseif ($requestMethod == 'PUT') {
            $user_data = AuthMiddleware::authenticate();
            $user = new User($db);
            $user->id = $user_data['id'];
            $user->name = $data->name;
            $user->email = $data->email;
            if (!empty($data->password)) {
                $user->password = $data->password;
                $user->old_password = $data->old_password ?? '';
            }
            try {
                if ($user->updateProfile()) {
                    Response::json(["message" => "Profile updated successfully."]);
                } else {
                    Response::error("Failed to update profile.", 503);
                }
            } catch (Exception $e) {
                if ($e->getCode() == 23000) {
                    Response::error("Email already exists.", 400);
                } else if ($e->getMessage() == 'Incorrect current password.') {
                    Response::error("Incorrect current password. Action denied.", 401);
                } else {
                    Response::error("Failed to update profile.", 503);
                }
            }
        }
        break;
    default:
        Response::error("Endpoint not found: " . $resource, 404);
        break;
}
?>
