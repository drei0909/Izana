<?php
session_start();
require_once('./classes/database.php');
$db = new database();
$alert = '';

$username = ''; // default empty

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username']; // keep the typed username
    $password = $_POST['password'];

    $result = $db->loginAdmin_L($username, $password);

    if ($result === 'no_user') {
        $alert = "<script>Swal.fire('No Account Found', 'No admin found with this username.', 'warning');</script>";
    } elseif ($result === 'wrong_password') {
        $alert = "<script>Swal.fire('Login Failed', 'Incorrect password. Please try again.', 'error');</script>";
    } elseif (is_array($result)) {
        $_SESSION['admin_ID'] = $result['admin_id'];
        $_SESSION['admin_FN'] = $result['admin_FN'];
        header("Location: admin.php"); // Admin dashboard
        exit();
    } else {
        $alert = "<script>Swal.fire('Error', 'Unexpected error occurred. Please try again.', 'error');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login | Izana</title>
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
    }

    .login-container {
      max-width: 500px;
      margin: 80px auto;
      background: rgba(255, 248, 230, 0.15);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      border-radius: 18px;
      padding: 40px 35px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.3);
      backdrop-filter: blur(8px);
    }

    .title {
      font-family: 'Playfair Display', serif;
      font-size: 2.3rem;
      text-align: center;
      color: #fff8f3;
      margin-bottom: 25px;
      text-shadow: 1px 1px 0 #f2e1c9;
    }

    .icon-box {
      text-align: center;
      font-size: 3rem;
      color: #b07542;
      margin-bottom: 10px;
    }

    .form-label {
      font-weight: 600;
      color: #f5e9dc;
    }

    .form-control {
      border-radius: 30px;
      padding: 12px;
      background-color: #fffdf7;
      border: 1px solid #d2b79e;
      color: #4b3a2f;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(166, 124, 82, 0.25);
      border-color: #b4875b;
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

    .btn-coffee:hover {
      background-color: #8a5c33;
    }

    .back-home {
      position: absolute;
      top: 20px;
      left: 20px;
      background: transparent;
      border: 2px solid white;
      color: white;
      padding: 8px 18px;
      border-radius: 30px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease-in-out;
    }

    .back-home:hover {
      background: rgba(255, 255, 255, 0.15);
      color: white;
    }
  </style>
</head>
<body>

<?= $alert ?>

<a href="home.php" class="back-home"><i class="fas fa-home me-2"></i>Home</a>

<div class="login-container">
  <div class="icon-box">
    <i class="fas fa-user-shield"></i>
  </div>
  <h2 class="title">Admin Login</h2>
  <form method="POST" action="" autocomplete="off">
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input 
        type="text" 
        name="username" 
        class="form-control" 
        required 
        autocomplete="new-username"
        value="<?= htmlspecialchars($username) ?>">
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input 
        type="password" 
        name="password" 
        class="form-control" 
        required 
        autocomplete="new-password">
    </div>
    <button type="submit" name="login" class="btn-coffee mt-2">Login</button>
  </form>
</div>

</body>
</html>
