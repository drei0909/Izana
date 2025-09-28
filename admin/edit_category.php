<?php
session_start();
require_once('../classes/database.php');
require_once(__DIR__ . "/../classes/config.php");

$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin_L.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Category ID.");
}

$category_id = intval($_GET['id']);

// Fetch category
$stmt = $db->conn->prepare("SELECT * FROM product_categories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("Category not found.");
}

// Update on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category'] ?? '');

    if ($name !== '') {
        $update = $db->conn->prepare("UPDATE product_categories SET category = ? WHERE category_id = ?");
        $update->execute([$name, $category_id]);

        header("Location: product-categories.php?updated=success");
        exit();
    } else {
        $error = "Category name cannot be empty.";
    }
}
?>

<?php include('templates/header.php'); ?>
<div class="container mt-5">
    <h3>Edit Category</h3>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" name="category" value="<?= htmlspecialchars($category['category']) ?>" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="product-categories.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
