<?php
session_start();

require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: " . BASE_URL . "admin_L.php");
    exit();
}

// Get product ID
$id = $_GET['id'] ?? null;

// Validate product
if (!$id || !$product = $db->getProductById($id)) {
    die("Invalid product ID.");
}

$success = false;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName     = trim($_POST['product_name'] ?? '');
    $productPrice    = $_POST['product_price'] ?? 0;
    $productCategory = $_POST['product_category'] ?? '';
    $imagePath       = $product['image_path']; // existing image

    // ✅ Remove image
    if (!empty($_POST['remove_image'])) {
        if ($imagePath && file_exists("../" . $imagePath)) {
            unlink("../" . $imagePath);
        }
        $imagePath = null; // mark as removed
    }

    // Upload new image (replaces old one if exists)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            $newPath = 'uploads/' . uniqid('product_', true) . '.' . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], "../" . $newPath)) {
                // delete old file
                if ($imagePath && file_exists("../" . $imagePath)) {
                    unlink("../" . $imagePath);
                }
                $imagePath = $newPath;
            } else {
                $error = "Failed to upload new image.";
            }
        } else {
            $error = "Invalid image format. Allowed: jpg, jpeg, png, gif.";
        }
    }

    // ✅ Update DB
    if (!$error) {
        $result = $db->updateProduct($id, $productName, $productPrice, $productCategory, $imagePath);

        if ($result) {
            $success = true;
        } else {
            $error = "Failed to update product.";
        }
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

<form method="POST" enctype="multipart/form-data">
    
    <div class="mb-3">
      <label class="form-label">Product Name</label>
      <input type="text" name="product_name" class="form-control" 
             value="<?= htmlspecialchars($product['product_name']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Price</label>
      <input type="number" step="0.01" name="product_price" class="form-control" 
             value="<?= htmlspecialchars($product['product_price']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Category</label>
      <input type="text" name="product_category" class="form-control" 
             value="<?= htmlspecialchars($product['category_id']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Current Image</label><br>
      <?php if (!empty($product['image_path'])): ?>
        <img src="../<?= htmlspecialchars($product['image_path']) ?>"
             alt="Current Image"
             style="max-width: 150px; height:auto; border:1px solid #ccc;">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage">
          <label class="form-check-label" for="removeImage">Remove this image</label>
        </div>
      <?php else: ?>
        <p>No image uploaded.</p>
      <?php endif; ?>
    </div>

    

    <div class="mb-3">
      <label class="form-label">Change Image</label>
      <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
      <small class="text-muted">Leave blank if you don’t want to change.</small>
    </div>
    <!--End Image Section -->

    <button type="submit" class="btn btn-primary">Update Product</button>
    <a href="manage_products.php" class="btn btn-secondary">Back</a>
</form>


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
