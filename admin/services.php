<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('admin');

$error   = '';
$success = '';

// Get providers for dropdown
$providers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'provider' AND status = 'active'")->fetchAll();

// Handle add
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name        = trim($_POST['name']);
    $category    = $_POST['category'];
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $provider_id = (int)$_POST['provider_id'];

    if (!$name || !$category || !$price) {
        $error = "Please fill in all required fields.";
    } else {
        $pdo->prepare("INSERT INTO services (name, category, description, price, provider_id) VALUES (?,?,?,?,?)")
            ->execute([$name, $category, $description, $price, $provider_id ?: null]);
        $success = "Service added successfully!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: http://localhost/estateserve/admin/services.php");
    exit();
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("SELECT status FROM services WHERE id = ?");
    $stmt->execute([(int)$_GET['toggle']]);
    $current = $stmt->fetchColumn();
    $new = $current === 'available' ? 'unavailable' : 'available';
    $pdo->prepare("UPDATE services SET status = ? WHERE id = ?")->execute([$new, (int)$_GET['toggle']]);
    header("Location: http://localhost/estateserve/admin/services.php");
    exit();
}

$services = $pdo->query("
    SELECT s.*, u.full_name AS provider_name
    FROM services s
    LEFT JOIN users u ON s.provider_id = u.id
    ORDER BY s.category
")->fetchAll();

$categories = ['laundry','car_washing','grocery','house_cleaning','plumbing','food_delivery','salon'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Services — EstateServe</title>
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
    .form-control:focus, .form-select:focus { border-color: #00A550; box-shadow: 0 0 0 .2rem rgba(0,165,80,.25); }
    .btn-add { background: #00A550; border: none; font-weight: 600; }
    .btn-add:hover { background: #007a3d; }
  </style>
</head>
<body>

<div class="sidebar">
  <span class="brand">🏠 EstateServe</span>
  <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="users.php"     class="nav-link"><i class="bi bi-people"></i> Users</a>
    <a href="bookings.php"  class="nav-link"><i class="bi bi-calendar-check"></i> Bookings</a>
    <a href="services.php"  class="nav-link active"><i class="bi bi-grid"></i> Services</a>
    <a href="payments.php"  class="nav-link"><i class="bi bi-cash-stack"></i> Payments</a>
    <a href="reviews.php"   class="nav-link"><i class="bi bi-star"></i> Reviews</a>
    <hr style="border-color:#1e293b">
    <a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold">Manage Services</h5>
    <button class="btn btn-add text-white btn-sm px-3"
      data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg me-1"></i>Add Service
    </button>
  </div>

  <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">#</th>
              <th>Service Name</th>
              <th>Category</th>
              <th>Provider</th>
              <th>Price (KES)</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($services as $s): ?>
            <tr>
              <td class="ps-4 text-muted"><?= $s['id'] ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($s['name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($s['description']) ?></small>
              </td>
              <td><?= str_replace('_',' ',ucfirst($s['category'])) ?></td>
              <td><?= $s['provider_name'] ? htmlspecialchars($s['provider_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
              <td class="fw-semibold"><?= number_format($s['price'],0) ?></td>
              <td>
                <span class="badge bg-<?= $s['status'] === 'available' ? 'success' : 'secondary' ?>">
                  <?= ucfirst($s['status']) ?>
                </span>
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                  <a href="?toggle=<?= $s['id'] ?>"
                     class="btn btn-sm btn-outline-<?= $s['status'] === 'available' ? 'warning' : 'success' ?>"
                     title="Toggle status">
                    <i class="bi bi-toggle-<?= $s['status'] === 'available' ? 'on' : 'off' ?>"></i>
                  </a>
                  <a href="?delete=<?= $s['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Delete this service?')"
                     title="Delete">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Add New Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Premium Laundry" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
              <option value="">-- Select category --</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"><?= str_replace('_',' ',ucfirst($cat)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2"
              placeholder="Brief description of the service..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Price (KES) <span class="text-danger">*</span></label>
            <input type="number" name="price" class="form-control" placeholder="e.g. 500" min="1" required>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Assign Provider</label>
            <select name="provider_id" class="form-select">
              <option value="">-- Unassigned --</option>
              <?php foreach ($providers as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-add text-white px-4 flex-grow-1">
              <i class="bi bi-plus-lg me-2"></i>Add Service
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>