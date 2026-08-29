<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) redirectByRole();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['email']     = $user['email'];
        redirectByRole();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — EstateServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
    .brand { color: #00A550; font-weight: 700; font-size: 1.8rem; }
    .btn-primary { background: #00A550; border: none; }
    .btn-primary:hover { background: #007a3d; }
    .form-control:focus { border-color: #00A550; box-shadow: 0 0 0 0.2rem rgba(0,165,80,0.25); }
    .link-green { color: #00A550; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card p-4">
          <div class="text-center mb-4">
            <div class="brand">EstateServe</div>
            <p class="text-muted mb-0">Sign in to your account</p>
          </div>

          <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label fw-semibold">Email address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
          </form>

          <hr class="my-3">
          <p class="text-center mb-0 text-muted">
            Don't have an account? 
            <a href="register.php" class="link-green fw-semibold">Register here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>