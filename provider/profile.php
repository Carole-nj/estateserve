<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$error   = '';
$success = '';

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);

    if (!$full_name || !$phone) {
        $error = "Name and phone cannot be empty.";
    } else {
        $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?")
            ->execute([$full_name, $phone, $user_id]);
        $_SESSION['full_name'] = $full_name;
        $success = "Profile updated successfully!";
        $user['full_name'] = $full_name;
        $user['phone']     = $phone;
    }
}

// Handle password change
if (isset($_POST['action']) && $_POST['action'] === 'password') {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
        $success = "Password changed successfully!";
    }
}

// Sidebar links per role
$nav_links = [
    'resident' => [
        ['href'=>'dashboard.php', 'icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
        ['href'=>'services.php',  'icon'=>'bi-grid',         'label'=>'Browse Services'],
        ['href'=>'bookings.php',  'icon'=>'bi-calendar-check','label'=>'My Bookings'],
        ['href'=>'payments.php',  'icon'=>'bi-cash-stack',   'label'=>'Payments'],
        ['href'=>'reviews.php',   'icon'=>'bi-star',         'label'=>'My Reviews'],
        ['href'=>'profile.php',   'icon'=>'bi-person',       'label'=>'Profile'],
    ],
    'provider' => [
        ['href'=>'dashboard.php', 'icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
        ['href'=>'bookings.php',  'icon'=>'bi-calendar-check','label'=>'My Bookings'],
        ['href'=>'earnings.php',  'icon'=>'bi-cash-stack',   'label'=>'Earnings'],
        ['href'=>'profile.php',   'icon'=>'bi-person',       'label'=>'Profile'],
    ],
    'delivery' => [
        ['href'=>'dashboard.php', 'icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
        ['href'=>'orders.php',    'icon'=>'bi-box-seam',     'label'=>'All Orders'],
        ['href'=>'profile.php',   'icon'=>'bi-person',       'label'=>'Profile'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile — EstateServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f0f4f8; }
    .sidebar { background: #0f172a; min-height: 100vh; width: 240px; position: fixed; top: 0; left: 0; padding: 1.5rem 1rem; }
    .sidebar .brand { color: #00A550; font-weight: 700; font-size: 1.3rem; margin-bottom: 2rem; display: block; }
    .sidebar .nav-link { color: #94a3b8; border-radius: 8px; padding: .6rem 1rem; margin-bottom: .2rem; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #1e293b; color: #fff; }
    .sidebar .nav-link i { margin-right: 8px; }
    .main { margin-left: 240px; padding: 2rem; }
    .topbar { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .avatar-lg { width: 80px; height: 80px; border-radius: 50%; background: #00A550; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; }
    .form-control:focus { border-color: #00A550; box-shadow: 0 0 0 .2rem rgba(0,165,80,.25); }
    .btn-save { background: #00A550; border: none; font-weight: 600; border-radius: 8px; }
    .btn-save:hover { background: #007a3d; }
    .role-badge { display: inline-block; padding: .35rem 1rem; border-radius: 99px; font-size: .85rem; font-weight: 600; background: #e8f5e9; color: #00A550; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <?php foreach ($nav_links[$role] ?? [] as $link): ?>
    <a href="<?= $link['href'] ?>"
       class="nav-link <?= basename($_SERVER['PHP_SELF']) === $link['href'] ? 'active' : '' ?>">
      <i class="bi <?= $link['icon'] ?>"></i> <?= $link['label'] ?>
    </a>
    <?php endforeach; ?>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h5 class="mb-0 fw-bold">My Profile</h5>
  </div>

  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- Profile Info -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center gap-4 mb-4">
            <div class="avatar-lg">
              <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div>
              <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
              <div class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></div>
              <span class="role-badge"><?= ucfirst($user['role']) ?></span>
            </div>
          </div>

          <h6 class="fw-bold mb-3">Edit Profile</h6>
          <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control"
                  value="<?= htmlspecialchars($user['full_name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control"
                  value="<?= htmlspecialchars($user['phone']) ?>" required>
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control"
                  value="<?= htmlspecialchars($user['email']) ?>" disabled>
                <div class="form-text">Email cannot be changed.</div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-save text-white px-4">
                  <i class="bi bi-check-lg me-2"></i>Save Changes
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3">Change Password</h6>
          <form method="POST">
            <input type="hidden" name="action" value="password">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label fw-semibold">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">New Password</label>
                <input type="password" name="new_password" class="form-control"
                  placeholder="Min. 6 characters" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-outline-danger px-4">
                  <i class="bi bi-shield-lock me-2"></i>Change Password
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Account Summary -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3">Account Details</h6>
          <div class="mb-3">
            <div class="text-muted small">Member Since</div>
            <div class="fw-semibold"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
          </div>
          <div class="mb-3">
            <div class="text-muted small">Account Status</div>
            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'warning' ?>">
              <?= ucfirst($user['status']) ?>
            </span>
          </div>
          <div class="mb-3">
            <div class="text-muted small">Role</div>
            <div class="fw-semibold"><?= ucfirst($user['role']) ?></div>
          </div>
          <div>
            <div class="text-muted small">Email</div>
            <div class="fw-semibold"><?= htmlspecialchars($user['email']) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>