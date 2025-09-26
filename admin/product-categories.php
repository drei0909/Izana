<?php
session_start();

    require_once('../classes/database.php');
    require_once (__DIR__. "/../classes/config.php");

    $db = new Database();

    if (!isset($_SESSION['admin_ID'])) {
        header("Location: admin_L.php");
        exit();
}

$totalProducts = $db->conn->query("SELECT COUNT(*) FROM product")->fetchColumn();


// Fetch product categories
$stmt = $db->conn->prepare("SELECT * FROM product_categories WHERE deleted = 0");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>

<?php include ('templates/header.php'); ?>

<div class="wrapper">
 
<?php include ('templates/sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main">
        <div class="admin-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
        <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="section-title"><i class="fas fa-mug-hot me-2"></i>Product Categories</h4>
        <a href="<?php echo BASE_URL?>admin/add_product_category.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add category</a>
    </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table id="productTable" class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                <th>Category</th>
                                <th>Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                                
                            <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>

                                    <tr class="<?= $product['is_active'] ? '' : 'inactive-row' ?>">
                                        
                                    <td><?= htmlspecialchars($category['category']) ?></td>                      
                                <td>
                                    <a href="edit_product.php?id=<?= $category['category_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="delete_product.php?id=<?= $category['category_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                                    <?php if ($category['deleted']): ?>
                                    <a href=toggle_product.php?id=<?= $category['id'] ?>&status=0" class="btn btn-secondary btn-sm">Deactivate</a>
                                    <?php else: ?>
                                    <a href="toggle_product.php?id=<?= $category['category_id'] ?>&status=1" class="btn btn-success btn-sm">Activate</a>
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

<!-- jQuery (needed for DataTables only) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<?php if (isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>
<?php endif; ?>

<script>
    $(document).ready(function(){
        $('#productTable').DataTable();
    });
</script>

</body>
</html>