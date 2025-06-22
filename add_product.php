<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();

$id = $_GET['id'] ?? null;
if (!$id || !$product = $db->getProductById($id)) {
    die("Invalid product ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $best_seller = isset($_POST['best_seller']) ? 1 : 0;
    $image = $_FILES['image'];

    $result = $db->updateProduct($id, $name, $price, $category, $image, $best_seller);

    if ($result === true) {
        header("Location: manage_products.php?edited=success");
        exit();
    } else {
        $error = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="mb-4">Edit Product</h3>
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Product Name</label>
      <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Price</label>
      <input type="number" name="price" class="form-control" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Category</label>
      <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($product['category']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Replace Image (optional)</label>
      <input type="file" name="image" class="form-control" accept="image/*">
      <small class="text-muted">Current image: <?= htmlspecialchars($product['image']) ?></small>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="best_seller" id="best_seller" <?= $product['best_seller'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="best_seller">Best Seller</label>
    </div>
    <button type="submit" class="btn btn-primary">Update Product</button>
    <a href="manage_products.php" class="btn btn-secondary">Back</a>
  </form>
</div>
</body>
</html>
