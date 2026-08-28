<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id    = $_SESSION['user_id'];
$booking_id = isset($_GET['booking']) ? (int)$_GET['booking'] : 0;

// Get booking — must be completed and paid
$stmt = $pdo->prepare("
    SELECT b.*, s.name AS service, u.full_name AS provider, u.id AS provider_id
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.provider_id = u.id
    WHERE b.id = ? AND b.resident_id = ? AND b.status = 'completed'
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: http://localhost/estateserve/resident/bookings.php");
    exit();
}

// Check if already reviewed
$existing = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ? AND resident_id = ?");
$existing->execute([$booking_id, $user_id]);
$already_reviewed = $existing->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_reviewed) {
    $rating  = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, resident_id, provider_id, rating, comment) VALUES (?,?,?,?,?)");
        $stmt->execute([$booking_id, $user_id, $booking['provider_id'], $rating, $comment]);
        $success = "Review submitted successfully!";
        $already_reviewed = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Leave a Review — EstateServe</title>
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
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: .5rem; }
    .star-rating input { display: none; }
    .star-rating label { font-size: 2.5rem; color: #d1d5db; cursor: pointer; transition: color .15s; }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label { color: #f59e0b; }
    .form-control:focus { border-color: #00A550; box-shadow: 0 0 0 .2rem rgba(0,165,80,.25); }
    .btn-submit { background: #00A550; border: none; font-weight: 600; border-radius: 8px; }
    .btn-submit:hover { background: #007a3d; }
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
    <h5 class="mb-0 fw-bold">Leave a Review</h5>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-6">

      <!-- Booking Summary -->
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3">Booking Summary</h6>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Service</span>
            <span class="fw-semibold"><?= htmlspecialchars($booking['service']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Provider</span>
            <span class="fw-semibold"><?= htmlspecialchars($booking['provider']) ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Date</span>
            <span><?= date('d M Y', strtotime($booking['booking_date'])) ?></span>
          </div>
        </div>
      </div>

      <?php if ($already_reviewed && !$success): ?>
        <div class="alert alert-info text-center">
          <i class="bi bi-info-circle me-2"></i>
          You have already reviewed this booking.
          <div class="mt-2">
            <a href="bookings.php" class="btn btn-sm btn-outline-primary">Back to Bookings</a>
          </div>
        </div>

      <?php elseif ($success): ?>
        <div class="card border-0 shadow-sm rounded-3 text-center p-4">
          <i class="bi bi-star-fill text-warning fs-1 mb-3"></i>
          <h5 class="fw-bold">Thank you for your review!</h5>
          <p class="text-muted">Your feedback helps improve service quality for everyone.</p>
          <a href="bookings.php" class="btn btn-success px-4">Back to Bookings</a>
        </div>

      <?php else: ?>
        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-body p-4">
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-4 text-center">
                <label class="form-label fw-semibold d-block mb-3">
                  How would you rate this service?
                </label>
                <div class="star-rating">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
                  <label for="star<?= $i ?>">★</label>
                  <?php endfor; ?>
                </div>
                <div class="text-muted small mt-2">Click a star to rate</div>
              </div>

              <div class="mb-4">
                <label class="form-label fw-semibold">Your Review</label>
                <textarea name="comment" class="form-control" rows="4"
                  placeholder="Tell others about your experience with this service..."></textarea>
              </div>

              <div class="d-flex gap-2">
                <a href="bookings.php" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-submit text-white px-4 flex-grow-1">
                  <i class="bi bi-star me-2"></i>Submit Review
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>