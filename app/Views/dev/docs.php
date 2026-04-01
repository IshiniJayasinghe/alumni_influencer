<?= view('layout/header', ['title' => 'API Docs']) ?>
<div class="card">
    <h1>API documentation</h1>
    <p style="margin-top:10px;">Base URL: <code><?= esc(base_url('/')) ?></code></p>
    <p style="margin-top:10px;">OpenAPI JSON: <a class="btn" href="<?= base_url('openapi.json') ?>" target="_blank">openapi.json</a></p>
    <h2 style="margin-top:18px;">Bearer authentication</h2>
    <p style="margin-top:10px;">Send your generated API key in the Authorization header as <code>Bearer YOUR_KEY</code>.</p>
    <h2 style="margin-top:18px;">Featured alumnus endpoint</h2>
    <pre style="margin-top:10px; white-space:pre-wrap; background:#111827; padding:14px; border-radius:10px;">GET <?= esc(base_url('api/featured')) ?>
Authorization: Bearer YOUR_KEY</pre>
    <h2 style="margin-top:18px;">Example response</h2>
    <pre style="margin-top:10px; white-space:pre-wrap; background:#111827; padding:14px; border-radius:10px;">{
  "status": "success",
  "data": {
    "feature_date": "<?= date('Y-m-d') ?>",
    "winning_bid": "250.00",
    "name": "Jane Doe",
    "bio": "Senior Product Designer",
    "linkedin_url": "https://linkedin.com/in/example",
    "job_title_now": "Senior Product Designer",
    "profile_image": "uploads/profile/example.png"
  }
}</pre>
</div>
<?= view('layout/footer') ?>
