<?php
require_once('./classes/database.php');
$db = new Database();
$products = $db->getAllProducts();

// Group products by category, skip Add-ons
$grouped = [];
foreach ($products as $p) {
    $cat = $p['product_category'] ?? 'Other';
    if (strtolower($cat) === 'add-ons') continue;
    $grouped[$cat][] = [
        $p['product_id'],
        $p['product_name'],
        $p['product_price'],
        $p['product_category'] == 1,
        $p['stock_quantity'] ?? 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Menu Preview | Izana Coffee</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Quicksand', sans-serif;
      color: #fdfdfd;
      position: relative;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Dark overlay */
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.45);
      z-index: -1;
    }

    .container-menu {
      max-width: 1200px;
      margin: 80px auto 40px auto;
      background: rgba(245, 245, 245, 0.1);
      border: 1.5px solid rgba(255, 255, 255, 0.25);
      border-radius: 18px;
      padding: 40px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.3);
      backdrop-filter: blur(10px);
      flex: 1;
    }

    .title {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      text-align: center;
      color: #fff;
      margin-bottom: 30px;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.8);
    }

    .category-title {
      font-size: 1.8rem;
      font-weight: bold;
      margin-top: 40px;
      margin-bottom: 20px;
      color: #f5f5f5;
      border-bottom: 3px solid #b07542;
      padding-bottom: 8px;
      text-shadow: 1px 1px 4px rgba(0,0,0,0.7);
    }

    .menu-card {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.25);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 25px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      transition: transform 0.3s ease;
      backdrop-filter: blur(6px);
    }

    .menu-card:hover {
      transform: translateY(-5px);
    }

    .menu-card img {
      width: 100%;
      max-height: 180px;
      object-fit: cover;
      border-radius: 12px;
      margin-bottom: 15px;
    }

    .menu-name {
      font-size: 1.3rem;
      font-weight: 700;
      color: #fdfdfd;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
    }

    .menu-price {
      color: #f2d9be;
      margin-bottom: 10px;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .badge-best {
      background-color: #b07542;
      color: #fff;
      font-weight: 700;
      font-size: 0.8rem;
      margin-top: 5px;
      padding: 6px 12px;
      border-radius: 50px;
      display: inline-block;
      box-shadow: 1px 1px 6px rgba(0,0,0,0.5);
    }

    .note-text {
      font-size: 1rem;
      font-style: italic;
      color: #ddd;
      text-align: center;
      margin-bottom: 30px;
    }

    /* Softer back button */
    .back-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background: transparent;
      color: #f2d9be;
      border: 2px solid #f2d9be;
      border-radius: 25px;
      padding: 10px 20px;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      transition: all 0.3s ease;
    }

    .back-btn:hover {
      background: #f2d9be;
      color: #000;
    }

    /* Footer */
    footer {
      background: rgba(0, 0, 0, 0.7);
      text-align: center;
      padding: 15px;
      color: #f2d9be;
      font-size: 1rem;
      border-top: 2px solid #b07542;
    }

    footer a {
      color: #fff;
      font-weight: 600;
      text-decoration: none;
      margin: 0 8px;
    }

    footer a:hover {
      color: #f2d9be;
    }
  </style>
</head>
<body>

<a href="home.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Back</a>

<div class="container-menu">
  <h2 class="title">Izana Coffee Menu Preview</h2>
  <p class="note-text">Browse our coffee selections below. Ordering is currently disabled on this page.</p>

  <?php
  function renderCategoryPreview($title, $items) {
    echo "<div class='category-title'>{$title}</div><div class='row'>";
    foreach ($items as $item) {
      echo cardPreview($item[0], $item[1], $item[2], $item[3] ?? false);
    }
    echo "</div>";
  }

  function cardPreview($productID, $name, $price, $best = false) {
    $img = "uploads/t.jpg";
    $bestLabel = $best ? "<div class='badge-best'>Best Seller</div>" : "";
    return <<<HTML
    <div class="col-md-4">
      <div class="menu-card">
        <img src="$img" alt="$name">
        <div class="menu-name">$name</div>
        <div class="menu-price">₱$price</div>
        $bestLabel
      </div>
    </div>
    HTML;
  }

  foreach ($grouped as $category => $items) {
    renderCategoryPreview($category, $items);
  }
  ?>
</div>

<footer>
  <p>If you want to place an order, please <a href="registration.php">Register</a> or <a href="login.php">Login</a> first.</p>
</footer>

</body>
</html>
