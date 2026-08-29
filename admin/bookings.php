<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('admin');

// Handle status update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $status = $_GET['status'];
    $allowed = ['pending','confirmed','in_progress','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$status, $id]);
    }
    header("Location: http://localhost/estateserve/admin/bookings.php");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where  = $filter !== 'all' ? "WHERE b.status = '$filter'" : '';

$stmt = $pdo->query("
    SELECT b.*, 
           r.full_name AS resident, r.phone AS resident_phone,
           p2.full_name AS provider,
           s.name AS service, s.category,
           pay.transaction_code, pay.amount, pay.status AS payment_status
    FROM bookings b
    JOIN users r  ON b.resident_id  = r.id
    JOIN users p2 ON b.provider_id  = p2.id
    JOIN services s ON b.service_id = s.id
    LEFT JOIN payments pay ON pay.booking_id = b.id
    $where
    ORDER BY b.created_at DESC
");
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
  <title>Manage Bookings — EstateServe</title>
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
    .filter-btn { border-radius: 99px; font-size: .85rem; padding: .35rem 1rem; }
    .filter-btn.active { background: #00A550; color: #fff; border-color: #00A550; }
    .txn-code { font-family: monospace; background: #f0fdf4; color: #00A550; padding: .15rem .5rem; border-radius: 6px; font-size: .8rem; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"     class="nav-link"><i class="bi bi-people"></i> Users</a>
    <a href="bookings.php"  class="nav-link active"><i class="bi bi-calendar-check"></i> Bookings</a>
    <a href="services.php"  class="nav-link"><i class="bi bi-grid"></i> Services</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> Reviews</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0 fw-bold">Manage Bookings</h5>
      <small class="text-muted"><?= count($bookings) ?> booking(s) found</small>
    </div>
  </div>

  <!-- Filters -->
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach (['all','pending','confirmed','in_progress','completed','cancelled'] as $f): ?>
    <a href="?filter=<?= $f ?>"
       class="btn btn-outline-secondary filter-btn <?= $filter === $f ? 'active' : '' ?>">
      <?= ucfirst(str_replace('_',' ',$f)) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
      <?php if (empty($bookings)): ?>
        <p class="text-center text-muted py-4">No bookings found.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">#</th>
              <th>Resident</th>
              <th>Service</th>
              <th>Provider</th>
              <th>Date</th>
              <th>Payment</th>
              <th>Status</th>
              <th class="text-center">Update</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td class="ps-4 text-muted">#<?= $b['id'] ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($b['resident']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($b['resident_phone']) ?></small>
              </td>
              <td>
                <div><?= htmlspecialchars($b['service']) ?></div>
                <small class="text-muted"><?= str_replace('_',' ',ucfirst($b['category'])) ?></small>
              </td>
              <td><?= htmlspecialchars($b['provider']) ?></td>
              <td>
                <div><?= date('d M Y', strtotime($b['booking_date'])) ?></div>
                <small class="text-muted"><?= date('h:i A', strtotime($b['booking_time'])) ?></small>
              </td>
              <td>
                <?php if ($b['transaction_code']): ?>
                  <div class="txn-code"><?= $b['transaction_code'] ?></div>
                  <small class="text-success">KES <?= number_format($b['amount'],0) ?></small>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Unpaid</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-<?= $status_colors[$b['status']] ?> px-2 py-1 rounded-pill">
                  <?= ucfirst(str_replace('_',' ',$b['status'])) ?>
                </span>
              </td>
              <td class="text-center">
                <select class="form-select form-select-sm"
                  onchange="location='?id=<?= $b['id'] ?>&status='+this.value"
                  style="width:130px;display:inline-block">
                  <?php foreach (['pending','confirmed','in_progress','completed','cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>>
                    <?= ucfirst(str_replace('_',' ',$s)) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
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