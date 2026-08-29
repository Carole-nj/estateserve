<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id = $_SESSION['user_id'];

// Handle cancellation
if (isset($_GET['cancel'])) {
    $cancel_id = (int)$_GET['cancel'];
    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND resident_id = ? AND status = 'pending'")
        ->execute([$cancel_id, $user_id]);
    header("Location: http://localhost/estateserve/resident/bookings.php");
    exit();
}

// Filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where  = $filter !== 'all' ? "AND b.status = '$filter'" : '';

$stmt = $pdo->prepare("
    SELECT b.*, s.name AS service, s.category, s.price,
           u.full_name AS provider, u.phone AS provider_phone,
           p.transaction_code, p.status AS payment_status, p.amount
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.provider_id = u.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.resident_id = ? $where
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

$status_colors = [
    'pending'     => 'warning',
    'confirmed'   => 'primary',
    'in_progress' => 'info',
    'completed'   => 'success',
    'cancelled'   => 'danger',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Bookings — EstateServe</title>
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
    .booking-card { border: none; border-radius: 12px; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s; }
    .booking-card:hover { transform: translateY(-2px); }
    .category-badge { display: inline-block; padding: .25rem .75rem; border-radius: 99px; font-size: .75rem; font-weight: 600; background: #e8f5e9; color: #00A550; }
    .filter-btn { border-radius: 99px; font-size: .85rem; padding: .35rem 1rem; }
    .filter-btn.active { background: #00A550; color: #fff; border-color: #00A550; }
    .txn-code { font-family: monospace; background: #f0fdf4; color: #00A550; padding: .2rem .6rem; border-radius: 6px; font-size: .85rem; font-weight: 600; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Browse Services</a>
    <a href="bookings.php"  class="nav-link active"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> My Reviews</a>
    <a href="profile.php"   class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0 fw-bold">My Bookings</h5>
      <small class="text-muted"><?= count($bookings) ?> booking(s) found</small>
    </div>
    <a href="services.php" class="btn btn-success btn-sm px-3">
      <i class="bi bi-plus-lg me-1"></i>New Booking
    </a>
  </div>

  <!-- Filters -->
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach (['all','pending','confirmed','in_progress','completed','cancelled'] as $f): ?>
    <a href="?status=<?= $f ?>"
       class="btn btn-outline-secondary filter-btn <?= $filter === $f ? 'active' : '' ?>">
      <?= ucfirst(str_replace('_',' ',$f)) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
      <h5>No bookings found</h5>
      <p>You haven't made any bookings yet.</p>
      <a href="services.php" class="btn btn-success px-4">Book a Service</a>
    </div>
  <?php else: ?>
    <?php foreach ($bookings as $b): ?>
    <div class="booking-card card">
      <div class="card-body p-4">
        <div class="row align-items-start">
          <div class="col-md-6">
            <div class="d-flex align-items-start gap-3">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h6 class="fw-bold mb-0"><?= htmlspecialchars($b['service']) ?></h6>
                  <span class="category-badge"><?= str_replace('_',' ',ucfirst($b['category'])) ?></span>
                </div>
                <div class="text-muted small mb-1">
                  <i class="bi bi-person me-1"></i><?= htmlspecialchars($b['provider']) ?>
                  &nbsp;·&nbsp;
                  <i class="bi bi-phone me-1"></i><?= htmlspecialchars($b['provider_phone']) ?>
                </div>
                <div class="text-muted small mb-1">
                  <i class="bi bi-calendar me-1"></i><?= date('d M Y', strtotime($b['booking_date'])) ?>
                  &nbsp;at&nbsp;
                  <i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($b['booking_time'])) ?>
                </div>
                <div class="text-muted small">
                  <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($b['address']) ?>
                </div>
                <?php if ($b['notes']): ?>
                <div class="text-muted small mt-1">
                  <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($b['notes']) ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-md-3 mt-3 mt-md-0">
            <div class="small text-muted mb-1">Payment</div>
            <?php if ($b['transaction_code']): ?>
              <div class="txn-code"><?= $b['transaction_code'] ?></div>
              <div class="text-success small mt-1 fw-semibold">KES <?= number_format($b['amount'], 0) ?> paid</div>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Unpaid</span>
              <br>
              <a href="payment.php?booking=<?= $b['id'] ?>" class="btn btn-sm btn-success mt-1">Pay Now</a>
            <?php endif; ?>
          </div>
          <div class="col-md-3 mt-3 mt-md-0 text-md-end">
            <span class="badge bg-<?= $status_colors[$b['status']] ?> px-3 py-2 rounded-pill mb-2 d-inline-block">
              <?= ucfirst(str_replace('_',' ',$b['status'])) ?>
            </span>
            <div class="d-flex gap-2 justify-content-md-end mt-2">
              <?php if ($b['status'] === 'pending'): ?>
                <a href="?cancel=<?= $b['id'] ?>"
                   onclick="return confirm('Cancel this booking?')"
                   class="btn btn-sm btn-outline-danger">Cancel</a>
              <?php endif; ?>
              <?php if ($b['status'] === 'completed' && $b['transaction_code']): ?>
                <a href="review.php?booking=<?= $b['id'] ?>"
                   class="btn btn-sm btn-outline-warning">
                  <i class="bi bi-star me-1"></i>Review
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>