  <?php
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;

  // Load PHPMailer
  require 'vendor/autoload.php'; // If using Composer
  require_once('./classes/database.php');

  $db = new database();

  ?>

  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <title>Register | Izana</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  
  <!-- Montserrat Font -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- SweetAlert -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body {
      font-family: 'Montserrat', sans-serif;
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
      background: #b07542;
      color: #fff;
      border-radius: 25px;
      padding: 10px 20px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
    }
     .back-home:hover {
      background: #8c5a33;
    }

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

    /* Add top margin so it doesn’t overlap with the fixed home button */
    margin-top: 80px; 
}


    .icon-box { font-size: 3rem; color: #b07542; margin-bottom: 10px; }
    .title {
      font-weight: 700;
      font-size: 2.4rem;
      color: #fff8f3;
      margin-bottom: 25px;
      text-shadow: 1px 1px 2px #4b3a2f;
    }

    .form-label { 
      font-weight: 600; 
      color: #f5e9dc; 
      font-size: 0.95rem; 
    }

    .form-control {
      border-radius: 30px;
      padding: 12px;
      background-color: #f7f5f0;
      border: 1px solid #d2b79e;
      color: #4b3a2f;
      font-family: 'Montserrat', sans-serif;
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
      font-family: 'Montserrat', sans-serif;
    }
    .back-btn:hover {
      background: #8c5a33;
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
      font-family: 'Montserrat', sans-serif;
    }

        .swal2-html-container::-webkit-scrollbar {
      width: 7px;
    }
    .swal2-html-container::-webkit-scrollbar-thumb {
      background-color: #b07542;
      border-radius: 4px;
    }
    .swal2-html-container::-webkit-scrollbar-track {
      background-color: #fff8f3;
    }

    .top-buttons a:hover,
    .top-buttons .info-icon:hover {
      color: #f2c9a0;       
    }

    .text-center { color: #f0f0f0; margin-top: 15px; font-family: 'Montserrat', sans-serif; }
    .text-center a { color: #f2c9a0; font-weight: 600; text-decoration: none; }
    .text-center a:hover { text-decoration: underline; }

    /* ✅ Responsive Adjustments */
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

       .back-btn:hover {
      background: #8c5a33;
    }

      .register-container {
        padding: 20px 15px;
      }
    }

    /* 📱 Background fixes for mobile */
    @media (max-width: 768px) {
      body {
        background-attachment: scroll;
        background-position: center top;
      }
    }

    @media (max-width: 480px) {
      body {
        background-size: cover;
        background-position: center;
      }
    }

    @media (max-width: 768px) {
    .register-container {
        margin-top: 70px;
        padding: 25px 20px;
    }
}

@media (max-width: 480px) {
    .register-container {
        margin-top: 60px;
        padding: 20px 15px;
    }

    .back-home {
        top: 10px;
        left: 10px;
        padding: 8px 16px;
        font-size: 0.9rem;
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
    <div class="row g-3">
      <div class="col-md-6">
        <label for="first_name"class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" autocomplete="new-first-name" required>
      </div>
      <div class="col-md-6">
        <label for="last_name" class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" autocomplete="new-last-name" required>
      </div>
      <div class="col-md-6">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" class="form-control" autocomplete="new-username" required>
      </div>
      <div class="col-md-6">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" autocomplete="new-email" required>
      </div>
      <div class="col-md-6">
        <label for="contact" class="form-label">Contact Number</label>
        <input type="text" name="contact" id="contact" class="form-control" pattern="^09\d{9}$" placeholder="09xxxxxxxxx" autocomplete="new-contact" required>
        <small class="text-light">Format: 09xxxxxxxxx</small>
      </div>
      <div class="col-md-6">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" required>
      </div>
    </div>



   <!-- Terms & Conditions Checkbox -->
<div class="form-check mt-4 text-start">
  <input class="form-check-input" type="checkbox" id="terms" required>
  <label class="form-check-label text-light" for="terms">
    I agree to the 
    <a href="#" id="openTerms" 
       style="
         color:#f2c9a0; 
         text-decoration: underline; 
         text-underline-offset: 4px; 
         transition:0.3s; 
       "
       onmouseover="this.style.color='#ffd9b3';" 
       onmouseout="this.style.color='#f2c9a0';">
       Terms and Conditions
    </a>
  </label>
</div>

    <button type="submit" class="btn-coffee mt-4" id="registerBtn">Register</button>
  </form>

  <div class="text-center mt-4">
    Already have an account? <a href="login.php">Login here</a>
  </div>
</div>


<!-- Terms & Conditions Script (No Icon Version) -->
<script>
document.getElementById("openTerms").addEventListener("click", function (e) {
  e.preventDefault();

  Swal.fire({
    title: `
      <span style="
        color:#b07542;
        text-decoration: underline;
        text-underline-offset: 6px;
        font-weight:600;
        letter-spacing:0.5px;
      ">
        Terms & Conditions
      </span>
    `,
    html: `
      <div style="
        text-align:left; 
        max-height:300px; 
        overflow-y:auto; 
        padding-right:10px; 
        font-size:14.5px; 
        line-height:1.6;
        scrollbar-width: thin;
        scrollbar-color: #b07542 #fff8f3;
      ">
        <p>
          Welcome to <strong style="color:#b07542;">Izana</strong>! 
          By registering and using our platform, you agree to these 
          <strong>Terms & Conditions</strong> that guide your use of our services.
        </p>

        <h5 style="color:#b07542; margin-top:16px;">1. Account Information</h5>
        <p>
          Provide accurate and complete information during registration. 
          You are responsible for keeping your login details secure and confidential.
        </p>

        <h5 style="color:#b07542; margin-top:16px;">2. Privacy</h5>
        <p>
          Your personal information (name, email, contact number) is used 
          only for managing your account and processing orders. 
          Izana does not share your data without consent.
        </p>

        <h5 style="color:#b07542; margin-top:16px;">3. Orders & Payments</h5>
        <p>
          Payments may be made via <strong>GCash</strong>. 
          If an order is canceled due to customer underpayment, 
          a ₱5 inconvenience fee will be deducted from the refund. 
          If an order is canceled due to admin or product issues, 
          a full refund will be issued, 
          and the customer may place a new order.
        </p>

        <h5 style="color:#b07542; margin-top:16px;">4. Prohibited Use</h5>
        <p>
          Do not submit false details, commit fraudulent actions, or interfere 
          with our system. Any violations may result in account suspension.
        </p>


        <h5 style="color:#b07542; margin-top:16px;">5. Contact</h5>
        <p>
          For assistance, 
          please contact us via our number 
          +63 908 141 4131.
        </p>
      </div>
    `,
    confirmButtonText: "I Understand",
    confirmButtonColor: "#b07542",
    background: "#fff8f3",
    color: "#4b3a2f",
    width: 520,
    showClass: {
      popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
      popup: 'animate__animated animate__fadeOutUp'
    }
  });
});
</script>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
$("#registrationForm").on("submit", function(e) {
  e.preventDefault();

  let email = $("#email").val();
  $("#registerBtn").prop("disabled", true).text("Creating an account...");

  $.ajax({
    url: "functions.php",
    type: "POST",
    data: $(this).serialize() + "&ref=register_customer",
    dataType: "json",
    success: function(response) {
      if (response.status === "success") {
        Swal.fire({
          title: "Registered!",
          text: response.message + " Please check your email for the verification code.",
          icon: "success",
          confirmButtonText: "Verify Now",
        }).then((result) => {
          if (result.isConfirmed) {
            // redirect only AFTER SweetAlert confirmation
            window.location.href = "email-verification.php?email=" + encodeURIComponent(email);
          }
        });
      } else {
        $("#registerBtn").prop("disabled", false).text("Register");
        Swal.fire("Error", response.message, "error");
      }
    },
    error: function() {
      $("#registerBtn").prop("disabled", false).text("Register");
      Swal.fire("Error", "Something went wrong. Please try again.", "error");
    },
  });
});
</script>

<script>
window.addEventListener("load", () => {
  document.querySelectorAll("input").forEach((input) => {
    input.value = ""; // clears autofill
  });
});
</script>


  </body>
  <script>
function showInfo() {
  Swal.fire({
    title: `
      <span style="
        color:#b07542;
        text-decoration: underline;
        text-underline-offset: 6px;
        font-weight:600;
        letter-spacing:0.5px;
      ">
        Registration Info
      </span>
    `,
    html: `
      <div style="
        text-align:left;
        max-height:320px;
        overflow-y:auto;
        padding-right:10px;
        font-size:14.5px;
        line-height:1.6;
        scrollbar-width: thin;
        scrollbar-color: #b07542 #fff8f3;
      ">
        <p>
          Welcome to <strong style="color:#b07542;">Izana</strong>! 
          Before creating your account, please read these quick reminders to make your registration smooth and secure.
        </p>

        <h5 style="color:#b07542; margin-top:15px;">1. Required Information</h5>
        <ul style="margin-left:18px;">
          <li><strong>First & Last Name</strong> – Please use your real name.</li>
          <li><strong>Username</strong> – Must be unique and easy to remember.</li>
          <li><strong>Email</strong> – Used for verification and order updates.</li>
          <li><strong>Contact Number</strong> – Must follow <code>09xxxxxxxxx</code> format.</li>
          <li><strong>Password</strong> – Keep it secure and avoid sharing it with others.</li>
        </ul>

        <h5 style="color:#b07542; margin-top:15px;">2. Email Verification</h5>
        <p>
          After registration, check your Gmail inbox for the verification code. 
          You’ll need to verify your email before logging in to your Izana account.
        </p>

        <h5 style="color:#b07542; margin-top:15px;">3. Privacy & Security</h5>
        <p>
          Izana collects your details only for managing your account and processing coffee orders. 
          We never share your personal data without your consent.
        </p>

       
        <h5 style="color:#b07542; margin-top:15px;">4. Need Help?</h5>
        <p>
          For any registration issues, contact our support team or visit the shop in <strong>San Antonio, Quezon</strong>.
        </p>
      </div>
    `,
    confirmButtonText: "Got it!",
    confirmButtonColor: "#b07542",
    background: "#fff8f3",
    color: "#4b3a2f",
    width: 520,
    showClass: {
      popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
      popup: 'animate__animated animate__fadeOutUp'
    }
  });
}
</script>

  </html>