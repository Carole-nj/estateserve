<?php
require_once 'includes/auth.php';
if (isLoggedIn()) redirectByRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EstateServe — Estate Services Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    * { scroll-behavior: smooth; }
    body { font-family: 'Segoe UI', sans-serif; }

    /* NAV */
    .navbar { background: #0f172a; padding: 1rem 2rem; }
    .navbar-brand { color: #00A550 !important; font-weight: 700; font-size: 1.4rem; }
    .nav-link { color: #94a3b8 !important; }
    .nav-link:hover { color: #fff !important; }
    .btn-nav { background: #00A550; color: #fff; border: none; padding: .5rem 1.5rem; border-radius: 8px; font-weight: 600; }
    .btn-nav:hover { background: #007a3d; color: #fff; }

    /* HERO */
    .hero { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 92vh; display: flex; align-items: center; position: relative; overflow: hidden; }
    .hero::before { content:''; position:absolute; width:600px; height:600px; background: radial-gradient(circle, rgba(0,165,80,.15) 0%, transparent 70%); top:-100px; right:-100px; border-radius:50%; }
    .hero::after  { content:''; position:absolute; width:400px; height:400px; background: radial-gradient(circle, rgba(232,0,45,.1) 0%, transparent 70%); bottom:-50px; left:-50px; border-radius:50%; }
    .hero-title { font-size: 3.5rem; font-weight: 800; color: #fff; line-height: 1.15; }
    .hero-title span { color: #00A550; }
    .hero-sub { color: #94a3b8; font-size: 1.15rem; max-width: 520px; }
    .btn-hero-primary { background: #00A550; color: #fff; border: none; padding: .85rem 2rem; border-radius: 10px; font-weight: 700; font-size: 1rem; }
    .btn-hero-primary:hover { background: #007a3d; color: #fff; }
    .btn-hero-secondary { background: transparent; color: #fff; border: 2px solid #334155; padding: .85rem 2rem; border-radius: 10px; font-weight: 600; font-size: 1rem; }
    .btn-hero-secondary:hover { border-color: #00A550; color: #00A550; }
    .hero-badge { display: inline-block; background: rgba(0,165,80,.15); color: #00A550; border: 1px solid rgba(0,165,80,.3); border-radius: 99px; padding: .4rem 1rem; font-size: .85rem; font-weight: 600; margin-bottom: 1.5rem; }

    /* SERVICES */
    .services { padding: 5rem 0; background: #f8fafc; }
    .section-label { color: #00A550; font-weight: 700; font-size: .85rem; letter-spacing: .1em; text-transform: uppercase; }
    .section-title { font-size: 2.2rem; font-weight: 800; color: #0f172a; }
    .service-card { border: none; border-radius: 16px; padding: 1.75rem; transition: transform .2s, box-shadow .2s; cursor: default; height: 100%; }
    .service-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,.1); }
    .service-icon { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 1rem; }
    .service-icon .svc-ico { width: 32px; height: 32px; display: inline-block; -webkit-mask-position: center; mask-position: center; -webkit-mask-size: contain; mask-size: contain; -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat; }
    .service-card h5 { font-weight: 700; font-size: 1.05rem; color: #0f172a; }
    .service-card p  { color: #64748b; font-size: .9rem; margin: 0; }

    /* HOW IT WORKS */
    .how { padding: 5rem 0; background: #fff; }
    .step-num { width: 48px; height: 48px; border-radius: 50%; background: #00A550; color: #fff; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
    .step-connector { flex: 1; height: 2px; background: linear-gradient(90deg, #00A550, #e2e8f0); margin: 0 1rem; margin-top: -2.5rem; }

    /* ROLES */
    .roles { padding: 5rem 0; background: #0f172a; }
    .role-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; height: 100%; transition: border-color .2s; }
    .role-card:hover { border-color: #00A550; }
    .role-icon { font-size: 2.5rem; margin-bottom: 1rem; }
    .role-icon .role-ico { width: 40px; height: 40px; display: inline-block; background-color: currentColor; -webkit-mask-position: center; mask-position: center; -webkit-mask-size: contain; mask-size: contain; -webkit-mask-repeat: no-repeat; mask-repeat: no-repeat; }
    .role-card h5 { color: #fff; font-weight: 700; }
    .role-card p  { color: #94a3b8; font-size: .9rem; }
    .role-card ul { color: #94a3b8; font-size: .875rem; padding-left: 1.2rem; }
    .role-card ul li { margin-bottom: .4rem; }

    /* CTA */
    .cta { padding: 5rem 0; background: linear-gradient(135deg, #00A550, #007a3d); }
    .cta h2 { color: #fff; font-size: 2.5rem; font-weight: 800; }
    .cta p  { color: rgba(255,255,255,.8); font-size: 1.1rem; }
    .btn-cta { background: #fff; color: #00A550; font-weight: 700; padding: .85rem 2.5rem; border-radius: 10px; border: none; font-size: 1rem; }
    .btn-cta:hover { background: #f0fdf4; color: #007a3d; }

    /* FOOTER */
    footer { background: #0f172a; color: #64748b; padding: 2rem; text-align: center; font-size: .875rem; }
    footer span { color: #00A550; }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php"> EstateServe</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="#how">How it Works</a></li>
        <li class="nav-item"><a class="nav-link" href="#roles">For You</a></li>
        <li class="nav-item"><a href="login.php" class="btn btn-outline-light btn-sm px-3">Login</a></li>
        <li class="nav-item"><a href="register.php" class="btn btn-nav btn-sm px-3">Get Started</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="hero-badge"><i class="bi bi-geo-alt-fill"></i> Built for Kenyan Estates</div>
        <h1 class="hero-title">All Your Estate Services, <span>One Platform</span></h1>
        <p class="hero-sub mt-3 mb-4">Book laundry, car washing, grocery shopping, house cleaning, plumbing, food delivery, and salon services — all from one place. Pay simulated M-Pesa. Track in real time.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="register.php" class="btn btn-hero-primary">
            <i class="bi bi-person-plus me-2"></i>Get Started Free
          </a>
          <a href="login.php" class="btn btn-hero-secondary">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
          </a>
        </div>
        <div class="mt-4 d-flex gap-4">
          <div>
            <div class="text-white fw-bold fs-5">7+</div>
            <div class="text-muted small">Service Categories</div>
          </div>
          <div>
            <div class="text-white fw-bold fs-5">4</div>
            <div class="text-muted small">User Roles</div>
          </div>
          <div>
            <div class="text-white fw-bold fs-5">100%</div>
            <div class="text-muted small">Digital Payments</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-flex justify-content-center">
        <div style="position:relative; width:360px; height:360px;">
          <div style="background:#1e293b; border:1px solid #334155; border-radius:20px; padding:1.5rem; position:absolute; top:0; left:0; width:260px;">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div style="width:10px;height:10px;background:#00A550;border-radius:50%"></div>
              <span style="color:#94a3b8;font-size:.8rem">New Booking</span>
            </div>
            <div style="color:#fff;font-weight:600;margin-bottom:.25rem">House Cleaning</div>
            <div style="color:#94a3b8;font-size:.8rem">Today at 10:00 AM</div>
            <div style="color:#00A550;font-weight:700;margin-top:.5rem">KES 1,250</div>
          </div>
          <div style="background:#1e293b; border:1px solid #00A550; border-radius:20px; padding:1.5rem; position:absolute; bottom:0; right:0; width:240px;">
            <div style="color:#00A550;font-size:.8rem;font-weight:600;margin-bottom:.5rem"><i class="bi bi-check-circle-fill"></i> PAYMENT SUCCESS</div>
            <div style="color:#fff;font-weight:600;margin-bottom:.25rem">Transaction Code</div>
            <div style="color:#94a3b8;font-size:.85rem;font-family:monospace">ES4A2F9B1C</div>
          </div>
          <div style="background:#1e293b; border:1px solid #334155; border-radius:20px; padding:1.5rem; position:absolute; top:50%; left:50%; transform:translate(-20%,-50%); width:200px;">
            <div style="font-size:1.5rem;margin-bottom:.5rem"><i class="bi bi-car-front"></i></div>
            <div style="color:#fff;font-weight:600;font-size:.9rem">Car Wash</div>
            <div style="color:#94a3b8;font-size:.75rem">In Progress...</div>
            <div style="background:#334155;border-radius:4px;height:6px;margin-top:.75rem;overflow:hidden">
              <div style="background:#00A550;width:65%;height:100%;border-radius:4px"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SERVICES -->
<section class="services" id="services">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">What We Offer</div>
      <h2 class="section-title mt-1">7 Services at Your Doorstep</h2>
    </div>
    <div class="row g-4">
      <?php
      $services = [
        ['icon'=>'laundry.svg',        'bg'=>'#e8f5e9','color'=>'#00A550','name'=>'Laundry',        'desc'=>'Wash, dry and fold — collected and delivered to your door.'],
        ['icon'=>'car-wash.svg',       'bg'=>'#e3f2fd','color'=>'#1565c0','name'=>'Car Washing',     'desc'=>'Full exterior and interior clean at your parking spot.'],
        ['icon'=>'grocery.svg',        'bg'=>'#fff8e1','color'=>'#f59e0b','name'=>'Grocery Shopping','desc'=>'We shop your list and deliver fresh to your unit.'],
        ['icon'=>'house-cleaning.svg', 'bg'=>'#fce4ec','color'=>'#e8002d','name'=>'House Cleaning',  'desc'=>'Professional full clean for any size home.'],
        ['icon'=>'plumbing.svg',       'bg'=>'#ede9fe','color'=>'#7c3aed','name'=>'Plumbing & Repairs','desc'=>'Leaks, fittings, electrical and general repairs.'],
        ['icon'=>'food-delivery.svg',  'bg'=>'#fff3e0','color'=>'#ea580c','name'=>'Food Delivery',   'desc'=>'Hot meals from restaurants delivered fast.'],
        ['icon'=>'salon.svg',          'bg'=>'#f0fdf4','color'=>'#059669','name'=>'Salon & Barber',  'desc'=>'Professional haircuts and styling at your convenience.'],
      ];
      foreach ($services as $s): ?>
      <div class="col-md-4 col-lg-3">
        <div class="service-card card shadow-sm">
          <div class="service-icon" style="background:<?= $s['bg'] ?>">
            <span class="svc-ico" style="background-color:<?= $s['color'] ?>;
                  -webkit-mask-image:url('assets/icons/<?= $s['icon'] ?>');
                  mask-image:url('assets/icons/<?= $s['icon'] ?>');"></span>
          </div>
          <h5><?= $s['name'] ?></h5>
          <p><?= $s['desc'] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="col-md-4 col-lg-3 d-flex align-items-center justify-content-center">
        <a href="register.php" class="btn btn-hero-primary px-4 py-3">
          <i class="bi bi-arrow-right me-2"></i>Book Now
        </a>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how" id="how">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Simple Process</div>
      <h2 class="section-title mt-1">How EstateServe Works</h2>
    </div>
    <div class="row g-4 text-center">
      <?php
      $steps = [
        ['num'=>1,'icon'=>'bi-person-plus','title'=>'Create Account','desc'=>'Register as a resident in under 2 minutes.'],
        ['num'=>2,'icon'=>'bi-grid','title'=>'Browse Services','desc'=>'Choose from 7 service categories available in your estate.'],
        ['num'=>3,'icon'=>'bi-calendar-plus','title'=>'Book a Service','desc'=>'Pick your provider, date, time and address.'],
        ['num'=>4,'icon'=>'bi-phone','title'=>'Simulated Payment','desc'=>'Pay securely via simulated M-Pesa — instant confirmation.'],
        ['num'=>5,'icon'=>'bi-check-circle','title'=>'Service Delivered','desc'=>'Track your booking status in real time until completion.'],
      ];
      foreach ($steps as $st): ?>
      <div class="col-md">
        <div class="step-num"><?= $st['num'] ?></div>
        <i class="bi <?= $st['icon'] ?> fs-3 text-success mb-2 d-block"></i>
        <h6 class="fw-bold"><?= $st['title'] ?></h6>
        <p class="text-muted small"><?= $st['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="roles" id="roles">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-label">Who It's For</div>
      <h2 class="section-title mt-1" style="color:#fff">Built for Everyone in the Estate</h2>
    </div>
    <div class="row g-4">
      <?php
      $roles = [
        ['icon'=>'resident.svg', 'title'=>'Resident','color'=>'#00A550','items'=>['Browse & book services','Track booking status','Pay via simulated M-Pesa','Rate & review providers']],
        ['icon'=>'provider.svg', 'title'=>'Service Provider','color'=>'#3b82f6','items'=>['Manage incoming bookings','Update service status','View earnings dashboard','Build your reputation']],
        ['icon'=>'delivery.svg', 'title'=>'Delivery Personnel','color'=>'#8b5cf6','items'=>['View assigned deliveries','Update delivery status','Mark orders as delivered','Receive notifications']],
        ['icon'=>'manager.svg',  'title'=>'Estate Manager','color'=>'#f59e0b','items'=>['Manage all users & services','Oversee all bookings','View analytics & reports','Approve service providers']],
      ];
      foreach ($roles as $r): ?>
      <div class="col-md-6 col-lg-3">
        <div class="role-card">
          <div class="role-icon" style="color:<?= $r['color'] ?>"><span class="role-ico" style="-webkit-mask-image:url('assets/icons/<?= $r['icon'] ?>'); mask-image:url('assets/icons/<?= $r['icon'] ?>');"></span></div>
          <h5><?= $r['title'] ?></h5>
          <ul class="mt-2">
            <?php foreach ($r['items'] as $item): ?>
              <li><?= $item ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="register.php" class="btn btn-sm mt-3 px-3 py-2 fw-semibold"
             style="background:<?= $r['color'] ?>;color:#fff;border-radius:8px;">
            Join as <?= $r['title'] ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta text-center">
  <div class="container">
    <h2>Ready to simplify estate living?</h2>
    <p class="mt-2 mb-4">Join EstateServe today — it's free and takes less than 2 minutes.</p>
    <a href="register.php" class="btn btn-cta">
      <i class="bi bi-person-plus me-2"></i>Create Free Account
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <p class="mb-1">© <?= date('Y') ?> <span>EstateServe</span> — Estate Services Management System</p>
  <p class="mb-0">Final Year Project &nbsp;·&nbsp; BSc. Information Technology &nbsp;·&nbsp; JKUAT Karen Campus</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>