<?php
// admin/enroll_users.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $uid      = (int)($_POST['user_id']   ?? 0);
    $cid      = (int)($_POST['course_id'] ?? 0);

    if ($uid && $cid) {
        if ($action === 'enroll') {
            try {
                $pdo->prepare('INSERT INTO enrollments (user_id, course_id) VALUES (?,?)')->execute([$uid, $cid]);
                set_flash('success', 'User enrolled.');
            } catch (PDOException $e) {
                set_flash('error', 'User is already enrolled in this course.');
            }
        } elseif ($action === 'unenroll') {
            $pdo->prepare('DELETE FROM enrollments WHERE user_id=? AND course_id=?')->execute([$uid, $cid]);
            set_flash('success', 'User unenrolled.');
        }
    }
    header('Location: enroll_users.php'); exit;
}

$courses  = $pdo->query('SELECT * FROM courses ORDER BY title')->fetchAll();
$students = $pdo->query("SELECT * FROM users WHERE role IN ('student','instructor') ORDER BY name")->fetchAll();

// Current enrollments as set for quick lookup
$enrolled = [];
foreach ($pdo->query('SELECT user_id, course_id FROM enrollments')->fetchAll() as $e) {
    $enrolled[$e['user_id']][$e['course_id']] = true;
}

$nav = [
    ['label'=>'Dashboard',    'href'=>BASE_URL.'admin/dashboard.php',     ],
    ['label'=>'Manage Users', 'href'=>BASE_URL.'admin/manage_users.php',  ],
    ['label'=>'Manage Courses','href'=>BASE_URL.'admin/manage_courses.php',],
    ['label'=>'Enroll Users', 'href'=>BASE_URL.'admin/enroll_users.php',  ],
];

layout_head('Enroll Users');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Enrollment Management');
render_flash();
?>

<div class="card">
  <div class="card-header"><h3>Quick Enroll</h3></div>
  <form method="post" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="action" value="enroll">
    <div class="form-group" style="margin:0;flex:1;min-width:180px">
      <label>Select User</label>
      <select name="user_id" required>
        <option value="">— choose —</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= $s['role'] ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;flex:1;min-width:180px">
      <label>Select Course</label>
      <select name="course_id" required>
        <option value="">— choose —</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" style="margin-bottom:0">Enroll</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h3>Enrollment Matrix</h3></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>User</th>
          <?php foreach ($courses as $c): ?>
            <th style="white-space:nowrap;max-width:120px;overflow:hidden;text-overflow:ellipsis" title="<?= e($c['title']) ?>">
              <?= e(substr($c['title'],0,16)) ?>…
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><strong><?= e($s['name']) ?></strong><br><small style="color:#94a3b8"><?= $s['role'] ?></small></td>
          <?php foreach ($courses as $c): ?>
            <td style="text-align:center">
              <?php if (!empty($enrolled[$s['id']][$c['id']])): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="action"    value="unenroll">
                  <input type="hidden" name="user_id"   value="<?= $s['id'] ?>">
                  <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                  <button class="btn btn-danger btn-sm" title="Unenroll">✓</button>
                </form>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="action"    value="enroll">
                  <input type="hidden" name="user_id"   value="<?= $s['id'] ?>">
                  <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                  <button class="btn btn-outline btn-sm" title="Enroll">+</button>
                </form>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p style="margin-top:10px;font-size:.78rem;color:#94a3b8">
    ✓ = enrolled (click to unenroll) &nbsp;|&nbsp; + = not enrolled (click to enroll)
  </p>
</div>

<?php layout_close(); ?>