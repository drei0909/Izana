<?php
require_once('./classes/database.php');
session_start();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

$db = new Database();

// ✅ Pagination setup
$limit = 20; // products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalProducts = $db->conn->query("SELECT COUNT(*) FROM product")->fetchColumn();
$totalPages = ceil($totalProducts / $limit);
if ($totalPages < 1) $totalPages = 1;

// ✅ Fetch paginated products
$stmt = $db->conn->prepare("SELECT * FROM product LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: #e0e0e0;
            color: #2b2b2b;
            margin: 0;
            height: 100vh;
            overflow: hidden;
        }
        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background: #1c1c1c;
            color: #fff;
            box-shadow: 3px 0 12px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }
        .main {
            margin-left: 250px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        .content {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .sidebar .nav-link {
            color: #bdbdbd;
            font-weight: 500;
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background-color: #6f4e37;
            color: #fff;
            transform: translateX(6px);
        }
        .admin-header {
            background: #f4f4f4;
            padding: 15px 25px;
            border-bottom: 1px solid #d6d6d6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .section-title {
            border-left: 6px solid #6f4e37;
            padding-left: 12px;
            margin: 30px 0 20px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card {
            border: none;
            border-radius: 15px;
            background: #f4f4f4;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table thead {
            background: #6f4e37;
            color: #fff;
        }
        .table tbody tr:hover {
            background: #f8f1ed;
        }
        .pagination .page-item .page-link {
            border-radius: 50%;
            margin: 0 3px;
            color: #1c1c1c;
            background-color: #f4f4f4;
            border: none;
            font-weight: bold;
        }
        .pagination .page-item.active .page-link {
            background-color: #6f4e37;
            color: #fff;
        }
        .pagination .page-item .page-link:hover {
            background-color: #333;
            color: #fff;
        }
        .inactive-row {
            opacity: 0.6;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <h4 class="text-white mb-4 text-center"><i class="fas fa-mug-hot me-2"></i>Izana Admin</h4>
        <ul class="nav nav-pills flex-column">
            <li><a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li><a href="view_customers.php" class="nav-link"><i class="fas fa-users me-2"></i>View Customers</a></li>
            <li><a href="view_orders.php" class="nav-link"><i class="fas fa-receipt me-2"></i>View Orders</a></li>
            <li><a href="manage_products.php" class="nav-link active"><i class="fas fa-boxes me-2"></i>Manage Products</a></li>
            <li><a href="cashier.php" class="nav-link"><i class="fas fa-cash-register me-2"></i>Online Cashier</a></li>
            <li><a href="manage_cashier.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>POS</a></li>
            <li><a href="sales_report.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Sales Report</a></li>
             <li><a href="salesHistory.php" class="nav-link"><i class="fas fa-history me-2"></i>Sales History</a></li>
            <li><a href="edit_profile.php" class="nav-link"><i class="fas fa-user-edit me-2"></i>Edit Profile</a></li>
            <li><a href="Logout_A.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>

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
                    <table class="table table-bordered table-hover align-middle">
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

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center pagination-lg">
                        <!-- Prev -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Prev</a>
                        </li>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);

                        if ($end - $start < 4) {
                            if ($start == 1) {
                                $end = min(5, $totalPages);
                            } elseif ($end == $totalPages) {
                                $start = max(1, $totalPages - 4);
                            }
                        }

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo "<li class='page-item'><a class='page-link' href='?page=$totalPages'>$totalPages</a></li>";
                        }
                        ?>

                        <!-- Next -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
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
</body>
</html>
