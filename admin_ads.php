<?php
require_once 'includes/config.php';
requireAuth();
if (empty(auth()['is_admin'])) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }

// ── Delete ───────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $d = db()->prepare("SELECT image_path FROM ads WHERE id=?");
    $d->execute([(int)$_GET['delete']]);
    $old = $d->fetch();
    if ($old && !empty($old['image_path']) && file_exists(__DIR__.'/'.$old['image_path'])) {
        @unlink(__DIR__.'/'.$old['image_path']);
    }
    db()->prepare("DELETE FROM ads WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success','Ad deleted.');
    header('Location:'.BASE_URL.'/admin_ads.php'); exit;
}

// ── Toggle active ────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $cur = db()->prepare("SELECT is_active FROM ads WHERE id=?");
    $cur->execute([(int)$_GET['toggle']]);
    $row = $cur->fetch();
    if ($row) db()->prepare("UPDATE ads SET is_active=? WHERE id=?")->execute([!$row['is_active'], (int)$_GET['toggle']]);
    header('Location:'.BASE_URL.'/admin_ads.php'); exit;
}

// ── Save (add or edit) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id       = (int)($_POST['id'] ?? 0);
    $type     = in_array($_POST['type']??'',['info','news','event','feature','notice']) ? $_POST['type'] : 'info';
    $title    = s($_POST['title']    ?? '');
    $body     = s($_POST['body']     ?? '');
    $cta_text = s($_POST['cta_text'] ?? '');
    $cta_link = filter_var(trim($_POST['cta_link'] ?? ''), FILTER_SANITIZE_URL);
    $bg       = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $_POST['bg_color']??'') ? $_POST['bg_color'] : '#6D28D9';
    $emoji    = mb_substr(s($_POST['emoji']??'📢'), 0, 4);
    $show_on  = in_array($_POST['show_on']??'',['login','dashboard','all']) ? $_POST['show_on'] : 'login';
    $sort     = (int)($_POST['sort_order'] ?? 0);
    $starts   = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
    $ends     = !empty($_POST['ends_at'])   ? $_POST['ends_at']   : null;

    if (strlen($title) < 3 || strlen($body) < 5) {
        flash('error','Title and body are required.');
        header('Location:'.BASE_URL.'/admin_ads.php'.($id?"?edit=$id":'')); exit;
    }

    // ── Image upload ─────────────────────────────────────
    $imagePath = $_POST['existing_image'] ?? null; // keep existing by default
    if (!empty($_FILES['ad_image']['name']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($_FILES['ad_image']['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (isset($allowed[$mime]) && $_FILES['ad_image']['size'] <= 5*1024*1024) {
            $dir = __DIR__.'/uploads/ads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            // Delete old image if editing
            if ($id && !empty($_POST['existing_image']) && file_exists(__DIR__.'/'.$_POST['existing_image'])) {
                @unlink(__DIR__.'/'.$_POST['existing_image']);
            }
            $fn = 'ad_'.$id.'_'.time().'.'.$allowed[$mime];
            if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $dir.$fn)) {
                $imagePath = 'uploads/ads/'.$fn;
            }
        } else {
            flash('error','Image must be JPG/PNG/WEBP/GIF under 5MB.');
            header('Location:'.BASE_URL.'/admin_ads.php'.($id?"?edit=$id":'')); exit;
        }
    }

    // Remove image if checked
    if (!empty($_POST['remove_image'])) {
        if (!empty($_POST['existing_image']) && file_exists(__DIR__.'/'.$_POST['existing_image'])) {
            @unlink(__DIR__.'/'.$_POST['existing_image']);
        }
        $imagePath = null;
    }

    if ($id) {
        db()->prepare("UPDATE ads SET type=?,title=?,body=?,cta_text=?,cta_link=?,bg_color=?,emoji=?,show_on=?,sort_order=?,starts_at=?,ends_at=?,image_path=? WHERE id=?")
           ->execute([$type,$title,$body,$cta_text?:null,$cta_link?:null,$bg,$emoji,$show_on,$sort,$starts,$ends,$imagePath,$id]);
        flash('success','✅ Ad updated.');
    } else {
        db()->prepare("INSERT INTO ads (type,title,body,cta_text,cta_link,bg_color,emoji,show_on,sort_order,starts_at,ends_at,image_path,created_by,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)")
           ->execute([$type,$title,$body,$cta_text?:null,$cta_link?:null,$bg,$emoji,$show_on,$sort,$starts,$ends,$imagePath,auth()['id']]);
        flash('success','✅ Ad created.');
    }
    header('Location:'.BASE_URL.'/admin_ads.php'); exit;
}

// ── Load ad for edit ─────────────────────────────────────
$editAd = null;
if (isset($_GET['edit'])) {
    $eq = db()->prepare("SELECT * FROM ads WHERE id=?");
    $eq->execute([(int)$_GET['edit']]);
    $editAd = $eq->fetch();
}

$allAds = db()->query("SELECT a.*,u.name as creator FROM ads a LEFT JOIN users u ON a.created_by=u.id ORDER BY a.sort_order ASC, a.created_at DESC")->fetchAll();
$successMsg = getFlash('success');
$errorMsg   = getFlash('error');

$pageTitle = 'Admin — Ads & Notices';
include 'includes/header.php';
?>

<style>
.admin-wrap{max-width:960px;}
.ad-table{width:100%;border-collapse:collapse;font-size:13px;}
.ad-table th{text-align:left;padding:10px 14px;background:var(--surface2);color:var(--text3);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);}
.ad-table td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
.ad-table tr:last-child td{border-bottom:none;}
.ad-table tr:hover td{background:var(--surface2);}
.status-pill{font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;}
.status-on{background:#ECFDF5;color:#065F46;}
.status-off{background:#FEF2F2;color:#991B1B;}
.action-btns{display:flex;gap:6px;}
.btn-icon{padding:5px 10px;font-size:12px;border-radius:6px;border:1px solid var(--border);background:none;cursor:pointer;color:var(--text2);text-decoration:none;transition:var(--t);}
.btn-icon:hover{background:var(--surface2);}
.btn-icon.del:hover{background:#FEF2F2;color:#ef4444;border-color:#fca5a5;}
.preview-card{border-radius:12px;overflow:hidden;margin-bottom:16px;position:relative;min-height:160px;display:flex;flex-direction:column;justify-content:flex-end;}
.preview-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;}
.preview-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.1) 60%,transparent 100%);z-index:1;}
.preview-content{position:relative;z-index:2;padding:20px;}
.preview-type{display:inline-block;background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;color:#fff;margin-bottom:8px;}
.preview-title{font-size:20px;font-weight:800;color:#fff;line-height:1.2;margin-bottom:4px;}
.preview-body{font-size:12px;color:rgba(255,255,255,.75);}
.img-upload-box{border:2px dashed var(--border);border-radius:var(--r);padding:20px;text-align:center;cursor:pointer;transition:var(--t);background:var(--surface2);}
.img-upload-box:hover{border-color:var(--brand);background:#F5F3FF;}
.img-thumb{width:100%;max-height:140px;object-fit:cover;border-radius:var(--r);margin-bottom:8px;display:block;}
</style>

<div class="admin-wrap">

  <div class="sec-hdr">
    <div>
      <div class="sec-title">🛡 Admin — Ads &amp; Notices</div>
      <div class="sec-sub"><?= count($allAds) ?> total · shown on login page left panel</div>
    </div>
    <button class="btn btn-primary" onclick="openForm()">+ New Ad</button>
  </div>

  <?php if ($successMsg): ?>
  <div class="alert" style="background:#ECFDF5;color:#065F46;border:1px solid #6EE7B7;padding:12px 16px;border-radius:10px;margin-bottom:16px;"><?= clean($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
  <div class="alert al-e" style="margin-bottom:16px;"><?= clean($errorMsg) ?></div>
  <?php endif; ?>

  <!-- ── Add / Edit Form ─────────────────────────────── -->
  <div class="card" id="adForm" style="margin-bottom:24px;<?= $editAd ? '' : 'display:none;' ?>">
    <div class="card-header" style="justify-content:space-between;">
      <h3 id="formTitle"><?= $editAd ? '✏️ Edit Ad' : '➕ New Ad / Notice' ?></h3>
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeForm()">Cancel ×</button>
    </div>
    <div class="card-body">

      <!-- Live preview -->
      <label class="form-label" style="margin-bottom:6px;">Live Preview</label>
      <div class="preview-card" id="livePreview" style="background:<?= $editAd ? clean($editAd['bg_color']) : '#6D28D9' ?>;">
        <img id="prevImg" src="<?= ($editAd && !empty($editAd['image_path'])) ? BASE_URL.'/'.$editAd['image_path'] : '' ?>"
             style="<?= ($editAd && !empty($editAd['image_path'])) ? '' : 'display:none;' ?>"
             class="preview-img">
        <div class="preview-overlay"></div>
        <div class="preview-content">
          <div class="preview-type" id="prevType"><?= $editAd ? ucfirst(clean($editAd['type'])) : 'Info' ?></div>
          <div class="preview-title" id="prevTitle"><?= $editAd ? clean($editAd['title']) : 'Ad Title' ?></div>
          <div class="preview-body"  id="prevBody"><?= $editAd ? clean($editAd['body']) : 'Ad description...' ?></div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="id"   value="<?= $editAd ? $editAd['id'] : 0 ?>" id="fId">
        <input type="hidden" name="existing_image" value="<?= $editAd ? clean($editAd['image_path'] ?? '') : '' ?>" id="fExistingImg">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control" id="fType" oninput="updatePreview()">
              <?php foreach(['info'=>'ℹ Info','news'=>'📰 News','event'=>'📅 Event','feature'=>'🎉 Feature','notice'=>'📢 Notice'] as $t=>$l): ?>
              <option value="<?= $t ?>" <?= ($editAd&&$editAd['type']===$t)?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Show On</label>
            <select name="show_on" class="form-control">
              <?php foreach(['login'=>'Login Page','dashboard'=>'Dashboard','all'=>'Both'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($editAd&&$editAd['show_on']===$v)?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Emoji</label>
            <input type="text" name="emoji" class="form-control" value="<?= $editAd ? clean($editAd['emoji']) : '📢' ?>" maxlength="4">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" id="fTitle" class="form-control" value="<?= $editAd ? clean($editAd['title']) : '' ?>" oninput="updatePreview()" required>
        </div>
        <div class="form-group">
          <label class="form-label">Body *</label>
          <textarea name="body" id="fBody" class="form-control" rows="2" oninput="updatePreview()" required><?= $editAd ? clean($editAd['body']) : '' ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">CTA Button Text</label>
            <input type="text" name="cta_text" class="form-control" placeholder="Learn More →" value="<?= $editAd ? clean($editAd['cta_text']??'') : '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">CTA Link</label>
            <input type="text" name="cta_link" class="form-control" placeholder="/campus_connect_x_v2/career.php" value="<?= $editAd ? clean($editAd['cta_link']??'') : '' ?>">
          </div>
        </div>

        <!-- Image Upload -->
        <div class="form-group">
          <label class="form-label">Ad Image <span style="font-weight:400;color:var(--text3);">(optional · JPG/PNG/WEBP/GIF · max 5MB)</span></label>

          <?php if ($editAd && !empty($editAd['image_path'])): ?>
          <div id="currentImgWrap" style="margin-bottom:10px;">
            <img src="<?= BASE_URL.'/'.$editAd['image_path'] ?>" class="img-thumb" alt="Current image">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--danger);cursor:pointer;">
              <input type="checkbox" name="remove_image" value="1" onchange="toggleRemoveImg(this)"> Remove current image
            </label>
          </div>
          <?php endif; ?>

          <div class="img-upload-box" onclick="document.getElementById('adImgInput').click()" id="imgDropBox">
            <img id="imgPreviewThumb" style="display:none;" class="img-thumb">
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text3);margin-bottom:6px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <div style="font-size:13px;font-weight:600;color:var(--text2);">Click to upload image</div>
            <div style="font-size:11px;color:var(--text3);margin-top:3px;">Full-bleed background behind slide text</div>
          </div>
          <input type="file" id="adImgInput" name="ad_image" accept="image/*" style="display:none;" onchange="previewAdImg(this)">
        </div>

        <!-- Background Color (used when no image or as overlay) -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Background Color <span style="font-weight:400;color:var(--text3);">(used when no image)</span></label>
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
              <input type="color" name="bg_color" id="fBg" value="<?= $editAd ? $editAd['bg_color'] : '#6D28D9' ?>" oninput="updatePreview()" style="width:44px;height:36px;border:1px solid var(--border);border-radius:8px;cursor:pointer;padding:2px;">
              <input type="text" id="fBgText" value="<?= $editAd ? $editAd['bg_color'] : '#6D28D9' ?>" style="width:100px;" class="form-control" oninput="document.getElementById('fBg').value=this.value;updatePreview()">
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <?php foreach(['#6D28D9','#1E3A5F','#065F46','#92400E','#991B1B','#1E40AF','#0F172A','#7C3AED','#BE185D','#0369A1'] as $c): ?>
              <div onclick="setColor('<?= $c ?>')" style="width:22px;height:22px;background:<?= $c ?>;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform=''"></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Sort Order <span style="font-weight:400;color:var(--text3);">(lower = first)</span></label>
            <input type="number" name="sort_order" class="form-control" value="<?= $editAd ? $editAd['sort_order'] : 0 ?>" min="0">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date (optional)</label>
            <input type="datetime-local" name="starts_at" class="form-control" value="<?= ($editAd&&$editAd['starts_at']) ? date('Y-m-d\TH:i',strtotime($editAd['starts_at'])) : '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End Date (optional)</label>
            <input type="datetime-local" name="ends_at" class="form-control" value="<?= ($editAd&&$editAd['ends_at']) ? date('Y-m-d\TH:i',strtotime($editAd['ends_at'])) : '' ?>">
          </div>
        </div>

        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-primary" id="fSubmitBtn"><?= $editAd ? 'Update Ad →' : 'Create Ad →' ?></button>
          <button type="button" class="btn btn-ghost" onclick="closeForm()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Ads Table ───────────────────────────────────── -->
  <div class="card" style="padding:0;overflow:hidden;">
    <?php if (empty($allAds)): ?>
    <div class="empty-state" style="padding:48px;">
      <span class="icon">📢</span>
      <h3>No ads yet</h3>
      <p>Create your first notice or promotional ad to show on the login page.</p>
    </div>
    <?php else: ?>
    <table class="ad-table">
      <thead>
        <tr>
          <th style="width:40%">Ad / Notice</th>
          <th>Type</th>
          <th>Show On</th>
          <th>Status</th>
          <th>Expires</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allAds as $ad): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php if (!empty($ad['image_path']) && file_exists(__DIR__.'/'.$ad['image_path'])): ?>
                <img src="<?= BASE_URL.'/'.$ad['image_path'] ?>" style="width:48px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0;" alt="">
              <?php else: ?>
                <div style="width:48px;height:36px;border-radius:6px;background:<?= clean($ad['bg_color']) ?>;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;"><?= clean($ad['emoji']) ?></div>
              <?php endif; ?>
              <div>
                <div style="font-weight:600;font-size:13px;color:var(--text);"><?= clean($ad['title']) ?></div>
                <div style="font-size:11px;color:var(--text3);"><?= clean(mb_substr($ad['body'],0,55)) ?>…</div>
              </div>
            </div>
          </td>
          <td><span class="status-pill" style="background:var(--surface2);color:var(--text2);"><?= ucfirst($ad['type']) ?></span></td>
          <td style="font-size:12px;color:var(--text2);"><?= ucfirst($ad['show_on']) ?></td>
          <td><span class="status-pill <?= $ad['is_active'] ? 'status-on' : 'status-off' ?>"><?= $ad['is_active'] ? 'Active' : 'Paused' ?></span></td>
          <td style="font-size:12px;color:var(--text3);"><?= $ad['ends_at'] ? date('M d, Y',strtotime($ad['ends_at'])) : '—' ?></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= $ad['id'] ?>" class="btn-icon" title="Edit">✏️ Edit</a>
              <a href="?toggle=<?= $ad['id'] ?>" class="btn-icon" title="<?= $ad['is_active']?'Pause':'Activate' ?>"><?= $ad['is_active']?'⏸':'▶️' ?></a>
              <a href="?delete=<?= $ad['id'] ?>" class="btn-icon del" title="Delete" onclick="return confirm('Delete this ad permanently?')">🗑</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>

<script>
function openForm() {
  // Reset to "new" mode
  document.getElementById('formTitle').textContent = '➕ New Ad / Notice';
  document.getElementById('fId').value = '0';
  document.getElementById('fExistingImg').value = '';
  document.getElementById('fSubmitBtn').textContent = 'Create Ad →';
  document.getElementById('fTitle').value = '';
  document.getElementById('fBody').value  = '';
  document.getElementById('prevTitle').textContent = 'Ad Title';
  document.getElementById('prevBody').textContent  = 'Ad description...';
  document.getElementById('prevImg').style.display = 'none';
  document.getElementById('imgPreviewThumb').style.display = 'none';
  document.getElementById('adImgInput').value = '';
  document.getElementById('adForm').style.display = '';
  document.getElementById('adForm').scrollIntoView({behavior:'smooth'});
}
function closeForm() { document.getElementById('adForm').style.display = 'none'; }

function updatePreview() {
  const bg = document.getElementById('fBg').value;
  document.getElementById('livePreview').style.background = bg;
  document.getElementById('fBgText').value = bg;
  document.getElementById('prevTitle').textContent = document.getElementById('fTitle').value || 'Ad Title';
  document.getElementById('prevBody').textContent  = document.getElementById('fBody').value  || 'Ad description...';
  document.getElementById('prevType').textContent  = document.getElementById('fType').options[document.getElementById('fType').selectedIndex].text;
}

function setColor(hex) {
  document.getElementById('fBg').value = hex;
  document.getElementById('fBgText').value = hex;
  updatePreview();
}

function previewAdImg(input) {
  if (!input.files || !input.files[0]) return;
  const url = URL.createObjectURL(input.files[0]);
  const prevImg  = document.getElementById('prevImg');
  const thumbImg = document.getElementById('imgPreviewThumb');
  prevImg.src  = url; prevImg.style.display = '';
  thumbImg.src = url; thumbImg.style.display = 'block';
  URL.revokeObjectURL(url);
}

function toggleRemoveImg(cb) {
  const wrap = document.getElementById('currentImgWrap');
  if (cb.checked) {
    document.getElementById('prevImg').style.display = 'none';
  } else {
    document.getElementById('prevImg').style.display = '';
  }
}

// If page loaded in edit mode, show form
<?php if ($editAd): ?>
document.getElementById('adForm').style.display = '';
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>