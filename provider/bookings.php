<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('provider');

$user_id = $_SESSION['user_id'];

$filter  = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$allowed = ['all','pending','confirmed','in_progress','completed','cancelled'];
if (!in_array($filter, $allowed)) $filter = 'all';

$sql = "
    SELECT b.*, s.name AS service, s.price,
           u.full_name AS resident, u.phone AS resident_phone,
           p.transaction_code
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u    ON b.resident_id = u.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.provider_id = ?
";
$params = [$user_id];
if ($filter !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
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
    .topbar { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    .badge-status-pending    { background: #fef3c7; color: #92400e; }
    .badge-status-confirmed  { background: #dbeafe; color: #1e40af; }
    .badge-status-completed  { background: #dcfce7; color: #166534; }
    .badge-status-cancelled  { background: #fee2e2; color: #991b1b; }
    .badge-status-in_progress{ background: #ede9fe; color: #5b21b6; }
    .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .filter-btn.active { background: #00A550; color: #fff; border-color: #00A550; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand"><i class="bi bi-house-door-fill"></i> EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php"  class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="bookings.php"   class="nav-link active"><i class="bi bi-calendar-check"></i> My Bookings</a>
    <a href="earnings.php"   class="nav-link"><i class="bi bi-cash-stack"></i> Earnings</a>
    <a href="profile.php"    class="nav-link"><i class="bi bi-person"></i> Profile</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php"  class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <div>
      <h5 class="mb-0 fw-bold">My Bookings</h5>
      <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?></span>
  </div>

  <!-- Filters -->
  <div class="mb-3 d-flex flex-wrap gap-2">
    <?php foreach ($allowed as $f): ?>
      <a href="?filter=<?= $f ?>"
         class="btn btn-sm btn-outline-secondary filter-btn <?= $filter === $f ? 'active' : '' ?>">
        <?= ucfirst(str_replace('_',' ',$f)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
      <?php if (empty($bookings)): ?>
        <p class="text-center text-muted py-4">No bookings found for this filter.</p>
      <?php else: ?>
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">#</th>
            <th>Resident</th>
            <th>Service</th>
            <th>Date</th>
            <th>Address</th>
            <th>Status</th>
            <th>Action</th>
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
              <small class="text-muted">KES <?= number_format($b['price'], 0) ?></small>
            </td>
            <td>
              <div><?= date('d M Y', strtotime($b['booking_date'])) ?></div>
              <small class="text-muted"><?= date('h:i A', strtotime($b['booking_time'])) ?></small>
            </td>
            <td class="small text-muted" style="max-width:200px"><?= htmlspecialchars($b['address']) ?></td>
            <td>
              <span class="badge badge-status-<?= $b['status'] ?> px-2 py-1 rounded-pill">
                <?= ucfirst(str_replace('_',' ',$b['status'])) ?>
              </span>
            </td>
            <td>
              <?php if ($b['status'] === 'confirmed'): ?>
                <a href="update_status.php?id=<?= $b['id'] ?>&status=in_progress" class="btn btn-sm btn-outline-primary">Start</a>
              <?php elseif ($b['status'] === 'in_progress'): ?>
                <a href="update_status.php?id=<?= $b['id'] ?>&status=completed" class="btn btn-sm btn-success">Complete</a>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
