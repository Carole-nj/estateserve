<?php
$role     = $_SESSION['role'] ?? '';
$name     = $_SESSION['full_name'] ?? '';
$initials = strtoupper(substr($name, 0, 1));
$current  = basename($_SERVER['PHP_SELF']);

$menus = [
    'admin' => [
        ['href'=>'dashboard.php','icon'=>'bi-speedometer2','label'=>'Dashboard'],
        ['href'=>'users.php',    'icon'=>'bi-people',      'label'=>'Users'],
        ['href'=>'bookings.php', 'icon'=>'bi-calendar-check','label'=>'Bookings'],
        ['href'=>'services.php', 'icon'=>'bi-grid',         'label'=>'Services'],
        ['href'=>'payments.php', 'icon'=>'bi-cash-stack',   'label'=>'Payments'],
        ['href'=>'reviews.php',  'icon'=>'bi-star',         'label'=>'Reviews'],
    ],
    'resident' => [
        ['href'=>'dashboard.php','icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
        ['href'=>'services.php', 'icon'=>'bi-grid',         'label'=>'Browse Services'],
        ['href'=>'bookings.php', 'icon'=>'bi-calendar-check','label'=>'My Bookings'],
        ['href'=>'payments.php', 'icon'=>'bi-cash-stack',   'label'=>'Payments'],
        ['href'=>'reviews.php',  'icon'=>'bi-star',         'label'=>'My Reviews'],
        ['href'=>'profile.php',  'icon'=>'bi-person',       'label'=>'Profile'],
    ],
    'provider' => [
        ['href'=>'dashboard.php','icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
        ['href'=>'bookings.php', 'icon'=>'bi-calendar-check','label'=>'My Bookings'],
        ['href'=>'earnings.php', 'icon'=>'bi-cash-stack',   'label'=>'Earnings'],
        ['href'=>'profile.php',  'icon'=>'bi-person',       'label'=>'Profile'],
    ],
    'delivery' => [
        ['href'=>'dashboard.php','icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
        ['href'=>'orders.php',   'icon'=>'bi-box-seam',     'label'=>'All Orders'],
        ['href'=>'earnings.php', 'icon'=>'bi-cash-stack',   'label'=>'Revenue'],
        ['href'=>'profile.php',  'icon'=>'bi-person',       'label'=>'Profile'],
    ],
];
?>
<div class="sidebar">
  <div class="sidebar-header">
   <a href="#" class="brand">Estate<span>Serve</span></a>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= $initials ?></div>
    <div>
      <div class="sidebar-user-name"><?= htmlspecialchars($name) ?></div>
      <div class="sidebar-user-role"><?= ucfirst($role) ?></div>
    </div>
  </div>
  <div class="sidebar-nav">
    <?php foreach ($menus[$role] ?? [] as $item): ?>
    <a href="<?= $item['href'] ?>"
       class="nav-link <?= $current === $item['href'] ? 'active' : '' ?>">
      <i class="bi <?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-footer">
    <a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/', 1) - 1) ?>logout.php"
       class="nav-link">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</div>