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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="uploads/icon.svg">
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
      <a class="navbar-brand" href="#">
        <img src="uploads/izana_logo.png" alt="IZANA Logo" style="height: 80px;">
      </a>

      <div class="ms-auto d-flex gap-3">
        <?php if (isset($_SESSION['customer_FN'])): ?>
          <!-- User Dropdown (When Logged In) -->
          <div class="dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
               data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle me-1"></i>
              <?php echo htmlspecialchars($_SESSION['customer_FN']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="menu.php"><i class="fas fa-user me-2"></i>Menu</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Auth Dropdown (When Not Logged In) -->
          <div class="dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="authDropdown" role="button" 
               data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-user-circle me-1"></i> Account
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="authDropdown">
              <li><a class="dropdown-item" href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Login</a></li>
              <li><a class="dropdown-item" href="registration.php"><i class="fas fa-user-plus me-2"></i>Register</a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>


  <div class="header-content text-center">
    <h1>Where Every Sip Feels Like Home</h1>
    <a href="menu_preview.php" class="btn">Explore Our Products</a>
  </div>
</header>


<!-- About Section -->
<section class="container py-5">
  <div class="row align-items-center">
   <div class="col-md-6 col-12 text-center">
      <img src="uploads/about.png" 
           alt="Our Cafe" 
           class="img-fluid"
           style="max-width: 100%; height: auto; background: transparent; border: none; box-shadow: none;">
    </div>

   <div class="col-md-6 col-12 text-center text-md-start">
  <h2 class="mb-3" style="color:#6b3e2e;">Espresso Blend</h2>
  <p>
    We use carefully selected beans sourced from <strong>local farmers in the Philippines</strong>.  
    Roasted to perfection, our blend brings out a bold aroma and a smooth, well-balanced flavor 
    in every cup.
  </p>

  <h6 class="mt-4"><strong>Best For:</strong></h6>
  <p>Espresso, Cappuccino, Lattes</p>
</div>

  </div>
</section>



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
<section class="gallery container my-5 mb-5 pb-5">
  <div class="row g-4">
    <div class="col-6 col-md-4 col-lg-4">
      <img src="uploads/cof.jpg" alt="Coffee cup" class="img-fluid rounded shadow-sm w-100">
    </div>
    <div class="col-6 col-md-4 col-lg-4">
      <img src="uploads/cofff.jpg" alt="Cafe interior" class="img-fluid rounded shadow-sm w-100">
    </div>
    <div class="col-6 col-md-4 col-lg-4">
      <img src="uploads/bg.jpg" alt="Cafe ambiance" class="img-fluid rounded shadow-sm w-100">
    </div>
  </div>
</section>




<!-- About Section -->
<section class="container py-5">
  <div class="row align-items-center">
    <!-- Text first on desktop, but stack on mobile -->
    <div class="col-md-6 mb-4 mb-md-0 text-center text-md-start">
      <h2 class="mb-3">Our Story</h2>
      <p>
        At IZANA, every cup tells a story of passion, warmth, and community. 
        From our carefully sourced beans to the cozy atmosphere we’ve created, 
        we want every guest to feel at home while enjoying the finest coffee.
      </p>
      <p>
        Whether you’re here for a quick pick-me-up or to spend time with friends, 
        IZANA is your perfect spot to relax and savor every sip.
      </p>
    </div>

    <!-- Image on the right (centered on mobile) -->
    <div class="col-md-6 text-center">
      <img src="uploads/bggggg.jpg" 
           alt="Our Cafe" 
           class="img-fluid rounded shadow"
           style="max-height: 400px; object-fit: cover;">
    </div>
  </div>
</section>





<!-- Footer -->
<footer class="text-center">
 <div class="container text-center">
  <img src="uploads/izana_logo.png" alt="IZANA Logo" style="height: 65px;">

  <!-- Location Link -->
  <p class="mt-2">
    <a href="https://maps.app.goo.gl/hNkEcc3FzmerVUjh9" target="_blank" 
       title="Find us on Google Maps" 
       style="color: inherit; text-decoration: none;">
      <i class="fas fa-map-marker-alt me-2"></i> San Antonio, Quezon
    </a>
  </p>

  <!-- Social Media Section -->
  <p class="fw-semibold mt-3">Follow us on our social media accounts for more updates:</p>
  <div class="mb-3">
    <a href="https://www.instagram.com/2021cakes_and_coffee/?igsh=c3BqczNuYnBpMDAx#" target="_blank" title="Follow us on Instagram">
      <i class="fab fa-instagram me-3 fa-lg"></i>
    </a>
    <a href="https://www.tiktok.com/@izana_coffee_desserts" target="_blank" title="Follow us on TikTok">
      <i class="fab fa-tiktok me-3 fa-lg"></i>
    </a>
  </div>

  <!-- Contact Section -->
  <p class="fw-semibold mt-3">Contact Us:</p>
  <p>
    <a href="tel:+639123456789" style="color: inherit; text-decoration: none;">
      <i class="fas fa-phone-alt me-2"></i> +63 908 141 4131
    </a>
  </p>
</div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>