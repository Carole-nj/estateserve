<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id = $_SESSION['user_id'];
$service_id = isset($_GET['service']) ? (int)$_GET['service'] : 0;
$error   = '';
$success = '';

// Get the service
$stmt = $pdo->prepare("SELECT s.*, u.full_name AS provider_name FROM services s LEFT JOIN users u ON s.provider_id = u.id WHERE s.id = ? AND s.status = 'available'");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    header("Location: services.php");
    exit();
}

// Get available providers for this category
$providers = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'provider' AND status = 'active'");
$providers->execute();
$providers = $providers->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_id  = (int)$_POST['provider_id'];
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $address      = trim($_POST['address']);
    $notes        = trim($_POST['notes']);

    if (!$provider_id || !$booking_date || !$booking_time || !$address) {
        $error = "Please fill in all required fields.";
    } elseif (strtotime($booking_date) < strtotime('today')) {
        $error = "Booking date cannot be in the past.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookings (resident_id, service_id, provider_id, booking_date, booking_time, address, notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$user_id, $service_id, $provider_id, $booking_date, $booking_time, $address, $notes]);
        $booking_id = $pdo->lastInsertId();
        header("Location: payment.php?booking=$booking_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Service — EstateServe</title>
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
    .service-banner { background: linear-gradient(135deg,#00A550,#007a3d); border-radius: 12px; padding: 1.5rem; color: #fff; margin-bottom: 1.5rem; }
    .form-label { font-weight: 600; font-size: .9rem; }
    .btn-book { background: #00A550; border: none; padding: .75rem 2rem; font-weight: 600; border-radius: 8px; }
    .btn-book:hover { background: #007a3d; }
    .form-control:focus, .form-select:focus { border-color: #00A550; box-shadow: 0 0 0 .2rem rgba(0,165,80,.25); }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand">🏠 EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="services.php"  class="nav-link active"><i class="bi bi-grid"></i> Browse Services</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> My Reviews</a>
    <a href="profile.php"   class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h5 class="mb-0 fw-bold">Book a Service</h5>
  </div>

  <!-- Service Banner -->
  <div class="service-banner">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($service['name']) ?></h4>
        <p class="mb-0 opacity-75"><?= htmlspecialchars($service['description']) ?></p>
      </div>
      <div class="text-end">
        <div class="opacity-75 small">Price</div>
        <div class="fs-3 fw-bold">KES <?= number_format($service['price'], 0) ?></div>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-4">
      <form method="POST">
        <div class="row g-3">

          <div class="col-md-12">
            <label class="form-label">Select Service Provider</label>
            <select name="provider_id" class="form-select" required>
              <option value="">-- Choose a provider --</option>
              <?php foreach ($providers as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($providers)): ?>
              <div class="text-warning small mt-1">
                <i class="bi bi-exclamation-triangle me-1"></i>
                No providers available yet. Admin needs to approve providers first.
              </div>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label">Booking Date</label>
            <input type="date" name="booking_date" class="form-control"
              min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Preferred Time</label>
            <input type="time" name="booking_time" class="form-control" required>
          </div>

          <div class="col-md-12">
            <label class="form-label">Service Address</label>
            <input type="text" name="address" class="form-control"
              placeholder="e.g. House 4B, Greenpark Estate, Rongai" required>
          </div>

          <div class="col-md-12">
            <label class="form-label">Additional Notes <span class="text-muted fw-normal">(optional)</span></label>
            <textarea name="notes" class="form-control" rows="3"
              placeholder="Any special instructions for the provider..."></textarea>
          </div>

          <!-- Order Summary -->
          <div class="col-12">
            <div class="bg-light rounded-3 p-3">
              <h6 class="fw-bold mb-3">Order Summary</h6>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted"><?= htmlspecialchars($service['name']) ?></span>
                <span>KES <?= number_format($service['price'], 0) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Service Fee</span>
                <span>KES 50</span>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span class="text-success">KES <?= number_format($service['price'] + 50, 0) ?></span>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex gap-2">
            <a href="services.php" class="btn btn-outline-secondary px-4">
              <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="btn btn-book text-white px-4">
              <i class="bi bi-calendar-plus me-2"></i>Confirm Booking & Pay
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>
</div>

</body>
</html>