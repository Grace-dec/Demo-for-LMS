<?php
// admin/dashboard.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('admin');

$totalUsers   = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalCourses = $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$published    = $pdo->query('SELECT COUNT(*) FROM courses WHERE is_published=1')->fetchColumn();
$enrollments  = $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();

$recentUsers  = $pdo->query('SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6')->fetchAll();

$nav = [
    ['label'=>'Dashboard',    'href'=>BASE_URL.'admin/dashboard.php',     ],
    ['label'=>'Manage Users', 'href'=>BASE_URL.'admin/manage_users.php',  ],
    ['label'=>'Manage Courses','href'=>BASE_URL.'admin/manage_courses.php',],
    ['label'=>'Enroll Users', 'href'=>BASE_URL.'admin/enroll_users.php',  ],
];

layout_head('Admin Dashboard');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Admin Dashboard');
render_flash();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Users</div>
    <div class="stat-value"><?= $totalUsers ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Total Courses</div>
    <div class="stat-value"><?= $totalCourses ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Published Courses</div>
    <div class="stat-value"><?= $published ?></div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Enrollments</div>
    <div class="stat-value"><?= $enrollments ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>Recent Registrations</h3>
    <a href="<?= BASE_URL ?>admin/manage_users.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge badge-<?= e($u['role']) ?>"><?= ucfirst(e($u['role'])) ?></span></td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php layout_close(); ?>