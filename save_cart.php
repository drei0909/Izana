<?php
session_start();
$data = json_decode(file_get_contents("php://input"), true);

if ($data && is_array($data)) {
    $_SESSION['cart'] = $data;
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'fail', 'message' => 'Invalid cart data']);
}
