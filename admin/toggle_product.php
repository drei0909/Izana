    <?php
    session_start();
    require_once('../classes/database.php');
    require_once (__DIR__. "/../classes/config.php");

    if (!isset($_SESSION['admin_ID'])) {
        header("Location: ".BASE_URL."admin_L.php");
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
