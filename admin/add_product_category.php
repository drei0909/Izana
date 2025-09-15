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



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
   
    // Validate required fields
    if (!$category) {
        $error = "Please fill in all fields.";
    } else {
        
      
            $result = $db->addCategory($category);
            if ($result) {
                $success = "Product Category added successfully!";
            } else {
                $error = "Failed to add  product  category.";
            }
        } 
    }


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
  <h3 class="mb-4">Add New Product Category</h3>

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
      window.location.href = 'product-categories.php'; // Redirect after success
    });
    </script>
  <?php endif; ?>

  <!-- Add Product Form -->
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Category Name</label>
      <input type="text" name="category" class="form-control">
    </div>
    
    <button type="submit" class="btn btn-success">Add Category</button>
    <a href="<?php echo BASE_URL?>admin/product-categories.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>

</body>
</html>
