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

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
</script>
</body>
</html>
