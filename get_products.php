<?php
require_once('./classes/database.php'); // or correct path

$db = new Database();
$products = $db->getAllProducts();

header('Content-Type: application/json');
echo json_encode($products);
