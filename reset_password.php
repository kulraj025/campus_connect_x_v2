<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }

$token = trim($_GET['token'] ?? '');
$err   = ''; $done = false;

// Validate token (1 hour expiry)
$row = null;
if ($token) {
    $stmt = db()->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
}

if (!$row) {
    $err = 'This reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    verifyCsrf();
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (strlen($pass) < 8) {
        $err = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $err = 'Passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        db()->prepare("UPDATE users SET password=? WHERE email=?")->execute([$hash, $row['email']]);
        db()->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);
        $done = true;
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reset Password — Campus Connect X</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head><body>
<div class="auth-wrap">
  <div class="auth-left" style="background:linear-gradient(160deg,#0F172A,#2D1B69);padding:48px;display:flex;flex-direction:column;justify-content:space-between;">
    <a href="<?= BASE_URL ?>" class="auth-brand"><div class="auth-brand-icon">🎓</div>Campus Connect X</a>
    <div class="auth-hero">
      <h1>Set a new <span>password.</span></h1>
      <p>Choose a strong password that's at least 8 characters long.</p>
    </div>
    <div style="font-size:12px;color:rgba(255,255,255,.4);">© <?= date('Y') ?> Campus Connect X</div>
  </div>
  <div class="auth-right"><div class="auth-box">
    <h2>New Password</h2>

    <?php if ($done): ?>
    <div class="alert" style="background:#ECFDF5;color:#065F46;border:1px solid #6EE7B7;padding:14px;border-radius:10px;font-size:13px;margin-bottom:20px;">
      ✅ Password updated! You can now sign in.
    </div>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-full">Sign In →</a>

    <?php elseif ($err && !$row): ?>
    <div class="alert al-e"><?= clean($err) ?></div>
    <a href="<?= BASE_URL ?>/forgot_password.php" class="btn btn-primary btn-full">Request New Link →</a>

    <?php else: ?>
    <?php if ($err): ?><div class="alert al-e"><?= clean($err) ?></div><?php endif; ?>
    <form method="POST" novalidate>
      <input type="hidden" name="csrf"  value="<?= csrf() ?>">
      <input type="hidden" name="token" value="<?= clean($token) ?>">
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" placeholder="At least 8 characters" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password2" class="form-control" placeholder="Repeat your new password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">Update Password →</button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;">
      <a href="<?= BASE_URL ?>/login.php" style="font-size:13px;color:var(--text3);">← Back to Sign In</a>
    </div>
  </div></div>
</div>
</body></html>