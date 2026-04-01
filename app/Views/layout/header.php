<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Alumni Influencer') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; }
        body { background: #1f2937; color: #f9fafb; min-height: 100vh; }
        .navbar { background: #111827; padding: 14px 24px; display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; }
        .navbar h2 a { color:#facc15; text-decoration:none; }
        .navbar nav a { color:#fff; text-decoration:none; margin-left:14px; font-weight:bold; }
        .navbar nav a:hover { color:#facc15; }
        .container { width:95%; max-width:1250px; margin:24px auto; }
        .card { background:#374151; border-radius:14px; padding:20px; margin-bottom:20px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .grid-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
        .flash-error,.flash-success,.flash-info { padding:12px 14px; border-radius:8px; margin-bottom:14px; }
        .flash-error { background:#7f1d1d; color:#fecaca; }
        .flash-success { background:#14532d; color:#bbf7d0; }
        .flash-info { background:#1e3a8a; color:#dbeafe; word-break: break-all; }
        .hero { display:grid; grid-template-columns:1.2fr .8fr; gap:20px; align-items:start; }
        .btn { background:#facc15; color:#111827; border:none; padding:10px 16px; border-radius:8px; text-decoration:none; display:inline-block; font-weight:bold; cursor:pointer; }
        .btn:hover { background:#eab308; }
        .btn-danger { background:#dc2626; color:#fff; }
        .btn-danger:hover { background:#b91c1c; }
        .muted { color:#d1d5db; font-size:14px; }
        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:18px; }
        .stat { background:#111827; padding:14px; border-radius:12px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; margin-bottom:6px; font-weight:bold; }
        .form-group input, .form-group textarea, .form-group select { width:100%; padding:10px; border:none; border-radius:8px; }
        .form-group textarea { min-height:100px; resize:vertical; }
        table { width:100%; border-collapse: collapse; }
        th, td { text-align:left; padding:10px; border-bottom:1px solid #4b5563; vertical-align:top; }
        .tag { display:inline-block; background:#111827; padding:5px 8px; border-radius:999px; font-size:12px; }
        ul.clean { padding-left:18px; }
        img.profile { max-width:160px; border-radius:12px; display:block; margin-bottom:12px; }
        @media (max-width: 900px) { .hero, .grid-2, .grid-3 { grid-template-columns:1fr; } .navbar nav a { display:inline-block; margin:6px 10px 0 0; } }
    </style>
</head>
<body>
<div class="navbar">
    <h2><a href="<?= base_url('/') ?>">Alumni Influencer</a></h2>
    <nav>
        <a href="<?= base_url('/') ?>">Home</a>
        <?php if (session()->get('logged_in')): ?>
            <a href="<?= base_url('profile') ?>">Profile</a>
            <a href="<?= base_url('profile/manage') ?>">Manage</a>
            <a href="<?= base_url('bids') ?>">Bids</a>
            <?php if (session()->get('role') === 'developer'): ?>
                <a href="<?= base_url('api-docs') ?>">API Docs</a>
            <?php endif; ?>
            <a href="<?= base_url('logout') ?>">Logout</a>
        <?php else: ?>
            <a href="<?= base_url('register') ?>">Register</a>
            <a href="<?= base_url('login') ?>">Login</a>
        <?php endif; ?>
    </nav>
</div>
<div class="container">
<?php if (session()->getFlashdata('error')): ?><div class="flash-error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('success')): ?><div class="flash-success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('token_link')): ?><div class="flash-info"><?= esc(session()->getFlashdata('token_link')) ?></div><?php endif; ?>
