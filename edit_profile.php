<?php
session_start();
require_once('./classes/database.php');
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin.php");
    exit();
}

$admin_ID = $_SESSION['admin_ID'];
$admin = $db->getAdminById($admin_ID);

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['admin_FN'];
    $lastName  = $_POST['admin_LN'];
    $password  = $_POST['new_password'];
    $confirm   = $_POST['confirm_password'];

    if (!empty($password)) {
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $db->updateAdminPassword($admin_ID, $hashed);
            $success = "Password updated successfully.";
        }
    }

    if (!$error) {
        $updated = $db->updateAdminProfile($admin_ID, $firstName, $lastName);
        if ($updated) {
            $_SESSION['admin_FN'] = $firstName;
            $success = "Profile updated successfully.";
            $admin = $db->getAdminById($admin_ID);
        } else {
            $error = "Failed to update profile.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      background: url('uploads/bgg.jpg') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      backdrop-filter: blur(3px);
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .profile-container {
      background: rgba(255, 255, 255, 0.96);
      border-radius: 12px;
      padding: 30px;
      max-width: 500px;
      width: 100%;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      position: relative;
    }
    .profile-container h4 {
      margin-bottom: 20px;
      color: #343a40;
    }
    .back-btn {
      position: absolute;
      top: 15px;
      left: 15px;
      font-size: 1.1rem;
    }
    .btn-success {
      background-color: #28a745;
      border-color: #28a745;
    }
    .btn-success:hover {
      background-color: #218838;
    }
  </style>
</head>
<body>

<div class="profile-container">
  <a href="admin.php" class="btn btn-sm btn-outline-dark back-btn"><i class="fas fa-arrow-left"></i> Back</a>
  <h4 class="text-center"><i class="fas fa-user-cog"></i> Edit Profile</h4>

  <form method="POST">
    <div class="mb-3">
      <label for="admin_FN" class="form-label">First Name</label>
      <input type="text" name="admin_FN" class="form-control" value="<?= htmlspecialchars($admin['admin_FN']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="admin_LN" class="form-label">Last Name</label>
      <input type="text" name="admin_LN" class="form-control" value="<?= htmlspecialchars($admin['admin_LN']) ?>" required>
    </div>
    <hr>
    <h6>Change Password <small class="text-muted">(optional)</small></h6>
    <div class="mb-3">
      <label for="new_password" class="form-label">New Password</label>
      <input type="password" name="new_password" class="form-control">
    </div>
    <div class="mb-3">
      <label for="confirm_password" class="form-label">Confirm Password</label>
      <input type="password" name="confirm_password" class="form-control">
    </div>
    <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i> Update Profile</button>
  </form>
</div>

<?php if ($success): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Success',
  text: '<?= $success ?>',
  confirmButtonColor: '#198754'
});
</script>
<?php elseif ($error): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Error',
  text: '<?= $error ?>',
  confirmButtonColor: '#dc3545'
});
</script>
<?php endif; ?>

</body>
</html>
