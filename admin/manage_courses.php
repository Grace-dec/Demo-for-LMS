<?php
// admin/manage_courses.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('admin');

$error = '';

// ── Handle POST actions ─
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── CREATE course ──
    if ($action === 'create') {
        $title = trim($_POST['title']       ?? '');
        $desc  = trim($_POST['description'] ?? '');

        if (!$title) {
            $error = 'Course title is required.';
        } else {
            // Handle optional course image upload
            $imgPath = '';
            if (!empty($_FILES['course_image']['name'])) {
                $uploaded = handle_upload('course_image', 'uploads/course_images');
                if ($uploaded) {
                    $imgPath = $uploaded;
                } else {
                    $error = 'Image upload failed. Allowed types: jpg, jpeg, png, webp, gif. Try again.';
                }
            }

            if (!$error) {
                $pdo->prepare(
                    'INSERT INTO courses (title, description, image_path, created_by) VALUES (?,?,?,?)'
                )->execute([$title, $desc, $imgPath, current_user_id()]);
                set_flash('success', 'Course created successfully.');
            }
        }
    }

    // ── UPLOAD / REPLACE image on existing course ──
    if ($action === 'upload_image') {
        $cid = (int)$_POST['course_id'];
        if ($cid && !empty($_FILES['course_image']['name'])) {
            $uploaded = handle_upload('course_image', 'uploads/course_images');
            if ($uploaded) {
                // Delete old image file if it exists
                $old = $pdo->prepare('SELECT image_path FROM courses WHERE id=?');
                $old->execute([$cid]);
                $oldPath = $old->fetchColumn();
                if ($oldPath && file_exists(ROOT_PATH . '/' . $oldPath)) {
                    @unlink(ROOT_PATH . '/' . $oldPath);
                }
                $pdo->prepare('UPDATE courses SET image_path=? WHERE id=?')->execute([$uploaded, $cid]);
                set_flash('success', 'Course image updated.');
            } else {
                set_flash('error', 'Image upload failed. Allowed: jpg, jpeg, png, webp, gif.');
            }
        }
    }

    // ── TOGGLE publish ──
    if ($action === 'toggle_publish') {
        $cid = (int)$_POST['course_id'];
        $cur = (int)$pdo->query("SELECT is_published FROM courses WHERE id=$cid")->fetchColumn();
        $pdo->prepare('UPDATE courses SET is_published=? WHERE id=?')->execute([!$cur, $cid]);
        $label = $cur ? 'unpublished' : 'published and now visible on the home page';
        set_flash('success', "Course $label.");
    }

    // ── DELETE course ─
    if ($action === 'delete') {
        $cid = (int)$_POST['course_id'];
        // Remove image file
        $imgRow = $pdo->prepare('SELECT image_path FROM courses WHERE id=?');
        $imgRow->execute([$cid]);
        $imgPath = $imgRow->fetchColumn();
        if ($imgPath && file_exists(ROOT_PATH . '/' . $imgPath)) {
            @unlink(ROOT_PATH . '/' . $imgPath);
        }
        $pdo->prepare('DELETE FROM courses WHERE id=?')->execute([$cid]);
        set_flash('success', 'Course deleted.');
    }

    // ── ADD MODULE ──
    if ($action === 'add_module') {
        $cid      = (int)$_POST['course_id'];
        $mtitle   = trim($_POST['module_title']   ?? '');
        $mcontent = trim($_POST['module_content'] ?? '');
        $filepath = handle_upload('module_file', 'uploads/modules');
        if ($mtitle) {
            $pdo->prepare(
                'INSERT INTO course_modules (course_id,title,content,file_path) VALUES (?,?,?,?)'
            )->execute([$cid, $mtitle, $mcontent, $filepath]);
            set_flash('success', 'Module added.');
        }
    }

    if (!$error) { header('Location: manage_courses.php'); exit; }
}

// ── Fetch all courses ──
$courses = $pdo->query(
    'SELECT c.*, u.name AS creator,
            (SELECT COUNT(*) FROM course_modules WHERE course_id=c.id) AS module_count,
            (SELECT COUNT(*) FROM enrollments    WHERE course_id=c.id) AS enrolled_count
     FROM courses c JOIN users u ON u.id=c.created_by
     ORDER BY c.created_at DESC'
)->fetchAll();

$nav = [
    ['label'=>'Dashboard',     'href'=>BASE_URL.'admin/dashboard.php',      ],
    ['label'=>'Manage Users',  'href'=>BASE_URL.'admin/manage_users.php',   ],
    ['label'=>'Manage Courses','href'=>BASE_URL.'admin/manage_courses.php', ],
    ['label'=>'Enroll Users',  'href'=>BASE_URL.'admin/enroll_users.php',   ],
    ['label'=>'View Home Page','href'=>BASE_URL.'home.php',                 ],
];

layout_head('Manage Courses');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Course Management');
render_flash();
?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<!-- ── CREATE COURSE FORM  -->
<div class="card">
  <div class="card-header">
    <h3>Create New Course</h3>
    <a href="<?= BASE_URL ?>home.php" target="_blank" class="btn btn-outline btn-sm">
      Preview Home Page
    </a>
  </div>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">

    <div style="display:grid;gap:16px;grid-template-columns:1fr 1fr">

      <div class="form-group" style="margin:0">
        <label>Course Title *</label>
        <input type="text" name="title" required
               placeholder="e.g. Introduction to Web Design"
               value="<?= e($_POST['title'] ?? '') ?>">
      </div>

      <!-- IMAGE UPLOAD -->
      <div class="form-group" style="margin:0">
        <label>Course Image</label>
        <input type="file" name="course_image" id="course_image_new"
               accept="image/jpeg,image/png,image/webp,image/gif">
        <small style="color:#94a3b8;font-size:.76rem;display:block;margin-top:4px">
          Accepted: JPG, PNG, WEBP, GIF &nbsp;·&nbsp; Leave blank to use placeholder
        </small>
      </div>

    </div>

    <div class="form-group" style="margin-top:14px">
      <label>Description</label>
      <textarea name="description" rows="3"
                placeholder="What will students learn in this course?"><?= e($_POST['description'] ?? '') ?></textarea>
    </div>

    <!-- Image preview before upload -->
    <div id="img-preview-wrap" style="display:none;margin-bottom:14px">
      <div style="font-size:.78rem;color:#94a3b8;margin-bottom:6px">Image preview:</div>
      <img id="img-preview"
           style="max-height:140px;max-width:280px;border-radius:8px;
                  border:2px dashed #e2e8f0;object-fit:cover" src="" alt="preview">
    </div>

    <button class="btn btn-primary">Create Course</button>
  </form>
</div>

<!-- ── COURSE LIST  -->
<div class="card">
  <div class="card-header">
    <h3>All Courses (<?= count($courses) ?>)</h3>
    <span style="font-size:.8rem;color:#94a3b8">
      <?= count(array_filter($courses, fn($c) => $c['is_published'])) ?> published
    </span>
  </div>

  <?php if (!$courses): ?>
    <p style="color:#94a3b8;text-align:center;padding:40px">
      No courses yet. Create one above.
    </p>
  <?php else: ?>

  <!-- Responsive card layout for courses -->
  <div style="display:flex;flex-direction:column;gap:18px">
  <?php foreach ($courses as $c): ?>

    <div style="display:grid;grid-template-columns:120px 1fr;gap:16px;
                border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;
                background:#fff">

      <!-- Course image thumbnail -->
      <div style="background:linear-gradient(135deg,#1a2744,#2d4a8a);
                  min-height:110px;position:relative;overflow:hidden;
                  display:flex;align-items:center;justify-content:center">
        <?php if (!empty($c['image_path']) && file_exists(ROOT_PATH . '/' . $c['image_path'])): ?>
          <img src="<?= BASE_URL . e($c['image_path']) ?>"
               alt="<?= e($c['title']) ?>"
               style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">
        <?php else: ?>
          <div style="text-align:center;color:rgba(255,255,255,.5);font-size:.72rem;padding:10px">
            <div style="font-size:1.8rem"></div>
            No image
          </div>
        <?php endif; ?>
      </div>

      <!-- Course info & actions -->
      <div style="padding:14px 16px 14px 0">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap">
          <div>
            <strong style="font-size:.98rem;color:#1a2744"><?= e($c['title']) ?></strong>
            <span class="badge badge-<?= $c['is_published'] ? 'published':'draft' ?>" style="margin-left:8px">
              <?= $c['is_published'] ? ' Published' : '⚪ Draft' ?>
            </span>
            <?php if ($c['is_published']): ?>
              <a href="<?= BASE_URL ?>home.php#courses-section"
                 target="_blank"
                 style="font-size:.72rem;color:#94a3b8;margin-left:6px;text-decoration:none"
                 title="View on home page"> visible on home</a>
            <?php endif; ?>
          </div>
        </div>

        <p style="font-size:.82rem;color:#94a3b8;margin:6px 0 10px;line-height:1.5">
          <?= e(substr($c['description'] ?? '', 0, 90)) ?><?= strlen($c['description'] ?? '') > 90 ? '…' : '' ?>
        </p>

        <div style="font-size:.76rem;color:#94a3b8;display:flex;gap:14px;flex-wrap:wrap;margin-bottom:12px">
          <span> <?= e($c['creator']) ?></span>
          <span> <?= $c['module_count'] ?> module<?= $c['module_count']!=1?'s':'' ?></span>
          <span> <?= $c['enrolled_count'] ?> enrolled</span>
        </div>

        <!-- Action buttons row -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">

          <!-- Publish / Unpublish -->
          <form method="post" style="display:inline">
            <input type="hidden" name="action"    value="toggle_publish">
            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
            <button class="btn btn-amber btn-sm">
              <?= $c['is_published'] ? '⬇ Unpublish' : ' Publish' ?>
            </button>
          </form>

          <!-- Add module toggle -->
          <button class="btn btn-outline btn-sm"
                  onclick="togglePanel('mod-<?= $c['id'] ?>')">
            📁 + Module
          </button>

          <!-- Upload / Replace image toggle -->
          <button class="btn btn-outline btn-sm"
                  onclick="togglePanel('img-<?= $c['id'] ?>')">
            🖼 <?= empty($c['image_path']) ? 'Add Image' : 'Replace Image' ?>
          </button>

          <!-- Delete -->
          <form method="post" style="display:inline">
            <input type="hidden" name="action"    value="delete">
            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
            <button class="btn btn-danger btn-sm"
                    data-confirm="Delete '<?= e(addslashes($c['title'])) ?>' and all its data? This cannot be undone.">
              🗑 Delete
            </button>
          </form>

        </div>

        <!-- ── Image upload panel -->
        <div id="img-<?= $c['id'] ?>" class="hidden"
             style="margin-top:12px;padding:14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
          <form method="post" enctype="multipart/form-data"
                style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="action"    value="upload_image">
            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
            <div class="form-group" style="margin:0;flex:1;min-width:220px">
              <label style="font-size:.76rem">
                <?= empty($c['image_path']) ? 'Upload Course Image' : 'Replace Course Image' ?>
              </label>
              <input type="file" name="course_image"
                     accept="image/jpeg,image/png,image/webp,image/gif" required>
              <small style="color:#94a3b8;font-size:.72rem">JPG, PNG, WEBP, GIF</small>
            </div>
            <button class="btn btn-primary btn-sm" style="margin-bottom:0">Upload</button>
          </form>
        </div>

        <!-- ── Add module panel -->
        <div id="mod-<?= $c['id'] ?>" class="hidden"
             style="margin-top:12px;padding:14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action"    value="add_module">
            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
            <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr">
              <div class="form-group" style="margin:0">
                <label>Module Title *</label>
                <input type="text" name="module_title" required placeholder="e.g. Chapter 1">
              </div>
              <div class="form-group" style="margin:0">
                <label>Attach File (optional)</label>
                <input type="file" name="module_file">
              </div>
            </div>
            <div class="form-group" style="margin-top:10px">
              <label>Content / Notes</label>
              <textarea name="module_content" rows="2"
                        placeholder="Module notes or description..."></textarea>
            </div>
            <button class="btn btn-primary btn-sm">Save Module</button>
          </form>
        </div>

      </div><!-- end course info -->
    </div><!-- end course row -->

  <?php endforeach; ?>
  </div><!-- end course list -->
  <?php endif; ?>
</div>

<style>.hidden { display: none !important; }</style>

<script>
// Toggle collapsible panels (module form, image form)
function togglePanel(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('hidden');
}

// Live image preview on new course form
document.getElementById('course_image_new')?.addEventListener('change', function () {
  const wrap    = document.getElementById('img-preview-wrap');
  const preview = document.getElementById('img-preview');
  if (this.files && this.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      wrap.style.display = 'block';
    };
    reader.readAsDataURL(this.files[0]);
  } else {
    wrap.style.display = 'none';
  }
});
</script>

<?php layout_close(); ?>