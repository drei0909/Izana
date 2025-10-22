<?php 
session_start();
$username = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Izana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- CSS & Fonts -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- JS Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <link rel="icon" type="image/svg+xml" href="uploads/icon.svg">

  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.55);
      z-index: -1;
    }
    .back-home {
      position: fixed; top: 20px; left: 20px;
      background: #b07542; color: #fff;
      border-radius: 25px; padding: 10px 20px;
      font-weight: 600; text-decoration: none;
      transition: all 0.3s ease;
    }
    .back-home:hover { background: #8c5a33; }
    .login-container {
      width: 100%; max-width: 500px;
      background: rgba(255, 248, 230, 0.15);
      border: 1px solid rgba(255,255,255,0.25);
      border-radius: 18px; padding: 40px 35px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.35);
      backdrop-filter: blur(10px); text-align: center;
    }
    .icon-box { font-size: 3rem; color: #b07542; margin-bottom: 10px; }
    .title { font-weight: 700; font-size: 2.2rem; color: #fff8f3; margin-bottom: 25px; text-shadow: 1px 1px 2px #4b3a2f; }
    .form-label { font-weight: 600; color: #f5e9dc; font-size: 0.95rem; }
    .form-control { border-radius: 30px; padding: 12px; background-color: #f7f5f0; border: 1px solid #d2b79e; color: #4b3a2f; font-family: 'Montserrat', sans-serif; }
    .btn-coffee { background-color: #b07542; color: #fff; font-weight: 600; border: none; padding: 12px; width: 100%; border-radius: 30px; letter-spacing: 0.5px; transition: all 0.3s ease-in-out; font-family: 'Montserrat', sans-serif; }
    .btn-coffee:hover { background-color: #8a5c33; }
    .text-center { color: #f0f0f0; margin-top: 15px; }
    .text-center a { color: #f2c9a0; font-weight: 600; text-decoration: none; }
    .text-center a:hover { text-decoration: underline; }
    .modal-backdrop { z-index: 1050 !important; }
    .modal { z-index: 1060 !important; }
  </style>
</head>
<body>

  <a href="home.php" class="back-home"><i class="fas fa-home me-2"></i>Home</a>

  <div class="login-container">
    <div class="icon-box"><i class="fas fa-mug-hot"></i></div>
    <h2 class="title">Welcome Back</h2>

    <form id="loginForm" method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($username) ?>" autocomplete="off">
      </div>

      <div class="mb-3 position-relative">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required autocomplete="new-password">
      </div>

      <button type="submit" id="loginBtn" class="btn-coffee mt-2">Login</button>

      <div class="text-center mt-4">
        Don't have an account? <a href="registration.php">Register here</a>
      </div>
    </form>
  </div>

<script>
$("#loginForm").on("submit", function(e) {
  e.preventDefault();
  $("#loginBtn").prop("disabled", true).text("Logging in...");

  $.ajax({
    url: "functions.php",
    type: "POST",
    data: $(this).serialize() + "&ref=login_customer",
    dataType: "json",
    success: function(response) {
      $("#loginBtn").prop("disabled", false).text("Login");

      if(response.status === "success") {
        Swal.fire({
          title: "Login Successful!",
          text: "Redirecting to your menu...",
          icon: "success",
          timer: 2000,
          showConfirmButton: false
        });
        setTimeout(() => window.location.href = response.redirect, 2000);

      } else if(response.status === "error" && response.message === "blocked") {
        const reason = response.reason || "No reason provided";
        Swal.fire({
          icon: "error",
          title: "Account Blocked",
          html: `<p>Your account has been blocked by the admin.</p>
                 <p><strong>Reason:</strong> ${reason}</p>
                 <p>Redirecting in <span id='countdown'>3</span> seconds...</p>`,
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            let timer = 5;
            const countdown = Swal.getHtmlContainer().querySelector("#countdown");
            const interval = setInterval(() => {
              timer--;
              if(countdown) countdown.textContent = timer;
              if(timer <= 0){
                clearInterval(interval);
                window.location.href = "login.php";
              }
            }, 1000);
          }
        });

      } else {
        Swal.fire("Login Failed", response.message, "error");
      }
    },
    error: function() {
      $("#loginBtn").prop("disabled", false).text("Login");
      Swal.fire("Error", "Something went wrong. Please try again.", "error");
    }
  });
});
</script>

</body>
</html>
