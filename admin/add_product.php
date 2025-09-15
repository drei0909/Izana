<?php
session_start();
require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");

if (!isset($_SESSION['admin_ID'])) {
    header("Location: ".BASE_URL." admin_L.php");
    exit();
}

$db = new Database();

$error = "";
$success = "";

$productName = '';
$productPrice = '';
$productCategory = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['product_name'] ?? '';
    $productPrice = $_POST['price'] ?? 0;
    $productCategory = $_POST['category'] ?? '';
       $category_id = $_POST['category_id'] ?? '';


    // Validate required fields
    if (!$productName || !$productPrice || !$productCategory  || !$category_id) {
        $error = "Please fill in all fields.";
    } else {
        // Optional: Handle image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array(strtolower($ext), $allowed)) {
        $error = "Invalid image format. Allowed formats: jpg, jpeg, png, gif.";
    } else {
        $imagePath = '../uploads/' . uniqid('product_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        
            // Image successfully uploaded, proceed to store the product in the database
            $result = $db->addProduct($productName, $productPrice, $productCategory, $category_id, $imagePath);
            if ($result) {
                $success = "Product added successfully!";
            } else {
                $error = "Failed to add product.";
            }
        } else {
            $error = "Failed to upload image.";
        }
    }
}
    }
}

$stmt = $db->conn->prepare("SELECT * FROM product_categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="mb-4">Add New Product</h3>

  <!-- Show error or success messages using SweetAlert -->
  <?php if ($error): ?>
    <script>
    Swal.fire({
      icon: 'error',
      title: 'Oops!',
      text: '<?= addslashes($error) ?>',
    });
    </script>
  <?php elseif ($success): ?>
    <script>
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: '<?= addslashes($success) ?>',
    }).then(() => {
      window.location.href = 'manage_products.php'; // Redirect after success
    });
    </script>
  <?php endif; ?>

  <!-- Add Product Form -->
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Product Name</label>
      <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($productName) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Price (₱)</label>
      <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($productPrice) ?>" step="0.01" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Category</label>
     <select name="category_id" id="category_id" class="form-select">
      <option value="">Select</option>
          <?php foreach ($categories as $category): ?>
   <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category']) ?></option>
       <?php endforeach; ?>
     </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Product Image (optional)</label>
      <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif">
    </div>
    <button type="submit" class="btn btn-success">Add Product</button>
    <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

</body>
</html>
