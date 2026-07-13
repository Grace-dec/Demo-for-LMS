<?php
// admin/manage_users.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('admin');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($uid && $uid !== current_user_id()) {
        if ($action === 'toggle_active') {
            $cur = $pdo->prepare('SELECT is_active FROM users WHERE id=?');
            $cur->execute([$uid]);
            $current = (int)$cur->fetchColumn();
            $pdo->prepare('UPDATE users SET is_active=? WHERE id=?')->execute([!$current, $uid]);
            set_flash('success', 'User status updated.');
        } elseif ($action === 'change_role') {
            $role = $_POST['new_role'] ?? '';
            if (in_array($role, ['admin','instructor','student'], true)) {
                $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $uid]);
                set_flash('success', 'Role updated.');
            }
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
            set_flash('success', 'User deleted.');
        }
    }
    header('Location: manage_users.php');
    exit;
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

$nav = [
    ['label'=>'Dashboard',    'href'=>BASE_URL.'admin/dashboard.php',     ],
    ['label'=>'Manage Users', 'href'=>BASE_URL.'admin/manage_users.php',  ],
    ['label'=>'Manage Courses','href'=>BASE_URL.'admin/manage_courses.php',],
    ['label'=>'Enroll Users', 'href'=>BASE_URL.'admin/enroll_users.php',  ],
];

layout_head('Manage Users');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('User Management');
render_flash();
?>

<div class="card">
  <div class="card-header">
    <h3>All Users (<?= count($users) ?>)</h3>
    <a href="<?= BASE_URL ?>register.php" class="btn btn-amber btn-sm">+ Add User</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td>
            <?php if ($u['id'] !== current_user_id()): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="action"  value="change_role">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="new_role" onchange="this.form.submit()" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;font-size:.8rem;">
                <?php foreach (['student','instructor','admin'] as $r): ?>
                  <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <?php else: ?>
              <span class="badge badge-admin">Admin (You)</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge-<?= $u['is_active'] ? 'active' : 'inactive' ?>">
              <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['id'] !== current_user_id()): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="action"  value="toggle_active">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button class="btn btn-outline btn-sm">
                <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
              </button>
            </form>
            <form method="post" style="display:inline">
              <input type="hidden" name="action"  value="delete">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button class="btn btn-danger btn-sm"
                      data-confirm="Delete this user? This cannot be undone.">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php layout_close(); ?>