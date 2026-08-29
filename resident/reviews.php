<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, s.name AS service, u.full_name AS provider, b.booking_date
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN services s ON b.service_id = s.id
    JOIN users u ON r.provider_id = u.id
    WHERE r.resident_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Reviews — EstateServe</title>
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
    .review-card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1rem; }
    .stars { color: #f59e0b; font-size: 1.1rem; letter-spacing: 2px; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Browse Services</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link active"><i class="bi bi-star"></i> My Reviews</a>
    <a href="profile.php"   class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h5 class="mb-0 fw-bold">My Reviews</h5>
  </div>

  <?php if (empty($reviews)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-star fs-1 d-block mb-3"></i>
      <h5>No reviews yet</h5>
      <p>Complete a booking and leave a review for your service provider.</p>
      <a href="services.php" class="btn btn-success px-4">Book a Service</a>
    </div>
  <?php else: ?>
    <?php foreach ($reviews as $r): ?>
    <div class="review-card card">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($r['service']) ?></h6>
            <div class="text-muted small mb-2">
              <i class="bi bi-person me-1"></i><?= htmlspecialchars($r['provider']) ?>
              &nbsp;·&nbsp;
              <?= date('d M Y', strtotime($r['booking_date'])) ?>
            </div>
            <div class="stars mb-2">
              <?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?>
              <span class="text-muted small ms-1"><?= $r['rating'] ?>/5</span>
            </div>
            <?php if ($r['comment']): ?>
              <p class="text-muted mb-0">"<?= htmlspecialchars($r['comment']) ?>"</p>
            <?php endif; ?>
          </div>
          <small class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>