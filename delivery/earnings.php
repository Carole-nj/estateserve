<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('delivery');

$user_id = $_SESSION['user_id'];

// Totals (payments on orders handled by this delivery person)
$total = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0)
    FROM payments p JOIN bookings b ON p.booking_id = b.id
    WHERE b.provider_id = ? AND p.status = 'success'");
$total->execute([$user_id]);
$total = $total->fetchColumn();

$this_month = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0)
    FROM payments p JOIN bookings b ON p.booking_id = b.id
    WHERE b.provider_id = ? AND p.status = 'success'
      AND MONTH(p.paid_at) = MONTH(CURDATE()) AND YEAR(p.paid_at) = YEAR(CURDATE())");
$this_month->execute([$user_id]);
$this_month = $this_month->fetchColumn();

$paid_orders = $pdo->prepare("SELECT COUNT(*)
    FROM payments p JOIN bookings b ON p.booking_id = b.id
    WHERE b.provider_id = ? AND p.status = 'success'");
$paid_orders->execute([$user_id]);
$paid_orders = $paid_orders->fetchColumn();

$avg_order = $paid_orders > 0 ? $total / $paid_orders : 0;

// Revenue history
$stmt = $pdo->prepare("
    SELECT p.*, s.name AS service, b.booking_date, b.booking_time, u.full_name AS resident
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN services s ON b.service_id = s.id
    JOIN users u    ON p.resident_id = u.id
    WHERE b.provider_id = ? AND p.status = 'success'
    ORDER BY p.paid_at DESC
");
$stmt->execute([$user_id]);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Revenue — EstateServe</title>
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
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .txn-code { font-family: monospace; font-size: .85rem; background: #f1f5f9; padding: .15rem .4rem; border-radius: 4px; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="orders.php"    class="nav-link"><i class="bi bi-box-seam"></i> All Orders</a>
    <a href="earnings.php"  class="nav-link active"><i class="bi bi-cash-stack"></i> Revenue</a>
    <a href="profile.php"   class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">Revenue</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#00A550,#007a3d)">
        <div class="opacity-75 small mb-1">Total Revenue (KES)</div>
        <div class="fs-2 fw-bold"><?= number_format($total, 0) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
        <div class="opacity-75 small mb-1">This Month (KES)</div>
        <div class="fs-2 fw-bold"><?= number_format($this_month, 0) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
        <div class="opacity-75 small mb-1">Paid Orders</div>
        <div class="fs-2 fw-bold"><?= $paid_orders ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
        <div class="opacity-75 small mb-1">Avg / Order (KES)</div>
        <div class="fs-2 fw-bold"><?= number_format($avg_order, 0) ?></div>
      </div>
    </div>
  </div>

  <!-- Revenue history -->
  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 pt-3">
      <h6 class="fw-bold mb-0">Revenue History</h6>
    </div>
    <div class="card-body p-0">
      <?php if (empty($payments)): ?>
        <p class="text-center text-muted py-4">No revenue recorded yet.</p>
      <?php else: ?>
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Txn Code</th>
            <th>Service</th>
            <th>Resident</th>
            <th>Order Date</th>
            <th>Paid At</th>
            <th class="text-end pe-4">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
          <tr>
            <td class="ps-4"><span class="txn-code"><?= htmlspecialchars($p['transaction_code']) ?></span></td>
            <td><?= htmlspecialchars($p['service']) ?></td>
            <td><?= htmlspecialchars($p['resident']) ?></td>
            <td>
              <div><?= date('d M Y', strtotime($p['booking_date'])) ?></div>
              <small class="text-muted"><?= date('h:i A', strtotime($p['booking_time'])) ?></small>
            </td>
            <td><?= date('d M Y, h:i A', strtotime($p['paid_at'])) ?></td>
            <td class="text-end pe-4 fw-bold text-success">KES <?= number_format($p['amount'], 0) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="5" class="text-end">Total</th>
            <th class="text-end pe-4 text-success">KES <?= number_format($total, 0) ?></th>
          </tr>
        </tfoot>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
