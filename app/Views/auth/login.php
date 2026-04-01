<?= view('layout/header', ['title' => 'Login']) ?>

<style>
    .auth-box {
        max-width: 520px;
        margin: 48px auto;
        background: #1e293b;
        border-radius: 20px;
        padding: 36px 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        color: white;
    }
    .auth-box h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 24px;
        color: #facc15;
    }
    .alert {
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.6;
    }
    .alert-error {
        background: #7f1d1d;
        border-left: 4px solid #ef4444;
        color: #fecaca;
    }
    .alert-success {
        background: #14532d;
        border-left: 4px solid #22c55e;
        color: #bbf7d0;
    }
    .alert-verify {
        background: #1e3a5f;
        border-left: 4px solid #38bdf8;
        color: #e0f2fe;
    }
    .alert-verify .verify-title {
        font-weight: 700;
        font-size: 15px;
        margin-bottom: 8px;
        color: #38bdf8;
    }
    .alert-verify .verify-link {
        display: block;
        background: #0f172a;
        border-radius: 10px;
        padding: 10px 14px;
        margin-top: 10px;
        word-break: break-all;
        font-size: 13px;
        color: #7dd3fc;
        text-decoration: none;
        border: 1px solid #38bdf8;
    }
    .alert-verify .verify-link:hover {
        background: #1e3a5f;
        color: white;
    }
    .verify-btn {
        display: inline-block;
        margin-top: 12px;
        background: #38bdf8;
        color: #0f172a;
        font-weight: 700;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 14px;
    }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #cbd5e1; }
    .form-group input {
        width: 100%; box-sizing: border-box;
        padding: 13px 16px; border: none; border-radius: 12px;
        font-size: 15px; background: #f1f5f9; color: #0f172a;
    }
    .submit-btn {
        width: 100%; padding: 14px;
        background: #facc15; color: #0f172a;
        font-size: 16px; font-weight: 700;
        border: none; border-radius: 12px; cursor: pointer;
        margin-top: 4px;
    }
    .submit-btn:hover { background: #fde047; }
    .auth-footer { margin-top: 18px; text-align: center; }
    .auth-footer a { color: #38bdf8; font-size: 14px; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }
</style>

<div class="auth-box">
    <h1>Login</h1>

    <?php
    $successMsg = session()->getFlashdata('success');
    $errorMsg   = session()->getFlashdata('error');
    ?>

    <?php if ($errorMsg): ?>
        <div class="alert alert-error"><?= esc($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($successMsg && str_starts_with($successMsg, '__LINK__')): ?>
        <?php $verifyUrl = substr($successMsg, 8); ?>
        <div class="alert alert-verify">
            <div class="verify-title">✉ Verify your email address</div>
            <p style="margin:0 0 4px;">SMTP is not configured, so no email was sent. Click the button below to verify your account:</p>
            <a class="verify-btn" href="<?= esc($verifyUrl) ?>">Click here to verify your email</a>
            <p style="margin:12px 0 4px; font-size:12px; color:#93c5fd;">Or copy this link:</p>
            <a class="verify-link" href="<?= esc($verifyUrl) ?>"><?= esc($verifyUrl) ?></a>
        </div>

    <?php elseif ($successMsg && str_starts_with($successMsg, '__RESET__')): ?>
        <?php $resetUrl = substr($successMsg, 9); ?>
        <div class="alert alert-verify">
            <div class="verify-title">🔑 Reset your password</div>
            <p style="margin:0 0 4px;">SMTP is not configured. Click the button below to reset your password:</p>
            <a class="verify-btn" href="<?= esc($resetUrl) ?>">Click here to reset password</a>
            <p style="margin:12px 0 4px; font-size:12px; color:#93c5fd;">Or copy this link:</p>
            <a class="verify-link" href="<?= esc($resetUrl) ?>"><?= esc($resetUrl) ?></a>
        </div>

    <?php elseif ($successMsg && str_starts_with($successMsg, '__EMAIL__')): ?>
        <?php $emailMsg = substr($successMsg, 9); ?>
        <div class="alert alert-success"><?= $emailMsg ?></div>

    <?php elseif ($successMsg): ?>
        <div class="alert alert-success"><?= esc($successMsg) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('login') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Email address</label>
            <input type="email" name="email" value="<?= esc(old('email')) ?>" placeholder="you@iit.ac.lk" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="submit-btn">Login</button>
    </form>

    <div class="auth-footer">
        <a href="<?= base_url('forgot-password') ?>">Forgot your password?</a>
        &nbsp;&middot;&nbsp;
        <a href="<?= base_url('register') ?>">Create an account</a>
    </div>
</div>

<?= view('layout/footer') ?>
