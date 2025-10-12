<?php
session_start();

    require_once('./classes/database.php');

    require_once (__DIR__. "/classes/config.php");
    $db = new database();

    if (isset($_SESSION['admin_ID'])) {
      header("Location: " . BASE_URL . "admin/admin.php");
exit();

}

$alert = '';

$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $db->loginAdmin_L($username, $password);

    if ($result === 'no_user') {
        $alert = "<script>Swal.fire('No Account Found', 'No admin found with this username.', 'warning');</script>";
    } elseif ($result === 'wrong_password') {
        $alert = "<script>Swal.fire('Login Failed', 'Incorrect password. Please try again.', 'error');</script>";
    } elseif (is_array($result)) {
        $_SESSION['admin_ID'] = $result['admin_id'];
        $_SESSION['admin_FN'] = $result['admin_FN'];

        header("Location: ".BASE_URL."admin/admin.php");

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
    <link rel="icon" type="image/svg+xml" href="uploads/icon.svg">
    <style>
    body {
        font-family: 'Quicksand', sans-serif;
        margin: 0; padding: 0;
        background: url('uploads/bgg.jpg') no-repeat center center fixed;
        background-size: cover;
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

    .login-container {
        width: 100%;
        max-width: 450px;
        background: rgba(255, 248, 230, 0.15);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 18px;
        padding: 40px 35px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.35);
        backdrop-filter: blur(10px);
        text-align: center;
    }

    .icon-box {
        font-size: 3rem;
        color: #b07542;
        margin-bottom: 10px;
    }

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
    .btn-coffee:hover { background-color: #8a5c33; }

    .text-center {
        color: #f0f0f0;
        margin-top: 15px;
    }
    .text-center a {
        color: #f2c9a0;
        font-weight: 600;
        text-decoration: none;
    }
    .text-center a:hover { text-decoration: underline; }

    @media (max-width: 576px) {
        .login-container { margin: 30px 15px; padding: 30px 25px; }
    }
</style>
</head>
<body>

<?= $alert ?>

<div class="login-container">
  <div class="icon-box"><i class="fas fa-user-shield"></i></div>
  <h2 class="title">Admin Login</h2>
  <form method="POST" action="" autocomplete="off">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input 
        type="text" 
        name="username" 
        class="form-control" 
        required 
        value="<?= htmlspecialchars($username) ?>" 
        autocomplete="off">
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
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
