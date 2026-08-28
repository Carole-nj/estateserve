<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id    = $_SESSION['user_id'];
$booking_id = isset($_GET['booking']) ? (int)$_GET['booking'] : 0;

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, s.name AS service, s.price, u.full_name AS provider
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.provider_id = u.id
    WHERE b.id = ? AND b.resident_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit();
}

$total = $booking['price'] + 50;
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);

    // Simulate payment — generate fake transaction code
    $transaction_code = 'ES' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Save payment record
    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, resident_id, amount, phone, transaction_code, status) VALUES (?,?,?,?,?,'success')");
    $stmt->execute([$booking_id, $user_id, $total, $phone, $transaction_code]);

    // Update booking status
    $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking_id]);

    // Save notification for provider
    $msg = "New booking confirmed for " . $booking['service'] . " on " . date('d M Y', strtotime($booking['booking_date']));
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")->execute([$booking['provider_id'], $msg]);

    $success = $transaction_code;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment — EstateServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f0f4f8; }
    .sidebar { background: #0f172a; min-height: 100vh; width: 240px; position: fixed; top: 0; left: 0; padding: 1.5rem 1rem; }
    .sidebar .brand { color: #00A550; font-weight: 700; font-size: 1.3rem; margin-bottom: 2rem; display: block; }
    .sidebar .nav-link { color: #94a3b8; border-radius: 8px; padding: .6rem 1rem; margin-bottom: .2rem; }
    .sidebar .nav-link:hover { background: #1e293b; color: #fff; }
    .sidebar .nav-link i { margin-right: 8px; }
    .main { margin-left: 240px; padding: 2rem; }
    .topbar { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .mpesa-card { border: 2px solid #00A550; border-radius: 16px; }
    .mpesa-header { background: linear-gradient(135deg,#00A550,#007a3d); border-radius: 12px 12px 0 0; padding: 1.5rem; color:#fff; text-align:center; }
    .form-control:focus { border-color: #00A550; box-shadow: 0 0 0 .2rem rgba(0,165,80,.25); }
    .btn-pay { background: #00A550; border: none; padding: .75rem; font-weight: 700; font-size: 1.1rem; border-radius: 8px; }
    .btn-pay:hover { background: #007a3d; }
    .success-box { background: #dcfce7; border: 2px solid #00A550; border-radius: 16px; padding: 2rem; text-align: center; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand">🏠 EstateServe</span>
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
  <div class="topbar">
    <h5 class="mb-0 fw-bold">Complete Payment</h5>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-6">

      <?php if ($success): ?>
      <!-- Success Screen -->
      <div class="success-box">
        <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
        <h4 class="fw-bold mt-3 text-success">Payment Successful!</h4>
        <p class="text-muted">Your booking has been confirmed.</p>
        <div class="bg-white rounded-3 p-3 mb-3">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Service</span>
            <span class="fw-semibold"><?= htmlspecialchars($booking['service']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Provider</span>
            <span class="fw-semibold"><?= htmlspecialchars($booking['provider']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Date</span>
            <span class="fw-semibold"><?= date('d M Y', strtotime($booking['booking_date'])) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Amount Paid</span>
            <span class="fw-bold text-success">KES <?= number_format($total, 0) ?></span>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Transaction Code</span>
            <span class="fw-bold text-success"><?= $success ?></span>
          </div>
        </div>
        <a href="bookings.php" class="btn btn-success px-4">
          <i class="bi bi-calendar-check me-2"></i>View My Bookings
        </a>
      </div>

      <?php else: ?>
      <!-- Payment Form -->
      <div class="card mpesa-card border-0 shadow-sm">
        <div class="mpesa-header">
          <div class="fw-bold fs-5 mb-1">📱 Simulated M-Pesa Payment</div>
          <div class="opacity-75 small">Enter your phone number to complete payment</div>
        </div>
        <div class="card-body p-4">
          <!-- Order Summary -->
          <div class="bg-light rounded-3 p-3 mb-4">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Service</span>
              <span class="fw-semibold"><?= htmlspecialchars($booking['service']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Provider</span>
              <span><?= htmlspecialchars($booking['provider']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Date</span>
              <span><?= date('d M Y', strtotime($booking['booking_date'])) ?> at <?= date('h:i A', strtotime($booking['booking_time'])) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Service fee</span>
              <span>KES 50</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fw-bold">
              <span>Total</span>
              <span class="text-success fs-5">KES <?= number_format($total, 0) ?></span>
            </div>
          </div>

          <form method="POST">
            <div class="mb-4">
              <label class="form-label fw-semibold">M-Pesa Phone Number</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                <input type="text" name="phone" class="form-control"
                  placeholder="e.g. 0712345678" required
                  value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
              </div>
              <div class="form-text">
                <i class="bi bi-info-circle me-1"></i>
                This is a simulated payment — no real money will be deducted.
              </div>
            </div>
            <button type="submit" class="btn btn-pay text-white w-100">
              <i class="bi bi-phone me-2"></i>Pay KES <?= number_format($total, 0) ?>
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>