<?php
// student/my_grades.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('student');

$sid = current_user_id();

// All assignments for enrolled+published courses
$allAssignments = $pdo->query(
    "SELECT a.*, c.title AS course_title,
            s.score, s.feedback, s.submitted_at, s.graded_at
     FROM assignments a
     JOIN courses c ON c.id=a.course_id
     JOIN enrollments e ON e.course_id=c.id AND e.user_id=$sid
     LEFT JOIN submissions s ON s.assignment_id=a.id AND s.student_id=$sid
     WHERE a.is_published=1
     ORDER BY c.title, a.due_date"
)->fetchAll();

// Group by course
$byCourse = [];
foreach ($allAssignments as $a) {
    $byCourse[$a['course_title']][] = $a;
}

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'student/dashboard.php',       ],
    ['label'=>'My Courses',  'href'=>BASE_URL.'student/courses.php',            ],
    ['label'=>'Submit Work', 'href'=>BASE_URL.'student/submit_assignment.php',  ],
    ['label'=>'My Grades',   'href'=>BASE_URL.'student/my_grades.php',          ],
];

layout_head('My Grades');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('My Grades & Feedback');
render_flash();
?>

<?php if (!$byCourse): ?>
  <div class="alert alert-info">No graded assignments yet. Check back after your instructor grades your work.</div>
<?php else: ?>
<?php foreach ($byCourse as $courseTitle => $items):
  $earned   = 0; $possible = 0; $complete = true;
  foreach ($items as $i) {
      $possible += $i['points'];
      if ($i['score'] !== null) $earned += $i['score'];
      else $complete = false;
  }
?>
<div class="card">
  <div class="card-header">
    <h3> <?= e($courseTitle) ?></h3>
    <?php if ($complete && $possible): ?>
      <span style="font-weight:700;font-size:1.1rem;color:#1a2744">
        <?= $earned ?>/<?= $possible ?> (<?= round($earned/$possible*100) ?>%)
      </span>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Assignment</th><th>Due</th><th>Status</th><th>Score</th><th>Feedback</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $a):
        if ($a['submitted_at']) {
            $status = submission_status($a, $a['due_date'] ?? '9999-01-01');
        } else {
            $status = 'missing';
        }
      ?>
        <tr>
          <td><strong><?= e($a['title']) ?></strong></td>
          <td>
            <?php if ($a['due_date']): ?>
              <span class="due-info <?= strtotime($a['due_date'])<time()?'overdue':'' ?>">
                <?= date('M d, Y', strtotime($a['due_date'])) ?>
              </span>
            <?php else: ?><span style="color:#94a3b8">Open</span><?php endif; ?>
          </td>
          <td><span class="badge badge-<?= $status ?>"><?= ucfirst($status) ?></span></td>
          <td>
            <?php if ($a['score'] !== null): ?>
              <strong class="grade-cell"><?= $a['score'] ?>/<?= $a['points'] ?></strong>
              <span style="color:#94a3b8;font-size:.8rem">
                (<?= round($a['score']/$a['points']*100) ?>%)
              </span>
            <?php elseif ($a['submitted_at']): ?>
              <span style="color:#f59e0b;font-size:.82rem">Awaiting grade</span>
            <?php else: ?>
              <span class="grade-na">Not submitted</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($a['feedback']): ?>
              <span style="font-size:.84rem;color:#475569;font-style:italic"><?= e($a['feedback']) ?></span>
            <?php elseif ($a['graded_at']): ?>
              <span class="grade-na">No feedback</span>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php layout_close(); ?>