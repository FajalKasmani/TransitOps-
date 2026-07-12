<?php
declare(strict_types=1);
?>
    </main>

    <!-- Footer Area -->
    <footer class="py-3 px-4 border-top border-secondary border-opacity-10 text-muted mt-auto" style="font-size: 0.85rem; background-color: rgba(15, 23, 42, 0.1);">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
            <span>&copy; <?php echo date('Y'); ?> TransitOps Platform. All rights reserved.</span>
            <span class="d-flex gap-3">
                <a href="#" class="text-decoration-none text-muted">Terms</a>
                <a href="#" class="text-decoration-none text-muted">Privacy</a>
                <a href="#" class="text-decoration-none text-muted">Support</a>
            </span>
        </div>
    </footer>
</div>

<!-- Global Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i id="toastIcon" class="bi"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sidebar responsive toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebarMenu');
        sidebar.classList.toggle('show');
    }

    // Theme setup and matching
    const headerThemeToggle = document.getElementById('headerThemeToggle');
    const headerThemeIcon = document.getElementById('headerThemeIcon');
    const rootHtml = document.documentElement;

    const currentTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(currentTheme);
    if (headerThemeToggle) {
        headerThemeToggle.checked = currentTheme === 'dark';
        headerThemeToggle.addEventListener('change', () => {
            const nextTheme = headerThemeToggle.checked ? 'dark' : 'light';
            applyTheme(nextTheme);
        });
    }

    function applyTheme(theme) {
        document.body.setAttribute('data-theme', theme);
        rootHtml.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (headerThemeIcon) {
            if (theme === 'dark') {
                headerThemeIcon.className = 'bi bi-moon-fill';
                document.querySelectorAll('.text-light-theme').forEach(el => {
                    el.classList.remove('text-dark');
                    el.classList.add('text-white');
                });
            } else {
                headerThemeIcon.className = 'bi bi-sun-fill';
                document.querySelectorAll('.text-light-theme').forEach(el => {
                    el.classList.remove('text-white');
                    el.classList.add('text-dark');
                });
            }
        }
    }

    // Toast trigger based on URL queries
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        let toastMsg = "";
        let toastType = "success"; 
        
        if (urlParams.has('created')) {
            toastMsg = "Record created successfully.";
        } else if (urlParams.has('updated')) {
            toastMsg = "Record updated successfully.";
        } else if (urlParams.has('deleted')) {
            toastMsg = "Record deleted successfully.";
        } else if (urlParams.has('error')) {
            toastMsg = "An error occurred or action failed.";
            toastType = "danger";
        }

        if (toastMsg) {
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            
            toastMessage.textContent = toastMsg;
            
            if (toastType === 'success') {
                toastEl.className = 'toast align-items-center text-white bg-success border-0 show';
                toastIcon.className = 'bi bi-check-circle-fill text-white fs-5';
            } else if (toastType === 'danger') {
                toastEl.className = 'toast align-items-center text-white bg-danger border-0 show';
                toastIcon.className = 'bi bi-exclamation-triangle-fill text-white fs-5';
            }
            
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>
</body>
</html>
