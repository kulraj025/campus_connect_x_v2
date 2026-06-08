<?php
require_once '../includes/config.php';
requireAuth();

// Simple admin check - only user with id=1 or role=admin
if (auth()['role'] !== 'admin' && auth()['id'] !== 1) {
    flash('error', 'Access denied.');
    header('Location:' . BASE_URL . '/dashboard.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // ── CREATE / EDIT AD ──
    if (in_array($action, ['create', 'edit'])) {
        $title    = s($_POST['title']    ?? '');
        $subtitle = s($_POST['subtitle'] ?? '');
        $body     = s($_POST['body']     ?? '');
        $ctaText  = s($_POST['cta_text'] ?? 'Learn More');
        $ctaLink  = s($_POST['cta_link'] ?? '');
        $type     = in_array($_POST['type']??'',['info','news','promo','event','tip','alert']) ? $_POST['type'] : 'info';
        $placement= $_POST['placement'] ?? 'login,dashboard';
        $bgFrom   = $_POST['bg_from']   ?? '#1E3A5F';
        $bgTo     = $_POST['bg_to']     ?? '#2D1B69';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $startDate= !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate  = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;
        $sortOrder= (int)($_POST['sort_order'] ?? 0);
        $id       = (int)($_POST['id'] ?? 0);

        // Handle image upload
        $imagePath = s($_POST['existing_image'] ?? '');
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($_FILES['image']['tmp_name']);
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (isset($allowed[$mime]) && $_FILES['image']['size'] <= 5*1024*1024) {
                $ext = $allowed[$mime];
                $fn  = 'ad_'.time().'_'.rand(100,999).'.'.$ext;
                $dir = __DIR__ . '/../uploads/ads/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir.$fn)) {
                    // Delete old image
                    if ($imagePath && file_exists(__DIR__.'/../'.$imagePath)) @unlink(__DIR__.'/../'.$imagePath);
                    $imagePath = 'uploads/ads/' . $fn;
                }
            }
        }

        if ($action === 'create') {
            db()->prepare("INSERT INTO ads(title,subtitle,body,cta_text,cta_link,image_path,type,placement,bg_from,bg_to,is_active,start_date,end_date,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([$title,$subtitle,$body,$ctaText,$ctaLink,$imagePath?:null,$type,$placement,$bgFrom,$bgTo,$isActive,$startDate,$endDate,$sortOrder]);
            flash('success', 'Ad created successfully!');
        } else {
            db()->prepare("UPDATE ads SET title=?,subtitle=?,body=?,cta_text=?,cta_link=?,image_path=?,type=?,placement=?,bg_from=?,bg_to=?,is_active=?,start_date=?,end_date=?,sort_order=? WHERE id=?")
               ->execute([$title,$subtitle,$body,$ctaText,$ctaLink,$imagePath?:null,$type,$placement,$bgFrom,$bgTo,$isActive,$startDate,$endDate,$sortOrder,$id]);
            flash('success', 'Ad updated successfully!');
        }
        header('Location:'.BASE_URL.'/admin/ads.php'); exit;
    }

    // ── DELETE AD ──
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $ad = db()->prepare("SELECT image_path FROM ads WHERE id=?"); $ad->execute([$id]); $ad = $ad->fetch();
        if ($ad && $ad['image_path'] && file_exists(__DIR__.'/../'.$ad['image_path'])) {
            @unlink(__DIR__.'/../'.$ad['image_path']);
        }
        db()->prepare("DELETE FROM ads WHERE id=?")->execute([$id]);
        flash('success', 'Ad deleted.');
        header('Location:'.BASE_URL.'/admin/ads.php'); exit;
    }

    // ── TOGGLE ACTIVE ──
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE ads SET is_active=NOT is_active WHERE id=?")->execute([$id]);
        header('Location:'.BASE_URL.'/admin/ads.php'); exit;
    }
}

// Fetch edit target
$editAd = null;
if (isset($_GET['edit'])) {
    $s = db()->prepare("SELECT * FROM ads WHERE id=?"); $s->execute([(int)$_GET['edit']]); $editAd = $s->fetch();
}

// Fetch all ads
$ads = db()->query("SELECT * FROM ads ORDER BY sort_order ASC, id DESC")->fetchAll();

$pageTitle = 'Manage Ads & Notices';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ad Manager — Campus Connect X</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --brand:#2563EB;--brand-d:#1D4ED8;--brand-l:#EFF6FF;
  --accent:#8B5CF6;--success:#10B981;--warning:#F59E0B;--danger:#EF4444;
  --bg:#F0F4FF;--surface:#fff;--border:#E2E8F0;--border2:#CBD5E1;
  --text:#0F172A;--text2:#475569;--text3:#94A3B8;
  --r:10px;--rl:16px;--t:all .2s ease;
  --fd:'Syne',sans-serif;--fb:'DM Sans',sans-serif;
}
body{font-family:var(--fb);background:var(--bg);color:var(--text);font-size:14px;line-height:1.6;min-height:100vh;}
a{text-decoration:none;color:inherit;}
.topbar{background:#0F172A;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;gap:16px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.3);}
.topbar-brand{display:flex;align-items:center;gap:10px;font-family:var(--fd);font-size:16px;font-weight:800;color:#fff;}
.topbar-brand-icon{width:32px;height:32px;background:linear-gradient(135deg,var(--brand),var(--accent));border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:16px;}
.topbar-right{display:flex;align-items:center;gap:8px;}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 16px;border-radius:var(--r);font-size:13px;font-weight:600;border:none;cursor:pointer;transition:var(--t);text-decoration:none;font-family:var(--fb);}
.btn-primary{background:var(--brand);color:#fff;} .btn-primary:hover{background:var(--brand-d);}
.btn-success{background:var(--success);color:#fff;}
.btn-danger{background:var(--danger);color:#fff;}
.btn-ghost{background:var(--surface);color:var(--text2);border:1px solid var(--border);} .btn-ghost:hover{background:var(--border);}
.btn-sm{padding:5px 12px;font-size:12px;}
.page{padding:24px 28px;max-width:1400px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-family:var(--fd);font-size:15px;font-weight:700;}
.card-body{padding:20px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;}
.form-control{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r);font-size:13px;color:var(--text);background:var(--surface);outline:none;transition:var(--t);font-family:var(--fb);}
.form-control:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
textarea.form-control{resize:vertical;min-height:80px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
.form-hint{font-size:11px;color:var(--text3);margin-top:3px;}
.alert{padding:10px 14px;border-radius:var(--r);font-size:13px;margin-bottom:16px;}
.al-s{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;}
.al-e{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;}

/* Grid layout */
.admin-grid{display:grid;grid-template-columns:400px 1fr;gap:24px;align-items:start;}

/* Ad table */
.ad-table{width:100%;border-collapse:collapse;}
.ad-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);padding:10px 16px;text-align:left;border-bottom:2px solid var(--border);background:var(--bg);}
.ad-table td{padding:12px 16px;border-bottom:1px solid var(--border);vertical-align:middle;}
.ad-table tr:last-child td{border-bottom:none;}
.ad-table tr:hover td{background:var(--bg);}

/* Ad preview card */
.ad-preview{border-radius:12px;overflow:hidden;position:relative;min-height:180px;display:flex;flex-direction:column;justify-content:flex-end;padding:20px;}
.ad-preview-bg{position:absolute;inset:0;background-size:400% 400%;animation:gradShift 6s ease infinite;}
@keyframes gradShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
.ad-preview-orb{position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);border-radius:50%;}
.ad-preview-content{position:relative;z-index:2;}
.ad-preview img{width:100%;max-height:100px;object-fit:cover;border-radius:8px;margin-bottom:10px;border:1px solid rgba(255,255,255,.2);}

/* Status badges */
.badge{display:inline-flex;align-items:center;font-size:10px;font-weight:700;padding:3px 8px;border-radius:99px;letter-spacing:.04em;}
.badge-green{background:#ECFDF5;color:#065F46;}
.badge-red{background:#FEF2F2;color:#991B1B;}
.badge-blue{background:#EFF6FF;color:#1D4ED8;}
.badge-purple{background:#F5F3FF;color:#5B21B6;}
.badge-amber{background:#FFF7ED;color:#92400E;}

/* Color picker row */
.color-row{display:flex;align-items:center;gap:8px;}
.color-preview{width:36px;height:36px;border-radius:8px;border:2px solid var(--border);flex-shrink:0;transition:var(--t);}

/* Image upload area */
.img-upload-area{border:2px dashed var(--border);border-radius:var(--r);padding:24px;text-align:center;cursor:pointer;transition:var(--t);position:relative;overflow:hidden;}
.img-upload-area:hover{border-color:var(--brand);background:var(--brand-l);}
.img-upload-area input{position:absolute;inset:0;opacity:0;cursor:pointer;}
.img-preview-wrap{margin-top:12px;}
.img-preview-wrap img{max-height:120px;border-radius:8px;border:1px solid var(--border);}

/* Placement checkboxes */
.placement-grid{display:flex;flex-wrap:wrap;gap:8px;}
.placement-opt{display:flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;transition:var(--t);font-size:12px;font-weight:500;}
.placement-opt:has(input:checked){border-color:var(--brand);background:var(--brand-l);color:var(--brand);}
.placement-opt input{accent-color:var(--brand);}

@media(max-width:1024px){.admin-grid{grid-template-columns:1fr;}.form-row-3{grid-template-columns:1fr 1fr;}}
@media(max-width:640px){.form-row{grid-template-columns:1fr;}.form-row-3{grid-template-columns:1fr;}}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-brand-icon">🎓</div>
    Campus Connect X — Ad Manager
  </div>
  <div class="topbar-right">
    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
  </div>
</div>

<div class="page">
  <?php if($s=getFlash('success')): ?><div class="alert al-s" style="margin-bottom:20px;">✓ <?= clean($s) ?></div><?php endif;?>
  <?php if($e=getFlash('error')):   ?><div class="alert al-e" style="margin-bottom:20px;">✕ <?= clean($e) ?></div><?php endif;?>

  <div class="admin-grid">

    <!-- LEFT: Create / Edit Form -->
    <div>
      <div class="card">
        <div class="card-header">
          <h3><?= $editAd ? '✏️ Edit Ad/Notice' : '+ Create New Ad/Notice' ?></h3>
          <?php if($editAd): ?><a href="<?= BASE_URL ?>/admin/ads.php" class="btn btn-ghost btn-sm">Cancel</a><?php endif;?>
        </div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data" id="ad-form">
            <input type="hidden" name="csrf"   value="<?= csrf() ?>">
            <input type="hidden" name="action" value="<?= $editAd ? 'edit' : 'create' ?>">
            <?php if($editAd): ?><input type="hidden" name="id" value="<?= $editAd['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= clean($editAd['image_path']??'') ?>"><?php endif;?>

            <!-- Title -->
            <div class="form-group">
              <label class="form-label">Headline / Title *</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Student Visa Workshop 2025" value="<?= clean($editAd['title']??'') ?>" required oninput="updatePreview()">
            </div>

            <!-- Subtitle -->
            <div class="form-group">
              <label class="form-label">Subtitle</label>
              <input type="text" name="subtitle" class="form-control" placeholder="e.g. Free registration open now" value="<?= clean($editAd['subtitle']??'') ?>" oninput="updatePreview()">
            </div>

            <!-- Body -->
            <div class="form-group">
              <label class="form-label">Description (2–3 lines)</label>
              <textarea name="body" class="form-control" rows="3" placeholder="Brief description of the notice or ad..." oninput="updatePreview()"><?= clean($editAd['body']??'') ?></textarea>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
              <label class="form-label">Photo / Image</label>
              <div class="img-upload-area" onclick="document.getElementById('img-input').click()">
                <input type="file" id="img-input" name="image" accept="image/*" onchange="previewAdImg(this)">
                <div id="img-placeholder">
                  <svg width="28" height="28" fill="none" stroke="#94A3B8" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  <div style="font-size:13px;font-weight:600;color:var(--text2);">Click to upload image</div>
                  <div style="font-size:11px;color:var(--text3);margin-top:3px;">JPG, PNG, WEBP · Max 5MB · Recommended: 800×500px</div>
                </div>
                <div class="img-preview-wrap" id="img-preview-wrap" style="<?= empty($editAd['image_path'])?'display:none':'' ?>">
                  <?php if(!empty($editAd['image_path'])): ?>
                  <img src="<?= BASE_URL.'/'.$editAd['image_path'] ?>" id="img-preview-thumb" alt="Current image">
                  <div style="font-size:11px;color:var(--text3);margin-top:4px;">Current image · Upload new to replace</div>
                  <?php else: ?>
                  <img id="img-preview-thumb" src="" alt="">
                  <?php endif;?>
                </div>
              </div>
            </div>

            <!-- CTA -->
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Button Text</label>
                <input type="text" name="cta_text" class="form-control" placeholder="Learn More" value="<?= clean($editAd['cta_text']??'Learn More') ?>" oninput="updatePreview()">
              </div>
              <div class="form-group">
                <label class="form-label">Button Link</label>
                <input type="text" name="cta_link" class="form-control" placeholder="/community.php" value="<?= clean($editAd['cta_link']??'') ?>">
              </div>
            </div>

            <!-- Type -->
            <div class="form-group">
              <label class="form-label">Type / Category</label>
              <select name="type" class="form-control" onchange="updatePreview()">
                <?php foreach(['info'=>'ℹ️ Info','news'=>'📰 News','promo'=>'🎉 Feature','event'=>'📅 Event','tip'=>'💡 Tip','alert'=>'🔔 Alert'] as $v=>$l):?>
                  <option value="<?= $v ?>" <?= ($editAd['type']??'info')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach;?>
              </select>
            </div>

            <!-- Background Colors -->
            <div class="form-group">
              <label class="form-label">Background Gradient</label>
              <div class="form-row">
                <div>
                  <div class="color-row">
                    <input type="color" name="bg_from" id="bg_from" class="form-control" style="padding:4px;height:40px;cursor:pointer;" value="<?= $editAd['bg_from']??'#1E3A5F' ?>" oninput="updatePreview()">
                    <input type="text" id="bg_from_txt" class="form-control" style="width:100px;" value="<?= $editAd['bg_from']??'#1E3A5F' ?>" oninput="document.getElementById('bg_from').value=this.value;updatePreview()">
                    <span style="font-size:11px;color:var(--text3);">From</span>
                  </div>
                </div>
                <div>
                  <div class="color-row">
                    <input type="color" name="bg_to" id="bg_to" class="form-control" style="padding:4px;height:40px;cursor:pointer;" value="<?= $editAd['bg_to']??'#2D1B69' ?>" oninput="updatePreview()">
                    <input type="text" id="bg_to_txt" class="form-control" style="width:100px;" value="<?= $editAd['bg_to']??'#2D1B69' ?>" oninput="document.getElementById('bg_to').value=this.value;updatePreview()">
                    <span style="font-size:11px;color:var(--text3);">To</span>
                  </div>
                </div>
              </div>
              <!-- Quick gradient presets -->
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
                <?php foreach([
                  ['Navy','#0F172A','#1E3A5F'],['Purple','#2D1B69','#4C1D95'],
                  ['Green','#064E3B','#065F46'],['Blue','#1E3A5F','#2563EB'],
                  ['Red','#7F1D1D','#DC2626'],['Sky','#0C4A6E','#0EA5E9'],
                  ['Gold','#78350F','#D97706'],['Pink','#500724','#BE185D'],
                ] as [$name,$f,$t]):?>
                <button type="button" onclick="setGrad('<?= $f ?>','<?= $t ?>')"
                  style="padding:5px 10px;border-radius:6px;border:1px solid rgba(255,255,255,.3);font-size:11px;font-weight:600;color:#fff;cursor:pointer;background:linear-gradient(135deg,<?= $f ?>,<?= $t ?>);transition:all .2s;"
                  onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''"><?= $name ?></button>
                <?php endforeach;?>
              </div>
            </div>

            <!-- Placement -->
            <div class="form-group">
              <label class="form-label">Show On</label>
              <?php
              $placements = explode(',', $editAd['placement'] ?? 'login,dashboard');
              $placeOpts  = ['login'=>'🔐 Login Page','dashboard'=>'📊 Dashboard','community'=>'👥 Community','sidebar'=>'📌 Sidebar'];
              ?>
              <div class="placement-grid">
                <?php foreach($placeOpts as $v=>$l):?>
                <label class="placement-opt">
                  <input type="checkbox" name="placement[]" value="<?= $v ?>" <?= in_array($v,$placements)?'checked':'' ?>> <?= $l ?>
                </label>
                <?php endforeach;?>
              </div>
            </div>

            <!-- Dates & Order -->
            <div class="form-row-3">
              <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $editAd['start_date']??'' ?>">
              </div>
              <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $editAd['end_date']??'' ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?= $editAd['sort_order']??0 ?>" min="0" max="999">
                <div class="form-hint">Lower = shown first</div>
              </div>
            </div>

            <!-- Active toggle -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;padding:12px 14px;background:var(--bg);border-radius:var(--r);border:1px solid var(--border);">
              <input type="checkbox" name="is_active" id="is_active" style="width:18px;height:18px;accent-color:var(--brand);cursor:pointer;" <?= ($editAd['is_active']??1)?'checked':'' ?>>
              <label for="is_active" style="font-size:13px;font-weight:600;cursor:pointer;">Active (show this ad/notice)</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:14px;">
              <?= $editAd ? '💾 Save Changes' : '🚀 Publish Ad/Notice' ?>
            </button>
          </form>
        </div>
      </div>

      <!-- Live Preview -->
      <div class="card" style="margin-top:16px;">
        <div class="card-header"><h3>👁 Live Preview</h3></div>
        <div class="card-body" style="padding:14px;">
          <div class="ad-preview" id="ad-preview-card">
            <div class="ad-preview-bg" id="preview-bg" style="background:linear-gradient(135deg,<?= $editAd['bg_from']??'#1E3A5F' ?>,<?= $editAd['bg_to']??'#2D1B69' ?>,<?= $editAd['bg_from']??'#1E3A5F' ?>);background-size:400% 400%;"></div>
            <div class="ad-preview-orb"></div>
            <div class="ad-preview-content">
              <div id="preview-img-wrap" style="<?= empty($editAd['image_path'])?'display:none':'' ?>;margin-bottom:10px;">
                <img id="preview-img" src="<?= !empty($editAd['image_path'])?BASE_URL.'/'.$editAd['image_path']:'' ?>" style="width:100%;max-height:90px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.2);">
              </div>
              <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;" id="preview-badge">ℹ️ Info</div>
              <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:4px;" id="preview-title"><?= clean($editAd['title']??'Your headline here') ?></div>
              <div style="font-size:11px;font-weight:600;color:rgba(255,255,255,.65);margin-bottom:5px;" id="preview-sub"><?= clean($editAd['subtitle']??'') ?></div>
              <div style="font-size:11px;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:12px;" id="preview-body"><?= clean(substr($editAd['body']??'',0,100)) ?></div>
              <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.3);color:#fff;padding:7px 14px;border-radius:99px;font-size:11px;font-weight:700;" id="preview-cta"><?= clean($editAd['cta_text']??'Learn More') ?> →</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: All Ads List -->
    <div>
      <div class="card">
        <div class="card-header">
          <h3>📋 All Ads & Notices (<?= count($ads) ?>)</h3>
          <div style="display:flex;gap:6px;font-size:12px;color:var(--text3);">
            <span style="color:var(--success);">● <?= count(array_filter($ads,fn($a)=>$a['is_active'])) ?> active</span>
            <span style="color:var(--danger);">● <?= count(array_filter($ads,fn($a)=>!$a['is_active'])) ?> inactive</span>
          </div>
        </div>

        <?php if(empty($ads)): ?>
        <div style="padding:48px;text-align:center;color:var(--text3);">
          <div style="font-size:40px;margin-bottom:12px;">📢</div>
          <div style="font-size:14px;font-weight:600;color:var(--text2);">No ads yet</div>
          <div style="font-size:12px;margin-top:4px;">Create your first notice or ad using the form.</div>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="ad-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Preview</th>
              <th>Title</th>
              <th>Type</th>
              <th>Placement</th>
              <th>Views</th>
              <th>Clicks</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($ads as $ad): ?>
          <tr>
            <td style="color:var(--text3);font-size:12px;"><?= $ad['id'] ?></td>
            <td>
              <div style="width:80px;height:50px;border-radius:6px;overflow:hidden;position:relative;flex-shrink:0;background:linear-gradient(135deg,<?= clean($ad['bg_from']) ?>,<?= clean($ad['bg_to']) ?>);">
                <?php if(!empty($ad['image_path'])): ?>
                <img src="<?= BASE_URL.'/'.$ad['image_path'] ?>" style="width:100%;height:100%;object-fit:cover;opacity:.7;">
                <?php endif;?>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:18px;"><?= ['info'=>'ℹ️','news'=>'📰','promo'=>'🎉','event'=>'📅','tip'=>'💡','alert'=>'🔔'][$ad['type']]??'ℹ️' ?></div>
              </div>
            </td>
            <td>
              <div style="font-weight:600;font-size:13px;max-width:200px;"><?= clean($ad['title']) ?></div>
              <?php if($ad['subtitle']): ?><div style="font-size:11px;color:var(--text3);"><?= clean(substr($ad['subtitle'],0,50)) ?></div><?php endif;?>
            </td>
            <td><span class="badge badge-blue"><?= ucfirst($ad['type']) ?></span></td>
            <td>
              <?php foreach(explode(',',$ad['placement']) as $pl): ?>
              <span class="badge badge-purple" style="margin:1px;"><?= $pl ?></span>
              <?php endforeach;?>
            </td>
            <td style="font-family:'Syne',sans-serif;font-weight:700;"><?= number_format($ad['view_count']) ?></td>
            <td style="font-family:'Syne',sans-serif;font-weight:700;color:var(--brand);"><?= number_format($ad['click_count']) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf"   value="<?= csrf() ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id"     value="<?= $ad['id'] ?>">
                <button type="submit" class="badge <?= $ad['is_active']?'badge-green':'badge-red' ?>" style="border:none;cursor:pointer;font-size:10px;padding:4px 10px;">
                  <?= $ad['is_active']?'● Active':'○ Inactive' ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="?edit=<?= $ad['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this ad permanently?')">
                  <input type="hidden" name="csrf"   value="<?= csrf() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id"     value="<?= $ad['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        </div>
        <?php endif;?>
      </div>

      <!-- Tips -->
      <div class="card" style="margin-top:16px;">
        <div class="card-header"><h3>💡 Quick Tips</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:var(--text2);">
          <div>📐 <strong>Image size:</strong> 800×500px works best for the login carousel</div>
          <div>🎨 <strong>Gradient:</strong> Use dark colors (navy, purple, dark green) for best text readability</div>
          <div>📍 <strong>Placement:</strong> "Login" shows in the login carousel · "Dashboard" shows as the animated banner</div>
          <div>📅 <strong>Scheduling:</strong> Set start/end dates to auto-show/hide seasonal notices</div>
          <div>🔢 <strong>Order:</strong> Sort order 0 = shown first · Use 1,2,3... to control sequence</div>
          <div>📊 <strong>Analytics:</strong> View/click counts track engagement automatically</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
const typeIcons = {info:'ℹ️ INFO',news:'📰 NEWS',promo:'🎉 FEATURE',event:'📅 EVENT',tip:'💡 TIP',alert:'🔔 ALERT'};

function updatePreview() {
  const title   = document.querySelector('[name=title]').value || 'Your headline here';
  const sub     = document.querySelector('[name=subtitle]').value;
  const body    = document.querySelector('[name=body]').value;
  const cta     = document.querySelector('[name=cta_text]').value || 'Learn More';
  const type    = document.querySelector('[name=type]').value;
  const bgFrom  = document.getElementById('bg_from').value;
  const bgTo    = document.getElementById('bg_to').value;

  document.getElementById('preview-title').textContent = title;
  document.getElementById('preview-sub').textContent   = sub;
  document.getElementById('preview-body').textContent  = body.substring(0,100);
  document.getElementById('preview-cta').textContent   = cta + ' →';
  document.getElementById('preview-badge').textContent = typeIcons[type] || 'ℹ️ INFO';
  document.getElementById('preview-bg').style.background =
    `linear-gradient(135deg,${bgFrom},${bgTo},${bgFrom})`;
  document.getElementById('preview-bg').style.backgroundSize = '400% 400%';

  // Sync text fields with color pickers
  document.getElementById('bg_from_txt').value = bgFrom;
  document.getElementById('bg_to_txt').value   = bgTo;
}

function setGrad(from, to) {
  document.getElementById('bg_from').value    = from;
  document.getElementById('bg_to').value      = to;
  document.getElementById('bg_from_txt').value = from;
  document.getElementById('bg_to_txt').value   = to;
  updatePreview();
}

function previewAdImg(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('img-preview-thumb').src = e.target.result;
    document.getElementById('img-preview-wrap').style.display = 'block';
    document.getElementById('img-placeholder').style.display  = 'none';
    document.getElementById('preview-img').src = e.target.result;
    document.getElementById('preview-img-wrap').style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}

// Fix placement checkboxes → join to string for POST
document.getElementById('ad-form').addEventListener('submit', function() {
  const checked = Array.from(document.querySelectorAll('[name="placement[]"]:checked')).map(c=>c.value);
  // Remove old checkboxes, add hidden field
  document.querySelectorAll('[name="placement[]"]').forEach(c=>c.disabled=true);
  const h = document.createElement('input');
  h.type = 'hidden'; h.name = 'placement'; h.value = checked.join(',');
  this.appendChild(h);
});

updatePreview();
</script>
</body>
</html>