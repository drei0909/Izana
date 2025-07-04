<?php
require_once('./classes/database.php');
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ Invalid request: No valid product ID provided.");
}

$product_id = intval($_GET['id']);
$db = new Database();

// Fetch product to get image path
$product = $db->getProductById($product_id);
if (!$product) {
    die("❌ Product not found.");
}

// Delete the product using the function (which now handles foreign key properly)
$deleted = $db->deleteProduct($product_id);

if ($deleted) {
    // Optionally delete image
    if (!empty($product['image'])) {
        $imagePath = 'uploads/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    header("Location: manage_products.php?deleted=success");
    exit();
} else {
    die("❌ Failed to delete the product. It may still be linked to an order.");
}
?>
