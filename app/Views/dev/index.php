<?= view('layout/header', ['title' => 'Developer']) ?>

<div class="grid-2">
    <div class="card">
        <h1>Manage API keys</h1>

        <?php if (session()->getFlashdata('generated_api_key')): ?>
            <div class="flash-info" style="margin-top:14px;">Generated API key: <?= esc(session()->getFlashdata('generated_api_key')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('developer/generate-key') ?>" style="margin-top:16px;">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Key name</label>
                <input type="text" name="key_name" required>
            </div>

            <div class="form-group">
                <label>Permissions</label>
                <div style="display:grid; gap:10px; margin-top:8px;">
                    <?php foreach (($availableScopes ?? []) as $scope => $label): ?>
                        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="permissions[]" value="<?= esc($scope) ?>" style="width:auto; margin-top:4px;">
                            <span><strong><?= esc($scope) ?></strong><br><small><?= esc($label) ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

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
                <tr>
                    <th>Name</th>
                    <th>Scopes</th>
                    <th>Status</th>
                    <th>Last used</th>
                    <th></th>
                </tr>
                <?php foreach ($keys as $key): ?>
                    <tr>
                        <td><?= esc($key['key_name']) ?></td>
                        <td><?= esc(implode(', ', $key['decoded_permissions'] ?? [])) ?: '-' ?></td>
                        <td><?= (int) $key['is_active'] === 1 ? 'Active' : 'Revoked' ?></td>
                        <td><?= esc($key['last_used_at'] ?? '-') ?></td>
                        <td><?php if ((int) $key['is_active'] === 1): ?><a class="btn btn-danger" href="<?= base_url('developer/revoke/' . $key['id']) ?>">Revoke</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Recent API usage logs</h2>
        <?php if (empty($logs)): ?>
            <p>No API usage yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>API key</th>
                    <th>Endpoint</th>
                    <th>Method</th>
                    <th>IP</th>
                    <th>Used at</th>
                </tr>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= esc($log['key_name'] ?? 'Unknown key') ?></td>
                        <td><?= esc($log['endpoint']) ?></td>
                        <td><?= esc($log['method']) ?></td>
                        <td><?= esc($log['client_ip']) ?></td>
                        <td><?= esc($log['used_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Usage overview</h2>
        <?php if (empty($usageSummary ?? [])): ?>
            <p>No API usage summary yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>API key</th>
                    <th>Endpoint</th>
                    <th>Total requests</th>
                    <th>Last used</th>
                </tr>
                <?php foreach ($usageSummary as $item): ?>
                    <tr>
                        <td><?= esc($item['key_name']) ?></td>
                        <td><?= esc($item['endpoint']) ?></td>
                        <td><?= esc($item['total_requests']) ?></td>
                        <td><?= esc($item['last_used_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= view('layout/footer') ?>
