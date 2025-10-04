<?php
session_start();
require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Category ID.");
}

$category_id = intval($_GET['id']);
$status = isset($_GET['status']) ? intval($_GET['status']) : 1;

// Update status
$stmt = $db->conn->prepare("UPDATE product SET is_active = ? WHERE product_id = ?");
$stmt->execute([$status, $category_id]);

header("Location: manage_products.php?updated=success");
exit();
