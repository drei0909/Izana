<?php
require_once('./classes/database.php');
$db = new database();
$alert = '';

// keep text fields empty on first load
$fname = $lname = $username = $email = "";

// process submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // trim/sanitize inputs
    $fname    = trim($_POST['first_name'] ?? '');
    $lname    = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // REQUIRED check (does not clear anything yet, just warn)
    if ($fname === '' || $lname === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
        $alert = "<script>Swal.fire('Missing Fields', 'All fields are required.', 'warning');</script>";
    }
    // EMAIL format check -> clear only email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert = "<script>Swal.fire('Invalid Email', 'Please enter a valid email address.', 'warning');</script>";
        $email = ""; // reset only the incorrect field
    }
    // PASSWORD strength check -> clear only password fields
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*\W)(?=.*\d).{6,}$/', $password)) {
        $alert = "<script>
            Swal.fire('Weak Password', 
            'Password must be at least 6 characters long, contain 1 uppercase letter, 1 number, and 1 special character.', 
            'warning');
        </script>";
        $password = $confirm = ""; // reset only password fields
    }
    // PASSWORD match check -> clear only password fields
    elseif ($password !== $confirm) {
        $alert = "<script>Swal.fire('Password Mismatch', 'Passwords do not match.', 'error');</script>";
        $password = $confirm = ""; // reset only password fields
    }
    else {
        // attempt registration
        $success = $db->registerCustomer($fname, $lname, $username, $email, $password);
        if ($success) {
            $alert = "<script>Swal.fire('Registered!', 'Your account has been created successfully.', 'success');</script>";
            // clear everything after success
            $fname = $lname = $username = $email = "";
        } else {
            // username/email taken -> clear only those fields; keep names
            $alert = "<script>Swal.fire('Username/Email Taken', 'Please choose another username or email.', 'error');</script>";
            // You can choose to clear only one or both; commonly both are candidates
            $username = "";
            $email = "";
        }
    }
}
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
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Quicksand', sans-serif;
      color: #f7f1eb;
    }
    .top-buttons { position: absolute; top: 20px; width: 100%; display: flex; justify-content: space-between; padding: 0 25px; z-index: 10; }
    .back-home, .info-icon { background: transparent; border: 2px solid white; color: white; padding: 8px 18px; border-radius: 30px; font-weight: 600; text-decoration: none; transition: all 0.3s ease-in-out; font-size: 0.95rem; }
    .info-icon { padding: 8px 15px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
    .back-home:hover, .info-icon:hover { background: rgba(255, 255, 255, 0.15); color: white; text-decoration: none; }
    .register-container { max-width: 550px; margin: 100px auto; background: rgba(255, 248, 230, 0.15); border: 1.5px solid rgba(255, 255, 255, 0.3); border-radius: 18px; padding: 40px 35px; box-shadow: 0 12px 30px rgba(0,0,0,0.3); backdrop-filter: blur(8px); }
    .icon-box { text-align: center; font-size: 3rem; color: #b07542; margin-bottom: 10px; }
    .title { font-family: 'Playfair Display', serif; font-size: 2.3rem; text-align: center; color: #fff8f3; margin-bottom: 25px; text-shadow: 1px 1px 0 #f2e1c9; }
    .form-label { font-weight: 600; color: #f5e9dc; font-size: 0.95rem; }
    .form-control { border-radius: 30px; padding: 12px; background-color: #fffdf7; border: 1px solid #d2b79e; color: #4b3a2f; }
    .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(166, 124, 82, 0.25); border-color: #b4875b; }
    .btn-coffee { background-color: #b07542; color: #fff; font-weight: 600; border: none; padding: 12px; width: 100%; border-radius: 30px; letter-spacing: 1px; transition: all 0.3s ease-in-out; }
    .btn-coffee:hover { background-color: #8a5c33; }
    .text-center a { color:white; font-weight: 600; text-decoration: none; }
    .text-center a:hover { text-decoration: underline; }
    @media (max-width: 576px) {
      .register-container { margin: 30px 15px; padding: 30px 25px; }
      .top-buttons { flex-direction: column; gap: 10px; align-items: center; }
    }
  </style>
</head>
<body>

<div class="top-buttons">
  <a href="home.php" class="back-home"><i class="fas fa-home me-2"></i>Home</a>
  <div class="info-icon" onclick="showInfo()">
    <i class="fas fa-circle-info"></i> Info
  </div>
</div>

<div class="register-container">
  <div class="icon-box">
    <i class="fas fa-mug-hot"></i>
  </div>
  <h2 class="title">Join Izana</h2>

  <!-- Disable browser autofill; preserve only valid fields -->
  <form method="POST" action="" autocomplete="off">
    <div class="mb-3">
      <label for="first_name" class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" required
             value="<?= htmlspecialchars($fname) ?>" autocomplete="off">
    </div>
    <div class="mb-3">
      <label for="last_name" class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" required
             value="<?= htmlspecialchars($lname) ?>" autocomplete="off">
    </div>
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required
             value="<?= htmlspecialchars($username) ?>" autocomplete="new-username">
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" required
             value="<?= htmlspecialchars($email) ?>" autocomplete="off">
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required autocomplete="new-password">
    </div>
    <div class="mb-3">
      <label for="confirm_password" class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
    </div>
    <button type="submit" name="register" class="btn-coffee mt-2">Register</button>
  </form>

  <div class="text-center mt-4">
    Already have an account? <a href="Login.php">Login here</a>
  </div>
</div>

<?= $alert ?>

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

</body>
</html>
