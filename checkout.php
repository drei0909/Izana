<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['customer_ID'])) {
    header("Location: login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$customer_name = $_SESSION['customer_FN'] ?? 'Guest';
$customerID = $_SESSION['customer_ID'];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | Izana Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --accent: #b07542;
            --accent-dark: #8a5c33;
            --gray-dark: #1e1e1e;
            --gray-mid: #2b2b2b;
            --gray-light: #444;
            --text-light: #f5f5f5;
            --success: #28a745;
        }

        body {
            margin: 0;
            font-family: 'Quicksand', sans-serif;
            color: var(--text-light);
            background: url('uploads/bgg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        .navbar-custom {
            background: var(--gray-dark);
            border-bottom: 1px solid var(--gray-light);
        }

        .navbar-brand {
            color: var(--accent) !important;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 2rem;
        }

        .btn-back {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 30px;
            padding: 6px 18px;
            font-weight: 700;
            transition: .2s;
        }

        .btn-back:hover {
            background: var(--accent);
            color: #fff;
        }

        .checkout-container {
            max-width: 1000px;
            margin: 120px auto 60px;
            padding: 30px;
            background: var(--gray-mid);
            border: 1px solid var(--gray-light);
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, .5);
        }

        .checkout-title {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.5rem;
            text-align: center;
            color: var(--accent);
            margin-bottom: 35px;
        }

        .table {
            background: var(--gray-dark);
            color: var(--text-light);
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead {
            background: var(--accent-dark);
            color: #fff;
        }

        .table td,
        .table th {
            vertical-align: middle;
            border-color: var(--gray-light) !important;
        }

        #totalDisplay {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--success);
        }

        .btn-place-order {
            background: var(--accent);
            color: white;
            font-weight: 700;
            border-radius: 30px;
            padding: 12px;
            width: 100%;
            transition: .2s;
        }

        .btn-place-order:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }

        .payment-label {
            font-weight: 700;
            margin-top: 15px;
        }

        .form-select,
        .form-control {
            background: var(--gray-dark);
            border: 1px solid var(--gray-light);
            color: var(--text-light);
        }

        .form-select option {
            color: #000;
        }

        .alert-info {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--gray-light);
            color: var(--text-light);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container-fluid" style="max-width:1400px;">
        <a class="navbar-brand" href="#">Izana Coffee</a>
        <button class="btn-back ms-auto" id="backBtn"><i class="fas fa-arrow-left me-2"></i>Back</button>
    </div>
</nav>

<div class="checkout-container">
    <h2 class="checkout-title">Checkout Summary</h2>
    <p><strong>Customer:</strong> <?= htmlspecialchars($customer_name); ?></p>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div class="alert alert-warning text-center">
            Your cart is empty. Please go back to the <a href="menu.php" class="alert-link">menu</a> to add items.
        </div>
    <?php else: ?>
        <form action="place_order.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="order_channel" value="online">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr><th>Drink</th><th>Price (₱)</th><th>Qty</th><th>Subtotal (₱)</th></tr>
                </thead>
                <tbody>
                    <?php $total = 0;
                    foreach ($cart as $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal; ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']); ?></td>
                            <td>₱<?= number_format($item['price'], 2); ?></td>
                            <td><?= (int)$item['quantity']; ?></td>
                            <td>₱<?= number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total:</td>
                        <td class="fw-bold" id="totalDisplay">₱<?= number_format($total, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="mb-3">
                <label for="payment_method" class="payment-label">Payment Method:</label>
                <select class="form-select" name="payment_method" id="payment_method" required>
                    <option value="">-- Choose --</option>
                    <option value="GCash">GCash</option>
                </select>
            </div>

            <div class="mb-3" id="gcash_upload" style="display:none;">
                <label class="form-label">Upload GCash Receipt:</label>
                <input type="file" class="form-control" name="gcash_receipt" accept=".jpg,.jpeg,.png" />
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> For online orders, pickup only. Pay the exact amount to avoid issues.
            </div>

            <button type="submit" class="btn btn-place-order">Place Order</button>
        </form>
    <?php endif; ?>
</div>

<script>
    const pm = document.getElementById('payment_method');
    const uploadDiv = document.getElementById('gcash_upload');
    pm?.addEventListener('change', function () {
        uploadDiv.style.display = this.value === 'GCash' ? 'block' : 'none';
        uploadDiv.querySelector('input')?.setAttribute('required', this.value === 'GCash');
    });

    document.getElementById('backBtn').addEventListener('click', function (e) {
        e.preventDefault();
        window.location.href = 'menu.php';
    });
</script>

</body>
</html>
