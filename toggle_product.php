    <?php
    require_once('./classes/database.php');
    session_start();

    if (!isset($_SESSION['admin_ID'])) {
        header("Location: admin_L.php");
        exit();
    }

    if (isset($_GET['id'], $_GET['status'])) {
        $id = intval($_GET['id']);
        $status = intval($_GET['status']);

        $db = new Database();

        // If your Database class exposes the connection as $db->conn (common pattern)
        $stmt = $db->conn->prepare("UPDATE product SET is_active = ? WHERE product_id = ?");
        $stmt->execute([$status, $id]);

        header("Location: manage_products.php?updated=success");
        exit();
    } else {
        header("Location: manage_products.php");
        exit();
    }
    ?>
