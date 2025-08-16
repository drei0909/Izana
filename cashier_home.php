<?php
session_start();
if (!isset($_SESSION['cashier_ID'])) {
    header("Location: cashier_login.php");
    exit();
}

require_once('./classes/database.php');
$db = new Database();

$cashier_ID = $_SESSION['cashier_ID'];
$cashier_name = $_SESSION['cashier_FN'] ?? 'Cashier';

// Fetch all products
$products = $db->getAllProducts();
$categories = array_unique(array_map(fn($p) => $p['product_category'] ?? 'Other', $products));

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $cart = json_decode($_POST['cart'], true);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $cash_received = floatval($_POST['cash_received'] ?? 0);
    $order_type = $_POST['order_type'] ?? 'Dine-in';

    if (!empty($cart)) {
        $total_price = 0;
        foreach ($cart as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        $change = $payment_method === 'Cash' ? max($cash_received - $total_price, 0) : 0;

        // Insert into order
        $sql = "INSERT INTO `order` (customer_id, order_type, total_amount, receipt) VALUES (?, ?, ?, NULL)";
        $stmt = $db->conn->prepare($sql);

        if ($stmt->execute([$cashier_ID, $order_type, $total_price])) {
            $order_ID = $db->conn->lastInsertId();

            // Insert order items
            $sql_item = "INSERT INTO `order_item` (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_item = $db->conn->prepare($sql_item);
            foreach ($cart as $item) {
                $stmt_item->execute([$order_ID, $item['id'], $item['quantity'], $item['price']]);
            }

            // Insert payment
            $sql_payment = "INSERT INTO `payment` (order_id, payment_method, payment_amount) VALUES (?, ?, ?)";
            $stmt_payment = $db->conn->prepare($sql_payment);
            $stmt_payment->execute([$order_ID, $payment_method, $total_price]);

            $success_message = "Order placed successfully!";
            $change_amount = number_format($change, 2);
        } else {
            $error_message = "Failed to place order.";
        }
    } else {
        $error_message = "Cart is empty!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cashier Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { background: #fff8f0; font-family: 'Quicksand', sans-serif; }
.navbar { background-color: #6f4e37; }
.navbar-brand, .navbar-text { color: #fff !important; }
.menu-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; border-radius: 12px; }
.menu-card:hover { transform: scale(1.03); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.card-img-top { height: 140px; object-fit: cover; border-radius: 12px 12px 0 0; }
.cart-container { position: sticky; top: 20px; background: #fffdf7; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
.cart-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 8px; background: #fff7e6; margin-bottom: 6px; font-size: 14px; }
.cart-item button { padding: 3px 6px; }
.btn-add { background-color: #b07542; border: none; color: #fff; font-size: 13px; }
.btn-add:hover { background-color: #8a5c33; }
.btn-place { background-color: #6c4b35; margin-top: 10px;  border: none; color: #fff; font-weight: bold; }
.btn-place:hover { background-color: #8a5c33; }
.total-price { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
.change-display { font-weight: bold; margin-top: 10px; color: green; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg px-3 shadow">
    <a class="navbar-brand" href="#"><i class="fas fa-mug-saucer me-2"></i> Coffee Shop</a>
    <div class="ms-auto">
        <span class="navbar-text me-3"><i class="fa fa-user"></i> <?= htmlspecialchars($cashier_name) ?></span>
        <a href="cashier_logout.php" class="btn btn-danger btn-sm"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Menu -->
        <div class="col-md-8">
            <h4 class="mb-3">Menu</h4>
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control mb-2" placeholder="Search products...">
                <select id="categoryFilter" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row" id="menuList">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-3 menu-item" data-category="<?= htmlspecialchars($product['product_category'] ?? 'Other') ?>" data-name="<?= strtolower($product['product_name']) ?>">
                    <div class="card menu-card shadow-sm">
                        <img src="uploads/<?= htmlspecialchars($product['product_image'] ?? 't.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>">
                        <div class="card-body text-center">
                            <h6 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h6>
                            <p class="card-text mb-2">₱<?= number_format($product['product_price'], 2) ?></p>
                            <button class="btn btn-add btn-sm add-to-cart"
                                data-id="<?= $product['product_id'] ?>"
                                data-name="<?= htmlspecialchars($product['product_name']) ?>"
                                data-price="<?= $product['product_price'] ?>">Add <i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart -->
        <div class="col-md-4">
            <div class="cart-container">
                <h4>Cart</h4>
                <form method="POST" id="orderForm">
                    <ul class="list-group" id="cartItems"></ul>
                    <input type="hidden" name="cart" id="cartInput">
                    <div class="total-price">Total: ₱<span id="totalPrice">0</span></div>

                    <select name="order_type" class="form-select mb-2" required>
                        <option value="Dine-in">Dine-in</option>
                        <option value="Take-out">Take-out</option>
                    </select>

                    <select name="payment_method" id="paymentMethod" class="form-select mb-2">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                    </select>

                    <input type="number" name="cash_received" id="cashReceived" class="form-control mb-1" placeholder="Cash received" step="0.01">
                    <div class="change-display" id="changeDisplay">Change: ₱0.00</div>
                    <button type="submit" name="place_order" class="btn btn-place w-100"><i class="fa fa-check"></i> Place Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

function renderCart() {
    let cartList = document.getElementById('cartItems');
    cartList.innerHTML = '';
    let total = 0;
    cart.forEach((item, index) => {
        total += item.price * item.quantity;
        cartList.innerHTML += `
        <li class="cart-item list-group-item">
            ${item.name} 
            <span class="badge bg-primary rounded-pill">${item.quantity}</span>
            <div>
                <button onclick="changeQty(${index}, -1)" class="btn btn-sm btn-danger me-1"><i class="fa fa-minus"></i></button>
                <button onclick="changeQty(${index}, 1)" class="btn btn-sm btn-success me-1"><i class="fa fa-plus"></i></button>
                <button onclick="removeItem(${index})" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
            </div>
        </li>`;
    });
    document.getElementById('totalPrice').textContent = total.toFixed(2);
    document.getElementById('cartInput').value = JSON.stringify(cart);
    updateChange();
}

function removeItem(index) { cart.splice(index, 1); renderCart(); }
function changeQty(index, delta) { cart[index].quantity += delta; if(cart[index].quantity<=0) cart.splice(index,1); renderCart(); }

document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);
        const existing = cart.find(i => i.id == id);
        if (existing) existing.quantity++;
        else cart.push({id, name, price, quantity:1});
        renderCart();
    });
});

// Category filter
document.getElementById('categoryFilter').addEventListener('change', function(){
    const category = this.value.toLowerCase();
    document.querySelectorAll('.menu-item').forEach(item => {
        item.style.display = (!category || item.dataset.category.toLowerCase() === category) ? 'block' : 'none';
    });
});

// Search filter
document.getElementById('searchInput').addEventListener('input', function(){
    const term = this.value.toLowerCase();
    document.querySelectorAll('.menu-item').forEach(item => {
        item.style.display = item.dataset.name.includes(term) ? 'block' : 'none';
    });
});

// Change calculator
const cashInput = document.getElementById('cashReceived');
cashInput.addEventListener('input', updateChange);

function updateChange(){
    let total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
    let cash = parseFloat(cashInput.value) || 0;
    let change = Math.max(cash - total, 0);
    document.getElementById('changeDisplay').textContent = `Change: ₱${change.toFixed(2)}`;
}

<?php if(isset($success_message)): ?>
Swal.fire('Success', '<?= $success_message ?><?php if(isset($change_amount)) echo " | Change: ₱$change_amount"; ?>', 'success');
<?php endif; ?>
<?php if(isset($error_message)): ?>
Swal.fire('Error', '<?= $error_message ?>', 'error');
<?php endif; ?>
</script>
</body>
</html>
