<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$category_filter = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$where = $category_filter !== 'all' ? "WHERE s.status = 'available' AND s.category = '$category_filter'" : "WHERE s.status = 'available'";

$services = $pdo->query("
    SELECT s.*, u.full_name AS provider_name
    FROM services s
    LEFT JOIN users u ON s.provider_id = u.id
    $where
    ORDER BY s.category
")->fetchAll();

$categories = [
    'all'           => ['label'=>'All Services', 'icon'=>'bi-grid'],
    'laundry'       => ['label'=>'Laundry',       'icon'=>'bi-water'],
    'car_washing'   => ['label'=>'Car Washing',   'icon'=>'bi-car-front'],
    'grocery'       => ['label'=>'Grocery',       'icon'=>'bi-cart3'],
    'house_cleaning'=> ['label'=>'House Cleaning','icon'=>'bi-house-heart'],
    'plumbing'      => ['label'=>'Plumbing',      'icon'=>'bi-wrench-adjustable'],
    'food_delivery' => ['label'=>'Food Delivery', 'icon'=>'bi-bag-heart'],
    'salon'         => ['label'=>'Salon',         'icon'=>'bi-scissors'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Services — EstateServe</title>
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
    .cat-btn { border-radius: 99px; font-size: .85rem; padding: .4rem 1rem; }
    .cat-btn.active { background: #00A550; color: #fff; border-color: #00A550; }
    .service-card { border: none; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s, box-shadow .2s; height: 100%; }
    .service-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,.1); }
    .service-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; background: #e8f5e9; }
    .price-tag { font-size: 1.3rem; font-weight: 800; color: #00A550; }
    .btn-book { background: #00A550; color: #fff; border: none; border-radius: 8px; font-weight: 600; }
    .btn-book:hover { background: #007a3d; color: #fff; }
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
    <h5 class="mb-0 fw-bold">Browse Services</h5>
  </div>

  <!-- Category Filter -->
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach ($categories as $key => $cat): ?>
    <a href="?cat=<?= $key ?>"
       class="btn btn-outline-secondary cat-btn <?= $category_filter === $key ? 'active' : '' ?>">
      <i class="bi <?= $cat['icon'] ?> me-1"></i><?= $cat['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($services)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-inbox fs-1 d-block mb-3"></i>
      <h5>No services available in this category</h5>
    </div>
  <?php else: ?>
  <div class="row g-4">
    <?php foreach ($services as $s): ?>
    <div class="col-md-6 col-lg-4">
      <div class="service-card card p-4">
        <div class="d-flex align-items-start gap-3 mb-3">
          <div class="service-icon">
            <i class="bi <?= $categories[$s['category']]['icon'] ?? 'bi-star' ?>"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($s['name']) ?></h6>
            <span class="badge bg-light text-muted"><?= str_replace('_',' ',ucfirst($s['category'])) ?></span>
          </div>
        </div>
        <p class="text-muted small mb-3"><?= htmlspecialchars($s['description']) ?></p>
        <?php if ($s['provider_name']): ?>
        <div class="text-muted small mb-3">
          <i class="bi bi-person-check me-1 text-success"></i>
          <?= htmlspecialchars($s['provider_name']) ?>
        </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center">
          <div class="price-tag">KES <?= number_format($s['price'], 0) ?></div>
          <a href="book.php?service=<?= $s['id'] ?>" class="btn btn-book px-3 py-2">
            <i class="bi bi-calendar-plus me-1"></i>Book Now
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</body>
</html>