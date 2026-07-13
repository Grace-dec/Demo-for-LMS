 <?php
// instructor/course_progress.php
// Shows per-student module completion progress for a selected course.

require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('instructor');

$iid = current_user_id();
$cid = (int)($_GET['course_id'] ?? 0);

// Courses this instructor is enrolled in
$courses = $pdo->query(
    "SELECT c.* FROM courses c
     JOIN enrollments e ON e.course_id=c.id
     WHERE e.user_id=$iid AND c.is_published=1 ORDER BY c.title"
)->fetchAll();

$course = $modules = $students = $progress_map = [];
$total_modules = 0;

if ($cid) {
    // Verify instructor is in this course
    $ok = $pdo->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?');
    $ok->execute([$iid, $cid]);
    if (!$ok->fetchColumn()) {
        set_flash('error', 'You are not enrolled in that course.');
        header('Location: course_progress.php'); exit;
    }

    $stmt = $pdo->prepare(
        'SELECT c.*, u.name AS creator FROM courses c JOIN users u ON u.id=c.created_by WHERE c.id=?'
    );
    $stmt->execute([$cid]);
    $course = $stmt->fetch();

    $modules = $pdo->query(
        "SELECT * FROM course_modules WHERE course_id=$cid ORDER BY sort_order, id"
    )->fetchAll();
    $total_modules = count($modules);

    $students = $pdo->query(
        "SELECT u.id, u.name, u.email FROM users u
         JOIN enrollments e ON e.user_id=u.id
         WHERE e.course_id=$cid AND u.role='student' ORDER BY u.name"
    )->fetchAll();

    // Build progress map: $progress_map[student_id][module_id] = completed_at
    if ($students && $modules) {
        $sids = implode(',', array_column($students, 'id'));
        $mids = implode(',', array_column($modules,  'id'));
        $rows = $pdo->query(
            "SELECT user_id, module_id, completed_at FROM module_progress
             WHERE user_id IN ($sids) AND module_id IN ($mids)"
        )->fetchAll();
        foreach ($rows as $r) {
            $progress_map[$r['user_id']][$r['module_id']] = $r['completed_at'];
        }
    }
}

$nav = [
    ['label'=>'Dashboard',       'href'=>BASE_URL.'instructor/dashboard.php',      ],
    ['label'=>'Assignments',     'href'=>BASE_URL.'instructor/assignments.php',     ],
    ['label'=>'Submissions',     'href'=>BASE_URL.'instructor/submissions.php',     ],
    ['label'=>'Gradebook',       'href'=>BASE_URL.'instructor/gradebook.php',       ],
    ['label'=>'Course Progress', 'href'=>BASE_URL.'instructor/course_progress.php', ],
    ['label'=>'My Course',     'href'=>BASE_URL.'instructor/course.php',            ],
];

layout_head('Course Progress');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Student Course Progress');
render_flash();
?>

<!-- Course selector -->
<div class="card" style="padding:16px 24px;margin-bottom:22px">
  <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <div class="form-group" style="margin:0;flex:1;min-width:220px">
      <label>Select Course</label>
      <select name="course_id" onchange="this.form.submit()">
        <option value="">— choose a course —</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cid===$c['id']?'selected':'' ?>><?= e($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if (!$cid): ?>
  <div class="alert alert-info">Select a course above to view student progress.</div>

<?php elseif (!$students): ?>
  <div class="alert alert-info">No students are enrolled in <strong><?= e($course['title']) ?></strong> yet.</div>

<?php elseif (!$modules): ?>
  <div class="alert alert-info">No modules have been added to this course yet.</div>

<?php else: ?>

<!-- Summary stats row -->
<?php
  $class_avg_sum = 0;
  foreach ($students as $s) {
      $done = count($progress_map[$s['id']] ?? []);
      $class_avg_sum += $total_modules > 0 ? ($done / $total_modules * 100) : 0;
  }
  $class_avg = count($students) > 0 ? (int)round($class_avg_sum / count($students)) : 0;
  $fully_done = 0;
  foreach ($students as $s) {
      if (count($progress_map[$s['id']] ?? []) === $total_modules) $fully_done++;
  }
?>
<div class="stats-grid" style="margin-bottom:22px">
  <div class="stat-card">
    <div class="stat-label">Students Enrolled</div>
    <div class="stat-value"><?= count($students) ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Course Modules</div>
    <div class="stat-value"><?= $total_modules ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Avg. Completion</div>
    <div class="stat-value"><?= $class_avg ?>%</div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Fully Complete</div>
    <div class="stat-value"><?= $fully_done ?></div>
  </div>
</div>

<!-- Per-student progress table -->
<div class="card">
  <div class="card-header">
    <h3>Progress by Student — <?= e($course['title']) ?></h3>
    <span style="font-size:.8rem;color:#94a3b8"><?= count($students) ?> students</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Overall</th>
          <?php foreach ($modules as $m): ?>
            <th style="white-space:nowrap;max-width:110px" title="<?= e($m['title']) ?>">
              <?= e(substr($m['title'], 0, 14)) ?><?= strlen($m['title'])>14?'…':'' ?>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $s):
        $done_count = count($progress_map[$s['id']] ?? []);
        $pct        = $total_modules > 0 ? (int)round($done_count / $total_modules * 100) : 0;
        $pct_color  = $pct >= 80 ? '#16a34a' : ($pct >= 40 ? '#f59e0b' : '#dc2626');
      ?>
        <tr>
          <td>
            <strong><?= e($s['name']) ?></strong>
            <br><small style="color:#94a3b8"><?= e($s['email']) ?></small>
          </td>
          <td style="min-width:140px">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct_color ?>;border-radius:99px;transition:width .4s"></div>
              </div>
              <span style="font-size:.8rem;font-weight:700;color:<?= $pct_color ?>;min-width:36px"><?= $pct ?>%</span>
            </div>
            <div style="font-size:.72rem;color:#94a3b8;margin-top:2px"><?= $done_count ?>/<?= $total_modules ?> modules</div>
          </td>
          <?php foreach ($modules as $m):
            $completed_at = $progress_map[$s['id']][$m['id']] ?? null;
          ?>
            <td style="text-align:center">
              <?php if ($completed_at): ?>
                <span title="Completed <?= date('M d, Y H:i', strtotime($completed_at)) ?>"
                      style="color:#16a34a;font-size:1.1rem;cursor:default">✓</span>
              <?php else: ?>
                <span style="color:#e2e8f0;font-size:.9rem">○</span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p style="margin-top:14px;font-size:.76rem;color:#94a3b8;padding:0 4px">
    ✓ = module completed &nbsp;|&nbsp; ○ = not yet started &nbsp;|&nbsp;
    Hover ✓ to see completion date
  </p>
</div>

<!-- Module-level completion summary -->
<div class="card">
  <div class="card-header">
    <h3>Completion by Module</h3>
  </div>
  <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($modules as $idx => $m):
      $completed_count = 0;
      foreach ($students as $s) {
          if (!empty($progress_map[$s['id']][$m['id']])) $completed_count++;
      }
      $mod_pct = count($students) > 0 ? (int)round($completed_count / count($students) * 100) : 0;
      $col     = $mod_pct >= 80 ? '#16a34a' : ($mod_pct >= 40 ? '#f59e0b' : '#dc2626');
    ?>
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:.88rem">
        <span><strong><?= $idx+1 ?>.</strong> <?= e($m['title']) ?></span>
        <span style="color:<?= $col ?>;font-weight:700"><?= $completed_count ?>/<?= count($students) ?> students (<?= $mod_pct ?>%)</span>
      </div>
      <div style="height:10px;background:#f1f5f9;border-radius:99px;overflow:hidden">
        <div style="height:100%;width:<?= $mod_pct ?>%;background:<?= $col ?>;border-radius:99px;transition:width .5s"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php endif; ?>

<?php layout_close(); ?>