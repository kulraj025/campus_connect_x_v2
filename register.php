<?php
require_once 'includes/config.php';
require_once 'includes/ads.php';
if(isLoggedIn()){header('Location:'.BASE_URL.'/dashboard.php');exit;}
$errors=[];$old=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $old=$_POST;
    $name=s($_POST['name']??'');$username=strtolower(s($_POST['username']??''));
    $email=filter_var(trim($_POST['email']??''),FILTER_SANITIZE_EMAIL);
    $univ=s($_POST['university']??'');$dept=s($_POST['department']??'');
    $gradyr=(int)($_POST['graduation_year']??0);
    $pass=$_POST['password']??'';$conf=$_POST['password_confirmation']??'';
    if(strlen($name)<2)                                    $errors['name']='Name required.';
    if(!preg_match('/^[a-z0-9_]{3,30}$/',$username))       $errors['username']='3–30 chars, letters/numbers/underscore only.';
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))           $errors['email']='Valid email required.';
    if(strlen($univ)<2)                                    $errors['university']='University required.';
    if(strlen($dept)<2)                                    $errors['department']='Department required.';
    if($gradyr<2024||$gradyr>2035)                         $errors['graduation_year']='Valid year required.';
    if(strlen($pass)<8)                                    $errors['password']='Min 8 characters.';
    if($pass!==$conf)                                      $errors['password']='Passwords do not match.';
    if(empty($errors)){
        $chk=db()->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $chk->execute([$email,$username]);
        if($chk->fetch()){$errors['email']='Email or username already taken.';}
        else{
            $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
            db()->prepare("INSERT INTO users(name,username,email,password,university,department,graduation_year)VALUES(?,?,?,?,?,?,?)")
               ->execute([$name,$username,$email,$hash,$univ,$dept,$gradyr]);
            $u=db()->prepare("SELECT * FROM users WHERE email=?");$u->execute([$email]);
            $_SESSION['user']=$u->fetch();
            session_regenerate_id(true);
            // Welcome notification
            notify($_SESSION['user']['id'],'welcome','Welcome to Campus Connect X, '.$name.'! Complete your profile to get started.','/profile.php?tab=profile');
            flash('success','Welcome to Campus Connect X, '.$name.'! 🎉');
            header('Location:'.BASE_URL.'/dashboard.php');exit;
        }
    }
}
$loginAds = getAds('login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Join Campus Connect X</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body style="display:grid;grid-template-columns:1fr 1fr;min-height:100vh;">

<!-- LEFT: Ad Carousel (same as login) -->
<div style="position:relative;overflow:hidden;">
  <div id="adCarousel" style="position:relative;height:100%;min-height:100vh;">
    <?php if(empty($loginAds)): ?>
    <div style="position:absolute;inset:0;background:linear-gradient(160deg,#0F172A,#1E3A5F,#2D1B69);display:flex;flex-direction:column;justify-content:center;padding:48px;">
      <div style="font-family:'Syne',sans-serif;font-size:32px;font-weight:800;color:#fff;margin-bottom:16px;">Join thousands of verified students.</div>
      <p style="font-size:15px;color:rgba(255,255,255,.55);line-height:1.75;">Connect, learn, earn and build your career — all in one place.</p>
    </div>
    <?php else: foreach($loginAds as $i=>$ad): $tc=adTypeConfig($ad['type']); trackAdView($ad['id']); ?>
    <div class="ad-slide <?= $i===0?'active':'' ?>" data-index="<?= $i ?>" style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:space-between;padding:40px 44px;opacity:<?= $i===0?1:0 ?>;transition:opacity .7s ease,transform .7s ease;pointer-events:<?= $i===0?'auto':'none' ?>;transform:<?= $i===0?'translateY(0)':'translateY(30px)' ?>;">
      <div style="position:absolute;inset:0;background:linear-gradient(135deg,<?= clean($ad['bg_from']) ?>,<?= clean($ad['bg_to']) ?>,<?= clean($ad['bg_from']) ?>);background-size:400% 400%;animation:gradShift 8s ease infinite;"></div>
      <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,.07) 0%,transparent 70%);border-radius:50%;"></div>
      <div style="position:relative;z-index:2;">
        <a href="<?= BASE_URL ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:40px;">
          <div style="width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;">🎓</div>
          <span style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#fff;">Campus Connect X</span>
        </a>
        <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:11px;font-weight:700;padding:5px 12px;border-radius:99px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:18px;"><?= $tc['icon'] ?> <?= $tc['label'] ?></div>
        <?php if(!empty($ad['image_path'])): ?><img src="<?= BASE_URL.'/'.$ad['image_path'] ?>" style="width:100%;max-height:150px;object-fit:cover;border-radius:12px;margin-bottom:18px;border:1px solid rgba(255,255,255,.15);"><?php endif;?>
        <div style="font-family:'Syne',sans-serif;font-size:30px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:10px;"><?= clean($ad['title']) ?></div>
        <?php if($ad['subtitle']): ?><div style="font-size:13px;font-weight:600;color:rgba(255,255,255,.65);margin-bottom:8px;"><?= clean($ad['subtitle']) ?></div><?php endif;?>
        <?php if($ad['body']): ?><p style="font-size:13px;color:rgba(255,255,255,.5);line-height:1.7;margin-bottom:20px;max-width:340px;"><?= clean($ad['body']) ?></p><?php endif;?>
        <?php if($ad['cta_link']): ?><a href="<?= clean($ad['cta_link']) ?>" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);color:#fff;padding:10px 20px;border-radius:99px;font-size:13px;font-weight:700;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'"><?= clean($ad['cta_text']??'Learn More') ?> →</a><?php endif;?>
      </div>
      <div id="prog-<?= $i ?>" style="position:absolute;bottom:0;left:0;right:0;height:2px;background:rgba(255,255,255,.1);"><div style="height:100%;background:rgba(255,255,255,.5);transition:width linear;border-radius:0 2px 2px 0;width:0%;"></div></div>
    </div>
    <?php endforeach; endif;?>
    <?php if(count($loginAds)>1): ?>
    <div style="position:absolute;bottom:20px;left:44px;z-index:10;display:flex;gap:8px;">
      <?php foreach($loginAds as $i=>$a): ?>
      <div onclick="goSlide(<?= $i ?>)" style="width:<?= $i===0?'22':'6' ?>px;height:6px;border-radius:99px;background:<?= $i===0?'#fff':'rgba(255,255,255,.3)' ?>;cursor:pointer;transition:all .4s;" id="dot-<?= $i ?>"></div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
</div>

<!-- RIGHT: Register Form -->
<div style="background:#F0F4FF;padding:48px;display:flex;align-items:center;justify-content:center;overflow-y:auto;">
  <div style="width:100%;max-width:420px;">
    <h2 style="font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#0F172A;margin-bottom:5px;">Create your account</h2>
    <p style="font-size:13px;color:#94A3B8;margin-bottom:24px;">Already have one? <a href="<?= BASE_URL ?>/login.php" style="color:#2563EB;font-weight:600;">Sign in</a></p>
    <?php if(!empty($errors)): ?><div style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;">Please fix the errors below.</div><?php endif;?>
    <form method="POST" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">Full Name</label>
          <input type="text" name="name" style="width:100%;padding:10px 13px;border:1.5px solid <?= isset($errors['name'])?'#EF4444':'#E2E8F0' ?>;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="John Doe" value="<?= clean($old['name']??'') ?>" required autofocus>
          <?php if(isset($errors['name'])): ?><span style="font-size:11px;color:#EF4444;"><?= $errors['name'] ?></span><?php endif;?>
        </div>
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">Username</label>
          <div style="position:relative;">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:13px;">@</span>
            <input type="text" name="username" style="width:100%;padding:10px 13px 10px 26px;border:1.5px solid <?= isset($errors['username'])?'#EF4444':'#E2E8F0' ?>;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="johndoe" value="<?= clean($old['username']??'') ?>" required>
          </div>
          <?php if(isset($errors['username'])): ?><span style="font-size:11px;color:#EF4444;"><?= $errors['username'] ?></span><?php endif;?>
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">University Email</label>
        <input type="email" name="email" style="width:100%;padding:10px 13px;border:1.5px solid <?= isset($errors['email'])?'#EF4444':'#E2E8F0' ?>;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="you@university.edu" value="<?= clean($old['email']??'') ?>" required>
        <?php if(isset($errors['email'])): ?><span style="font-size:11px;color:#EF4444;"><?= $errors['email'] ?></span><?php endif;?>
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">University</label>
        <input type="text" name="university" style="width:100%;padding:10px 13px;border:1.5px solid <?= isset($errors['university'])?'#EF4444':'#E2E8F0' ?>;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="University of Manchester" value="<?= clean($old['university']??'') ?>" required>
        <?php if(isset($errors['university'])): ?><span style="font-size:11px;color:#EF4444;"><?= $errors['university'] ?></span><?php endif;?>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">Department</label>
          <input type="text" name="department" style="width:100%;padding:10px 13px;border:1.5px solid <?= isset($errors['department'])?'#EF4444':'#E2E8F0' ?>;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="Computer Science" value="<?= clean($old['department']??'') ?>" required>
          <?php if(isset($errors['department'])): ?><span style="font-size:11px;color:#EF4444;"><?= $errors['department'] ?></span><?php endif;?>
        </div>
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">Grad Year</label>
          <select name="graduation_year" style="width:100%;padding:10px 13px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;background:#fff;">
            <option value="">Year</option>
            <?php for($y=date('Y');$y<=date('Y')+7;$y++): ?><option value="<?= $y ?>" <?= ($old['graduation_year']??'')==$y?'selected':'' ?>><?= $y ?></option><?php endfor;?>
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
        <div>
          <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">Password</label>
          <input type="password" name="password" style="width:100%;padding:10px 13px;border:1.5px solid <?= isset($errors['password'])?'#EF4444':'#E2E8F0' ?>;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="Min 8 characters" required>
          <?php if(isset($errors['password'])): ?><span style="font-size:11px;color:#EF4444;"><?= $errors['password'] ?></span><?php endif;?>
        </div>
        <div>
          <label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:5px;">Confirm Password</label>
          <input type="password" name="password_confirmation" style="width:100%;padding:10px 13px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:13.5px;outline:none;font-family:inherit;" placeholder="Repeat password" required>
        </div>
      </div>
      <button type="submit" style="width:100%;padding:12px;background:#2563EB;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;" onmouseover="this.style.background='#1D4ED8'" onmouseout="this.style.background='#2563EB'">Create Account →</button>
      <p style="font-size:11px;color:#94A3B8;text-align:center;margin-top:14px;">By joining you agree to our Terms &amp; Privacy Policy.</p>
    </form>
  </div>
</div>

<style>
@keyframes gradShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
.ad-slide.exit{opacity:0!important;transform:translateY(-30px)!important;}
@media(max-width:768px){body{grid-template-columns:1fr}div:first-child{display:none}}
</style>
<script>
const SLIDES=document.querySelectorAll('.ad-slide'),DOTS=document.querySelectorAll('[id^="dot-"]'),N=SLIDES.length;
let cur=0,timer=null;
function goSlide(n){
  SLIDES[cur].classList.add('exit');SLIDES[cur].style.opacity='0';SLIDES[cur].style.pointerEvents='none';
  if(DOTS[cur]){DOTS[cur].style.width='6px';DOTS[cur].style.background='rgba(255,255,255,.3)';}
  setTimeout(()=>SLIDES[cur].classList.remove('exit'),700);
  cur=(n+N)%N;
  SLIDES[cur].style.opacity='1';SLIDES[cur].style.transform='translateY(0)';SLIDES[cur].style.pointerEvents='auto';
  if(DOTS[cur]){DOTS[cur].style.width='22px';DOTS[cur].style.background='#fff';}
  resetProg();
}
function resetProg(){
  clearInterval(timer);
  const fill=document.querySelector('#prog-'+cur+' div');
  if(!fill)return;
  fill.style.transition='none';fill.style.width='0%';
  requestAnimationFrame(()=>{fill.style.transition='width 6000ms linear';fill.style.width='100%';});
  timer=setInterval(()=>goSlide(cur+1),6000);
}
if(N>0)resetProg();
</script>
</body></html>