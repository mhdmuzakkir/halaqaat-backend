<?php
// Footer template for Kahaf Halaqaat
$lang = get_language();
$isRTL = $lang === 'ur';
$isLoggedIn = !empty($_SESSION['user_id']);
?>

<?php if ($isLoggedIn): ?>
  </main>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sidebar toggle
const sideToggle = document.getElementById('sideToggle');
const layout = document.getElementById('layout');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

// Desktop sidebar collapse toggle
if (sideToggle) {
  sideToggle.addEventListener('click', () => {
    layout.classList.toggle('collapsed');
  });
}

// Mobile sidebar toggle
function toggleSidebar() {
  if (sidebar) {
    sidebar.classList.toggle('open');
    if (overlay) {
      overlay.classList.toggle('show');
    }
  }
}

// Close sidebar (for overlay click)
function closeSidebar() {
  if (sidebar) {
    sidebar.classList.remove('open');
  }
  if (overlay) {
    overlay.classList.remove('show');
  }
}

// Close sidebar on window resize to desktop
window.addEventListener('resize', () => {
  if (window.innerWidth > 980) {
    closeSidebar();
  }
});

// Real-time search
function setupSearch(inputId, containerSelector, itemSelector, searchFields) {
  const searchInput = document.getElementById(inputId);
  if (!searchInput) return;
  
  searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const items = document.querySelectorAll(itemSelector);
    
    items.forEach(item => {
      let found = false;
      searchFields.forEach(field => {
        const element = item.querySelector(field);
        if (element && element.textContent.toLowerCase().includes(searchTerm)) {
          found = true;
        }
      });
      item.style.display = found || searchTerm === '' ? '' : 'none';
    });
  });
}

// Confirm delete
function confirmDelete(message) {
  return confirm(message || '<?php echo $isRTL ? 'کیا آپ کو یقین ہے؟' : 'Are you sure?'; ?>');
}

// Print page
function printPage() {
  window.print();
}

// Export to CSV
function exportToCSV(filename) {
  const tables = document.querySelectorAll('table');
  if (tables.length === 0) return;
  
  let csv = '';
  tables.forEach(table => {
    const rows = table.querySelectorAll('tr');
    rows.forEach(row => {
      const cells = row.querySelectorAll('th, td');
      const rowData = [];
      cells.forEach(cell => {
        rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
      });
      csv += rowData.join(',') + '\n';
    });
    csv += '\n';
  });
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename + '.csv';
  link.click();
}
</script>
</body>
</html>
