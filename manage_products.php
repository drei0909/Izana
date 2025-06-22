<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$products = $db->getAllProducts(); // Define this function in your database class
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Products | Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="text-center mb-4">Manage Products</h2>

  <div class="mb-3">
    <a href="admin.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    <!-- Optional: Add button -->
    <a href="add_product.php" class="btn btn-success float-end">Add New Product</a>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Price (₱)</th>
          <th>Category</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($products)): ?>
          <?php foreach ($products as $product): ?>
            <tr>
              <td><?= htmlspecialchars($product['product_id']) ?></td>
              <td><?= htmlspecialchars($product['product_name']) ?></td>
              <td>₱<?= number_format($product['product_price'], 2) ?></td>
              <td><?= htmlspecialchars($product['product_category']) ?></td>
              <td>
                <?php if (!empty($product['image_path'])): ?>
                  <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" width="60">
                <?php else: ?>
                  <span class="text-muted">No image</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="delete_product.php?id=<?= $product['product_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center">No products found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
