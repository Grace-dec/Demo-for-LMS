<?php
// profile/index.php
// Shared profile page — works for admin, instructor, and student.
// Every role sees their own details and can edit them here.

require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_login(); // any logged-in user can access

$uid  = current_user_id();
$role = current_role();

// ── Fetch full user record ──────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// ── Handle form submissions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Update profile details ──────────────────────────────
    if ($action === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio   = trim($_POST['bio']   ?? '');

        if (!$name) {
            set_flash('error', 'Name cannot be empty.');
        } else {
            // Handle avatar upload
            $avatar = $user['avatar'];
            if (!empty($_FILES['avatar']['name'])) {
                $uploaded = handle_upload('avatar', 'uploads/avatars');
                if ($uploaded) {
                    // Delete old avatar
                    if ($avatar && file_exists(ROOT_PATH . '/' . $avatar)) {
                        @unlink(ROOT_PATH . '/' . $avatar);
                    }
                    $avatar = $uploaded;
                } else {
                    set_flash('error', 'Avatar upload failed. Use JPG, PNG, or WEBP under 2MB.');
                    header('Location: ' . BASE_URL . 'profile/index.php');
                    exit;
                }
            }

            $pdo->prepare(
                'UPDATE users SET name=?, phone=?, bio=?, avatar=? WHERE id=?'
            )->execute([$name, $phone, $bio, $avatar, $uid]);

            // Update session name immediately
            $_SESSION['name'] = $name;
            set_flash('success', 'Profile updated successfully.');
        }
        header('Location: ' . BASE_URL . 'profile/index.php');
        exit;
    }

    // ── Change password ─────────────────────────────────────
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            set_flash('error', 'All password fields are required.');
        } elseif (!password_verify($current, $user['password'])) {
            set_flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            set_flash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $uid]);
            set_flash('success', 'Password changed successfully.');
        }
        header('Location: ' . BASE_URL . 'profile/index.php');
        exit;
    }
}

// ── Re-fetch user after possible update ─────────────────────
$stmt->execute([$uid]);
$user = $stmt->fetch();

// ── Role-specific stats ─────────────────────────────────────
$stats = [];
if ($role === 'student') {
    $stats = [
        'Enrolled Courses'  => $pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid")->fetchColumn(),
        'Assignments Done'  => $pdo->query("SELECT COUNT(*) FROM submissions WHERE student_id=$uid")->fetchColumn(),
        'Graded'            => $pdo->query("SELECT COUNT(*) FROM submissions WHERE student_id=$uid AND score IS NOT NULL")->fetchColumn(),
    ];
} elseif ($role === 'instructor') {
    $stats = [
        'Courses Enrolled'  => $pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid")->fetchColumn(),
        'Assignments Made'  => $pdo->query("SELECT COUNT(*) FROM assignments WHERE instructor_id=$uid")->fetchColumn(),
        'Submissions Rcvd'  => $pdo->query("SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id=s.assignment_id WHERE a.instructor_id=$uid")->fetchColumn(),
    ];
} elseif ($role === 'admin') {
    $stats = [
        'Total Users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'Total Courses'  => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
        'Published'      => $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published=1")->fetchColumn(),
    ];
}

// ── Build nav by role ───────────────────────────────────────
$nav_map = 
[    'admin' => [
        ['label'=>'Dashboard',     'href'=>BASE_URL.'admin/dashboard.php',      ],
        ['label'=>'Manage Users',  'href'=>BASE_URL.'admin/manage_users.php',   ],
        ['label'=>'Manage Courses','href'=>BASE_URL.'admin/manage_courses.php', ],
        ['label'=>'Enroll Users',  'href'=>BASE_URL.'admin/enroll_users.php',   ],
        ['label'=>'View Home',     'href'=>BASE_URL.'home.php',                 ],
        ['label'=>'My Profile',    'href'=>BASE_URL.'profile/index.php',        ],
    ],
    'instructor' => [
        ['label'=>'Dashboard',       'href'=>BASE_URL.'instructor/dashboard.php',      ],
        ['label'=>'Assignments',     'href'=>BASE_URL.'instructor/assignments.php',     ],
        ['label'=>'Submissions',     'href'=>BASE_URL.'instructor/submissions.php',     ],
        ['label'=>'Gradebook',       'href'=>BASE_URL.'instructor/gradebook.php',       ],
        ['label'=>'Course Progress', 'href'=>BASE_URL.'instructor/course_progress.php', ],
        ['label'=>'My Profile',      'href'=>BASE_URL.'profile/index.php',              ],
    ],
    'student' => [
        ['label'=>'Dashboard',   'href'=>BASE_URL.'student/dashboard.php',        ],
        ['label'=>'My Courses',  'href'=>BASE_URL.'student/courses.php',          ],
        ['label'=>'Submit Work', 'href'=>BASE_URL.'student/submit_assignment.php', ],
        ['label'=>'My Grades',   'href'=>BASE_URL.'student/my_grades.php',        ],
        ['label'=>'My Profile',  'href'=>BASE_URL.'profile/index.php',            ],
    ],
];
$nav = $nav_map[$role] ?? $nav_map['student'];

// ── Avatar initials fallback ────────────────────────────────
$initials = '';
foreach (explode(' ', $user['name']) as $word) {
    $initials .= strtoupper(mb_substr($word, 0, 1));
    if (strlen($initials) >= 2) break;
}

layout_head('My Profile');
layout_sidebar($nav, $role, current_name());
layout_main_open('My Profile');
render_flash();
?>

<!-- ── Profile Header Card ───────────────────────────────── -->
<div class="card" style="padding:0;overflow:hidden;margin-bottom:24px">

  <!-- Banner -->
  <div style="height:100px;background:linear-gradient(135deg,#1a2744 0%,#2d4a8a 60%,#f59e0b 100%);position:relative"></div>

  <!-- Avatar + name row -->
  <div style="padding:0 28px 24px;position:relative">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px">

      <!-- Avatar -->
      <div style="margin-top:-44px;position:relative">
        <?php if (!empty($user['avatar']) && file_exists(ROOT_PATH . '/' . $user['avatar'])): ?>
          <img src="<?= BASE_URL . e($user['avatar']) ?>"
               alt="<?= e($user['name']) ?>"
               style="width:88px;height:88px;border-radius:50%;object-fit:cover;
                      border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.15)">
        <?php else: ?>
          <div style="width:88px;height:88px;border-radius:50%;
                      background:linear-gradient(135deg,#1a2744,#2d4a8a);
                      border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.15);
                      display:flex;align-items:center;justify-content:center;
                      font-size:1.8rem;font-weight:700;color:#f59e0b;
                      font-family:'Playfair Display',serif">
            <?= e($initials) ?>
          </div>
        <?php endif; ?>
        <!-- Camera icon to trigger avatar upload -->
        <label for="quick-avatar"
               style="position:absolute;bottom:2px;right:2px;width:26px;height:26px;
                      background:#f59e0b;border-radius:50%;display:flex;align-items:center;
                      justify-content:center;cursor:pointer;font-size:.75rem;
                      box-shadow:0 2px 6px rgba(0,0,0,.2)"
               title="Change photo">📷</label>
      </div>

      <!-- Name + role + joined -->
      <div style="flex:1;min-width:180px;padding-top:12px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <h2 style="font-size:1.3rem;font-weight:700;color:#1a2744"><?= e($user['name']) ?></h2>
          <span class="badge badge-<?= e($role) ?>"><?= ucfirst(e($role)) ?></span>
          <?php if ($user['is_active']): ?>
            <span class="badge badge-active">Active</span>
          <?php else: ?>
            <span class="badge badge-inactive">Inactive</span>
          <?php endif; ?>
        </div>
        <div style="color:#94a3b8;font-size:.84rem;margin-top:4px">
          📧 <?= e($user['email']) ?>
          <?php if ($user['phone']): ?>
            &nbsp;·&nbsp; 📞 <?= e($user['phone']) ?>
          <?php endif; ?>
          &nbsp;·&nbsp; 🗓 Member since <?= date('F Y', strtotime($user['created_at'])) ?>
        </div>
        <?php if ($user['bio']): ?>
          <p style="font-size:.86rem;color:#475569;margin-top:8px;line-height:1.6;max-width:500px">
            <?= nl2br(e($user['bio'])) ?>
          </p>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- ── Stats Row ─────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:28px">
  <?php
  $stat_colors = ['', 'blue', 'green'];
  $i = 0;
  foreach ($stats as $label => $value):
  ?>
  <div class="stat-card <?= $stat_colors[$i++] ?>">
    <div class="stat-label"><?= e($label) ?></div>
    <div class="stat-value"><?= $value ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Two column layout: Edit Profile | Change Password ─── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

  <!-- Edit Profile -->
  <div class="card">
    <div class="card-header">
      <h3>✏️ Edit Profile</h3>
    </div>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_profile">

      <!-- Hidden avatar field (triggered by camera icon above) -->
      <input type="file" id="quick-avatar" name="avatar"
             accept="image/jpeg,image/png,image/webp"
             style="display:none"
             onchange="previewAvatar(this)">

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" required
               value="<?= e($user['name']) ?>"
               placeholder="Your full name">
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <input type="email" value="<?= e($user['email']) ?>" disabled
               style="background:#f8fafc;color:#94a3b8;cursor:not-allowed"
               title="Email cannot be changed. Contact admin.">
        <small style="color:#94a3b8;font-size:.75rem">Email cannot be changed here</small>
      </div>

      <div class="form-group">
        <label>Phone Number</label>
        <input type="tel" name="phone"
               value="<?= e($user['phone'] ?? '') ?>"
               placeholder="+1 234 567 8900">
      </div>

      <div class="form-group">
        <label>Bio / About Me</label>
        <textarea name="bio" rows="4"
                  placeholder="Tell us a little about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
      </div>

      <!-- Avatar upload (alternate full-size picker) -->
      <div class="form-group">
        <label>Profile Photo</label>
        <input type="file" name="avatar" id="avatar-main"
               accept="image/jpeg,image/png,image/webp"
               onchange="previewAvatar(this)">
        <small style="color:#94a3b8;font-size:.75rem">JPG, PNG, or WEBP · max 2MB</small>
        <!-- preview -->
        <img id="avatar-preview"
             style="display:none;width:64px;height:64px;border-radius:50%;
                    object-fit:cover;margin-top:8px;border:2px solid #e2e8f0"
             src="" alt="preview">
      </div>

      <button class="btn btn-primary" style="width:100%;justify-content:center">
        💾 Save Changes
      </button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header">
      <h3>🔒 Change Password</h3>
    </div>

    <form method="post">
      <input type="hidden" name="action" value="change_password">

      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" required
               placeholder="Enter your current password">
      </div>

      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" required
               placeholder="Min 6 characters" minlength="6">
      </div>

      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required
               placeholder="Repeat new password">
      </div>

      <!-- Password strength hint -->
      <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;
                  margin-bottom:16px;font-size:.78rem;color:#64748b;line-height:1.7">
        <strong style="color:#1a2744">💡 Password tips:</strong><br>
        Use at least 6 characters<br>
        Mix letters, numbers, and symbols<br>
        Avoid using your name or email
      </div>

      <button class="btn btn-primary" style="width:100%;justify-content:center">
        🔑 Update Password
      </button>
    </form>

    <!-- Account Info box -->
    <div style="margin-top:20px;padding:14px;background:#f8fafc;border-radius:8px;
                border:1px solid #e2e8f0;font-size:.82rem;color:#475569;line-height:1.8">
      <strong style="display:block;color:#1a2744;margin-bottom:6px">Account Information</strong>
      <div>🆔 User ID: <strong><?= $user['id'] ?></strong></div>
      <div>👤 Role: <strong><?= ucfirst(e($role)) ?></strong></div>
      <div>📅 Joined: <strong><?= date('d M Y', strtotime($user['created_at'])) ?></strong></div>
      <div>🔒 Status:
        <strong style="color:<?= $user['is_active'] ? '#16a34a' : '#dc2626' ?>">
          <?= $user['is_active'] ? 'Active' : 'Disabled' ?>
        </strong>
      </div>
    </div>
  </div>

</div>

<!-- ── Responsive single column on mobile ────────────────── -->
<style>
  @media (max-width: 720px) {
    div[style*="grid-template-columns:1fr 1fr"] {
      grid-template-columns: 1fr !important;
    }
  }
</style>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      // Update the big avatar at the top
      const topAvatar = document.querySelector('.profile-top-avatar');
      if (topAvatar) topAvatar.src = e.target.result;
      // Update the small preview
      const prev = document.getElementById('avatar-preview');
      prev.src = e.target.result;
      prev.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
// Sync the two file inputs (camera icon + main picker)
document.getElementById('quick-avatar')?.addEventListener('change', function() {
  // copy file to main input is not possible cross-browser,
  // so just auto-submit when camera icon is used
  if (this.files[0]) {
    this.closest('form')?.submit() ||
    document.querySelector('form[enctype]').submit();
  }
});
</script>

<?php layout_close(); ?>