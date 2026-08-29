<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id = $_SESSION['user_id'];

// Stats for this resident
$total_bookings    = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE resident_id = ?");
$total_bookings->execute([$user_id]);
$total_bookings    = $total_bookings->fetchColumn();

$active_bookings   = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE resident_id = ? AND status IN ('pending','confirmed','in_progress')");
$active_bookings->execute([$user_id]);
$active_bookings   = $active_bookings->fetchColumn();

$total_spent       = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE resident_id = ? AND status = 'success'");
$total_spent->execute([$user_id]);
$total_spent       = $total_spent->fetchColumn();

// Recent bookings
$stmt = $pdo->prepare("
    SELECT b.id, s.name AS service, s.category, b.status, b.booking_date, b.booking_time, p.status AS payment_status
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.resident_id = ?
    ORDER BY b.created_at DESC LIMIT 5
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// Available services
$services = $pdo->query("SELECT * FROM services WHERE status = 'available' ORDER BY category")->fetchAll();

$category_icons = [
    'laundry'       => 'bi-water',
    'car_washing'   => 'bi-car-front',
    'grocery'       => 'bi-cart3',
    'house_cleaning'=> 'bi-house-heart',
    'plumbing'      => 'bi-wrench-adjustable',
    'food_delivery' => 'bi-bag-heart',
    'salon'         => 'bi-scissors',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Dashboard — EstateServe</title>
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
    .service-card { border: none; border-radius: 12px; transition: transform .2s, box-shadow .2s; cursor: pointer; }
    .service-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
    .service-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: #e8f5e9; color: #00A550; }
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
    <a href="dashboard.php"  class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="services.php"   class="nav-link"><i class="bi bi-grid"></i> Browse Services</a>
    <a href="bookings.php"   class="nav-link"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="payments.php"   class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"    class="nav-link"><i class="bi bi-star"></i> My Reviews</a>
    <a href="profile.php"    class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php"  class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?>!</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
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
    <div class="col-md-4">
      <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Active Bookings</div>
            <div class="fs-2 fw-bold"><?= $active_bookings ?></div>
          </div>
          <i class="bi bi-hourglass-split fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Total Spent (KES)</div>
            <div class="fs-2 fw-bold"><?= number_format($total_spent, 0) ?></div>
          </div>
          <i class="bi bi-cash-stack fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Available Services -->
    <div class="col-md-7">
      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0">Available Services</h6>
          <a href="services.php" class="btn btn-sm btn-outline-success">View All</a>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ($services as $s): ?>
            <div class="col-6">
              <div class="service-card card p-3" onclick="window.location='book.php?service=<?= $s['id'] ?>'">
                <div class="d-flex align-items-center gap-3">
                  <div class="service-icon">
                    <i class="bi <?= $category_icons[$s['category']] ?? 'bi-star' ?>"></i>
                  </div>
                  <div>
                    <div class="fw-semibold small"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="text-success fw-bold">KES <?= number_format($s['price'], 0) ?></div>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="col-md-5">
      <div class="card border-0 shadow-sm rounded-3 h-100">
        <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0">Recent Bookings</h6>
          <a href="bookings.php" class="btn btn-sm btn-outline-success">View All</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($bookings)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
              No bookings yet.<br>
              <a href="services.php" class="btn btn-success btn-sm mt-2">Book a Service</a>
            </div>
          <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($bookings as $b): ?>
            <li class="list-group-item px-3 py-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold small"><?= htmlspecialchars($b['service']) ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= date('d M Y', strtotime($b['booking_date'])) ?> at <?= date('h:i A', strtotime($b['booking_time'])) ?></div>
                </div>
                <span class="badge badge-status-<?= $b['status'] ?> px-2 py-1 rounded-pill" style="font-size:.7rem">
                  <?= ucfirst(str_replace('_',' ',$b['status'])) ?>
                </span>
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