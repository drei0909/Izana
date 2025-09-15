<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");


if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$active_page = 'manage_products';

$db = new Database();

$stmt = $db->conn->prepare("SELECT * FROM product");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>

<?php include ('templates/header.php'); ?>

<div class="wrapper">
 
<?php include ('templates/sidebar.php'); ?>

    <!-- Main Content -->
   <div class="main">
    <!-- Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

        <!-- Content -->
        <div class="content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title"><i class="fas fa-mug-hot me-2"></i>Manage Products</h4>
                    <a href="add_product.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add Product</a>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table id="productTable" class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price (₱)</th>
                                <th>Category</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $product): ?>
                                <tr class="<?= $product['is_active'] ? '' : 'inactive-row' ?>">
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
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete_product.php?id=<?= $product['product_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                                        <?php if ($product['is_active']): ?>
                                            <a href="toggle_product.php?id=<?= $product['product_id'] ?>&status=0" class="btn btn-secondary btn-sm">Deactivate</a>
                                        <?php else: ?>
                                            <a href="toggle_product.php?id=<?= $product['product_id'] ?>&status=1" class="btn btn-success btn-sm">Activate</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-box-open fa-2x mb-2"></i><br>No products found.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<?php if (isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Product Status Updated!',
    text: 'Product activation status has been changed.',
    confirmButtonColor: '#28a745'
});

</script>
<?php endif; ?>

<script>
    $(document).ready(function(){
        $('#productTable').DataTable();
    });
</script>

</body>
</html>