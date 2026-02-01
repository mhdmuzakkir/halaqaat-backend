<?php if (isLoggedIn()): ?>
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo t('app_name'); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0"><?php echo t('assalam_alaikum'); ?></p>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Real-time search functionality
function setupSearch(inputId, containerSelector, itemSelector, searchFields) {
    const searchInput = document.getElementById(inputId);
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const items = document.querySelectorAll(itemSelector);
        
        items.forEach(item => {
            let found = false;
            searchFields.forEach(field => {
                const element = item.querySelector(field);
                if (element && element.textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                }
            });
            item.style.display = found ? '' : 'none';
        });
    });
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || '<?php echo t('are_you_sure'); ?>');
}

// Print functionality
function printPage() {
    window.print();
}

// Export to Excel
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let html = table.outerHTML;
    const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    
    const downloadLink = document.createElement('a');
    downloadLink.href = url;
    downloadLink.download = filename + '.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

</body>
</html>
