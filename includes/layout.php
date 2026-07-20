<?php
// includes/layout.php
// Usage: call layout_head($title) then layout_sidebar($navItems) at top of each page,
// and layout_foot() at the bottom.

function layout_head(string $title): void {
    global $BASE_URL_LAYOUT;
    $BASE_URL_LAYOUT = defined('BASE_URL') ? BASE_URL : '/test-project/';
    echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . htmlspecialchars($title, ENT_QUOTES) . ' — Lightlearn LMS</title>
  <link rel="stylesheet" href="' . $BASE_URL_LAYOUT . 'assets/css/style.css">
</head>
<body>
<div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:99;"
     onclick="document.querySelector(\'.sidebar\').classList.remove(\'open\');this.style.display=\'none\'"></div>
<div class="app-shell">';
}

/**
 * $navItems = [ ['label'=>'Dashboard','href'=>'...','icon'=>'🏠'], ... ]
 * $role, $name from session
 */
function layout_sidebar(array $navItems, string $role, string $name): void {
    global $BASE_URL_LAYOUT;
    $badge = ['admin'=>'Admin','instructor'=>'Instructor','student'=>'Student'][$role] ?? $role;

    // Build initials for avatar fallback
    $initials = '';
    foreach (explode(' ', $name) as $word) {
        $initials .= strtoupper(mb_substr($word, 0, 1));
        if (strlen($initials) >= 2) break;
    }

    // Fetch avatar from DB if available
    $avatar = '';
    if (!empty($_SESSION['user_id'])) {
        global $pdo;
        if (isset($pdo)) {
            $av = $pdo->prepare('SELECT avatar FROM users WHERE id=?');
            $av->execute([$_SESSION['user_id']]);
            $avatar = $av->fetchColumn() ?? '';
        }
    }

    echo '<aside class="sidebar">
  <div class="sidebar-brand">
    <span>LightLearn</span>
    <small>' . htmlspecialchars($badge) . ' Portal</small>
  </div>
  <nav class="sidebar-nav">';
    foreach ($navItems as $item) {
        $icon    = $item['icon'] ?? '•';
        $current = basename($_SERVER['PHP_SELF']);
        $href    = htmlspecialchars($item['href']);
        $active  = (strpos($_SERVER['REQUEST_URI'], $item['href']) !== false) ? ' active' : '';
        echo '<a href="' . $href . '" class="' . trim($active) . '">' . $icon . ' ' . htmlspecialchars($item['label']) . '</a>';
    }
    echo '  </nav>

  <!-- Sidebar profile footer -->
  <div class="sidebar-footer">
    <a href="' . $BASE_URL_LAYOUT . 'profile/index.php" class="sidebar-profile-link">
      <div class="sidebar-avatar">';
    if ($avatar && file_exists(defined('ROOT_PATH') ? ROOT_PATH . '/' . $avatar : __DIR__ . '/../' . $avatar)) {
        echo '<img src="' . $BASE_URL_LAYOUT . htmlspecialchars($avatar) . '" alt="avatar">';
    } else {
        echo '<span>' . htmlspecialchars($initials) . '</span>';
    }
    echo '    </div>
      <div class="sidebar-profile-info">
        <strong>' . htmlspecialchars($name) . '</strong>
        <small>View Profile</small>
      </div>
    </a>
    <a href="' . $BASE_URL_LAYOUT . 'logout.php" class="sidebar-logout" title="Logout">⎋</a>
  </div>
</aside>';
}

function layout_main_open(string $pageTitle): void {
    global $BASE_URL_LAYOUT;

    // Build initials + avatar for topbar
    $name     = $_SESSION['name'] ?? '';
    $role     = $_SESSION['role'] ?? '';
    $initials = '';
    foreach (explode(' ', $name) as $word) {
        $initials .= strtoupper(mb_substr($word, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    $avatar = '';
    if (!empty($_SESSION['user_id'])) {
        global $pdo;
        if (isset($pdo)) {
            $av = $pdo->prepare('SELECT avatar FROM users WHERE id=?');
            $av->execute([$_SESSION['user_id']]);
            $avatar = $av->fetchColumn() ?? '';
        }
    }

    $roleLabel = ucfirst($role);
    $profileUrl = $BASE_URL_LAYOUT . 'profile/index.php';
    $logoutUrl  = $BASE_URL_LAYOUT . 'logout.php';

    echo '<div class="main-content">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button id="sidebar-toggle" class="btn btn-outline btn-sm topbar-hamburger">☰</button>
      <span class="topbar-title">' . htmlspecialchars($pageTitle) . '</span>
    </div>
    <div class="topbar-actions">

      <!-- Profile dropdown -->
      <div class="topbar-profile" id="topbar-profile">
        <button class="topbar-avatar-btn" id="profile-toggle" onclick="toggleProfileMenu()" title="Account">
          <div class="topbar-avatar">';
    if ($avatar && file_exists(defined('ROOT_PATH') ? ROOT_PATH . '/' . $avatar : __DIR__ . '/../' . $avatar)) {
        echo '<img src="' . $BASE_URL_LAYOUT . htmlspecialchars($avatar) . '" alt="avatar">';
    } else {
        echo '<span>' . htmlspecialchars($initials) . '</span>';
    }
    echo '        </div>
          <div class="topbar-avatar-info">
            <span class="topbar-name">' . htmlspecialchars($name) . '</span>
            <span class="topbar-role">' . htmlspecialchars($roleLabel) . '</span>
          </div>
          <span class="topbar-caret">▾</span>
        </button>

        <!-- Dropdown menu -->
        <div class="profile-dropdown" id="profile-dropdown">
          <div class="profile-dropdown-header">
            <div class="pd-avatar">';
    if ($avatar && file_exists(defined('ROOT_PATH') ? ROOT_PATH . '/' . $avatar : __DIR__ . '/../' . $avatar)) {
        echo '<img src="' . $BASE_URL_LAYOUT . htmlspecialchars($avatar) . '" alt="avatar">';
    } else {
        echo '<span>' . htmlspecialchars($initials) . '</span>';
    }
    echo '            </div>
            <div>
              <div class="pd-name">' . htmlspecialchars($name) . '</div>
              <div class="pd-role">' . htmlspecialchars($roleLabel) . '</div>
            </div>
          </div>
          <div class="profile-dropdown-body">
            <a href="' . $profileUrl . '" class="pd-link">👤 My Profile</a>
            <a href="' . $profileUrl . '#change-password" class="pd-link">🔒 Change Password</a>
            <div class="pd-divider"></div>
            <a href="' . $logoutUrl . '" class="pd-link pd-link-danger">⎋ Sign Out</a>
          </div>
        </div>
      </div>

    </div>
  </div>
  <div class="page-content">';
}

function layout_close(): void {
    echo '</div></div></div><!-- app-shell -->
<script src="' . (defined('BASE_URL') ? BASE_URL : '/test-project/') . 'assets/js/main.js"></script>
</body></html>';
}