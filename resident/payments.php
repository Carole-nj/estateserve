<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id = $_SESSION['user_id'];

$payments = $pdo->prepare("
    SELECT p.*, s.name AS service, b.booking_date, b.booking_time
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN services s ON b.service_id = s.id
    WHERE p.resident_id = ?
    ORDER BY p.paid_at DESC
");
$payments->execute([$user_id]);
$payments = $payments->fetchAll();

$total_spent = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE resident_id = ? AND status = 'success'");
$total_spent->execute([$user_id]);
$total_spent = $total_spent->fetchColumn();

$total_count = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE resident_id = ? AND status = 'success'");
$total_count->execute([$user_id]);
$total_count = $total_count->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Payments — EstateServe</title>
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
    .stat-card { border: none; border-radius: 12px; padding: 1.5rem; color: #fff; }
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .txn-code { font-family: monospace; background: #f0fdf4; color: #00A550; padding: .2rem .6rem; border-radius: 6px; font-size: .85rem; font-weight: 600; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand">🏠 EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Browse Services</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="payments.php"  class="nav-link active"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> My Reviews</a>
    <a href="profile.php"   class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h5 class="mb-0 fw-bold">My Payments</h5>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="stat-card" style="background:linear-gradient(135deg,#00A550,#007a3d)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Total Spent</div>
            <div class="fs-2 fw-bold">KES <?= number_format($total_spent, 0) ?></div>
          </div>
          <i class="bi bi-cash-stack fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="opacity-75 small mb-1">Successful Payments</div>
            <div class="fs-2 fw-bold"><?= $total_count ?></div>
          </div>
          <i class="bi bi-check-circle fs-2 opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Payments Table -->
  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
      <?php if (empty($payments)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-receipt fs-1 d-block mb-3"></i>
          <h5>No payments yet</h5>
          <p>Your payment history will appear here.</p>
          <a href="services.php" class="btn btn-success px-4">Book a Service</a>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">#</th>
              <th>Transaction Code</th>
              <th>Service</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td class="ps-4 text-muted"><?= $p['id'] ?></td>
              <td><span class="txn-code"><?= $p['transaction_code'] ?></span></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['service']) ?></div>
                <small class="text-muted">
                  <?= date('d M Y', strtotime($p['booking_date'])) ?>
                  at <?= date('h:i A', strtotime($p['booking_time'])) ?>
                </small>
              </td>
              <td><?= date('d M Y, h:i A', strtotime($p['paid_at'])) ?></td>
              <td class="fw-bold text-success">KES <?= number_format($p['amount'], 0) ?></td>
              <td>
                <?php if ($p['status'] === 'success'): ?>
                  <span class="badge bg-success px-2 py-1">Success</span>
                <?php elseif ($p['status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark px-2 py-1">Pending</span>
                <?php else: ?>
                  <span class="badge bg-danger px-2 py-1">Failed</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>