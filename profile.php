<?php
require_once 'includes/config.php'; requireAuth();
$user = auth();
$ps = db()->prepare("SELECT * FROM profiles WHERE user_id=?"); $ps->execute([$user['id']]);
$profile = $ps->fetch() ?: [];
$skills  = json_decode($profile['skills']    ?? '[]', true) ?: [];
$exp     = json_decode($profile['experience'] ?? '[]', true) ?: [];
$edu     = json_decode($profile['education']  ?? '[]', true) ?: [];
$langs   = json_decode($profile['languages']  ?? '[]', true) ?: [];
$certs   = json_decode($profile['certifications'] ?? '[]', true) ?: [];

// ── Handle avatar upload ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    verifyCsrf();
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 5*1024*1024) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (isset($allowed[$mime])) {
            $dir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            // Delete old avatar
            if (!empty($user['avatar']) && file_exists(__DIR__.'/'.$user['avatar'])) {
                @unlink(__DIR__.'/'.$user['avatar']);
            }
            $fn = 'avatar_' . $user['id'] . '_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($file['tmp_name'], $dir . $fn)) {
                $path = 'uploads/avatars/' . $fn;
                db()->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([$path, $user['id']]);
                $u2 = db()->prepare("SELECT * FROM users WHERE id=?"); $u2->execute([$user['id']]);
                $_SESSION['user'] = $u2->fetch();
                $user = $_SESSION['user'];
                flash('success', 'Profile photo updated!');
            } else {
                flash('error', 'Upload failed. Check folder permissions.');
            }
        } else {
            flash('error', 'Only JPG, PNG, WEBP, or GIF images allowed.');
        }
    } else {
        flash('error', 'File too large or upload error. Max 5MB.');
    }
    header('Location:' . BASE_URL . '/profile.php'); exit;
}

// ── Handle profile form ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio'])) {
    verifyCsrf();
    $bio     = s($_POST['bio']      ?? '');
    $phone   = s($_POST['phone']    ?? '');
    $linkedin= filter_var(trim($_POST['linkedin'] ?? ''), FILTER_SANITIZE_URL);
    $github  = filter_var(trim($_POST['github']   ?? ''), FILTER_SANITIZE_URL);
    $website = filter_var(trim($_POST['website']  ?? ''), FILTER_SANITIZE_URL);
    $city    = s($_POST['city']     ?? '');
    $country = s($_POST['country']  ?? '');
    $summary = s($_POST['summary']  ?? '');
    $skillArr = array_values(array_filter(array_map('trim', explode(',', $_POST['skills'] ?? ''))));
    $langArr  = array_values(array_filter(array_map('trim', explode(',', $_POST['languages'] ?? ''))));
    $certArr  = array_values(array_filter(array_map('trim', explode(',', $_POST['certifications'] ?? ''))));

    $expArr = [];
    foreach ($_POST['exp_title'] ?? [] as $i => $t) {
        if (trim($t)) $expArr[] = ['title'=>s($t),'org'=>s($_POST['exp_company'][$i]??''),'period'=>s($_POST['exp_period'][$i]??''),'desc'=>s($_POST['exp_desc'][$i]??'')];
    }
    $eduArr = [];
    foreach ($_POST['edu_degree'] ?? [] as $i => $t) {
        if (trim($t)) $eduArr[] = ['title'=>s($t),'org'=>s($_POST['edu_institution'][$i]??''),'period'=>s($_POST['edu_period'][$i]??''),'desc'=>s($_POST['edu_desc'][$i]??'')];
    }

    db()->prepare("UPDATE users SET bio=?,updated_at=NOW() WHERE id=?")->execute([$bio, $user['id']]);
    $d = [json_encode($skillArr),json_encode($expArr),json_encode($eduArr),json_encode($langArr),json_encode($certArr),
          $phone,$linkedin,$github,$website,$city,$country,$summary,$user['id']];
    if ($profile) {
        db()->prepare("UPDATE profiles SET skills=?,experience=?,education=?,languages=?,certifications=?,phone=?,linkedin=?,github=?,website=?,city=?,country=?,summary=?,updated_at=NOW() WHERE user_id=?")->execute($d);
    } else {
        db()->prepare("INSERT INTO profiles(skills,experience,education,languages,certifications,phone,linkedin,github,website,city,country,summary,user_id)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($d);
    }

    $u2 = db()->prepare("SELECT * FROM users WHERE id=?"); $u2->execute([$user['id']]); $_SESSION['user'] = $u2->fetch();
    flash('success', 'Profile updated successfully!');
    header('Location:' . BASE_URL . '/profile.php'); exit;
}

$pageTitle = 'My Profile'; include 'includes/header.php';
?>

<style>
.avatar-upload-wrap {
  position: relative; display: inline-block; cursor: pointer;
}
.avatar-upload-wrap:hover .avatar-overlay { opacity: 1; }
.avatar-overlay {
  position: absolute; inset: 0; background: rgba(0,0,0,.5);
  border-radius: 50%; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  opacity: 0; transition: opacity .2s;
  color: #fff; font-size: 11px; font-weight: 700; text-align: center;
  line-height: 1.3;
}
.avatar-overlay svg { margin-bottom: 3px; }
</style>

<!-- Profile Banner -->
<div class="pb" style="margin-bottom:24px;">

  <!-- Clickable avatar with upload overlay -->
  <form method="POST" enctype="multipart/form-data" id="avatarForm">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <div class="avatar-upload-wrap" onclick="document.getElementById('avatarInput').click()" title="Change profile photo">
      <?php if (!empty($user['avatar']) && file_exists(__DIR__.'/'.$user['avatar'])): ?>
        <img src="<?= BASE_URL.'/'.$user['avatar'] ?>" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3);" alt="Avatar">
      <?php else: ?>
        <div class="pb-av" style="width:70px;height:70px;font-size:24px;"><?= initials($user['name']) ?></div>
      <?php endif; ?>
      <div class="avatar-overlay">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Change Photo
      </div>
    </div>
    <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;"
           onchange="document.getElementById('avatarForm').submit()">
  </form>

  <div style="flex:1;">
    <div class="pb-name"><?= clean($user['name']) ?>
      <?php if($user['is_verified']):?><span class="verified-badge" style="font-size:12px;vertical-align:middle;">✓ Verified</span><?php endif;?>
    </div>
    <div class="pb-meta">
      <?php if($user['university']):?><span>🎓 <?= clean($user['university']) ?></span><?php endif;?>
      <?php if($user['department']):?><span>📚 <?= clean($user['department']) ?></span><?php endif;?>
      <?php if(!empty($profile['city'])):?><span>📍 <?= clean($profile['city']) ?><?= !empty($profile['country'])?', '.clean($profile['country']):'' ?></span><?php endif;?>
    </div>
    <?php if($user['bio']):?><p style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.65);max-width:500px;"><?= clean($user['bio']) ?></p><?php endif;?>
    <p style="font-size:11px;color:rgba(255,255,255,.4);margin-top:6px;">Click your photo to change it · JPG/PNG/WEBP/GIF · max 5MB</p>
  </div>
  <div class="pb-actions">
    <a href="<?= BASE_URL ?>/cv.php" class="pb-btn hi">📄 Generate CV &amp; Download PDF</a>
  </div>
</div>

<!-- View Mode Cards -->
<?php if(!empty($skills)||!empty($exp)||!empty($edu)):?>
<div class="profile-grid" style="margin-bottom:24px;">
  <?php if(!empty($skills)):?>
  <div class="card">
    <div class="card-header"><h3>🎯 Skills</h3></div>
    <div class="card-body"><?php foreach($skills as $sk):?><span class="skill-pill"><?= clean($sk) ?></span><?php endforeach;?></div>
  </div>
  <?php endif;?>
  <?php if(!empty($langs)||!empty($certs)):?>
  <div class="card">
    <div class="card-header"><h3>🗣 Languages &amp; Certifications</h3></div>
    <div class="card-body">
      <?php if(!empty($langs)):?><div style="margin-bottom:10px;"><?php foreach($langs as $l):?><span class="skill-pill" style="background:#F5F3FF;color:#5B21B6;"><?= clean($l) ?></span><?php endforeach;?></div><?php endif;?>
      <?php if(!empty($certs)):?><?php foreach($certs as $c):?><span class="skill-pill" style="background:#ECFDF5;color:#065F46;">🏆 <?= clean($c) ?></span><?php endforeach;?><?php endif;?>
    </div>
  </div>
  <?php endif;?>
  <?php if(!empty($exp)):?>
  <div class="card">
    <div class="card-header"><h3>💼 Experience</h3></div>
    <div class="card-body">
      <?php foreach($exp as $e):?>
      <div class="tl-item">
        <div class="tl-title"><?= clean($e['title']) ?></div>
        <div class="tl-org"><?= clean($e['org']) ?></div>
        <div class="tl-period"><?= clean($e['period']) ?></div>
        <?php if(!empty($e['desc'])):?><div class="tl-desc"><?= clean($e['desc']) ?></div><?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <?php endif;?>
  <?php if(!empty($edu)):?>
  <div class="card">
    <div class="card-header"><h3>🎓 Education</h3></div>
    <div class="card-body">
      <?php foreach($edu as $e):?>
      <div class="tl-item" style="border-left-color:var(--accent);">
        <div class="tl-title"><?= clean($e['title']) ?></div>
        <div class="tl-org" style="color:var(--accent);"><?= clean($e['org']) ?></div>
        <div class="tl-period"><?= clean($e['period']) ?></div>
        <?php if(!empty($e['desc'])):?><div class="tl-desc"><?= clean($e['desc']) ?></div><?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<!-- Edit Form -->
<form method="POST" novalidate>
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>👤 Basic Information</h3></div>
    <div class="card-body">
      <div class="form-group"><label class="form-label">Bio / Headline</label>
        <textarea name="bio" class="form-control" rows="2" placeholder="e.g. Computer Science student passionate about AI"><?= clean($user['bio'] ?? '') ?></textarea></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($profile['phone'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?= clean($profile['city'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="<?= clean($profile['country'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Professional Summary (used in CV)</label>
        <textarea name="summary" class="form-control" rows="3" data-max="800"><?= clean($profile['summary'] ?? '') ?></textarea></div>
    </div>
  </div>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>🔗 Social Links</h3></div>
    <div class="card-body">
      <div class="form-group"><label class="form-label">LinkedIn URL</label><input type="url" name="linkedin" class="form-control" value="<?= clean($profile['linkedin'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">GitHub URL</label><input type="url" name="github" class="form-control" value="<?= clean($profile['github'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Website / Portfolio</label><input type="url" name="website" class="form-control" value="<?= clean($profile['website'] ?? '') ?>"></div>
    </div>
  </div>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>🎯 Skills &amp; Languages</h3></div>
    <div class="card-body">
      <div class="form-group"><label class="form-label">Skills <span style="font-weight:400;color:var(--text3);">(comma separated)</span></label>
        <input type="text" name="skills" class="form-control" value="<?= clean(implode(', ', $skills)) ?>"></div>
      <div class="form-group"><label class="form-label">Languages</label>
        <input type="text" name="languages" class="form-control" value="<?= clean(implode(', ', $langs)) ?>"></div>
      <div class="form-group"><label class="form-label">Certifications</label>
        <input type="text" name="certifications" class="form-control" value="<?= clean(implode(', ', $certs)) ?>"></div>
    </div>
  </div>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="justify-content:space-between;"><h3>💼 Work Experience</h3><button type="button" class="btn btn-ghost btn-sm" onclick="addRep('exp')">+ Add</button></div>
    <div class="card-body" id="exp-rep">
      <?php foreach($exp as $e):?>
      <div class="rep-item"><button type="button" class="rep-remove" onclick="this.parentElement.remove()">×</button>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Job Title</label><input type="text" name="exp_title[]" class="form-control" value="<?= clean($e['title']) ?>"></div>
          <div class="form-group"><label class="form-label">Company</label><input type="text" name="exp_company[]" class="form-control" value="<?= clean($e['org']) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Period</label><input type="text" name="exp_period[]" class="form-control" value="<?= clean($e['period']) ?>"></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="exp_desc[]" class="form-control" rows="2"><?= clean($e['desc']) ?></textarea></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="justify-content:space-between;"><h3>🎓 Education</h3><button type="button" class="btn btn-ghost btn-sm" onclick="addRep('edu')">+ Add</button></div>
    <div class="card-body" id="edu-rep">
      <?php foreach($edu as $e):?>
      <div class="rep-item"><button type="button" class="rep-remove" onclick="this.parentElement.remove()">×</button>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Degree</label><input type="text" name="edu_degree[]" class="form-control" value="<?= clean($e['title']) ?>"></div>
          <div class="form-group"><label class="form-label">Institution</label><input type="text" name="edu_institution[]" class="form-control" value="<?= clean($e['org']) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Period</label><input type="text" name="edu_period[]" class="form-control" value="<?= clean($e['period']) ?>"></div>
        <div class="form-group"><label class="form-label">Notes</label><textarea name="edu_desc[]" class="form-control" rows="2"><?= clean($e['desc']) ?></textarea></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div style="display:flex;gap:10px;margin-bottom:32px;">
    <a href="<?= BASE_URL ?>/cv.php" class="btn btn-ghost">📄 Preview CV</a>
    <button type="submit" class="btn btn-primary btn-lg">Save Profile →</button>
  </div>
</form>
<?php include 'includes/footer.php';?>