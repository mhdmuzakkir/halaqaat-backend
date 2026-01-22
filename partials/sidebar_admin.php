<?php
// partials/sidebar_admin.php
// expects: $tr, $lang, $activePage (dashboard|halaqaat|students|ustaaz|exams|reports|settings)

if (!isset($activePage)) $activePage = '';

function sb_active($key, $activePage) {
  return ($key === $activePage) ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div>
      <div class="name"><?php echo htmlspecialchars($tr['app']); ?></div>
      <div class="sub"><?php echo htmlspecialchars($tr['dashboard']); ?></div>
    </div>
    <button class="sideToggle" type="button" onclick="toggleSidebar()">☰</button>
  </div>

  <nav class="nav">
    <a class="<?php echo sb_active('dashboard', $activePage); ?> dash" href="dashboard_admin.php">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_dashboard']); ?></span>
    </a>

    <a class="<?php echo sb_active('halaqaat', $activePage); ?> halaqa" href="halaqaat_admin.php">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_halqaat']); ?></span>
    </a>

    <a class="<?php echo sb_active('students', $activePage); ?> students" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_students']); ?></span>
    </a>

    <a class="<?php echo sb_active('ustaaz', $activePage); ?> ustaaz" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_ustaaz']); ?></span>
    </a>

    <a class="<?php echo sb_active('exams', $activePage); ?> exams" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_exams']); ?></span>
    </a>

    <a class="<?php echo sb_active('reports', $activePage); ?> reports" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_reports']); ?></span>
    </a>

    <a class="<?php echo sb_active('settings', $activePage); ?> settings" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_settings']); ?></span>
    </a>
  </nav>

  <div class="sidebarBottom">
    <a class="navLink pill logout" style="text-align:center;" href="logout.php">
      <?php echo htmlspecialchars($tr['logout']); ?>
    </a>
  </div>
</aside>
