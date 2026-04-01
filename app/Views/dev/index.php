<?= view('layout/header', ['title' => 'Developer']) ?>
<div class="grid-2">
    <div class="card">
        <h1>Manage API keys</h1>
        <?php if (session()->getFlashdata('generated_api_key')): ?>
            <div class="flash-info" style="margin-top:14px;">Generated API key: <?= esc(session()->getFlashdata('generated_api_key')) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('developer/generate-key') ?>" style="margin-top:16px;">
            <?= csrf_field() ?>
            <div class="form-group"><label>Key name</label><input type="text" name="key_name" required></div>
            <button class="btn" type="submit">Generate API key</button>
        </form>
        <p style="margin-top:14px;"><a class="btn" href="<?= base_url('api-docs') ?>">Open API documentation</a></p>
    </div>
    <div class="card">
        <h2>Your keys</h2>
        <?php if (empty($keys)): ?>
            <p>No API keys yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Name</th><th>Status</th><th>Last used</th><th></th></tr>
                <?php foreach ($keys as $key): ?>
                    <tr>
                        <td><?= esc($key['key_name']) ?></td>
                        <td><?= (int)$key['is_active'] === 1 ? 'Active' : 'Revoked' ?></td>
                        <td><?= esc($key['last_used_at'] ?? '-') ?></td>
                        <td><?php if ((int)$key['is_active'] === 1): ?><a class="btn btn-danger" href="<?= base_url('developer/revoke/' . $key['id']) ?>">Revoke</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
<div class="card">
    <h2>Recent API usage logs</h2>
    <?php if (empty($logs)): ?>
        <p>No API usage yet.</p>
    <?php else: ?>
        <table>
            <tr><th>Endpoint</th><th>Method</th><th>IP</th><th>Used at</th></tr>
            <?php foreach ($logs as $log): ?>
                <tr><td><?= esc($log['endpoint']) ?></td><td><?= esc($log['method']) ?></td><td><?= esc($log['client_ip']) ?></td><td><?= esc($log['used_at']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?= view('layout/footer') ?>
