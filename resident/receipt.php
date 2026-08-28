<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('resident');

$user_id    = $_SESSION['user_id'];
$booking_id = isset($_GET['booking']) ? (int)$_GET['booking'] : 0;

$stmt = $pdo->prepare("
    SELECT b.*, 
           s.name AS service, s.category, s.price,
           res.full_name AS resident, res.email AS resident_email, res.phone AS resident_phone,
           prov.full_name AS provider, prov.phone AS provider_phone,
           p.transaction_code, p.amount, p.phone AS paid_phone, p.paid_at, p.status AS payment_status
    FROM bookings b
    JOIN services s   ON b.service_id   = s.id
    JOIN users res    ON b.resident_id  = res.id
    JOIN users prov   ON b.provider_id  = prov.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ? AND b.resident_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$data = $stmt->fetch();

if (!$data || !$data['transaction_code']) {
    header("Location: http://localhost/estateserve/resident/bookings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt #<?= $data['transaction_code'] ?> — EstateServe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
    .receipt-wrap { max-width: 680px; margin: 2rem auto; }
    .receipt {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 32px rgba(0,0,0,.1);
    }
    .receipt-header {
      background: linear-gradient(135deg, #0f172a, #1e293b);
      padding: 2rem;
      text-align: center;
    }
    .receipt-brand { color: #00A550; font-size: 1.5rem; font-weight: 800; margin-bottom: .25rem; }
    .receipt-tagline { color: #64748b; font-size: .8rem; }
    .receipt-badge {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      background: #dcfce7;
      color: #166534;
      border-radius: 99px;
      padding: .5rem 1.25rem;
      font-weight: 700;
      font-size: .9rem;
      margin: 1.25rem 0 0;
    }
    .receipt-body { padding: 2rem; }
    .receipt-txn {
      text-align: center;
      margin-bottom: 1.75rem;
    }
    .receipt-txn .txn-label { font-size: .75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .4rem; }
    .receipt-txn .txn-code {
      font-family: 'Courier New', monospace;
      font-size: 1.5rem;
      font-weight: 800;
      color: #00A550;
      background: #f0fdf4;
      border: 2px dashed #86efac;
      border-radius: 12px;
      padding: .75rem 1.5rem;
      display: inline-block;
      letter-spacing: .1em;
    }
    .receipt-section { margin-bottom: 1.5rem; }
    .receipt-section-title {
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: #94a3b8;
      font-weight: 600;
      margin-bottom: .75rem;
      padding-bottom: .5rem;
      border-bottom: 1px solid #f1f5f9;
    }
    .receipt-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: .5rem 0;
      font-size: .875rem;
    }
    .receipt-row .label { color: #64748b; }
    .receipt-row .value { font-weight: 600; color: #1e293b; text-align: right; }
    .receipt-total {
      background: #f8fafc;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin: 1.25rem 0;
    }
    .receipt-total .total-label { font-size: .8rem; color: #64748b; }
    .receipt-total .total-amount { font-size: 1.75rem; font-weight: 800; color: #00A550; }
    .receipt-footer {
      background: #f8fafc;
      padding: 1.25rem 2rem;
      text-align: center;
      font-size: .78rem;
      color: #94a3b8;
      border-top: 1px solid #f1f5f9;
    }
    .divider { border: none; border-top: 1px dashed #e2e8f0; margin: 1.25rem 0; }
    .btn-print {
      background: linear-gradient(135deg, #00A550, #007a3d);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: .75rem 2rem;
      font-weight: 700;
      font-size: .95rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      transition: all .2s;
      text-decoration: none;
    }
    .btn-print:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0,165,80,.3);
      color: #fff;
    }
    .btn-back {
      background: #fff;
      color: #64748b;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      padding: .75rem 1.5rem;
      font-weight: 600;
      font-size: .875rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      text-decoration: none;
      transition: all .15s;
    }
    .btn-back:hover { border-color: #00A550; color: #00A550; }

    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .receipt { box-shadow: none; border-radius: 0; }
      .receipt-wrap { margin: 0; max-width: 100%; }
    }
  </style>
</head>
<body>

<div class="receipt-wrap">

  <!-- Action Buttons -->
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="bookings.php" class="btn-back">
      <i class="bi bi-arrow-left"></i> Back to Bookings
    </a>
    <button onclick="window.print()" class="btn-print">
      <i class="bi bi-printer"></i> Print Receipt
    </button>
  </div>

  <!-- Receipt -->
  <div class="receipt">

    <!-- Header -->
    <div class="receipt-header">
      <div class="receipt-brand">🏠 EstateServe</div>
      <div class="receipt-tagline">Estate Services Management System · JKUAT Karen Campus</div>
      <div class="receipt-badge">
        <i class="bi bi-check-circle-fill"></i>
        Payment Successful
      </div>
    </div>

    <!-- Body -->
    <div class="receipt-body">

      <!-- Transaction Code -->
      <div class="receipt-txn">
        <div class="txn-label">Transaction Code</div>
        <div class="txn-code"><?= $data['transaction_code'] ?></div>
      </div>

      <hr class="divider">

      <!-- Service Details -->
      <div class="receipt-section">
        <div class="receipt-section-title">Service Details</div>
        <div class="receipt-row">
          <span class="label">Service</span>
          <span class="value"><?= htmlspecialchars($data['service']) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Category</span>
          <span class="value"><?= str_replace('_',' ',ucfirst($data['category'])) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Provider</span>
          <span class="value"><?= htmlspecialchars($data['provider']) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Provider Phone</span>
          <span class="value"><?= htmlspecialchars($data['provider_phone']) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Booking Date</span>
          <span class="value"><?= date('l, d F Y', strtotime($data['booking_date'])) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Booking Time</span>
          <span class="value"><?= date('h:i A', strtotime($data['booking_time'])) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Address</span>
          <span class="value"><?= htmlspecialchars($data['address']) ?></span>
        </div>
      </div>

      <hr class="divider">

      <!-- Customer Details -->
      <div class="receipt-section">
        <div class="receipt-section-title">Customer Details</div>
        <div class="receipt-row">
          <span class="label">Name</span>
          <span class="value"><?= htmlspecialchars($data['resident']) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Email</span>
          <span class="value"><?= htmlspecialchars($data['resident_email']) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Phone</span>
          <span class="value"><?= htmlspecialchars($data['resident_phone']) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">M-Pesa Number</span>
          <span class="value"><?= htmlspecialchars($data['paid_phone']) ?></span>
        </div>
      </div>

      <hr class="divider">

      <!-- Payment Breakdown -->
      <div class="receipt-section">
        <div class="receipt-section-title">Payment Breakdown</div>
        <div class="receipt-row">
          <span class="label">Service Fee</span>
          <span class="value">KES <?= number_format($data['price'], 2) ?></span>
        </div>
        <div class="receipt-row">
          <span class="label">Platform Fee</span>
          <span class="value">KES 50.00</span>
        </div>
        <div class="receipt-row">
          <span class="label">Payment Method</span>
          <span class="value">Simulated M-Pesa</span>
        </div>
        <div class="receipt-row">
          <span class="label">Paid At</span>
          <span class="value"><?= date('d M Y, h:i A', strtotime($data['paid_at'])) ?></span>
        </div>
      </div>

      <!-- Total -->
      <div class="receipt-total d-flex justify-content-between align-items-center">
        <div class="total-label">Total Amount Paid</div>
        <div class="total-amount">KES <?= number_format($data['amount'], 2) ?></div>
      </div>

      <!-- Booking Status -->
      <div class="receipt-row">
        <span class="label">Booking Status</span>
        <span class="value">
          <span style="background:#dcfce7;color:#166534;padding:.25rem .75rem;border-radius:99px;font-size:.78rem">
            <?= ucfirst(str_replace('_',' ',$data['status'])) ?>
          </span>
        </span>
      </div>

    </div>

    <!-- Footer -->
    <div class="receipt-footer">
      <p class="mb-1">Thank you for using <strong>EstateServe</strong>!</p>
      <p class="mb-1">This is a simulated receipt generated for demonstration purposes.</p>
      <p class="mb-0">EstateServe · BSc. IT Final Year Project · JKUAT Karen Campus · <?= date('Y') ?></p>
    </div>

  </div>
</div>

</body>
</html>