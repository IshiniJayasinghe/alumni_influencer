<?= view('layout/header', ['title' => 'Dashboard']) ?>

<div class="card">
    <h1>University Analytics Dashboard</h1>
    <p class="muted">Overview of alumni data for the university dashboard.</p>
</div>

<div class="grid-3">
    <div class="card">
        <h3>Total Alumni</h3>
        <p><?= esc($totalAlumni) ?></p>
    </div>
    <div class="card">
        <h3>Total Certifications</h3>
        <p><?= esc($totalCertifications) ?></p>
    </div>
    <div class="card">
        <h3>Employment Records</h3>
        <p><?= esc($totalEmploymentRecords) ?></p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3>Top Industries</h3>
        <?php if (!empty($topIndustries)): ?>
            <ul class="clean">
                <?php foreach ($topIndustries as $item): ?>
                    <li><?= esc($item['industry_sector']) ?> (<?= esc($item['total']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No industry data available yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Top Employers</h3>
        <?php if (!empty($topEmployers)): ?>
            <ul class="clean">
                <?php foreach ($topEmployers as $item): ?>
                    <li><?= esc($item['company_name']) ?> (<?= esc($item['total']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No employer data available yet.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <a class="btn" href="<?= base_url('dashboard/alumni') ?>">View Alumni</a>
    <a class="btn" href="<?= base_url('dashboard/charts') ?>">View Charts</a>
    <a class="btn" href="<?= base_url('dashboard/export/csv') ?>">Export CSV</a>
    <a class="btn" href="<?= base_url('dashboard/export/pdf') ?>">Export PDF</a>
</div>

<div class="card">
    <h3>Custom Reports</h3>
    <p class="muted">Choose the metrics you want, then export the report as CSV or PDF.</p>
    <form method="get" action="<?= base_url('dashboard/report') ?>" style="margin-top:16px;">
        <div class="grid-2">
            <?php foreach (($metricDefinitions ?? []) as $metricKey => $metricLabel): ?>
                <div class="card" style="margin-bottom:0; padding:12px 14px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="metrics[]" value="<?= esc($metricKey) ?>" checked style="width:auto;">
                        <span><?= esc($metricLabel) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn" type="submit" name="format" value="csv">Export Report CSV</button>
            <button class="btn" type="submit" name="format" value="pdf">Export Report PDF</button>
        </div>
    </form>
</div>

<?= view('layout/footer') ?>
