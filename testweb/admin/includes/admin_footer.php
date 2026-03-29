<?php
// admin/includes/admin_footer.php
?>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="material-icons">logout</span>
            <h3>Logout</h3>
            <p>Are you sure you want to sign out from your account?</p>
            <div class="modal-buttons">
                <button onclick="closeLogoutModal()" class="btn-cancel">Cancel</button>
                <a href="../../../testweb/admin/logout.php" class="btn-logout">Yes, Logout</a>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 1024) {
                if (sidebar && !sidebar.contains(event.target) && menuToggle && !menuToggle.contains(event.target) && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Logout modal functions
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (modal && event.target == modal) {
                closeLogoutModal();
            }
        }
        
        // Update page title based on current page
        function updatePageTitle() {
            const titleElement = document.getElementById('page-title');
            if (!titleElement) return;
            
            const currentPage = window.location.pathname.split('/').pop();
            const pageTitles = {
                'dashboard.php': { icon: 'dashboard', title: 'Dashboard' },
                'requests.php': { icon: 'assignment', title: 'Resident Requests' },
                'reports.php': { icon: 'flag', title: 'Resident Reports' },
                'information.php': { icon: 'people', title: 'Resident Information' },
                'pages.php': { icon: 'description', title: 'Page Management' },
                'services.php': { icon: 'miscellaneous_services', title: 'Services Management' },
                'team.php': { icon: 'groups', title: 'Team Management' },
                'scan.php': { icon: 'qr_code_scanner', title: 'Scan Resident QR' },
                'scan_request.php': { icon: 'assignment_turned_in', title: 'Scan Request QR' },
                'logs.php': { icon: 'history', title: 'History Logs' },
                'admins.php': { icon: 'admin_panel_settings', title: 'Admin Settings' }
            };
            
            const pageInfo = pageTitles[currentPage] || pageTitles['dashboard.php'];
            titleElement.innerHTML = `<span class="material-icons">${pageInfo.icon}</span><span>${pageInfo.title}</span>`;
        }
        
        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) alert.remove();
                }, 500);
            });
        }, 5000);
        
        // Initialize
        updatePageTitle();
    </script>
    <script src="../js/main.js"></script>
</body>
</html>