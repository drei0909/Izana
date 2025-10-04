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


        $html .=  '<ul class="list-group">';
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

if ($_POST['ref'] == 'get_orders_que') {
    

    $html = '<div class="row">
        <div class="col-md-3 ">
            <div class="mr-1">
                <ul class="list-group" id="pending">
                    <li class="list-group-item bg-success fw-bold text-white">Pending</li>';
                    $orders = $db->getCashierOrders(1);
                    if (count($orders) > 0){
                        foreach ($orders as $row){
                            $html .= '<li class="list-group-item" data-id="'. $row['order_id'] .'">';
                            $html .= $row['customer_FN'].' '.$row['total_amount'];
                            $html .= '</li>';
                        }
                    }
                    $html .= '
                </ul>
            </div>
      </div>
      <div class="col-md-3 ">
      
        <div class="mr-1">
          <ul class="list-group" id="preparing">
            <li class="list-group-item bg-success fw-bold text-white">Preparing</li>';
            $orders = $db->getCashierOrders(2);
            if (count($orders) > 0){
                foreach ($orders as $row){
                    $html .= '<li class="list-group-item" data-id="'. $row['order_id'] .'">';
                    $html .= $row['customer_FN'].' '.$row['total_amount'];
                    $html .= '</li>';
                }
            }
            $html .= '
          </ul>
        </div>
      </div>
      <div class="col-md-3 ">
        <div class="mr-1">
          <ul class="list-group" id="ready">
            <li class="list-group-item bg-success fw-bold text-white">Ready for Pickup</li>';
            $orders = $db->getCashierOrders(3);
            if (count($orders) > 0){
                foreach ($orders as $row){
                    $html .= '<li class="list-group-item" data-id="'. $row['order_id'] .'">';
                    $html .= $row['customer_FN'].' '.$row['total_amount'];
                    $html .= '</li>';
                }
            }
            $html .= '

          </ul>
        </div>
      </div>
      <div class="col-md-3 ">
        <div class="mr-1">
          <ul class="list-group" id="cancel">
            <li class="list-group-item bg-success fw-bold text-white">Cancel</li>';
            $orders = $db->getCashierOrders(4);
            if (count($orders) > 0){
                foreach ($orders as $row){
                    $html .= '<li class="list-group-item" data-id="'. $row['order_id'] .'">';
                    $html .= $row['customer_FN'].' '.$row['total_amount'];
                    $html .= '</li>';
                }
            }
            $html .= '

          </ul>
        </div>
      </div>
    </div>';

    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);
}

if ($_POST['ref'] == 'update_order_stats') {
    $order_id = intval($_POST['id']);
    $status = intval($_POST['status']);

    $stmt = $db->conn->prepare("
        UPDATE order_online 
        SET status = ?
        WHERE order_id = ?
    ");
    return $stmt->execute([$status, $order_id]);
}

?>