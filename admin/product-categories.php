<?php
session_start();

require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$totalProducts = $db->conn->query("SELECT COUNT(*) FROM product")->fetchColumn();

// ✅ Fetch ALL categories
$stmt = $db->conn->prepare("SELECT * FROM product_categories ORDER BY created_at DESC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>

<?php include('templates/header.php'); ?>

<div class="wrapper">
    <?php include('templates/sidebar.php'); ?>

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
                    <h4 class="section-title"><i class="fas fa-tags me-2"></i>Product Categories</h4>
                    <a href="<?= BASE_URL ?>admin/add_product_category.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Add Category
                    </a>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table id="categoryTable" class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Status</th>
                                <th width="30%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr class="<?= $category['is_active'] ? '' : 'table-danger' ?>">
                                    <td><?= htmlspecialchars($category['category']) ?></td>
                                    <td>
                                        <?php if ($category['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_category.php?id=<?= $category['category_id'] ?>" 
                                           class="btn btn-warning btn-sm">
                                           <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_category.php?id=<?= $category['category_id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Delete this category?')">
                                           <i class="fas fa-trash"></i> Delete
                                        </a>
                                        <?php if ($category['is_active']): ?>
                                            <a href="toggle_category.php?id=<?= $category['category_id'] ?>&status=0" 
                                               class="btn btn-secondary btn-sm">
                                               <i class="fas fa-ban"></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="toggle_category.php?id=<?= $category['category_id'] ?>&status=1" 
                                               class="btn btn-success btn-sm">
                                               <i class="fas fa-check"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-box-open fa-2x mb-2"></i><br>No categories found.
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

<!-- SweetAlert for status update -->
<?php if (isset($_GET['updated']) && $_GET['updated'] === 'success'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Category Status Updated!',
    text: 'Category activation status has been changed.',
    confirmButtonColor: '#28a745'
});
</script>
<?php endif; ?>

<!-- start -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    $('#categoryTable').DataTable();
});
</script>
<!-- end -->
</body>
</html>
