<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// Fetch summary stats
$stats = [];

$stats['users']    = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$stats['bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$stats['pending']  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$stats['revenue']  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'success'")->fetchColumn();
$stats['providers']= $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'provider' AND status = 'pending'")->fetchColumn();

// Recent bookings
$recent = $pdo->query("
    SELECT b.id, u.full_name AS resident, s.name AS service, b.status, b.booking_date
    FROM bookings b
    JOIN users u ON b.resident_id = u.id
    JOIN services s ON b.service_id = s.id
    ORDER BY b.created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard — EstateServe</title>
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
    .stat-card { border: none; border-radius: 12px; padding: 1.5rem; color: #fff; }
    .topbar { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .badge-status-pending    { background: #fef3c7; color: #92400e; }
    .badge-status-confirmed  { background: #dbeafe; color: #1e40af; }
    .badge-status-completed  { background: #dcfce7; color: #166534; }
    .badge-status-cancelled  { background: #fee2e2; color: #991b1b; }
    .badge-status-in_progress{ background: #ede9fe; color: #5b21b6; }
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"     class="nav-link"><i class="bi bi-people"></i> Users</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> Bookings</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Services</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> Reviews</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<!-- Main Content -->
<div class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Admin Dashboard</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if ($stats['providers'] > 0): ?>
        <span class="badge bg-danger"><?= $stats['providers'] ?> provider(s) awaiting approval</span>
      <?php endif; ?>
      <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#00A550,#007a3d)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Total Users</div>
            <div class="fs-2 fw-bold"><?= $stats['users'] ?></div>
          </div>
          <i class="bi bi-people fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Total Bookings</div>
            <div class="fs-2 fw-bold"><?= $stats['bookings'] ?></div>
          </div>
          <i class="bi bi-calendar-check fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Pending Bookings</div>
            <div class="fs-2 fw-bold"><?= $stats['pending'] ?></div>
          </div>
          <i class="bi bi-hourglass-split fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Revenue (KES)</div>
            <div class="fs-2 fw-bold"><?= number_format($stats['revenue'], 0) ?></div>
          </div>
          <i class="bi bi-cash-stack fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Bookings -->
  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 pt-3 pb-0">
      <h6 class="fw-bold mb-0">Recent Bookings</h6>
    </div>
    <div class="card-body">
      <?php if (empty($recent)): ?>
        <p class="text-muted text-center py-3">No bookings yet.</p>
      <?php else: ?>
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Resident</th>
            <th>Service</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $b): ?>
          <tr>
            <td class="text-muted">#<?= $b['id'] ?></td>
            <td><?= htmlspecialchars($b['resident']) ?></td>
            <td><?= htmlspecialchars($b['service']) ?></td>
            <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
            <td>
              <span class="badge badge-status-<?= $b['status'] ?> px-3 py-2 rounded-pill">
                <?= ucfirst(str_replace('_',' ',$b['status'])) ?>
              </span>
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