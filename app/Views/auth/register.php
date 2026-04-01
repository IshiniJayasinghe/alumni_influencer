<?= $this->include('layout/header') ?>

<div style="max-width: 900px; margin: 40px auto; background: #334155; padding: 30px; border-radius: 20px; color: white;">
    <h1 style="font-size: 56px; margin-bottom: 25px;">Register</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div style="background:#991b1b; color:white; padding:15px; border-radius:12px; margin-bottom:20px;">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div style="background:#166534; color:white; padding:15px; border-radius:12px; margin-bottom:20px;">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('register') ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom: 18px;">
            <label style="display:block; font-size:18px; font-weight:bold; margin-bottom:8px;">Full name</label>
            <input type="text" name="name" value="<?= old('name') ?>" required
                   style="width:100%; padding:14px; border-radius:12px; border:none; font-size:18px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display:block; font-size:18px; font-weight:bold; margin-bottom:8px;">University email</label>
            <input type="email" name="email" value="<?= old('email') ?>" required
                   style="width:100%; padding:14px; border-radius:12px; border:none; font-size:18px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display:block; font-size:18px; font-weight:bold; margin-bottom:8px;">Account role</label>
            <select name="role" required
                    style="width:100%; padding:14px; border-radius:12px; border:none; font-size:18px;">
                <option value="alumnus" <?= old('role') === 'alumnus' ? 'selected' : '' ?>>Alumnus</option>
                <option value="developer" <?= old('role') === 'developer' ? 'selected' : '' ?>>Developer</option>
            </select>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display:block; font-size:18px; font-weight:bold; margin-bottom:8px;">Password</label>
            <input type="password" name="password" required
                   style="width:100%; padding:14px; border-radius:12px; border:none; font-size:18px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display:block; font-size:18px; font-weight:bold; margin-bottom:8px;">Confirm password</label>
            <input type="password" name="confirm_password" required
                   style="width:100%; padding:14px; border-radius:12px; border:none; font-size:18px;">
        </div>

        <p style="font-size:16px; margin-bottom:18px; color:#e5e7eb;">
            Password must contain uppercase, lowercase and a number.
        </p>

        <button type="submit"
                style="background:#facc15; color:black; border:none; padding:14px 24px; border-radius:12px; font-size:18px; font-weight:bold; cursor:pointer;">
            Register
        </button>
    </form>
</div>

<?= $this->include('layout/footer') ?>