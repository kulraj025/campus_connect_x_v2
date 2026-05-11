<?php
require_once 'includes/config.php'; requireAuth();
$user = auth();
$ps = db()->prepare("SELECT * FROM profiles WHERE user_id=?"); $ps->execute([$user['id']]);
$profile = $ps->fetch() ?: [];
$skills  = json_decode($profile['skills']  ?? '[]', true) ?: [];
$exp     = json_decode($profile['experience'] ?? '[]', true) ?: [];
$edu     = json_decode($profile['education']  ?? '[]', true) ?: [];
$langs   = json_decode($profile['languages']  ?? '[]', true) ?: [];
$certs   = json_decode($profile['certifications'] ?? '[]', true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio     = s($_POST['bio']     ?? '');
    $phone   = s($_POST['phone']   ?? '');
    $linkedin= filter_var(trim($_POST['linkedin'] ?? ''), FILTER_SANITIZE_URL);
    $github  = filter_var(trim($_POST['github']   ?? ''), FILTER_SANITIZE_URL);
    $website = filter_var(trim($_POST['website']  ?? ''), FILTER_SANITIZE_URL);
    $city    = s($_POST['city']    ?? '');
    $country = s($_POST['country'] ?? '');
    $summary = s($_POST['summary'] ?? '');
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

    // Refresh session
    $u2 = db()->prepare("SELECT * FROM users WHERE id=?"); $u2->execute([$user['id']]); $_SESSION['user'] = $u2->fetch();
    flash('success', 'Profile updated successfully!');
    header('Location:' . BASE_URL . '/profile.php'); exit;
}

$pageTitle = 'My Profile'; include 'includes/header.php';
?>

<!-- Profile Banner -->
<div class="pb" style="margin-bottom:24px;">
  <div class="pb-av" style="width:70px;height:70px;font-size:24px;"><?= initials($user['name']) ?></div>
  <div style="flex:1;">
    <div class="pb-name"><?= clean($user['name']) ?>
      <?php if($user['is_verified']):?> <span class="verified-badge" style="font-size:12px;vertical-align:middle;">✓ Verified</span><?php endif;?>
    </div>
    <div class="pb-meta">
      <?php if($user['university']):?><span>🎓 <?= clean($user['university']) ?></span><?php endif;?>
      <?php if($user['department']):?><span>📚 <?= clean($user['department']) ?></span><?php endif;?>
      <?php if(!empty($profile['city'])):?><span>📍 <?= clean($profile['city']) ?><?= !empty($profile['country'])?', '.clean($profile['country']):'' ?></span><?php endif;?>
    </div>
    <?php if($user['bio']):?><p style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.65);max-width:500px;"><?= clean($user['bio']) ?></p><?php endif;?>
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

  <!-- Basic Info -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>👤 Basic Information</h3></div>
    <div class="card-body">
      <div class="form-group"><label class="form-label">Bio / Headline</label>
        <textarea name="bio" class="form-control" rows="2" placeholder="e.g. Computer Science student passionate about AI and web development"><?= clean($user['bio'] ?? '') ?></textarea></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" placeholder="+1 234 567 8900" value="<?= clean($profile['phone'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">City</label><input type="text" name="city" class="form-control" placeholder="e.g. London" value="<?= clean($profile['city'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Country</label><input type="text" name="country" class="form-control" placeholder="e.g. United Kingdom" value="<?= clean($profile['country'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Professional Summary (used in CV)</label>
        <textarea name="summary" class="form-control" rows="3" data-max="800" placeholder="Write 2-3 sentences about yourself, your goals and strengths..."><?= clean($profile['summary'] ?? '') ?></textarea></div>
    </div>
  </div>

  <!-- Social Links -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>🔗 Social Links</h3></div>
    <div class="card-body">
      <div class="form-group"><label class="form-label">LinkedIn URL</label><input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/username" value="<?= clean($profile['linkedin'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">GitHub URL</label><input type="url" name="github" class="form-control" placeholder="https://github.com/username" value="<?= clean($profile['github'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Website / Portfolio</label><input type="url" name="website" class="form-control" placeholder="https://yoursite.com" value="<?= clean($profile['website'] ?? '') ?>"></div>
    </div>
  </div>

  <!-- Skills -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>🎯 Skills &amp; Languages</h3></div>
    <div class="card-body">
      <div class="form-group"><label class="form-label">Skills <span style="font-weight:400;color:var(--text3);">(comma separated)</span></label>
        <input type="text" name="skills" class="form-control" placeholder="PHP, Laravel, JavaScript, React, MySQL, Python" value="<?= clean(implode(', ', $skills)) ?>"></div>
      <div class="form-group"><label class="form-label">Languages <span style="font-weight:400;color:var(--text3);">(comma separated)</span></label>
        <input type="text" name="languages" class="form-control" placeholder="English, Nepali, Hindi" value="<?= clean(implode(', ', $langs)) ?>"></div>
      <div class="form-group"><label class="form-label">Certifications <span style="font-weight:400;color:var(--text3);">(comma separated)</span></label>
        <input type="text" name="certifications" class="form-control" placeholder="Google Analytics, AWS Cloud Practitioner" value="<?= clean(implode(', ', $certs)) ?>"></div>
    </div>
  </div>

  <!-- Experience -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="justify-content:space-between;">
      <h3>💼 Work Experience</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="addRep('exp')">+ Add</button>
    </div>
    <div class="card-body" id="exp-rep">
      <?php foreach($exp as $e):?>
      <div class="rep-item"><button type="button" class="rep-remove" onclick="this.parentElement.remove()">×</button>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Job Title</label><input type="text" name="exp_title[]" class="form-control" value="<?= clean($e['title']) ?>" placeholder="e.g. Frontend Developer"></div>
          <div class="form-group"><label class="form-label">Company</label><input type="text" name="exp_company[]" class="form-control" value="<?= clean($e['org']) ?>" placeholder="e.g. Tech Corp"></div>
        </div>
        <div class="form-group"><label class="form-label">Period</label><input type="text" name="exp_period[]" class="form-control" value="<?= clean($e['period']) ?>" placeholder="Jan 2023 – Present"></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="exp_desc[]" class="form-control" rows="2"><?= clean($e['desc']) ?></textarea></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Education -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="justify-content:space-between;">
      <h3>🎓 Education</h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="addRep('edu')">+ Add</button>
    </div>
    <div class="card-body" id="edu-rep">
      <?php foreach($edu as $e):?>
      <div class="rep-item"><button type="button" class="rep-remove" onclick="this.parentElement.remove()">×</button>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Degree / Program</label><input type="text" name="edu_degree[]" class="form-control" value="<?= clean($e['title']) ?>" placeholder="BSc Computer Science"></div>
          <div class="form-group"><label class="form-label">Institution</label><input type="text" name="edu_institution[]" class="form-control" value="<?= clean($e['org']) ?>" placeholder="University name"></div>
        </div>
        <div class="form-group"><label class="form-label">Period</label><input type="text" name="edu_period[]" class="form-control" value="<?= clean($e['period']) ?>" placeholder="2021 – 2025"></div>
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
