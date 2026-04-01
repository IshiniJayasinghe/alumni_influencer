<?= view('layout/header', ['title' => 'Home']) ?>
<div class="hero">
    <div class="card">
        <h1>Alumni Influencer Platform</h1>
        <p class="muted" style="margin-top:10px;">University alumni can build professional profiles, place blind bids, and become the featured Alumni of the Day.</p>
        <div style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
            <a class="btn" href="<?= base_url('register') ?>">Create Account</a>
            <a class="btn" href="<?= base_url('login') ?>">Login</a>
            <a class="btn" href="<?= base_url('api-docs') ?>">View API Docs</a>
        </div>
        <div class="stats">
            <div class="stat"><strong>Blind bidding</strong><div class="muted">Users only see winning or losing.</div></div>
            <div class="stat"><strong>Daily winner</strong><div class="muted">Highest active bid is featured.</div></div>
            <div class="stat"><strong>Bearer API</strong><div class="muted">Developers manage keys and usage logs.</div></div>
        </div>
    </div>
    <div class="card">
        <h2>Featured alumnus today</h2>
        <?php if (! empty($winner)): ?>
            <p style="margin-top:10px;"><strong><?= esc($winner['name']) ?></strong></p>
            <p class="muted"><?= esc($winner['job_title_now'] ?? 'No current job title added') ?></p>
            <p style="margin-top:10px;"><?= esc($winner['bio'] ?? 'No biography yet.') ?></p>
            <?php if (! empty($winner['linkedin_url'])): ?><p style="margin-top:10px;"><a class="btn" href="<?= esc($winner['linkedin_url']) ?>" target="_blank">LinkedIn</a></p><?php endif; ?>
        <?php else: ?>
            <p style="margin-top:10px;">No winner has been activated for today yet.</p>
        <?php endif; ?>
    </div>
</div>
<?= view('layout/footer') ?>
