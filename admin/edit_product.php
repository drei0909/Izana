<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: ".BASE_URL."admin_L.php");
    exit();
}



// Get the product ID from the URL
$id = $_GET['id'] ?? null;

// Check if the product ID is valid and the product exists
if (!$id || !$product = $db->getProductById($id)) {
    // If the product is not found, display an error and exit
    die("Invalid product ID.");
}

$success = false;
$error = null;

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];
    $productCategory = $_POST['product_category'];

    // Update the product in the database
    $result = $db->updateProduct($id, $productName, $productPrice, $productCategory);

    if ($result) {
        $success = true;
    } else {
        $error = "Failed to update product.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="mb-4">Edit Product</h3>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Product Name</label>
      <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Price</label>
      <input type="number" step="0.01" name="product_price" class="form-control" value="<?= htmlspecialchars($product['product_price']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Category</label>
      <input type="text" name="product_category" class="form-control" value="<?= htmlspecialchars($product['category_id']) ?>" required>
    </div>
    
    <button type="submit" class="btn btn-primary">Update Product</button>
    <a href="manage_products.php" class="btn btn-secondary">Back</a>
  </form>
</div>

<?php if ($success): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Updated!',
  text: 'Product successfully updated.',
  confirmButtonColor: '#3085d6'
}).then(() => {
  window.location.href = "edit_product.php?id=<?= $id ?>"; // Redirect to the same page after successful update
});
</script>
<?php elseif ($error): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Update Failed',
  text: <?= json_encode($error) ?>,
  confirmButtonColor: '#d33'
});
</script>
<?php endif; ?>
</body>
</html>
