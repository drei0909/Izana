<?php
session_start();

require_once('../classes/database.php');
require_once (__DIR__. "/../classes/config.php");


header('Content-Type: application/json');


$db = new Database();


// Get the updated cart content
if ($_POST['ref'] == 'get_order_item') {
 
    $order_id = intval($_POST['order_id']);
        $html = '';
   

    $stmt = $db->conn->prepare("SELECT 
        o.*,p.product_name
        FROM order_item o
        INNER JOIN product p ON o.product_id = p.product_id
        WHERE o.order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $html .=  '<ul class="list-groups">';
        foreach ($rows as $row) {

            $html .=  '<li class="list-group-item">
            <span class="text-primary fw-bold">'.$row['product_name'].'</span> <br>
            <span>'.$row['quantity'].'x</span> <br>
            <span>₱'.$row['price'].'</span> <br>
            </li>';
          
            

        }
            $html .=  ' </ul>';

        echo json_encode([
            'status' => 'success',
            'html' => $html
        ]);

    } else {
        echo json_encode([
            'status' => 'error',
        ]);
    }
}  

?>