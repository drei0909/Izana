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

$defaultCategoryId = 1; // ID of "Uncategorized" category

// Move products to default category
$stmt = $db->conn->prepare("UPDATE product SET category_id = ? WHERE category_id = ?");
$stmt->execute([$defaultCategoryId, $category_id]);

// Now delete category
$delete = $db->conn->prepare("DELETE FROM product_categories WHERE category_id = ?");
$delete->execute([$category_id]);


if (!$category_id) {
    die("Category not found.");
}

// Delete category
$delete = $db->conn->prepare("DELETE FROM product_categories WHERE category_id = ?");
$delete->execute([$category_id]);

header("Location: product-categories.php?updated=success");
exit();
