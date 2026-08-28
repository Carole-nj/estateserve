<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('delivery');

$user_id = $_SESSION['user_id'];

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'in_progress'");
$stmt->execute([$user_id]);
$active = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE provider_id = ?");
$stmt->execute([$user_id]);
$total = $stmt->fetchColumn();

// Active deliveries
$stmt = $pdo->prepare("
    SELECT b.*, s.name AS service, u.full_name AS resident, 
           u.phone AS resident_phone
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.resident_id = u.id
    WHERE b.provider_id = ? AND b.status IN ('confirmed','in_progress')
    ORDER BY b.booking_date ASC
");
$stmt->execute([$user_id]);
$active_orders = $stmt->fetchAll();

// Completed deliveries
$stmt = $pdo->prepare("
    SELECT b.*, s.name AS service, u.full_name AS resident
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.resident_id = u.id
    WHERE b.provider_id = ? AND b.status = 'completed'
    ORDER BY b.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$completed_orders = $stmt->fetchAll();

// Notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delivery Dashboard — EstateServe</title>
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
    .order-card { border: none; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .order-card .card-body { padding: 1.25rem; }
    .badge-status-confirmed  { background: #dbeafe; color: #1e40af; }
    .badge-status-in_progress{ background: #ede9fe; color: #5b21b6; }
    .badge-status-completed  { background: #dcfce7; color: #166534; }
    .notif-item { background: #f0fdf4; border-left: 3px solid #00A550; border-radius: 6px; padding: .75rem 1rem; margin-bottom: .5rem; font-size: .875rem; }
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand">🏠 EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="orders.php"    class="nav-link"><i class="bi bi-box-seam"></i> All Orders</a>
    <a href="profile.php"   class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Delivery Dashboard</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if (count($notifs) > 0): ?>
        <span class="badge bg-danger"><?= count($notifs) ?> new</span>
      <?php endif; ?>
      <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card" style="background:linear-gradient(135deg,#00A550,#007a3d)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Total Orders</div>
            <div class="fs-2 fw-bold"><?= $total ?></div>
          </div>
          <i class="bi bi-box-seam fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Active</div>
            <div class="fs-2 fw-bold"><?= $active ?></div>
          </div>
          <i class="bi bi-truck fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-4">
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
  </div>

  <div class="row g-4">
    <!-- Active Orders -->
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-3">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-truck me-2 text-success"></i>Active Orders
          </h6>
        </div>
        <div class="card-body">
          <?php if (empty($active_orders)): ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-inbox fs-2 d-block mb-2"></i>
              No active orders right now.
            </div>
          <?php else: ?>
            <?php foreach ($active_orders as $o): ?>
            <div class="order-card card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($o['service']) ?></div>
                    <div class="text-muted small">
                      <i class="bi bi-person me-1"></i><?= htmlspecialchars($o['resident']) ?>
                      &nbsp;·&nbsp;
                      <i class="bi bi-phone me-1"></i><?= htmlspecialchars($o['resident_phone']) ?>
                    </div>
                  </div>
                  <span class="badge badge-status-<?= $o['status'] ?> px-2 py-1 rounded-pill">
                    <?= ucfirst(str_replace('_',' ',$o['status'])) ?>
                  </span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                  <i class="bi bi-geo-alt"></i>
                  <?= htmlspecialchars($o['address']) ?>
                </div>
                <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                  <i class="bi bi-calendar"></i>
                  <?= date('d M Y', strtotime($o['booking_date'])) ?>
                  at <?= date('h:i A', strtotime($o['booking_time'])) ?>
                </div>
                <div class="d-flex gap-2">
                  <?php if ($o['status'] === 'confirmed'): ?>
                    <a href="update_status.php?id=<?= $o['id'] ?>&status=in_progress"
                       class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-truck me-1"></i>Start Delivery
                    </a>
                  <?php elseif ($o['status'] === 'in_progress'): ?>
                    <a href="update_status.php?id=<?= $o['id'] ?>&status=completed"
                       class="btn btn-sm btn-success">
                      <i class="bi bi-check-circle me-1"></i>Mark Delivered
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Notifications + Completed -->
    <div class="col-md-5">

      <!-- Notifications -->
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between">
          <h6 class="fw-bold mb-0">Notifications</h6>
          <?php if (count($notifs) > 0): ?>
            <a href="mark_read.php" class="btn btn-sm btn-outline-success">Mark read</a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if (empty($notifs)): ?>
            <p class="text-muted text-center py-2">
              <i class="bi bi-bell-slash d-block fs-2 mb-1"></i>
              No new notifications
            </p>
          <?php else: ?>
            <?php foreach ($notifs as $n): ?>
            <div class="notif-item">
              <?= htmlspecialchars($n['message']) ?>
              <div><small class="text-muted"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></small></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Completed -->
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-3">
          <h6 class="fw-bold mb-0">Recently Completed</h6>
        </div>
        <div class="card-body p-0">
          <?php if (empty($completed_orders)): ?>
            <p class="text-muted text-center py-3">No completed orders yet.</p>
          <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($completed_orders as $o): ?>
            <li class="list-group-item px-3 py-3">
              <div class="fw-semibold small"><?= htmlspecialchars($o['service']) ?></div>
              <div class="text-muted" style="font-size:.75rem">
                <?= htmlspecialchars($o['resident']) ?> &nbsp;·&nbsp;
                <?= date('d M Y', strtotime($o['booking_date'])) ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>