<?php
require_once('./classes/database.php');

$db = new database();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | Izana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
  body {
    font-family: 'Quicksand', sans-serif;
    margin: 0;
    padding: 0;
    background: url('uploads/bgg.jpg') no-repeat center center;
    background-size: cover;
    background-attachment: fixed;
    color: #1e1e1e;
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
    position: fixed;
    top: 20px;
    left: 20px;
    background: rgba(255, 255, 255, 0.15);
    border: 2px solid #f5f5f5;
    color: #f5f5f5;
    padding: 8px 18px;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease-in-out;
    font-size: 1rem;
  }
  .back-home:hover { background: #b07542; color: #fff; }
  .register-container {
      width: 100%;
      max-width: 500px;
      background: rgba(255, 248, 230, 0.15);
      border: 1px solid rgba(255,255,255,0.25);
      border-radius: 18px;
      padding: 40px 35px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.35);
      backdrop-filter: blur(10px);
      text-align: center;
  }
  .icon-box { font-size: 3rem; color: #b07542; margin-bottom: 10px; }
  .title {
      font-family: 'Playfair Display', serif;
      font-size: 2.4rem;
      color: #fff8f3;
      margin-bottom: 25px;
      text-shadow: 1px 1px 2px #4b3a2f;
  }
  .form-label { font-weight: 600; color: #f5e9dc; font-size: 0.95rem; }
  .form-control {
      border-radius: 30px;
      padding: 12px;
      background-color: #f7f5f0;
      border: 1px solid #d2b79e;
      color: #4b3a2f;
  }
  .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(176,117,66,0.25);
      border-color: #b07542;
  }
  .btn-coffee {
      background-color: #b07542;
      color: #fff;
      font-weight: 600;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 30px;
      letter-spacing: 1px;
      transition: all 0.3s ease-in-out;
  }

    .top-buttons {
    position: fixed;       
    top: 15px;            
    right: 20px;          
    display: flex;
    align-items: center;
    z-index: 1000;       
  }

    .top-buttons a,
    .top-buttons .info-icon {
      color: #f5f5f5;        
      font-weight: 600;
      margin-left: 10px;
      transition: color 0.3s ease;
    }

    .top-buttons a:hover,
    .top-buttons .info-icon:hover {
      color: #f2c9a0;       
    }
      .btn-coffee:hover { background-color: #8a5c33; }
      .text-center { color: #f0f0f0; margin-top: 15px; }
      .text-center a { color: #f2c9a0; font-weight: 600; text-decoration: none; }
      .text-center a:hover { text-decoration: underline; }
      /* ✅ Extra Mobile Responsiveness */
  @media (max-width: 768px) {
    body {
      padding: 20px 10px; 
      flex-direction: column;
    }

    .register-container {
      width: 100%;
      max-width: 100%;
      margin: 20px 10px;
      padding: 25px 20px;
    }

    .title {
      font-size: 1.8rem;
    }

    .form-label {
      font-size: 0.9rem;
    }

    .form-control {
      font-size: 0.9rem;
      padding: 10px;
    }

    .btn-coffee {
      padding: 10px;
      font-size: 1rem;
    }

    /* Make top buttons stack nicer */
    .top-buttons {
      top: 10px;
      right: 10px;
      flex-direction: row;
      font-size: 0.9rem;
    }
  }

  @media (max-width: 480px) {
    .title {
      font-size: 1.6rem;
    }

    .top-buttons {
      font-size: 0.8rem;
    }

    .back-home {
      padding: 6px 12px;
      font-size: 0.8rem;
    }

    .register-container {
      padding: 20px 15px;
    }
  }

    /* ✅ Tablet & smaller */
  @media (max-width: 768px) {
    .back-home {
      padding: 6px 14px;
      font-size: 0.9rem;
      top: 15px;
      left: 15px;
    }
  }

  /* ✅ Small phones */
  @media (max-width: 480px) {
    .back-home {
      padding: 5px 12px;
      font-size: 0.8rem;
      top: 12px;
      left: 12px;
    }
  }

  /* 📱 Mobile & tablet fix */
  @media (max-width: 768px) {
    body {
      background-attachment: scroll; /* Prevents zoom/cutoff issue */
      background-position: center top; /* Keeps image aligned */
    }
  }

  @media (max-width: 480px) {
    body {
      background-size: cover;
      background-position: center; /* Always center on phones */
    }
  }


  </style>
</head>
<body>


<div class="top-buttons">
  <a href="home.php" class="back-home me-3 text-decoration-none">
    <i class="fas fa-home me-2"></i>Home
  </a>
  <div class="info-icon" onclick="showInfo()" style="cursor: pointer;">
    <i class="fas fa-circle-info"></i> Info
  </div>
</div>

<div class="register-container">
  <div class="icon-box">
    <i class="fas fa-mug-hot"></i>
  </div>
  <h2 class="title">Join Izana</h2>

  <form id="registrationForm" method="POST" autocomplete="off">
    <div class="mb-3">
      <label for="first_name" class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" autocomplete="new-first-name" required>
    </div>
    <div class="mb-3">
      <label for="last_name" class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" autocomplete="new-last-name" required>
    </div>
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" class="form-control" autocomplete="new-username" required>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" autocomplete="new-email" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" class="form-control" autocomplete="new-password" required>
    </div>
    <button type="submit" class="btn-coffee mt-2">Register</button>
  </form>

  <div class="text-center mt-4">
    Already have an account? <a href="Login.php">Login here</a>
  </div>
</div>

<!-- Info Script -->
<script>
function showInfo() {
  Swal.fire({
    title: 'Need Help Registering?',
    html: `
      <div style="text-align: left;">
        <ul style="list-style: none; padding-left: 0;">
          <li>✔️ All fields are required</li>
          <li>✔️ Use a <strong>valid email address</strong></li>
          <li>✔️ Username must be unique</li>
          <li>✔️ Minimum 6 characters, 1 uppercase, 1 special character, 1 number</li>
        </ul>
      </div>`,
    icon: 'info',
    confirmButtonColor: '#b07542',
    background: '#fff8f3',
    color: '#4b3a2f'
  });
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$("#registrationForm").on("submit", function(e) {
    e.preventDefault();

    $.ajax({
        url: "functions.php",
        type: "POST",
        data: $(this).serialize() + "&ref=register_customer",
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                Swal.fire("Registered!", response.message, "success");
                $("#registrationForm")[0].reset();
            } else {
                Swal.fire("Error", response.message, "error");
            }
        },
        error: function() {
            Swal.fire("Error", "Something went wrong. Please try again.", "error");
        }
    });
});
</script>

</body>
</html>
