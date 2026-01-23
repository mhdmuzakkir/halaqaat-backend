<?php
/**
 * includes/sidebar_admin.php
 *
 * Requires (already defined in parent page):
 * - $tr   (translations array for current lang)
 * - $lang (current language)
 * - $isRtl (bool)
 *
 * Optional (recommended):
 * - $active_nav (string key to highlight active menu item)
 *     e.g. $active_nav = 'dashboard';
 */

if (!isset($active_nav)) $active_nav = 'dashboard';

function nav_active($key, $active_nav) {
  return ($key === $active_nav) ? 'active' : '';
}
?>

<style>
/* =========================
   Sidebar + Layout (EXTRACTED)
   ========================= */

:root{
  --primary:#3e846a;
  --secondary:#b18f6e;
  --accent:#444444;
  --bg:#f6f2ee;
  --card:#ffffff;
  --border:#e7ddd4;

  --boy:#2f6fd6;
  --girl:#d24e8a;

  --sidebar:#3b3b3b;
  --sidebar2:#2f2f2f;
}

.layout{
  min-height:100vh;
  display:grid;
  grid-template-columns: 280px 1fr;
}

/* Sidebar */
.sidebar{
  background:linear-gradient(180deg, var(--sidebar), var(--sidebar2));
  color:#fff;
  padding:16px;
  position:sticky;
  top:0;
  height:100vh;
  overflow:auto;
}

.brand{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:8px 8px 16px;
  border-bottom:1px solid rgba(255,255,255,.12);
  margin-bottom:12px;
}
.brand .name{
  font-weight:900;
  font-size:16px;
  letter-spacing:.4px;
  font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
}
.brand .sub{
  font-weight:700;
  font-size:12px;
  opacity:.85;
  font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
}

.sideToggle{
  border:1px solid rgba(255,255,255,.25);
  background:rgba(255,255,255,.08);
  color:#fff;
  padding:8px 10px;
  border-radius:12px;
  font-weight:900;
  cursor:pointer;
  font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
}
.sideToggle:hover{ background:rgba(255,255,255,.12); }

/* PC collapsible sidebar behavior */
.layout.collapsed{
  grid-template-columns: 90px 1fr;
}
.layout.collapsed .brand .name,
.layout.collapsed .brand .sub,
.layout.collapsed .nav a span.txt,
.layout.collapsed .sidebarBottom{
  display:none;
}
.layout.collapsed .nav a{
  justify-content:center;
  padding:12px;
}
.nav a{ gap:10px; }

.nav{
  display:flex;
  flex-direction:column;
  gap:8px;
  margin-top:10px;
}
.nav a{
  text-decoration:none;
  color:#fff;
  padding:10px 12px;
  border-radius:12px;
  font-weight:800;
  font-size:13px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  background:transparent;
  border:1px solid rgba(255,255,255,.10);

  position:relative;
  padding-left:40px;
  font-family:'Montserrat', system-ui, -apple-system, Segoe UI, Arial, sans-serif !important;
}
html[dir="rtl"] .nav a{
  padding-left:12px;
  padding-right:40px;
}

.nav a.active{
  background:rgba(255,255,255,.10);
  border-color:rgba(255,255,255,.18);
}
.nav a:hover{
  background:rgba(255,255,255,.08);
}

.sidebarBottom{
  margin-top:14px;
  padding-top:14px;
  border-top:1px solid rgba(255,255,255,.12);
  display:flex;
  flex-direction:column;
  gap:10px;
}

/* Sidebar icons */
.nav a::before{
  content:'';
  position:absolute;
  width:16px;
  height:16px;
  background:rgba(255,255,255,.9);
  mask-size:contain;
  mask-repeat:no-repeat;
  mask-position:center;
  -webkit-mask-size:contain;
  -webkit-mask-repeat:no-repeat;
  -webkit-mask-position:center;
}
html[dir="ltr"] .nav a::before{ left:12px; }
html[dir="rtl"] .nav a::before{ right:12px; }

.nav a.dash::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>');
}
.nav a.halaqa::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>');
}
.nav a.students::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>');
}
.nav a.ustaaz::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>');
}
.nav a.exams::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>');
}
.nav a.reports::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm4 0h14v-2H7v2zm0-4h14v-2H7v2z"/></svg>');
}
.nav a.settings::before{
  -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.11-.2-.36-.28-.57-.22l-2.39.96c-.5-.38-1.04-.7-1.64-.94L14.5 2h-5l-.37 2.35c-.6.24-1.14.56-1.64.94l-2.39-.96c-.21-.06-.46.02-.57.22L2.61 7.87c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94L2.73 14.52c-.18.14-.23.41-.12.61l1.92 3.32c.11.2.36.28.57.22l2.39-.96c.5.38 1.04.7 1.64.94L9.5 22h5l.37-2.35c.6-.24 1.14-.56 1.64-.94l2.39.96c.21.06.46-.02.57-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.03-1.58z"/></svg>');
}

/* Mobile sidebar */
.menuBtn{
  display:none;
  background:#3e846a;
  border:1px solid rgba(255,255,255,.6);
  color:#ffffff;
  padding:10px 12px;
  border-radius:12px;
  font-weight:900;
  font-size:18px;
  cursor:pointer;
  line-height:1;
}
.menuBtn:focus,
.menuBtn:active{ outline:none; box-shadow:none; }

@media (max-width: 980px){
  .layout{grid-template-columns: 1fr;}

  .sidebar{
    position:fixed;
    z-index:50;
    top:0; bottom:0;
    width:280px;
    overflow:auto;
    transition:transform .2s ease;
  }

  html[dir="ltr"] .sidebar{
    left:0;
    transform:translateX(-110%);
  }
  html[dir="rtl"] .sidebar{
    right:0;
    transform:translateX(110%);
  }

  .sidebar.open{ transform:translateX(0) !important; }

  .overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.35);
    z-index:40;
  }
  .overlay.show{display:block;}
}
</style>

<!-- Mobile overlay -->
<div class="overlay" id="overlay" onclick="toggleSidebar(false)"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div>
      <div class="name"><?php echo htmlspecialchars($tr['app'], ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="sub"><?php echo htmlspecialchars($tr['dashboard'], ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <button class="sideToggle" type="button" onclick="toggleSidebar()">☰</button>
  </div>

  <nav class="nav">
    <a class="<?php echo nav_active('dashboard', $active_nav); ?> dash" href="dashboard_admin.php">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_dashboard'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <a class="<?php echo nav_active('halaqaat', $active_nav); ?> halaqa" href="halaqaat_admin.php">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_halqaat'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <a class="<?php echo nav_active('students', $active_nav); ?> students" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_students'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <a class="<?php echo nav_active('ustaaz', $active_nav); ?> ustaaz" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_ustaaz'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <a class="<?php echo nav_active('exams', $active_nav); ?> exams" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_exams'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <a class="<?php echo nav_active('reports', $active_nav); ?> reports" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_reports'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>

    <a class="<?php echo nav_active('settings', $active_nav); ?> settings" href="#">
      <span class="txt"><?php echo htmlspecialchars($tr['nav_settings'], ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
  </nav>

  <div class="sidebarBottom">
    <a class="pill logout" style="text-align:center;" href="logout.php">
      <?php echo htmlspecialchars($tr['logout'], ENT_QUOTES, 'UTF-8'); ?>
    </a>
  </div>
</aside>

<script>
function isMobile() {
  return window.innerWidth <= 980;
}

function toggleSidebar(forceOpen) {
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('overlay');
  var layout = document.querySelector('.layout');
  if (!sb || !ov || !layout) return;

  if (isMobile()) {
    var open = (typeof forceOpen === 'boolean') ? forceOpen : !sb.classList.contains('open');
    if (open) {
      sb.classList.add('open');
      ov.classList.add('show');
    } else {
      sb.classList.remove('open');
      ov.classList.remove('show');
    }
  } else {
    layout.classList.toggle('collapsed');
    sb.classList.remove('open');
    ov.classList.remove('show');
  }
}

document.getElementById('overlay') && document.getElementById('overlay').addEventListener('click', function () {
  toggleSidebar(false);
});

window.addEventListener('resize', function () {
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('overlay');
  var layout = document.querySelector('.layout');
  if (!sb || !ov || !layout) return;

  if (!isMobile()) {
    sb.classList.remove('open');
    ov.classList.remove('show');
  } else {
    layout.classList.remove('collapsed');
  }
});
</script>
