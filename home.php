<?php
session_start();
require_once('./classes/database.php');
$db = new database();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Izana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <style>

    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #fdfaf7;
      color: #4b3a2f;
    }

    .navbar {
      background-color: transparent;
    }

    .navbar-brand {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      color: white !important;
    }

    .nav-link {
      color: white !important;
      font-weight: 600;
    }

    .header {
      background: url('uploads/bgg.jpg') no-repeat center center / cover;
      height: 105vh;
      position: relative;
      color: white;
    }

    .header::before {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.5);
    }

    .header-content {
      position: relative;
      z-index: 2;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 3.5rem;
      margin-bottom: 20px;
    }

    .header .btn {
      background-color: #c69b7b;
      color: #fff;
      border-radius: 30px;
      padding: 12px 28px;
      font-weight: 600;
      border: none;
    }

    .features {
      padding: 60px 15px;
      text-align: center;
    }

    .features i {
      font-size: 2rem;
      color: #c69b7b;
    }

    .features h5 {
      margin-top: 10px;
      font-weight: 700;
    }

    .gallery {
      padding: 40px 0;
    }

    .gallery img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      border-radius: 10px;
    }

    footer {
      background-color: #4b3a2f;
      color: #f4e7d6;
      padding: 40px 20px;
    }

    footer a {
      color: #f4e7d6;
      text-decoration: none;
    }

    footer input {
      border-radius: 20px;
      padding: 8px 15px;
      border: none;
    }

    footer button {
      background-color: #c69b7b;
      border: none;
      padding: 8px 18px;
      border-radius: 20px;
      color: #fff;
    }

<style>
.transparent-navbar {
  background-color: rgba(0, 0, 0, 0.3) !important;
  backdrop-filter: blur(8px);
  transition: background-color 0.3s ease;
}
</style>





  </style>
</head>

<body>

<!-- Header with Nav -->
<header class="header">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="#">IZANA</a>
      <div class="ms-auto d-flex gap-3">
        <a class="nav-link" href="registration.php">Register</a>

        <!-- Login Dropdown -->
        <div class="dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Login
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
            <li><a class="dropdown-item" href="login.php">Customer Login</a></li>
            <li><a class="dropdown-item" href="admin_L.php">Admin Login</a></li>
             <li><a class="dropdown-item" href="cashier_login.php">Cashier Login</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="header-content text-center">
    <h1>Where Every Sip Feels Like Home</h1>
    <a href="menu_preview.php" class="btn">Menu View</a>
  </div>
</header>


<!-- Features -->
<section class="features container">
  <div class="row g-4">
    <div class="col-md-4">
      <i class="fas fa-leaf"></i>
<h5>Locally Brewed</h5>
<p>We use carefully selected beans sourced from local farmers in the Philippines.</p>
    </div>
    <div class="col-md-4">
      <i class="fas fa-star"></i>
<h5>Best Sellers</h5>
<p>Our Spanish Latte and Mango Supreme are crowd favorites you'll love!</p>
    </div>
    <div class="col-md-4">
      <i class="fas fa-coffee"></i>
      <h5>To-Go Orders</h5>
      <p>Convenient and quick — your coffee is ready when you are.</p>
    </div>
  </div>
</section>

<!-- Gallery -->
<section class="gallery container">
  <div class="row g-4">
    <div class="col-6 col-md-4"><img src="uploads/cof.jpg" alt="Coffee cup"></div>
    <div class="col-6 col-md-4"><img src="uploads/bgggg.jpg" alt="Barista pouring"></div>
    <div class="col-6 col-md-4"><img src="uploads/cofff.jpg" alt="Cafe interior"></div>
  </div>
</section>

<!-- Footer -->
<footer class="text-center">
  <div class="container">
    <h5 class="mb-3">IZANA</h5>
    <p>Located in San Antonio — Brewing joy one cup at a time.</p>
    <div class="mb-3">
      
    <a href="https://www.instagram.com/2021cakes_and_coffee/?igsh=c3BqczNuYnBpMDAx#" target="_blank" title="Follow us on Instagram">
  <i class="fab fa-instagram me-3"></i>
</a>


    </div>
    <p class="mt-4 small">&copy; <?= date('Y') ?> IZANA. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>