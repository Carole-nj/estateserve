<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) redirectByRole();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $role      = $_POST['role'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $status = ($role === 'provider') ? 'pending' : 'active';
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$full_name, $email, $phone, $hashed, $role, $status]);
            $success = "Account created! " . ($role === 'provider' ? "Await admin approval before logging in." : "You can now log in.");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register — EstateServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; }
    .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
    .brand { color: #00A550; font-weight: 700; font-size: 1.8rem; }
    .btn-primary { background: #00A550; border: none; }
    .btn-primary:hover { background: #007a3d; }
    .form-control:focus, .form-select:focus { border-color: #00A550; box-shadow: 0 0 0 0.2rem rgba(0,165,80,0.25); }
    .link-green { color: #00A550; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card p-4">
          <div class="text-center mb-4">
            <div class="brand">EstateServe</div>
            <p class="text-muted mb-0">Create your account</p>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold">Full Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="full_name" class="form-control" placeholder="Caroline Njeri" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Phone Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Register as</label>
              <select name="role" class="form-select" required>
                <option value="">-- Select role --</option>
                <option value="resident">Resident / Tenant</option>
                <option value="provider">Service Provider</option>
                <option value="delivery">Delivery Personnel</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Confirm Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
          </form>

          <hr class="my-3">
          <p class="text-center mb-0 text-muted">
            Already have an account?
            <a href="login.php" class="link-green fw-semibold">Sign in</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>