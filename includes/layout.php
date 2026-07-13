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
  <title>' . htmlspecialchars($title, ENT_QUOTES) . ' — LearnHub LMS</title>
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
    echo '<aside class="sidebar">
  <div class="sidebar-brand">
    <span>LearnHub</span>
    <small>' . htmlspecialchars($badge) . ' Portal</small>
  </div>
  <nav class="sidebar-nav">';
    foreach ($navItems as $item) {
        $icon = $item['icon'] ?? '•';
        echo '<a href="' . htmlspecialchars($item['href']) . '">' . $icon . ' ' . htmlspecialchars($item['label']) . '</a>';
    }
    echo '  </nav>
  <div class="sidebar-footer">
    <strong>' . htmlspecialchars($name) . '</strong>
    <a href="' . $BASE_URL_LAYOUT . 'logout.php" style="color:#f87171;font-size:.8rem;">⎋ Logout</a>
  </div>
</aside>';
}

function layout_main_open(string $pageTitle): void {
    echo '<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">' . htmlspecialchars($pageTitle) . '</span>
    <div class="topbar-actions">
      <button id="sidebar-toggle" class="btn btn-outline btn-sm" style="display:none">☰</button>
    </div>
  </div>
  <div class="page-content">';
}

function layout_close(): void {
    echo '</div></div></div><!-- app-shell -->
<script src="' . (defined('BASE_URL') ? BASE_URL : '/test-project/') . 'assets/js/main.js"></script>
</body></html>';
}