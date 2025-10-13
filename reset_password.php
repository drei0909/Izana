<?php
session_start();
require_once __DIR__ . '/classes/database.php';
$db = new Database();

// Get token from GET
$token = trim($_GET['token'] ?? '');

if ($token === '') {
    // Friendly page if token missing
    http_response_code(400);
    echo "<h2>Invalid or missing token.</h2>";
    echo "<p>Please request a new password reset from the login page.</p>";
    exit;
}

// Validate token exists and not expired
$stmt = $db->conn->prepare("
  SELECT pr.customer_id, pr.expires_at, c.customer_FN, c.customer_email
  FROM password_resets pr
  JOIN customer c ON pr.customer_id = c.customer_id
  WHERE pr.token = ?
  LIMIT 1
");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    http_response_code(404);
    echo "<h2>Invalid token.</h2><p>That reset link is invalid. Request a new one.</p>";
    exit;
}

if (strtotime($reset['expires_at']) < time()) {
    // token expired
    // optionally delete this token
    $db->conn->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
    http_response_code(410);
    echo "<h2>Reset link expired.</h2><p>Please request a new password reset.</p>";
    exit;
}

// If valid, show reset form (HTML)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Reset Password | Izana</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  body{font-family:Quicksand, sans-serif; background:#0f0f0f; color:#fff; display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
  .box{background:rgba(255,248,230,0.12);padding:30px;border-radius:12px;width:100%;max-width:420px}
  .form-control{border-radius:30px}
  .btn-coffee{background:#b07542;color:#fff;border-radius:30px;border:none;padding:10px 16px}
</style>
</head>
<body>
  <div class="box text-center">
    <h3>Reset your password</h3>
    <p style="color:#f2c9a0">Account: <?= htmlspecialchars($reset['customer_email']) ?></p>

    <form id="resetForm">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="mb-3 text-start">
        <label class="form-label">New password</label>
        <input type="password" name="new_password" class="form-control" required minlength="6">
      </div>
      <div class="mb-3 text-start">
        <label class="form-label">Confirm password</label>
        <input type="password" name="confirm_password" class="form-control" required minlength="6">
      </div>
      <button type="submit" class="btn-coffee w-100">Update Password</button>
    </form>
  </div>

<script>
$('#resetForm').on('submit', function(e){
  e.preventDefault();
  const data = $(this).serialize() + '&ref=reset_password';
  $.ajax({
    url: 'functions.php',
    type: 'POST',
    data: data,
    dataType: 'json',
    success: function(res){
      if (res.status === 'success') {
        Swal.fire('Done', res.message, 'success').then(()=> window.location.href = 'login.php');
      } else {
        Swal.fire('Error', res.message || 'Something went wrong', 'error');
      }
    },
    error: function(){
      Swal.fire('Error','Request failed','error');
    }
  });
});
</script>
</body>
</html>
