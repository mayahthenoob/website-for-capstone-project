<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'My Profile — Quiz Carnival';
require_once __DIR__ . '/includes/header-sidebar.php';

$pdo    = getDB();
$uid    = $user['id'];
$tab    = $_GET['tab'] ?? 'info';

// Full user data
$u = $pdo->prepare("SELECT * FROM users WHERE id=?");
$u->execute([$uid]); $u = $u->fetch();

// Certificates
$certs = $pdo->prepare("SELECT * FROM certificates WHERE user_id=? ORDER BY uploaded_at DESC");
$certs->execute([$uid]); $certs = $certs->fetchAll();

// Enrolled/created classes count
if ($u['role'] === 'teacher') {
    $classCount = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id=?"); $classCount->execute([$uid]);
} else {
    $classCount = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id=?"); $classCount->execute([$uid]);
}
$classCount = (int)$classCount->fetchColumn();

$error   = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio   = trim($_POST['bio'] ?? '');
    if (!$name) { $error = 'Name is required.'; }
    else {
        // Handle avatar upload
        $avatarPath = $u['profile_pic'];
        if (!empty($_FILES['avatar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { $error = 'Invalid image format.'; }
            elseif ($_FILES['avatar']['size'] > MAX_UPLOAD_MB * 1024 * 1024) { $error = 'Image too large (max '.MAX_UPLOAD_MB.'MB).'; }
            else {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                $fname = 'avatar_'.$uid.'_'.time().'.'.$ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_DIR.$fname)) {
                    $avatarPath = 'uploads/'.$fname;
                } else { $error = 'Failed to upload image.'; }
            }
        }
        if (!$error) {
            $pdo->prepare("UPDATE users SET name=?,email=?,bio=?,profile_pic=? WHERE id=?")->execute([$name,$email,$bio,$avatarPath,$uid]);
            $_SESSION['user_name']   = $name;
            $_SESSION['user_avatar'] = $avatarPath ?: 'assets/default-avatar.jpg';
            $u['name'] = $name; $u['email'] = $email; $u['bio'] = $bio; $u['profile_pic'] = $avatarPath;
            $success = 'Profile updated successfully!';
        }
    }
}

// Handle certificate upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_cert') {
    if (empty($_FILES['certificate']['name'])) { $error = 'No file selected.'; }
    else {
        $ext = strtolower(pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','png','jpg','jpeg'])) { $error = 'Only PDF, PNG, JPG allowed.'; }
        elseif ($_FILES['certificate']['size'] > MAX_UPLOAD_MB * 1024 * 1024) { $error = 'File too large (max '.MAX_UPLOAD_MB.'MB).'; }
        else {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $fname = 'cert_'.$uid.'_'.time().'.'.$ext;
            if (move_uploaded_file($_FILES['certificate']['tmp_name'], UPLOAD_DIR.$fname)) {
                $pdo->prepare("INSERT INTO certificates (user_id, filename, original_name) VALUES (?,?,?)")->execute([$uid, $fname, $_FILES['certificate']['name']]);
                $success = 'Certificate uploaded!';
                // Reload certs
                $certsQ = $pdo->prepare("SELECT * FROM certificates WHERE user_id=? ORDER BY uploaded_at DESC");
                $certsQ->execute([$uid]); $certs = $certsQ->fetchAll();
            } else { $error = 'Upload failed.'; }
        }
    }
}
?>
<style>
.page-content { padding: 0; flex: 1; }
.profile-hero { background: linear-gradient(135deg, #111827, #1f2937); padding: 36px 40px; display: flex; align-items: center; gap: 24px; }
.avatar-big { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.2); }
.hero-info h1 { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 2px; }
.hero-info p  { font-size: 13px; color: #9ca3af; }
.hero-stats { margin-left: auto; display: flex; gap: 24px; }
.hero-stat { text-align: center; }
.hero-stat-val { font-size: 22px; font-weight: 800; color: #fff; }
.hero-stat-lbl { font-size: 11px; color: #9ca3af; }
.tabs-bar { background: #fff; border-bottom: 1px solid #e6e9ef; padding: 0 40px; display: flex; }
.tab-btn { padding: 16px 0; margin-right: 28px; border: none; background: none; font-family: 'Inter',sans-serif; font-size: 14px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-bottom: 3px solid transparent; }
.tab-btn.active { color: var(--primary); border-color: var(--primary); }
.tab-content { padding: 32px 40px; display: none; }
.tab-content.active { display: block; }
.form-section { background: #fff; border-radius: 16px; padding: 28px; box-shadow: var(--shadow); max-width: 600px; }
.form-section h3 { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.form-group { margin-bottom: 18px; }
.form-group.full { grid-column: 1/-1; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 6px; }
.form-input { width: 100%; padding: 11px 14px; border: 1.5px solid #e6e9ef; border-radius: 10px; font-size: 14px; font-family:'Inter',sans-serif; outline: none; }
.form-input:focus { border-color: var(--primary); }
.avatar-upload { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.avatar-preview { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #e6e9ef; }
.btn-upload-file { padding: 9px 18px; border-radius: 10px; border: 1.5px solid #e6e9ef; background: #f4f6f9; font-weight: 600; font-size: 13px; cursor: pointer; font-family:'Inter',sans-serif; }
.btn-save { background: var(--primary); color: #fff; border: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; font-family:'Inter',sans-serif; }
.btn-save:hover { background: #3310cc; }
.alert { padding: 11px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; margin-bottom: 16px; }
.alert-error   { background: #fee2e2; color: #b91c1c; }
.alert-success { background: #e0f7f4; color: #00897b; }

/* Certs */
.cert-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 24px; }
.cert-card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); border: 1px solid #f0f0f0; }
.cert-thumb { height: 160px; display: flex; align-items: center; justify-content: center; background: #f8f9fc; overflow: hidden; }
.cert-thumb img { width: 100%; height: 100%; object-fit: cover; }
.cert-thumb-pdf { font-size: 50px; color: #e74c3c; }
.cert-info { padding: 12px 16px; }
.cert-name { font-size: 13px; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cert-date { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.cert-actions { padding: 10px 16px; border-top: 1px solid #f4f6f9; display: flex; gap: 8px; }
.btn-cert { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; font-family:'Inter',sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
.btn-cert-view { background: #ede9ff; color: var(--primary); }
.btn-cert-del  { background: #fee2e2; color: #e74c3c; }
.upload-area { border: 2px dashed #e6e9ef; border-radius: 14px; padding: 40px; text-align: center; cursor: pointer; transition: 0.2s; margin-bottom: 16px; }
.upload-area:hover { border-color: var(--primary); background: #fafbff; }
.upload-area i { font-size: 40px; color: #ddd; display: block; margin-bottom: 10px; }
.upload-area p  { color: var(--text-muted); font-size: 13px; }
</style>

<div class="page-content">
  <div class="profile-hero">
    <img src="<?= SITE_ROOT . ($u['profile_pic'] ?: 'assets/default-avatar.jpg') ?>" alt="" class="avatar-big" onerror="this.src='<?= SITE_ROOT ?>assets/default-avatar.jpg'">
    <div class="hero-info">
      <h1><?= h($u['name']) ?></h1>
      <p><?= h('@'.$u['username']) ?> &bull; <span style="text-transform:capitalize"><?= h($u['role']) ?></span></p>
      <?php if ($u['bio']): ?><p style="margin-top:6px;font-size:12px"><?= h($u['bio']) ?></p><?php endif; ?>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="hero-stat-val"><?= $classCount ?></div><div class="hero-stat-lbl"><?= $u['role']==='teacher'?'Classes':'Enrolled' ?></div></div>
      <div class="hero-stat"><div class="hero-stat-val"><?= count($certs) ?></div><div class="hero-stat-lbl">Certificates</div></div>
    </div>
  </div>

  <div class="tabs-bar">
    <button class="tab-btn <?= $tab==='info'?'active':'' ?>" onclick="switchTab('info', this)">Personal Info</button>
    <button class="tab-btn <?= $tab==='certs'?'active':'' ?>" onclick="switchTab('certs', this)">Certificates</button>
  </div>

  <!-- Info Tab -->
  <div class="tab-content <?= $tab==='info'?'active':'' ?>" id="tab-info">
    <?php if ($error && $tab==='info'):   ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success && $tab==='info'): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
    <div class="form-section">
      <h3>Edit Profile</h3>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        <div class="avatar-upload">
          <img id="avatarPreview" src="<?= SITE_ROOT . ($u['profile_pic'] ?: 'assets/default-avatar.jpg') ?>" class="avatar-preview" onerror="this.src='<?= SITE_ROOT ?>assets/default-avatar.jpg'">
          <div>
            <label for="avatarFile" class="btn-upload-file"><i class='bx bx-camera'></i> Change Photo</label>
            <input type="file" id="avatarFile" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px">JPG, PNG — max 5 MB</p>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-input" name="name" value="<?= h($u['name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" class="form-input" name="email" value="<?= h($u['email']??'') ?>">
          </div>
          <div class="form-group full">
            <label class="form-label">Bio</label>
            <textarea class="form-input" name="bio" rows="3" style="resize:vertical"><?= h($u['bio']??'') ?></textarea>
          </div>
        </div>
        <button type="submit" class="btn-save">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Certificates Tab -->
  <div class="tab-content <?= $tab==='certs'?'active':'' ?>" id="tab-certs">
    <?php if ($error && $tab==='certs'):   ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success && $tab==='certs'): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

    <div class="form-section" style="max-width:500px;margin-bottom:28px">
      <h3>Upload Certificate</h3>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_cert">
        <label for="certFile" class="upload-area">
          <i class='bx bx-upload'></i>
          <p>Click to upload a certificate<br><small>PDF, PNG, JPG — max 5 MB</small></p>
        </label>
        <input type="file" id="certFile" name="certificate" accept=".pdf,.png,.jpg,.jpeg" style="display:none" onchange="this.form.submit()">
      </form>
    </div>

    <?php if (empty($certs)): ?>
      <p style="color:var(--text-muted);font-size:14px">No certificates uploaded yet.</p>
    <?php else: ?>
    <div class="cert-grid">
      <?php foreach ($certs as $c):
        $ext  = strtolower(pathinfo($c['filename'], PATHINFO_EXTENSION));
        $url  = SITE_ROOT . 'uploads/' . $c['filename'];
      ?>
      <div class="cert-card">
        <div class="cert-thumb">
          <?php if ($ext === 'pdf'): ?>
            <i class='bx bxs-file-pdf cert-thumb-pdf'></i>
          <?php else: ?>
            <img src="<?= h($url) ?>" alt="<?= h($c['original_name']) ?>">
          <?php endif; ?>
        </div>
        <div class="cert-info">
          <div class="cert-name"><?= h($c['original_name']) ?></div>
          <div class="cert-date"><?= date('d M Y', strtotime($c['uploaded_at'])) ?></div>
        </div>
        <div class="cert-actions">
          <a href="<?= h($url) ?>" class="btn-cert btn-cert-view" target="_blank"><i class='bx bx-show'></i> View</a>
          <a href="<?= SITE_ROOT ?>api/delete-cert.php?id=<?= $c['id'] ?>" class="btn-cert btn-cert-del" onclick="return confirm('Delete this certificate?')"><i class='bx bx-trash'></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('certFile').addEventListener('change', function() {
    if (this.files.length) this.form.submit();
});

function toggleDropdown() { document.getElementById("profileDropdown").classList.toggle("show"); }
window.addEventListener('click', (e) => { if (!e.target.closest('.profile-container')) document.getElementById("profileDropdown")?.classList.remove("show"); });
</script>
</div></body></html>
