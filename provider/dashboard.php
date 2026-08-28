<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('provider');

$user_id = $_SESSION['user_id'];

// Stats
$total_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ?");
$total_bookings->execute([$user_id]);
$total_bookings = $total_bookings->fetchColumn();

$pending_bookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'pending'");
$pending_bookings->execute([$user_id]);
$pending_bookings = $pending_bookings->fetchColumn();

$completed = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'completed'");
$completed->execute([$user_id]);
$completed = $completed->fetchColumn();

$earnings = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON p.booking_id = b.id WHERE b.provider_id = ? AND p.status = 'success'");
$earnings->execute([$user_id]);
$earnings = $earnings->fetchColumn();

// Recent bookings
$stmt = $pdo->prepare("
    SELECT b.*, s.name AS service, s.price, u.full_name AS resident, u.phone AS resident_phone
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.resident_id = u.id
    WHERE b.provider_id = ?
    ORDER BY b.created_at DESC LIMIT 10
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Notifications
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$notifs->execute([$user_id]);
$notifs = $notifs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Provider Dashboard — EstateServe</title>
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
    .notif-item { background: #f0fdf4; border-left: 3px solid #00A550; border-radius: 6px; padding: .75rem 1rem; margin-bottom: .5rem; font-size: .875rem; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand">🏠 EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php"  class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="bookings.php"   class="nav-link"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="earnings.php"   class="nav-link"><i class="bi bi-cash-stack"></i> Earnings</a>
    <a href="profile.php"    class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php"  class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Provider Dashboard</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if (count($notifs) > 0): ?>
        <span class="badge bg-danger"><?= count($notifs) ?> new notification(s)</span>
      <?php endif; ?>
      <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#00A550,#007a3d)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Total Bookings</div>
            <div class="fs-2 fw-bold"><?= $total_bookings ?></div>
          </div>
          <i class="bi bi-calendar-check fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Pending</div>
            <div class="fs-2 fw-bold"><?= $pending_bookings ?></div>
          </div>
          <i class="bi bi-hourglass-split fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Completed</div>
            <div class="fs-2 fw-bold"><?= $completed ?></div>
          </div>
          <i class="bi bi-check-circle fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Earnings (KES)</div>
            <div class="fs-2 fw-bold"><?= number_format($earnings, 0) ?></div>
          </div>
          <i class="bi bi-cash-stack fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Bookings Table -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-3">
          <h6 class="fw-bold mb-0">Recent Bookings</h6>
        </div>
        <div class="card-body p-0">
          <?php if (empty($bookings)): ?>
            <p class="text-center text-muted py-4">No bookings assigned yet.</p>
          <?php else: ?>
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Resident</th>
                <th>Service</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $b): ?>
              <tr>
                <td class="text-muted">#<?= $b['id'] ?></td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($b['resident']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($b['resident_phone']) ?></small>
                </td>
                <td><?= htmlspecialchars($b['service']) ?></td>
                <td>
                  <div><?= date('d M Y', strtotime($b['booking_date'])) ?></div>
                  <small class="text-muted"><?= date('h:i A', strtotime($b['booking_time'])) ?></small>
                </td>
                <td>
                  <span class="badge badge-status-<?= $b['status'] ?> px-2 py-1 rounded-pill">
                    <?= ucfirst(str_replace('_',' ',$b['status'])) ?>
                  </span>
                </td>
                <td>
                  <?php if ($b['status'] === 'confirmed'): ?>
                    <a href="update_status.php?id=<?= $b['id'] ?>&status=in_progress" class="btn btn-sm btn-outline-primary">Start</a>
                  <?php elseif ($b['status'] === 'in_progress'): ?>
                    <a href="update_status.php?id=<?= $b['id'] ?>&status=completed" class="btn btn-sm btn-success">Complete</a>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm rounded-3 h-100">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between">
          <h6 class="fw-bold mb-0">Notifications</h6>
          <?php if (count($notifs) > 0): ?>
            <a href="mark_read.php" class="btn btn-sm btn-outline-success">Mark all read</a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if (empty($notifs)): ?>
            <p class="text-muted text-center py-3">
              <i class="bi bi-bell-slash d-block fs-2 mb-2"></i>
              No new notifications
            </p>
          <?php else: ?>
            <?php foreach ($notifs as $n): ?>
            <div class="notif-item">
              <div><?= htmlspecialchars($n['message']) ?></div>
              <small class="text-muted"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>