<?php
session_start();
require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>

<?php include('templates/header.php'); ?>

<div class="wrapper">
    <?php include('templates/sidebar.php'); ?>

    <div class="main">
        <div class="admin-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
            <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title"><i class="fas fa-tags me-2"></i>Product Categories</h4>
                    <a href="<?= BASE_URL ?>admin/add_product_category.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Add Category
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="categoryTable" class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Status</th>
                                <th width="30%">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert -->
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

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    let categoryTable = $('#categoryTable').DataTable({
        ajax: {
            url: 'admin_functions.php',
            type: 'POST',
            data: { ref: 'get_product_categories' },
            dataSrc: function(json) {
                if (json.status === 'success') {
                    return json.categories.map(cat => {
                        let statusBadge = cat.is_active == 1
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-secondary">Inactive</span>';

                        let actions = `
                            <a href="edit_category.php?id=${cat.category_id}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_category.php?id=${cat.category_id}" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                            ${cat.is_active == 1
                                ? `<a href="toggle_category.php?id=${cat.category_id}&status=0" class="btn btn-secondary btn-sm"><i class="fas fa-ban"></i> Deactivate</a>`
                                : `<a href="toggle_category.php?id=${cat.category_id}&status=1" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Activate</a>`}
                        `;
                        return {
                            category: cat.category,
                            status: statusBadge,
                            actions: actions
                        };
                    });
                } else {
                    return [];
                }
            }
        },
        columns: [
            { data: 'category' },
            { data: 'status' },
            { data: 'actions' }
        ],
        responsive: true,
        paging: true,
        order: [[0, 'desc']]
    });

    setInterval(() => categoryTable.ajax.reload(null, false), 5000);
});
</script>
</body>
</html>
