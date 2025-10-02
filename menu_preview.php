<?php
require_once('./classes/database.php');

$db = new Database();

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
  <link rel="icon" type="image/svg+xml" href="uploads/icon.svg">

  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Quicksand', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.55);
      z-index: -1;
    }

    /* HEADER */
    header {
      text-align: center;
      padding: 40px 20px 20px;
      color: #fff;
    }
    header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 2.8rem;
      font-weight: 700;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
    }
    header p {
      font-size: 1.2rem;
      color: #f2d9be;
      margin-top: 8px;
    }

    /* MENU CONTAINER */
    .container-menu {
      max-width: 1200px;
      margin: 20px auto 40px;
      padding: 20px;
    }

    .category-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: #fff;
      margin: 40px 0 20px;
      border-left: 6px solid #b07542;
      padding-left: 12px;
      text-shadow: 1px 1px 4px rgba(0,0,0,0.6);
    }

    /* PRODUCT CARD */
    .menu-card {
      background: #fff;
      border: none;
      border-radius: 15px;
      padding: 18px;
      text-align: center;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
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
      font-weight: 700;
      color: #333;
      margin-bottom: 6px;
    }
    .menu-price {
      font-size: 1.1rem;
      font-weight: 600;
      color: #b07542;
    }

    /* NOTE */
    .note-text {
      text-align: center;
      color: #ddd;
      margin: 20px 0;
      font-style: italic;
    }

    /* BACK BUTTON */
    .back-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background: #b07542;
      color: #fff;
      border-radius: 25px;
      padding: 10px 20px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    .back-btn:hover {
      background: #8c5a33;
    }

    /* FOOTER */
    footer {
      background: rgba(0, 0, 0, 0.85);
      text-align: center;
      padding: 15px;
      color: #f2d9be;
      font-size: 1rem;
      border-top: 3px solid #b07542;
    }
    footer a {
      color: #fff;
      font-weight: 600;
      text-decoration: none;
    }
    footer a:hover {
      color: #f2d9be;
    }
  </style>
</head>
<body>

<a href="home.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Back</a>

<header>
  <h1>☕ Explore Our Products</h1>
  <p>Discover the best of Izana Coffee</p>
</header>

<div class="container-menu">
  <p class="note-text">Browse our coffee selections below. This is a preview only.</p>

  <div id="menuContent">
    <p class="note-text">Loading menu...</p>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- <script>
function loadMenu() {
    $.ajax({
        url: "functions.php",
        method: "POST",
        data: { ref: "menu_preview" },
        dataType: "json",
        success: function(data) {
            if (data.status === "success") {
                $("#menuContent").html(data.html);
            } else {
                $("#menuContent").html("<p class='note-text'>Failed to load menu.</p>");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
            $("#menuContent").html("<p class='note-text'>Error loading menu.</p>");
        }
    });
}

$(document).ready(function() {
    loadMenu();                 // First load
    setInterval(loadMenu, 5000); // Refresh every 5 seconds
});
</script> -->




</body>
</html>
