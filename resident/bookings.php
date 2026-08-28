<div class="col-md-3 mt-3 mt-md-0">
  <div class="small text-muted mb-1">Payment</div>
  <?php if ($b['transaction_code']): ?>
    <div class="txn-code"><?= $b['transaction_code'] ?></div>
    <div class="text-success small mt-1 fw-semibold">KES <?= number_format($b['amount'], 0) ?> paid</div>
    <a href="receipt.php?booking=<?= $b['id'] ?>"
       class="btn btn-sm btn-outline-success mt-2 d-block">
      <i class="bi bi-receipt me-1"></i>View Receipt
    </a>
  <?php else: ?>
    <span class="badge bg-warning text-dark">Unpaid</span>
    <br>
    <a href="payment.php?booking=<?= $b['id'] ?>" class="btn btn-sm btn-success mt-1">Pay Now</a>
  <?php endif; ?>
</div>