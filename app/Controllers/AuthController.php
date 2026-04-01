<?php

namespace App\Controllers;

use Config\Database;

class AuthController extends BaseController
{
    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Send an HTML email using settings from .env (SMTP).
     */
    private function sendEmail(string $to, string $subject, string $body): bool
    {
        $email = \Config\Services::email();
        $email->initialize([
            'protocol'   => env('EMAIL_PROTOCOL', 'smtp'),
            'SMTPHost'   => env('EMAIL_SMTP_HOST', ''),
            'SMTPUser'   => env('EMAIL_SMTP_USER', ''),
            'SMTPPass'   => env('EMAIL_SMTP_PASS', ''),
            'SMTPPort'   => (int) env('EMAIL_SMTP_PORT', 587),
            'SMTPCrypto' => env('EMAIL_SMTP_CRYPTO', 'tls'),
            'mailType'   => 'html',
            'charset'    => 'UTF-8',
        ]);
        $email->setFrom(env('EMAIL_FROM_ADDRESS', 'no-reply@iit.ac.lk'), env('EMAIL_FROM_NAME', 'Alumni Influencer'));
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($body);
        return $email->send(false);
    }

    /**
     * Simple session-based rate limiter.
     * Returns false when the caller should be blocked.
     */
    private function checkRateLimit(string $action, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        $key  = 'rate_' . $action . '_' . $this->request->getIPAddress();
        $data = session()->get($key) ?? ['count' => 0, 'window_start' => time()];

        if ((time() - $data['window_start']) > $windowSeconds) {
            $data = ['count' => 0, 'window_start' => time()];
        }

        $data['count']++;
        session()->set($key, $data);

        return $data['count'] <= $maxAttempts;
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function register()
    {
        return view('auth/register');
    }

    public function registerPost()
    {
        if (!$this->checkRateLimit('register', 10, 600)) {
            return redirect()->back()->with('error', 'Too many registration attempts. Please wait 10 minutes before trying again.');
        }

        $db = Database::connect();

        $name            = trim((string) $this->request->getPost('name'));
        $email           = strtolower(trim((string) $this->request->getPost('email')));
        $role            = trim((string) $this->request->getPost('role'));
        $password        = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($name === '' || $email === '' || $role === '' || $password === '' || $confirmPassword === '') {
            return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid email address.');
        }

        if (!preg_match('/@iit\.ac\.lk$/', $email)) {
            return redirect()->back()->withInput()->with('error', 'Only IIT email addresses (@iit.ac.lk) are allowed.');
        }

        if ($password !== $confirmPassword) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
        }

        // Strong password: 8+ chars, uppercase, lowercase, digit
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters and include uppercase, lowercase and a number.');
        }

        $existingUser = $db->table('users')->where('email', $email)->get()->getRow();
        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'An account with that email address already exists.');
        }

        $token = bin2hex(random_bytes(32)); // 64-char hex, cryptographically random
        $now   = date('Y-m-d H:i:s');

        $db->table('users')->insert([
            'name'          => $name,
            'email'         => $email,
            'role'          => $role,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'is_verified'   => 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $userId = $db->insertID();

        // Store SHA-256 hash of token (never store raw tokens)
        $db->table('email_verifications')->insert([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'created_at' => $now,
        ]);

        $verifyLink = base_url('verify-email?token=' . urlencode($token));

        // Attempt to send email via SMTP (requires EMAIL_* vars set in .env)
        $emailSent = $this->sendEmail(
            $email,
            'Verify your Alumni Influencer account',
            '<p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Thank you for registering. Please click the link below to verify your email address. This link expires in <strong>24 hours</strong>.</p>'
            . '<p><a href="' . $verifyLink . '">' . $verifyLink . '</a></p>'
            . '<p>If you did not create an account, you can safely ignore this email.</p>'
        );

        // Fallback for local development: show the link directly if SMTP is not configured
        if ($emailSent) {
            $msg = '__EMAIL__Registered! A verification email has been sent to <strong>' . esc($email) . '</strong>. Click the link in your inbox to activate your account.';
        } else {
            $msg = '__LINK__' . $verifyLink;
        }

        return redirect()->to('/login')->with('success', $msg);
    }

    // -------------------------------------------------------------------------
    // Email Verification
    // -------------------------------------------------------------------------

    public function verifyEmail()
    {
        $db    = Database::connect();
        $token = trim((string) $this->request->getGet('token'));

        if ($token === '') {
            return redirect()->to('/login')->with('error', 'Invalid verification link.');
        }

        $tokenHash = hash('sha256', $token);
        $record    = $db->table('email_verifications')->where('token_hash', $tokenHash)->get()->getRow();

        if (!$record) {
            return redirect()->to('/login')->with('error', 'Invalid or already-used verification link.');
        }

        if (strtotime($record->expires_at) < time()) {
            // Clean up expired token
            $db->table('email_verifications')->where('id', $record->id)->delete();
            return redirect()->to('/login')->with('error', 'Verification link has expired. Please register again.');
        }

        $db->table('users')->where('id', $record->user_id)->update([
            'is_verified' => 1,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // Single-use: delete token after successful verification
        $db->table('email_verifications')->where('id', $record->id)->delete();

        return redirect()->to('/login')->with('success', 'Email verified successfully. You can now log in.');
    }

    // -------------------------------------------------------------------------
    // Login / Logout
    // -------------------------------------------------------------------------

    public function login()
    {
        return view('auth/login');
    }

    public function loginPost()
    {
        // Rate limit: 5 attempts per 5 minutes per IP
        if (!$this->checkRateLimit('login', 5, 300)) {
            return redirect()->back()->with('error', 'Too many login attempts. Please wait 5 minutes before trying again.');
        }

        $db       = Database::connect();
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'Email and password are required.');
        }

        $user = $db->table('users')->where('email', $email)->get()->getRowArray();

        // Check user exists and password matches (timing-safe via password_verify)
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if ((int)($user['is_verified'] ?? 0) !== 1) {
            return redirect()->back()->withInput()->with('error', 'Please verify your email address before logging in.');
        }

        // Regenerate session ID to prevent session fixation
        session()->regenerate();
        session()->set([
            'user_id'   => $user['id'],
            'user_name' => $user['name'] ?? '',
            'role'      => $user['role'] ?? 'alumnus',
            'logged_in' => true,
        ]);

        return redirect()->to('/profile')->with('success', 'Welcome back, ' . esc($user['name']) . '!');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'You have been logged out successfully.');
    }

    // -------------------------------------------------------------------------
    // Password Reset
    // -------------------------------------------------------------------------

    public function forgotPassword()
    {
        return view('auth/forgot_password');
    }

    public function forgotPasswordPost()
    {
        // Rate limit: 3 attempts per 10 minutes per IP
        if (!$this->checkRateLimit('forgot_password', 3, 600)) {
            return redirect()->back()->with('error', 'Too many reset requests. Please wait 10 minutes.');
        }

        $db    = Database::connect();
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Enter a valid email address.');
        }

        // Always show generic success to prevent email enumeration
        $user = $db->table('users')->where('email', $email)->where('is_verified', 1)->get()->getRowArray();

        if ($user) {
            // Invalidate any existing reset token for this user first
            $db->table('password_resets')->where('user_id', $user['id'])->delete();

            $token = bin2hex(random_bytes(32));
            $now   = date('Y-m-d H:i:s');

            $db->table('password_resets')->insert([
                'user_id'    => $user['id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'created_at' => $now,
            ]);

            $resetLink = base_url('reset-password?token=' . urlencode($token));

            $emailSent = $this->sendEmail(
                $email,
                'Reset your Alumni Influencer password',
                '<p>Hi ' . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>A password reset was requested for your account. Click the link below to set a new password. This link expires in <strong>1 hour</strong>.</p>'
                . '<p><a href="' . $resetLink . '">' . $resetLink . '</a></p>'
                . '<p>If you did not request this, you can safely ignore this email — your password will not be changed.</p>'
            );

            // Fallback for local development: show link directly if SMTP is not configured
            if (!$emailSent) {
                return redirect()->to('/login')->with('success', '__RESET__' . $resetLink);
            }
        }

        return redirect()->to('/login')->with('success', 'If that email address is registered and verified, a password reset link has been sent.');
    }

    public function resetPassword()
    {
        $token = trim((string) $this->request->getGet('token'));

        if ($token === '') {
            return redirect()->to('/forgot-password')->with('error', 'Invalid or missing reset token.');
        }

        $db        = Database::connect();
        $tokenHash = hash('sha256', $token);
        $record    = $db->table('password_resets')->where('token_hash', $tokenHash)->get()->getRow();

        if (!$record || strtotime($record->expires_at) < time()) {
            return redirect()->to('/forgot-password')->with('error', 'This password reset link has expired or is invalid. Please request a new one.');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function resetPasswordPost()
    {
        $db              = Database::connect();
        $token           = trim((string) $this->request->getPost('token'));
        $password        = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if ($token === '') {
            return redirect()->to('/forgot-password')->with('error', 'Invalid reset token.');
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters and include uppercase, lowercase and a number.');
        }

        if ($password !== $passwordConfirm) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
        }

        $tokenHash = hash('sha256', $token);
        $record    = $db->table('password_resets')->where('token_hash', $tokenHash)->get()->getRow();

        if (!$record || strtotime($record->expires_at) < time()) {
            return redirect()->to('/forgot-password')->with('error', 'Password reset link has expired. Please request a new one.');
        }

        $db->table('users')->where('id', $record->user_id)->update([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        // Single-use: delete token after successful reset
        $db->table('password_resets')->where('id', $record->id)->delete();

        return redirect()->to('/login')->with('success', 'Password reset successfully. You can now log in with your new password.');
    }
}
