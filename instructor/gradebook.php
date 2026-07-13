<?php
// instructor/gradebook.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('instructor');

$iid = current_user_id();
$cid = (int)($_GET['course_id'] ?? 0);

// Courses this instructor has
$courses = $pdo->query(
    "SELECT c.* FROM courses c JOIN enrollments e ON e.course_id=c.id
     WHERE e.user_id=$iid AND c.is_published=1 ORDER BY c.title"
)->fetchAll();

$assignments = $students = $matrix = [];
if ($cid) {
    $assignments = $pdo->query(
        "SELECT * FROM assignments WHERE instructor_id=$iid AND course_id=$cid ORDER BY created_at"
    )->fetchAll();

    $students = $pdo->query(
        "SELECT u.id, u.name FROM users u
         JOIN enrollments e ON e.user_id=u.id
         WHERE e.course_id=$cid AND u.role='student' ORDER BY u.name"
    )->fetchAll();

    if ($assignments && $students) {
        $aIds = implode(',', array_column($assignments, 'id'));
        $subs = $pdo->query("SELECT * FROM submissions WHERE assignment_id IN ($aIds)")->fetchAll();
        foreach ($subs as $s) {
            $matrix[$s['student_id']][$s['assignment_id']] = $s;
        }
    }
}

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'instructor/dashboard.php',  ],
    ['label'=>'Assignments', 'href'=>BASE_URL.'instructor/assignments.php', ],
    ['label'=>'Submissions', 'href'=>BASE_URL.'instructor/submissions.php', ],
    ['label'=>'Gradebook',   'href'=>BASE_URL.'instructor/gradebook.php',   ],
    ['label'=>'My Course',     'href'=>BASE_URL.'instructor/course.php',            ],
    ['label'=>'Course Progress', 'href'=>BASE_URL.'instructor/course_progress.php', ],
];

layout_head('Gradebook');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Gradebook');
render_flash();
?>

<div class="card" style="padding:16px 24px">
  <form method="get" style="display:flex;gap:12px;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:1;max-width:320px">
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

<?php if ($cid): ?>
<div class="card">
  <div class="card-header">
    <h3>Gradebook</h3>
    <span style="font-size:.8rem;color:#94a3b8">
      <?= count($students) ?> students · <?= count($assignments) ?> assignments
    </span>
  </div>
  <?php if (!$assignments): ?>
    <p style="color:#94a3b8;padding:20px;text-align:center">No assignments for this course yet.</p>
  <?php elseif (!$students): ?>
    <p style="color:#94a3b8;padding:20px;text-align:center">No students enrolled.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <?php foreach ($assignments as $a): ?>
            <th title="<?= e($a['title']) ?>">
              <?= e(substr($a['title'],0,14)) ?>…<br>
              <small style="font-weight:400;text-transform:none">(<?= $a['points'] ?> pts)</small>
            </th>
          <?php endforeach; ?>
          <th>Total</th>
          <th>%</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $s):
        $earnedTotal = 0; $possibleTotal = 0; $allGraded = true;
      ?>
        <tr>
          <td><strong><?= e($s['name']) ?></strong></td>
          <?php foreach ($assignments as $a):
            $sub = $matrix[$s['id']][$a['id']] ?? null;
            $possibleTotal += $a['points'];
          ?>
            <td style="text-align:center">
              <?php if ($sub && $sub['score'] !== null): ?>
                <span class="grade-cell"><?= $sub['score'] ?></span>
                <?php $earnedTotal += $sub['score']; ?>
              <?php elseif ($sub): ?>
                <span style="color:#f59e0b;font-size:.8rem">Submitted</span>
                <?php $allGraded = false; ?>
              <?php else: ?>
                <span class="grade-na">—</span>
                <?php $allGraded = false; ?>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td class="grade-cell"><?= $allGraded ? $earnedTotal : '—' ?></td>
          <td>
            <?php if ($allGraded && $possibleTotal): ?>
              <strong><?= round($earnedTotal/$possibleTotal*100) ?>%</strong>
            <?php else: ?>
              <span class="grade-na">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php else: ?>
  <div class="alert alert-info">Select a course above to view its gradebook.</div>
<?php endif; ?>

<?php layout_close(); ?>