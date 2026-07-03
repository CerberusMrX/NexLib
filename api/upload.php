<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create uploads directory in assets if it doesn't exist
$target_dir = __DIR__ . "/../assets/uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if(isset($_FILES["file"])) {
    $file = $_FILES["file"];
    
    // Validate it's an image
    $check = getimagesize($file["tmp_name"]);
    if($check !== false) {
        $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($file["name"]));
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            echo json_encode(["url" => "assets/uploads/" . $filename]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to move uploaded file."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "File is not a valid image."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "No file dispatched."]);
}
?>
