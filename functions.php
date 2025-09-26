<?php
session_start();
require_once('./classes/database.php');

$db = new Database();

// Check if 'ref' is set in the POST request
if ($_POST['ref'] == 'add_to_cart') {
    $carts = $_POST['cart'];

  foreach($carts as $row){

        // --- Changed: use local variables and execute([...]) to avoid bindParam reference issues ---
        $productId = isset($row['id']) ? intval($row['id']) : 0;
        $quantity  = isset($row['quantity']) ? intval($row['quantity']) : 0;

        // skip invalid entries
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        //1st: check if product is added already in the cart
        $check_item_query = $db->conn->prepare("SELECT * FROM cart 
            WHERE product_id = :product_id
            AND customer_id = :customer_id");
        // use execute with values (avoids bindParam-by-reference issues)
        $check_item_query->execute([
            ':product_id' => $productId,
            ':customer_id' => intval($_SESSION['customer_ID'])
        ]);

  //2nd: if product exists
    if ($check_item_query->rowCount() > 0) {
        $cart = $check_item_query->fetch(PDO::FETCH_ASSOC);
        $currentQty = intval($cart['qty']);
        $newQty = $currentQty + $quantity;


     //3rd: if exists, update the qty
     $update_query = $db->conn->prepare("UPDATE cart 
     SET qty = :qty 
     WHERE product_id = :product_id
     AND customer_id = :customer_id");
     $update_query->execute([
        ':qty' => $newQty,
        ':product_id' => $productId,
        ':customer_id' => intval($_SESSION['customer_ID'])
      ]);

   } else {

            // // 4th: If the product does not exist in the cart, insert it
             $insert_query = $db->conn->prepare("INSERT INTO cart (product_id, customer_id, qty) 
                 VALUES (:product_id, :customer_id, :qty)");
             $insert_query->execute([
                 ':product_id' => $productId,
                 ':customer_id' => intval($_SESSION['customer_ID']),
                 ':qty' => $quantity
             ]);
                
}

  }  // end foreach

  // Return a simple success response so frontend can refresh the cart
  echo json_encode(['status' => 'success']);
  exit;
}

// Get the updated cart content
if ($_POST['ref'] == 'show_cart') {
 

    $cart = [];
    $html_cart_content = '';
    $cart_grand_total = 0;  
    $cart_total =    0;
    

    $select_cart_query = $db->conn->prepare("SELECT 
        cart.*, product.product_name, product.product_price
        FROM cart
        INNER JOIN product ON cart.product_id = product.product_id
        WHERE customer_id = :customer_id");
    $select_cart_query->bindParam(':customer_id', $_SESSION['customer_ID'], PDO::PARAM_INT);
    if ($select_cart_query->execute()) {
        $carts = $select_cart_query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($carts as $cart) {


            $cart_subtotal = $cart['qty'] * $cart['product_price'];
            
            $cart_grand_total +=   $cart_subtotal; 

           $html_cart_content .= '
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>' . $cart['qty'] . ' x ' . $cart['product_name'] . '</strong><br>
                    <small>₱' . $cart['product_price'] . ' each</small><br>
                    <small>₱' . $cart_subtotal . '</small>
                </div>
                <button class="btn btn-sm btn-outline-danger delete-cart-item ms-2" data-id="' . $cart['cart_id'] . '">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <hr>';

        }

        echo json_encode([
            'status' => 'success',
            'html_cart_content' => $html_cart_content,
            'cart_grand_total' => number_format($cart_grand_total, 2)
        ]);

    } else {
        echo json_encode([
            'status' => 'error',
        ]);
    }
}  

// Delete item from cart
if (isset($_POST['ref']) && $_POST['ref'] == 'delete_cart_item') {
    $cart_id = $_POST['cart_id'];

    $delete_query = $db->conn->prepare("DELETE FROM cart 
        WHERE cart_id = :cart_id AND customer_id = :customer_id");
    $delete_query->bindParam(':cart_id', $cart_id, PDO::PARAM_INT);
    $delete_query->bindParam(':customer_id', $_SESSION['customer_ID'], PDO::PARAM_INT);

    if ($delete_query->execute()) {
        echo json_encode(['status' => 'success']);
    } 
}


?>