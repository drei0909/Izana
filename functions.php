<?php

if ($_POST['ref'] == 'add_to_cart') {

    $product_id = (int)$_POST['product_id'];
    $qty = (int) $_POST['qty'];

 $sql = "INSERT INTO cart (product_id, qty)
            VALUES (:product_id, :qty)";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':product_id' => $product_id,
        ':qty' => $qty,
        ':username' => $username,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_BCRYPT)
    ]);

}
?>