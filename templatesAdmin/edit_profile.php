<?php
session_start();
require_once('./classes/database.php');
include_once __DIR__. "/../classes/config.php";
$db = new Database();

if (!isset($_SESSION['admin_ID'])) {
    header("Location: admin.php");
    exit();
}

$admin_ID = $_SESSION['admin_ID'];
$admin = $db->getAdminById($admin_ID);
$adminName = htmlspecialchars($_SESSION['admin_FN'] ?? 'Admin');

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
<?php include ('templatesAdmin/header.php'); ?>

<div class="wrapper">

<?php include ('templatesAdmin/sidebar.php'); ?>

  <!-- Main -->
  <div class="main">
    <!-- Header -->
    <div class="admin-header d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <span class="toggle-btn d-lg-none text-dark" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>
        <h5 class="mb-0">Welcome, <?= $adminName ?></h5>
      </div>
      <span class="text-muted"><i class="fas fa-user-shield me-1"></i>Admin Panel</span>
    </div>

    <!-- Content -->
    <div class="dashboard-content">
      <div class="container-fluid">
        <h4 class="section-title"><i class="fas fa-user-cog me-2"></i>Edit Profile</h4>
        <div class="card p-4">
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
      </div>
    </div>
  </div>
</div>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
