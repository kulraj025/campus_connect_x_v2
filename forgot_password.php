<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    verifyCsrf();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $stmt  = db()->prepare("SELECT id,name FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user  = $stmt->fetch();

    if ($user) {
        // Delete old tokens for this email
        db()->prepare("DELETE FROM password_resets WHERE email=?")->execute([$email]);
        $token = bin2hex(random_bytes(32));
        db()->prepare("INSERT INTO password_resets (email,token,created_at) VALUES (?,?,NOW())")
           ->execute([$email, $token]);

        $resetLink = BASE_URL . '/reset_password.php?token=' . $token;
        $subject   = 'Reset your Campus Connect X password';
        $body      = "Hi {$user['name']},\n\nClick the link below to reset your password (expires in 1 hour):\n\n$resetLink\n\nIf you didn't request this, ignore this email.\n\n— Campus Connect X";
        @mail($email, $subject, $body, "From: no-reply@campusconnectx.com\r\nContent-Type: text/plain");
    }
    // Always show success (security: don't reveal if email exists)
    $msg = "If that email is registered, a reset link has been sent. Check your inbox.";
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Forgot Password — Campus Connect X</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head><body>
<div class="auth-wrap">
  <div class="auth-left" style="background:linear-gradient(160deg,#0F172A,#2D1B69);padding:48px;display:flex;flex-direction:column;justify-content:space-between;">
    <a href="<?= BASE_URL ?>" class="auth-brand"><div class="auth-brand-icon">🎓</div>Campus Connect X</a>
    <div class="auth-hero">
      <h1>Forgot your <span>password?</span></h1>
      <p>No worries — enter your email and we'll send you a secure reset link instantly.</p>
    </div>
    <div style="font-size:12px;color:rgba(255,255,255,.4);">© <?= date('Y') ?> Campus Connect X</div>
  </div>
  <div class="auth-right"><div class="auth-box">
    <h2>Reset Password</h2>
    <p class="auth-sub">Remember it? <a href="<?= BASE_URL ?>/login.php">Sign in instead</a></p>

    <?php if ($msg): ?>
    <div class="alert" style="background:#ECFDF5;color:#065F46;border:1px solid #6EE7B7;padding:14px;border-radius:10px;font-size:13px;margin-bottom:20px;">
      ✅ <?= clean($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (!$msg): ?>
    <form method="POST" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="form-group">
        <label class="form-label">Your Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@university.edu" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">Send Reset Link →</button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;">
      <a href="<?= BASE_URL ?>/login.php" style="font-size:13px;color:var(--text3);">← Back to Sign In</a>
    </div>
  </div></div>
</div>
</body></html>