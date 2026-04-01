<?= view('layout/header', ['title' => 'Forgot Password']) ?>
<div class="card" style="max-width:560px; margin:0 auto;">
    <h1>Forgot password</h1>
    <form method="post" action="<?= base_url('forgot-password') ?>" style="margin-top:18px;">
        <?= csrf_field() ?>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <button class="btn" type="submit">Generate reset link</button>
    </form>
</div>
<?= view('layout/footer') ?>
