<?php
if (!defined('in_nia_app')) exit;
?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("sidebarToggle");
        const overlay = document.getElementById("sidebarOverlay");
        const sidebar = document.querySelector(".admin-sidebar");
        
        if (toggleBtn && sidebar && overlay) {
            function toggleSidebar() {
                sidebar.classList.toggle("show");
                overlay.classList.toggle("show");
            }
            toggleBtn.addEventListener("click", toggleSidebar);
            overlay.addEventListener("click", toggleSidebar);
        }
    });
</script>
</body>
</html>
