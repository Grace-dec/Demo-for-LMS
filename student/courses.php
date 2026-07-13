<?php
// student/courses.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('student');

$sid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cid    = (int)$_POST['course_id'];
    if ($action === 'enroll' && $cid) {
        try {
            $pdo->prepare('INSERT INTO enrollments (user_id, course_id) VALUES (?,?)')->execute([$sid, $cid]);
            set_flash('success', 'Enrolled successfully!');
        } catch (PDOException) {
            set_flash('error', 'Already enrolled.');
        }
    } elseif ($action === 'unenroll' && $cid) {
        $pdo->prepare('DELETE FROM enrollments WHERE user_id=? AND course_id=?')->execute([$sid, $cid]);
        set_flash('success', 'Unenrolled from course.');
    }
    header('Location: courses.php'); exit;
}

// All published courses, marked with enrollment status + progress
$courses = $pdo->query(
    "SELECT c.*, u.name AS creator,
            (SELECT 1 FROM enrollments WHERE user_id=$sid AND course_id=c.id) AS is_enrolled,
            (SELECT COUNT(*) FROM course_modules WHERE course_id=c.id) AS module_count,
            (SELECT COUNT(*) FROM module_progress WHERE user_id=$sid AND course_id=c.id) AS done_count
     FROM courses c JOIN users u ON u.id=c.created_by
     WHERE c.is_published=1 ORDER BY c.title"
)->fetchAll();

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'student/dashboard.php',         ],
    ['label'=>'My Courses',  'href'=>BASE_URL.'student/courses.php',            ],
    ['label'=>'Submit Work', 'href'=>BASE_URL.'student/submit_assignment.php',  ],
    ['label'=>'My Grades',   'href'=>BASE_URL.'student/my_grades.php',          ],
    
];

layout_head('My Courses');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Courses');
render_flash();
?>

<div style="margin-bottom:16px;font-size:.9rem;color:#475569">
  Browse published courses and enroll. Enrolled courses appear highlighted.
</div>

<?php if (!$courses): ?>
  <div class="alert alert-info">No published courses are available yet.</div>
<?php else: ?>
<div class="course-grid">
  <?php foreach ($courses as $c): ?>
  <div class="course-card" style="<?= $c['is_enrolled'] ? 'border:2px solid #f59e0b' : '' ?>">
    <div class="course-card-banner"></div>
    <div class="course-card-body">
      <div class="course-card-title"><?= e($c['title']) ?></div>
      <div class="course-card-desc"><?= e(substr($c['description'],0,90)) ?>...</div>
      <div style="font-size:.78rem;color:#94a3b8;margin-bottom:10px">
        <?= $c['module_count'] ?> module(s) · by <?= e($c['creator']) ?>
      </div>
      <?php if ($c['is_enrolled']): ?>
        <?php
          $pct = ($c['module_count'] > 0)
              ? (int)round($c['done_count'] / $c['module_count'] * 100)
              : 0;
        ?>
        <!-- Mini progress bar -->
        <div style="margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;font-size:.74rem;color:#94a3b8;margin-bottom:4px">
            <span>Progress</span>
            <span><?= $c['done_count'] ?>/<?= $c['module_count'] ?> modules &nbsp;·&nbsp; <?= $pct ?>%</span>
          </div>
          <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden">
            <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#1a2744,#f59e0b);border-radius:99px;transition:width .4s"></div>
          </div>
          <?php if ($pct === 100): ?>
            <div style="font-size:.72rem;color:#15803d;font-weight:600;margin-top:4px"> Complete!</div>
          <?php endif; ?>
        </div>

        <div class="course-card-footer">
          <a href="<?= BASE_URL ?>student/course_detail.php?id=<?= $c['id'] ?>"
             class="btn btn-primary btn-sm"> View Course</a>
          <form method="post" style="display:inline">
            <input type="hidden" name="action"    value="unenroll">
            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
            <button class="btn btn-outline btn-sm" data-confirm="Unenroll from this course?">Leave</button>
          </form>
        </div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action"    value="enroll">
          <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
          <button class="btn btn-primary btn-sm">Enroll Now</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php layout_close(); ?>