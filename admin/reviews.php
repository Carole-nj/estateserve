<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: http://localhost/estateserve/admin/reviews.php");
    exit();
}

$reviews = $pdo->query("
    SELECT r.*,
           res.full_name AS resident,
           prov.full_name AS provider,
           s.name AS service,
           b.booking_date
    FROM reviews r
    JOIN users res  ON r.resident_id  = res.id
    JOIN users prov ON r.provider_id  = prov.id
    JOIN bookings b ON r.booking_id   = b.id
    JOIN services s ON b.service_id   = s.id
    ORDER BY r.created_at DESC
")->fetchAll();

$avg_rating = $pdo->query("SELECT COALESCE(AVG(rating),0) FROM reviews")->fetchColumn();
$total      = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

$dist = [];
for ($i = 1; $i <= 5; $i++) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE rating = ?");
    $stmt->execute([$i]);
    $dist[$i] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reviews — EstateServe</title>
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
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .stars { color: #f59e0b; letter-spacing: 2px; }
    .rating-bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
    .rating-bar-fill { height: 100%; background: #f59e0b; border-radius: 4px; }
    .avg-score { font-size: 4rem; font-weight: 800; color: #0f172a; line-height: 1; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"     class="nav-link"><i class="bi bi-people"></i> Users</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> Bookings</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Services</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link active"><i class="bi bi-star"></i> Reviews</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h5 class="mb-0 fw-bold">Reviews & Ratings</h5>
  </div>

  <!-- Rating Summary -->
  <div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
      <div class="row align-items-center">
        <div class="col-md-3 text-center border-end">
          <div class="avg-score"><?= number_format($avg_rating, 1) ?></div>
          <div class="stars fs-4 my-1">
            <?php
            $rounded = round($avg_rating);
            echo str_repeat('★', $rounded) . str_repeat('☆', 5 - $rounded);
            ?>
          </div>
          <div class="text-muted small"><?= $total ?> review(s) total</div>
        </div>
        <div class="col-md-9 ps-4">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <div class="d-flex align-items-center gap-3 mb-2">
            <div class="text-muted small" style="width:30px"><?= $i ?>★</div>
            <div class="rating-bar flex-grow-1">
              <div class="rating-bar-fill"
                style="width:<?= $total > 0 ? ($dist[$i] / $total * 100) : 0 ?>%"></div>
            </div>
            <div class="text-muted small" style="width:20px"><?= $dist[$i] ?></div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Reviews Table -->
  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
      <?php if (empty($reviews)): ?>
        <p class="text-center text-muted py-4">No reviews yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">#</th>
              <th>Resident</th>
              <th>Provider</th>
              <th>Service</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reviews as $r): ?>
            <tr>
              <td class="ps-4 text-muted"><?= $r['id'] ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($r['resident']) ?></td>
              <td><?= htmlspecialchars($r['provider']) ?></td>
              <td><?= htmlspecialchars($r['service']) ?></td>
              <td>
                <div class="stars"><?= str_repeat('★',$r['rating']) ?><?= str_repeat('☆',5-$r['rating']) ?></div>
                <small class="text-muted"><?= $r['rating'] ?>/5</small>
              </td>
              <td style="max-width:200px">
                <span class="text-muted small">
                  <?= $r['comment'] ? htmlspecialchars(substr($r['comment'],0,80)) . (strlen($r['comment']) > 80 ? '...' : '') : '—' ?>
                </span>
              </td>
              <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              <td class="text-center">
                <a href="?delete=<?= $r['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete this review?')">
                  <i class="bi bi-trash"></i>
                </a>
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