<?php
require_once 'includes/config.php';
if (isLoggedIn()) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }
$error = ''; $old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $pass  = $_POST['password'] ?? '';
    $old   = ['email' => $email];
    if (empty($email) || empty($pass)) {
        $error = 'Enter your email and password.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user'] = $user;
            session_regenerate_id(true);
            if (!empty($_POST['remember'])) {
                setcookie('ccx_rem', base64_encode($email), time()+7*86400, '/', '', false, true);
            }
            flash('success', 'Welcome back, '.$user['name'].'!');
            header('Location:'.BASE_URL.'/dashboard.php'); exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Active ads for login page
$adsStmt = db()->prepare("
    SELECT * FROM ads
    WHERE is_active=1
      AND (show_on='login' OR show_on='all')
      AND (starts_at IS NULL OR starts_at<=NOW())
      AND (ends_at   IS NULL OR ends_at>=NOW())
    ORDER BY sort_order ASC, created_at DESC
");
$adsStmt->execute();
$ads = $adsStmt->fetchAll();

$remembered = isset($_COOKIE['ccx_rem']) ? base64_decode($_COOKIE['ccx_rem']) : ($old['email'] ?? '');
$typeLabels = ['info'=>'ℹ Info','news'=>'📰 News','event'=>'📅 Event','feature'=>'🎉 Feature','notice'=>'📢 Notice'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sign In — Campus Connect X</title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/app.css">
<style>
/* ── Responsive ad system ─────────────────────────────────
   All sizes via clamp() — works on any screen width        */

/* Left panel fills sidebar fully */
.auth-left { padding:0 !important; overflow:hidden !important; position:relative; background:#0F172A !important; }

/* Brand badge — always top-left */
.ad-brand {
  position:absolute; top:clamp(16px,3vw,28px); left:clamp(16px,3vw,32px);
  z-index:10; display:flex; align-items:center; gap:10px;
  color:#fff; font-size:clamp(12px,1.6vw,15px); font-weight:800;
  text-decoration:none; letter-spacing:-.3px;
}
.ad-brand-icon {
  width:clamp(28px,3.5vw,34px); height:clamp(28px,3.5vw,34px);
  background:rgba(255,255,255,.15); border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  font-size:clamp(14px,2vw,18px); backdrop-filter:blur(6px);
}

/* Slide container */
.ad-stage { position:absolute; inset:0; }

/* Individual slide */
.ad-slide {
  position:absolute; inset:0;
  opacity:0; transform:translateY(14px);
  transition:opacity .65s ease, transform .65s ease;
  pointer-events:none;
  display:flex; flex-direction:column; justify-content:flex-end;
}
.ad-slide.active { opacity:1; transform:translateY(0); pointer-events:auto; }

/* Background colour layer */
.ad-bg { position:absolute; inset:0; z-index:0; transition:background .7s; }

/* Image fills full panel as background — any aspect ratio works */
.ad-img {
  position:absolute; inset:0; width:100%; height:100%;
  object-fit:cover;   /* fills the panel completely */
  object-position:center;
  z-index:1;
  opacity:.75;        /* toned down so text overlay stays readable */
}

/* Animated shapes (no-image fallback) */
.ad-shapes { position:absolute; inset:0; z-index:0; overflow:hidden; }
.ad-shape  {
  position:absolute; border-radius:50%;
  background:rgba(255,255,255,.05);
  animation:adFloat 8s ease-in-out infinite;
}
.ad-shape:nth-child(1){ width:55%; padding-bottom:55%; top:-15%; right:-8%;  animation-delay:0s; }
.ad-shape:nth-child(2){ width:35%; padding-bottom:35%; bottom:5%; left:-6%;  animation-delay:-3s; }
.ad-shape:nth-child(3){ width:22%; padding-bottom:22%; top:35%;  right:25%;  animation-delay:-5.5s; }
@keyframes adFloat {
  0%,100%{ transform:translateY(0) scale(1); }
  50%    { transform:translateY(-18px) scale(1.04); }
}

/* Gradient overlay — strong enough for any image */
.ad-vignette {
  position:absolute; inset:0; z-index:2;
  background:
    linear-gradient(to top,  rgba(0,0,0,.90) 0%,  rgba(0,0,0,.50) 40%, rgba(0,0,0,.10) 70%, transparent 100%),
    linear-gradient(to right, rgba(0,0,0,.25) 0%, transparent 60%);
}

/* Slide content — fluid sizes via clamp() */
.ad-content {
  position:relative; z-index:3;
  padding:clamp(20px,4vw,52px) clamp(18px,4vw,48px) clamp(24px,4vw,56px);
}
.ad-type-pill {
  display:inline-flex; align-items:center; gap:6px;
  background:rgba(255,255,255,.15); backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.2); color:#fff;
  font-size:clamp(9px,1vw,11px); font-weight:700;
  padding:clamp(3px,.5vw,5px) clamp(10px,1.5vw,14px);
  border-radius:20px; margin-bottom:clamp(10px,1.8vw,20px);
  text-transform:uppercase; letter-spacing:.7px;
}
.ad-title {
  font-size:clamp(18px,3.2vw,36px); font-weight:900; color:#fff;
  line-height:1.15; margin-bottom:clamp(8px,1.2vw,12px);
  font-family:var(--fd,'Plus Jakarta Sans',sans-serif);
}
.ad-body {
  font-size:clamp(11px,1.4vw,14px); color:rgba(255,255,255,.72);
  line-height:1.7; margin-bottom:clamp(14px,2.5vw,28px);
  max-width:min(380px, 85%);
  display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;
}
.ad-cta {
  display:inline-flex; align-items:center; gap:8px;
  background:rgba(255,255,255,.15); backdrop-filter:blur(8px);
  border:1.5px solid rgba(255,255,255,.35); color:#fff;
  font-size:clamp(11px,1.4vw,13px); font-weight:700;
  padding:clamp(8px,1.2vw,12px) clamp(16px,2.5vw,24px);
  border-radius:50px; text-decoration:none; transition:all .25s;
}
.ad-cta:hover { background:rgba(255,255,255,.28); border-color:rgba(255,255,255,.6); transform:translateY(-2px); }

/* Dots + progress */
.ad-dots {
  position:absolute; bottom:clamp(12px,2vw,22px); left:clamp(18px,3vw,48px);
  display:flex; gap:7px; z-index:10;
}
.ad-dot {
  width:clamp(5px,.8vw,7px); height:clamp(5px,.8vw,7px);
  border-radius:50%; background:rgba(255,255,255,.3);
  cursor:pointer; transition:all .3s; border:none; padding:0;
}
.ad-dot.active { background:#fff; transform:scale(1.35); }

.ad-progress { position:absolute; bottom:0; left:0; right:0; height:3px; background:rgba(255,255,255,.1); z-index:10; }
.ad-progress-bar { height:100%; background:rgba(255,255,255,.6); width:0%; transition:width linear; }

/* Touch swipe hint on mobile */
.ad-swipe-hint {
  position:absolute; bottom:clamp(12px,2vw,22px); right:clamp(14px,2.5vw,32px);
  z-index:10; font-size:clamp(8px,1vw,10px); color:rgba(255,255,255,.35);
  display:none;
}
@media(max-width:768px){ .ad-swipe-hint{ display:block; } }

/* ── Login page responsive breakpoints ───────── */

/* Large desktop (1440px+) */
@media(min-width:1440px){
  .auth-wrap  { grid-template-columns: 58% 42%; }
  .ad-title   { font-size:40px; }
  .ad-body    { font-size:15px; -webkit-line-clamp:4; }
}

/* Desktop/Laptop (1025–1439px): side by side */
@media(min-width:1025px) and (max-width:1439px){
  .auth-wrap  { grid-template-columns: 55% 45%; }
  .ad-title   { font-size:clamp(22px,2.8vw,34px); }
  .ad-body    { -webkit-line-clamp:3; }
}

/* Tablet landscape (769–1024px): banner above form */
@media(max-width:1024px) and (min-width:769px){
  .auth-wrap        { grid-template-columns:1fr; grid-template-rows:320px 1fr; }
  .auth-left        { height:320px; }
  .ad-title         { font-size:28px; }
  .ad-body          { font-size:13px; -webkit-line-clamp:2; }
  .ad-content       { padding:20px 28px 24px; }
  .ad-dots          { bottom:16px; left:28px; }
  .ad-brand         { top:18px; left:24px; }
}

/* Tablet portrait (481–768px): shorter banner */
@media(max-width:768px) and (min-width:481px){
  .auth-wrap        { grid-template-columns:1fr; grid-template-rows:260px 1fr; }
  .auth-left        { height:260px; }
  .ad-title         { font-size:22px; }
  .ad-body          { display:none; }  /* hide body — not enough room */
  .ad-content       { padding:14px 20px 20px; }
  .ad-type-pill     { margin-bottom:8px; font-size:9px; }
  .ad-dots          { bottom:14px; left:20px; }
  .ad-brand         { top:14px; left:18px; font-size:12px; }
  .ad-brand-icon    { width:28px; height:28px; font-size:14px; }
}

/* Mobile (≤480px): compact banner, form below */
@media(max-width:480px){
  .auth-wrap        { grid-template-columns:1fr; grid-template-rows:220px 1fr; }
  .auth-left        { height:220px; }
  .ad-title         { font-size:18px; line-height:1.2; }
  .ad-body          { display:none; }
  .ad-cta           { display:none; }
  .ad-content       { padding:10px 14px 14px; }
  .ad-type-pill     { font-size:8px; padding:3px 9px; margin-bottom:6px; }
  .ad-dots          { bottom:10px; left:14px; gap:5px; }
  .ad-dot           { width:5px; height:5px; }
  .ad-brand         { top:10px; left:12px; gap:7px; font-size:11px; }
  .ad-brand-icon    { width:24px; height:24px; font-size:12px; }
  .ad-progress      { height:2px; }
}
</style>
</head>
<body>
<div class="auth-wrap">

  <!-- ══ LEFT: Responsive Ad Panel ═══════════════════════ -->
  <div class="auth-left">

    <?php if (empty($ads)): ?>
    <!-- Fallback -->
    <div style="position:absolute;inset:0;background:linear-gradient(160deg,#0F172A,#2D1B69);display:flex;flex-direction:column;justify-content:flex-end;">
      <a href="<?=BASE_URL?>" class="ad-brand"><div class="ad-brand-icon">🎓</div><span>Campus Connect X</span></a>
      <div class="ad-content">
        <div class="ad-title">Your Student Ecosystem.</div>
        <p class="ad-body">Connect with verified students, find internships, sell your skills and build your career — all in one place.</p>
      </div>
    </div>

    <?php else: ?>

    <!-- Brand -->
    <a href="<?=BASE_URL?>" class="ad-brand">
      <div class="ad-brand-icon">🎓</div>
      <span>Campus Connect X</span>
    </a>

    <!-- Slides -->
    <div class="ad-stage" id="adStage">
      <?php foreach($ads as $i=>$ad):
        $hasImg = !empty($ad['image_path']) && file_exists(__DIR__.'/'.$ad['image_path']);
      ?>
      <div class="ad-slide <?=$i===0?'active':''?>" data-index="<?=$i?>">

        <!-- Background colour -->
        <div class="ad-bg" style="background:<?=clean($ad['bg_color'])?>"></div>

        <?php if($hasImg): ?>
          <!--
            Image fills the entire panel using object-fit:cover.
            The bg_color shows as letterbox behind transparent areas.
            Dual gradient overlay ensures text is always readable
            regardless of image brightness or aspect ratio.
          -->
          <img src="<?=BASE_URL.'/'.$ad['image_path']?>"
               class="ad-img"
               alt="<?=clean($ad['title'])?>">
          <div class="ad-vignette"></div>
        <?php else: ?>
          <!-- No image: animated shapes on colour background -->
          <div class="ad-shapes">
            <div class="ad-shape"></div>
            <div class="ad-shape"></div>
            <div class="ad-shape"></div>
          </div>
          <div class="ad-vignette"></div>
        <?php endif; ?>

        <div class="ad-content">
          <div class="ad-type-pill"><?=$typeLabels[$ad['type']]??('📢 '.ucfirst($ad['type']))?></div>
          <div class="ad-title"><?=clean($ad['title'])?></div>
          <p class="ad-body"><?=clean($ad['body'])?></p>
          <?php if(!empty($ad['cta_text'])&&!empty($ad['cta_link'])): ?>
          <a href="<?=clean($ad['cta_link'])?>" class="ad-cta"><?=clean($ad['cta_text'])?></a>
          <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

    <!-- Dots -->
    <?php if(count($ads)>1): ?>
    <div class="ad-dots" id="adDots">
      <?php foreach($ads as $i=>$ad): ?>
      <button class="ad-dot <?=$i===0?'active':''?>" onclick="goToSlide(<?=$i?>)"></button>
      <?php endforeach; ?>
    </div>
    <div class="ad-swipe-hint">← swipe →</div>
    <?php endif; ?>

    <!-- Progress bar -->
    <div class="ad-progress"><div class="ad-progress-bar" id="adBar"></div></div>

    <?php endif; ?>
  </div>

  <!-- ══ RIGHT: Sign In Form ══════════════════════════════ -->
  <div class="auth-right">
    <div class="auth-box">
      <h2>Sign in</h2>
      <p class="auth-sub">New here? <a href="<?=BASE_URL?>/register.php">Create a free account</a></p>

      <?php if($error): ?><div class="alert al-e"><?=clean($error)?></div><?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf" value="<?=csrf()?>">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control"
                 placeholder="you@university.edu"
                 value="<?=clean($remembered)?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between;">
            Password
            <a href="<?=BASE_URL?>/forgot_password.php" style="color:var(--brand);font-size:12px;font-weight:500;">Forgot password?</a>
          </label>
          <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
          <input type="checkbox" name="remember" id="rem" style="width:15px;height:15px;accent-color:var(--brand);" <?=!empty($remembered)?'checked':''?>>
          <label for="rem" style="font-size:13px;color:var(--text2);cursor:pointer;">Remember me for 7 days</label>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg">Sign In →</button>
      </form>

      <div style="text-align:center;margin:20px 0;font-size:12px;color:var(--text3);">or</div>
      <a href="<?=BASE_URL?>/register.php" class="btn btn-ghost btn-full">🎓 Create Student Account</a>

      <?php
      $students = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $jobs     = (int)db()->query("SELECT COUNT(*) FROM jobs WHERE is_active=1")->fetchColumn();
      $services = (int)db()->query("SELECT COUNT(*) FROM services WHERE is_active=1")->fetchColumn();
      ?>
      <div style="display:flex;justify-content:space-around;margin-top:24px;padding:14px;background:var(--surface2);border-radius:var(--r);">
        <?php foreach([[$students,'STUDENTS'],[$jobs,'OPEN JOBS'],[$services,'SERVICES']] as [$v,$l]): ?>
        <div style="text-align:center;">
          <div style="font-size:20px;font-weight:800;color:var(--brand);"><?=$v?></div>
          <div style="font-size:9px;color:var(--text3);font-weight:600;letter-spacing:.5px;"><?=$l?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <p style="font-size:11px;color:var(--text3);text-align:center;margin-top:14px;">By signing in you agree to our Terms of Service &amp; Privacy Policy.</p>
    </div>
  </div>

</div>

<?php if(!empty($ads)): ?>
<script>
(function(){
  const DUR    = 6000;
  const slides = document.querySelectorAll('.ad-slide');
  const dots   = document.querySelectorAll('.ad-dot');
  const bar    = document.getElementById('adBar');
  const stage  = document.getElementById('adStage');
  let cur = 0, timer;

  window.goToSlide = function(n) {
    slides[cur].classList.remove('active');
    if(dots[cur]) dots[cur].classList.remove('active');
    cur = (n + slides.length) % slides.length;
    slides[cur].classList.add('active');
    if(dots[cur]) dots[cur].classList.add('active');
    resetBar();
    clearTimeout(timer);
    timer = setTimeout(() => goToSlide(cur + 1), DUR);
  };

  function resetBar(){
    if(!bar) return;
    bar.style.transition = 'none';
    bar.style.width = '0%';
    requestAnimationFrame(() => requestAnimationFrame(() => {
      bar.style.transition = 'width ' + DUR + 'ms linear';
      bar.style.width = '100%';
    }));
  }

  // Auto-play
  if(slides.length > 1){
    timer = setTimeout(() => goToSlide(1), DUR);
    resetBar();

    // Pause on hover (desktop)
    stage.addEventListener('mouseenter', () => {
      clearTimeout(timer);
      if(bar){ bar.style.transition='none'; }
    });
    stage.addEventListener('mouseleave', () => {
      timer = setTimeout(() => goToSlide(cur+1), 2000);
      resetBar();
    });

    // Swipe (touch)
    let tx = 0;
    stage.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, {passive:true});
    stage.addEventListener('touchend',   e => {
      const dx = e.changedTouches[0].clientX - tx;
      if(Math.abs(dx) > 40) goToSlide(dx < 0 ? cur+1 : cur-1);
    }, {passive:true});
  }
})();
</script>
<?php endif; ?>
</body>
</html>