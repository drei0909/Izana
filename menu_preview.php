<?php
require_once('./classes/database.php');
$db = new Database();
$products = $db->getAllProducts();

// Group products by category
$grouped = [];
foreach ($products as $p) {
    $cat = $p['product_category'] ?? 'Other';
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
      color: #f7f1eb;
    }

    .container-menu {
      max-width: 1200px;
      margin: 80px auto;
      background: rgba(255, 248, 230, 0.15);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      border-radius: 18px;
      padding: 40px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.3);
      backdrop-filter: blur(8px);
    }

    .title {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      text-align: center;
      color: #fff8f3;
      margin-bottom: 30px;
      text-shadow: 1px 1px 0 #f2e1c9;
    }

    .category-title {
      font-size: 1.8rem;
      font-weight: bold;
      margin-top: 30px;
      margin-bottom: 20px;
      color: #fff8f3;
      border-bottom: 2px solid #fff;
      padding-bottom: 5px;
    }

    .menu-card {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 25px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      backdrop-filter: blur(6px);
      transition: transform 0.3s ease;
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
      font-size: 1.2rem;
      font-weight: 600;
      color: #fffaf2;
    }

    .menu-price {
      color: #f2d9be;
      margin-bottom: 10px;
    }

    .badge-best {
      background-color: #f5b041;
      color: #000;
      font-weight: 700;
      font-size: 0.75rem;
      margin-top: 5px;
      padding: 5px 10px;
      border-radius: 50px;
      display: inline-block;
    }

    .note-text {
      font-size: 0.9rem;
      font-style: italic;
      color: #e6dcd2;
      text-align: center;
      margin-bottom: 30px;
    }

    .back-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background: #b07542;
      color: white;
      border: none;
      border-radius: 25px;
      padding: 10px 20px;
      font-weight: 600;
      text-decoration: none;
    }

    .back-btn:hover {
      background: #8a5c33;
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

</body>
</html>
