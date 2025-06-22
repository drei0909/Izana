<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();
$products = $db->getAllProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Products | Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .container-bg {
      background: rgba(0, 0, 0, 0.7);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
      color: #fff;
    }
    table {
      color: #fff;
    }
    .table-dark th {
      background-color: #343a40 !important;
    }
    .btn-back {
      position: absolute;
      top: 20px;
      left: 20px;
    }
  </style>
</head>
<body>
  <a href="admin.php" class="btn btn-warning btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  <div class="container container-bg mt-5">
    <h2 class="text-center mb-4">Manage Products</h2>

    <div class="d-flex justify-content-between mb-3">
      <div>
       
      </div>

      <div>
        <a href="add_product.php" class="btn btn-success">Add New Product</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-dark table-hover table-bordered align-middle">
        <thead>
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
                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="Product Image" width="60" class="rounded">
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

  <?php if (isset($_GET['edited']) && $_GET['edited'] === 'success'): ?>
  <script>
  Swal.fire({
    icon: 'success',
    title: 'Product Updated!',
    text: 'Changes saved successfully.',
    confirmButtonColor: '#28a745'
  });
  </script>
  <?php endif; ?>

  <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
