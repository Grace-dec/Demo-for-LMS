<?php
// student/dashboard.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('student');

$sid = current_user_id();

$enrolled  = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$sid")->fetchColumn();
$submitted = $pdo->query("SELECT COUNT(*) FROM submissions WHERE student_id=$sid")->fetchColumn();
$graded    = $pdo->query("SELECT COUNT(*) FROM submissions WHERE student_id=$sid AND score IS NOT NULL")->fetchColumn();

// Upcoming assignments (published, enrolled courses, not yet submitted)
$upcoming = $pdo->query(
    "SELECT a.*, c.title AS course_title FROM assignments a
     JOIN courses c ON c.id=a.course_id
     JOIN enrollments e ON e.course_id=c.id
     WHERE e.user_id=$sid AND a.is_published=1
       AND a.id NOT IN (SELECT assignment_id FROM submissions WHERE student_id=$sid)
       AND (a.due_date IS NULL OR a.due_date >= NOW())
     ORDER BY a.due_date ASC LIMIT 5"
)->fetchAll();

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'student/dashboard.php',         ],
    ['label'=>'My Courses',  'href'=>BASE_URL.'student/courses.php',            ],
    ['label'=>'Submit Work', 'href'=>BASE_URL.'student/submit_assignment.php',  ],
    ['label'=>'My Grades',   'href'=>BASE_URL.'student/my_grades.php',          ],
];

layout_head('Student Dashboard');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Student Dashboard');
render_flash();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Enrolled Courses</div>
    <div class="stat-value"><?= $enrolled ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Assignments Submitted</div>
    <div class="stat-value"><?= $submitted ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Graded</div>
    <div class="stat-value"><?= $graded ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Upcoming Assignments</h3>
    <a href="<?= BASE_URL ?>student/submit_assignment.php" class="btn btn-amber btn-sm">Submit Work</a>
  </div>
  <?php if (!$upcoming): ?>
    <p style="color:#94a3b8;padding:20px;text-align:center"> All assignments submitted or no upcoming tasks.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Assignment</th><th>Course</th><th>Due</th><th>Points</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($upcoming as $a): ?>
        <tr>
          <td><strong><?= e($a['title']) ?></strong></td>
          <td><?= e($a['course_title']) ?></td>
          <td>
            <?php if ($a['due_date']): ?>
              <span class="due-info"><?= date('M d, Y H:i', strtotime($a['due_date'])) ?></span>
            <?php else: ?><span style="color:#94a3b8">Open</span><?php endif; ?>
          </td>
          <td><?= $a['points'] ?> pts</td>
          <td>
            <a href="<?= BASE_URL ?>student/course_detail.php?id=<?= $a['course_id'] ?>"
               class="btn btn-outline btn-sm">View Course</a>
            <a href="<?= BASE_URL ?>student/submit_assignment.php?assignment_id=<?= $a['id'] ?>"
               class="btn btn-primary btn-sm">Submit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php layout_close(); ?>