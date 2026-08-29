<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// Handle status toggle
if (isset($_GET['toggle'])) {
    $toggle_id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$toggle_id]);
    $current = $stmt->fetchColumn();
    $new_status = $current === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $toggle_id]);
    header("Location: http://localhost/estateserve/admin/users.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$delete_id]);
    header("Location: http://localhost/estateserve/admin/users.php");
    exit();
}

$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$where = $role_filter !== 'all' ? "WHERE role = '$role_filter'" : "WHERE role != 'admin'";

$users = $pdo->query("SELECT * FROM users $where ORDER BY created_at DESC")->fetchAll();

$role_colors = [
    'resident' => 'success',
    'provider' => 'primary',
    'delivery' => 'info',
    'admin'    => 'danger',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Users — EstateServe</title>
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
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .filter-btn { border-radius: 99px; font-size: .85rem; padding: .35rem 1rem; }
    .filter-btn.active { background: #00A550; color: #fff; border-color: #00A550; }
    .avatar { width: 36px; height: 36px; border-radius: 50%; background: #e8f5e9; color: #00A550; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; flex-shrink: 0; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"     class="nav-link active"><i class="bi bi-people"></i> Users</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> Bookings</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Services</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> Reviews</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0 fw-bold">Manage Users</h5>
      <small class="text-muted"><?= count($users) ?> user(s) found</small>
    </div>
  </div>

  <!-- Filters -->
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach (['all','resident','provider','delivery'] as $r): ?>
    <a href="?role=<?= $r ?>"
       class="btn btn-outline-secondary filter-btn <?= $role_filter === $r ? 'active' : '' ?>">
      <?= ucfirst($r) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
      <?php if (empty($users)): ?>
        <p class="text-center text-muted py-4">No users found.</p>
      <?php else: ?>
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">User</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar"><?= strtoupper(substr($u['full_name'], 0, 1)) ?></div>
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($u['phone']) ?></td>
            <td>
              <span class="badge bg-<?= $role_colors[$u['role']] ?? 'secondary' ?> px-2 py-1">
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td>
              <?php if ($u['status'] === 'active'): ?>
                <span class="badge bg-success px-2 py-1">Active</span>
              <?php elseif ($u['status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark px-2 py-1">Pending</span>
              <?php else: ?>
                <span class="badge bg-danger px-2 py-1">Inactive</span>
              <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <a href="?toggle=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-<?= $u['status'] === 'active' ? 'warning' : 'success' ?>"
                   title="<?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                  <i class="bi bi-<?= $u['status'] === 'active' ? 'pause-circle' : 'play-circle' ?>"></i>
                </a>
                <a href="?delete=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete <?= htmlspecialchars($u['full_name']) ?>? This cannot be undone.')"
                   title="Delete">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>