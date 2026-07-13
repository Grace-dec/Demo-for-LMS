<?php
// instructor/dashboard.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('instructor');

$iid = current_user_id();

$myCourses   = $pdo->query('SELECT COUNT(*) FROM enrollments WHERE user_id=' . $iid)->fetchColumn();
$myAssign    = $pdo->query('SELECT COUNT(*) FROM assignments WHERE instructor_id=' . $iid)->fetchColumn();
$submissions = $pdo->query(
    'SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id=s.assignment_id WHERE a.instructor_id=' . $iid
)->fetchColumn();
$ungraded    = $pdo->query(
    'SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id=s.assignment_id WHERE a.instructor_id=' . $iid . ' AND s.score IS NULL'
)->fetchColumn();

// Published courses available to instructors (enrolled)
$courses = $pdo->query(
    'SELECT c.* FROM courses c
     JOIN enrollments e ON e.course_id=c.id
     WHERE e.user_id=' . $iid . ' AND c.is_published=1 ORDER BY c.title'
)->fetchAll();

$nav = [
    ['label'=>'Dashboard',       'href'=>BASE_URL.'instructor/dashboard.php',     ],
    ['label'=>'Assignments',     'href'=>BASE_URL.'instructor/assignments.php',    ],
    ['label'=>'Submissions',     'href'=>BASE_URL.'instructor/submissions.php',     ],
    ['label'=>'Gradebook',       'href'=>BASE_URL.'instructor/gradebook.php',       ],
    ['label'=>'Course Progress', 'href'=>BASE_URL.'instructor/course_progress.php', ],
    ['label'=>'My Course',     'href'=>BASE_URL.'instructor/course.php',            ],
    
];

layout_head('Instructor Dashboard');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Instructor Dashboard');
render_flash();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">My Courses</div>
    <div class="stat-value"><?= $myCourses ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Assignments Created</div>
    <div class="stat-value"><?= $myAssign ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Total Submissions</div>
    <div class="stat-value"><?= $submissions ?></div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Awaiting Grade</div>
    <div class="stat-value"><?= $ungraded ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>My Enrolled Courses</h3>
    <a href="<?= BASE_URL ?>instructor/assignments.php" class="btn btn-amber btn-sm">+ New Assignment</a>
  </div>
  <?php if (!$courses): ?>
    <p style="color:#94a3b8;padding:20px">You are not enrolled in any published courses yet.</p>
  <?php else: ?>
  <div class="course-grid" style="padding-top:4px">
    <?php foreach ($courses as $c): ?>
    <div class="course-card">
      <div class="course-card-banner"></div>
      <div class="course-card-body">
        <div class="course-card-title"><?= e($c['title']) ?></div>
        <div class="course-card-desc"><?= e(substr($c['description'],0,80)) ?>...</div>
        <div class="course-card-footer">
          <a href="<?= BASE_URL ?>instructor/assignments.php?course_id=<?= $c['id'] ?>"
             class="btn btn-outline btn-sm">Assignments</a>
          <a href="<?= BASE_URL ?>instructor/gradebook.php?course_id=<?= $c['id'] ?>"
             class="btn btn-outline btn-sm">Gradebook</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php layout_close(); ?>