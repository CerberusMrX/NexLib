<?php
class Response {
    public static function json($data, $status = 200) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        http_response_code($status);
        echo json_encode($data);
        exit();
    }

    public static function error($message, $status = 400) {
        self::json(["error" => $message], $status);
    }

    public static function success($message, $data = [], $status = 200) {
        self::json(array_merge(["message" => $message], $data), $status);
    }
}
?>
