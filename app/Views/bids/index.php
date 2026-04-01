<?= view('layout/header', ['title' => 'Bids']) ?>
<div class="grid-2">
    <div class="card">
        <h1>Blind bidding</h1>
        <p style="margin-top:10px;">Status: <span class="tag"><?= esc($winningStatus) ?></span></p>
        <p class="muted" style="margin-top:10px;">Monthly featured limit used: <?= (int) $winsThisMonth ?> / <?= (int) $allowedWinsThisMonth ?></p>
        <?php if (! empty($myBidToday)): ?><p style="margin-top:10px;">Your current bid for today: <strong>£<?= esc(number_format((float)$myBidToday['bid_amount'], 2)) ?></strong></p><?php endif; ?>
        <form method="post" action="<?= base_url('bids/add') ?>" style="margin-top:16px;">
            <?= csrf_field() ?>
            <div class="form-group"><label>Bid amount</label><input type="number" step="0.01" min="0.01" name="bid_amount" required></div>
            <button class="btn" type="submit"><?= empty($myBidToday) ? 'Place bid' : 'Increase bid' ?></button>
        </form>
    </div>
    <div class="card">
        <h2>Your bid history</h2>
        <?php if (empty($bids)): ?>
            <p>No bids yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Date</th><th>Amount</th><th>Status</th><th></th></tr>
                <?php foreach ($bids as $bid): ?>
                    <tr>
                        <td><?= esc($bid['bid_date']) ?></td>
                        <td>£<?= esc(number_format((float)$bid['bid_amount'], 2)) ?></td>
                        <td><?= esc($bid['status']) ?></td>
                        <td><?php if ($bid['status'] === 'pending'): ?><a class="btn btn-danger" href="<?= base_url('bids/delete/' . $bid['id']) ?>">Cancel</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= view('layout/footer') ?>
