<?php
session_start();
require_once('./classes/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $customerID = $_POST['customer_id'];
    $newUsername = trim($_POST['new_username']);
    $newEmail = trim($_POST['new_email']);
    $newPassword = trim($_POST['new_password']);

    // If all fields are empty
    if (empty($newUsername) && empty($newEmail) && empty($newPassword)) {
        header("Location: profile.php?error=empty");
        exit();
    }

    // Check for duplicate only if username or email is being changed
    if (!empty($newUsername) || !empty($newEmail)) {
        $stmt = $db->conn->prepare("
            SELECT customer_id FROM customer 
            WHERE (customer_username = :username OR customer_email = :email) 
            AND customer_id != :id
        ");
        $stmt->execute([
            ':username' => $newUsername ?: '___ignore___',
            ':email'    => $newEmail ?: '___ignore___',
            ':id'       => $customerID
        ]);
        if ($stmt->fetch()) {
            header("Location: profile.php?error=duplicate");
            exit();
        }
    }

    // Validate email if it's not empty
    if (!empty($newEmail) && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        header("Location: profile.php?error=email");
        exit();
    }

    // Build update query dynamically
    $fields = [];
    $params = [':id' => $customerID];

    if (!empty($newUsername)) {
        $fields[] = 'customer_username = :username';
        $params[':username'] = $newUsername;
    }

    if (!empty($newEmail)) {
        $fields[] = 'customer_email = :email';
        $params[':email'] = $newEmail;
    }

    if (!empty($newPassword)) {
        $fields[] = 'customer_password = :password';
        $params[':password'] = password_hash($newPassword, PASSWORD_BCRYPT);
    }

    if (!empty($fields)) {
        $sql = "UPDATE customer SET " . implode(', ', $fields) . " WHERE customer_id = :id";
        $stmt = $db->conn->prepare($sql);
        if ($stmt->execute($params)) {
            header("Location: profile.php?success=1");
        } else {
            header("Location: profile.php?error=1");
        }
    } else {
        header("Location: profile.php?error=empty");
    }
    exit();
}
?>
