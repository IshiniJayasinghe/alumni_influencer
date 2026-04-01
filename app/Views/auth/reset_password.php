<?= view('layout/header', ['title' => 'Reset Password']) ?>
<div class="card" style="max-width:560px; margin:0 auto;">
    <h1>Reset password</h1>
    <form method="post" action="<?= base_url('reset-password') ?>" style="margin-top:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token ?? old('token')) ?>">
        <div class="form-group"><label>New password</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirm password</label><input type="password" name="password_confirm" required></div>
        <button class="btn" type="submit">Reset password</button>
    </form>
</div>
<?= view('layout/footer') ?>
