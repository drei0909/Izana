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

// Check if category exists
$stmt = $db->conn->prepare("SELECT * FROM product_categories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("Category not found.");
}

// Delete category
$delete = $db->conn->prepare("DELETE FROM product_categories WHERE category_id = ?");
$delete->execute([$category_id]);

header("Location: product-categories.php?updated=success");
exit();
