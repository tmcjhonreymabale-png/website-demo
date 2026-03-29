// main.js - Global JavaScript for Barangay System

document.addEventListener('DOMContentLoaded', function() {
    // ==================== MOBILE MENU (Matches header.php structure) ====================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');
    const navOverlay = document.getElementById('navOverlay');
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const navbar = document.getElementById('navbar');
    
    // Toggle Mobile Menu
    function toggleMobileMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        if (mobileNav && navOverlay) {
            mobileNav.classList.toggle('active');
            navOverlay.classList.toggle('active');
            
            if (mobileNav.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
                // Change icon to close
                if (mobileMenuBtn) {
                    const icon = mobileMenuBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    }
                }
            } else {
                document.body.style.overflow = '';
                // Change icon back to menu
                if (mobileMenuBtn) {
                    const icon = mobileMenuBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }
        }
    }
    
    // Close Mobile Menu
    function closeMobileMenu() {
        if (mobileNav && navOverlay) {
            mobileNav.classList.remove('active');
            navOverlay.classList.remove('active');
            document.body.style.overflow = '';
            if (mobileMenuBtn) {
                const icon = mobileMenuBtn.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        }
    }
    
    // Toggle Profile Dropdown
    function toggleDropdown(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (profileDropdown) {
            profileDropdown.classList.toggle('active');
        }
    }
    
    // Close Dropdown
    function closeDropdown() {
        if (profileDropdown && profileDropdown.classList.contains('active')) {
            profileDropdown.classList.remove('active');
        }
    }
    
    // Close all menus
    function closeAllMenus() {
        closeMobileMenu();
        closeDropdown();
    }
    
    // Event Listeners for Mobile Menu
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        // Touch device support
        mobileMenuBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            toggleMobileMenu(e);
        }, { passive: false });
    }
    
    // Event Listeners for Overlay
    if (navOverlay) {
        navOverlay.addEventListener('click', closeAllMenus);
    }
    
    // Event Listeners for Profile Dropdown
    if (profileBtn) {
        profileBtn.addEventListener('click', toggleDropdown);
        // Touch device support
        profileBtn.addEventListener('touchstart', function(e) {
            e.preventDefault();
            toggleDropdown(e);
        }, { passive: false });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (profileDropdown && !profileDropdown.contains(event.target)) {
            closeDropdown();
        }
        // Also close mobile menu if clicking outside and it's open
        if (mobileNav && mobileNav.classList.contains('active') && 
            !mobileNav.contains(event.target) && 
            event.target !== mobileMenuBtn && 
            !mobileMenuBtn.contains(event.target)) {
            closeMobileMenu();
        }
    });
    
    // Close mobile menu when clicking on any mobile link
    if (mobileNav) {
        const mobileLinks = mobileNav.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
            // Touch device support
            link.addEventListener('touchstart', function() {
                setTimeout(closeMobileMenu, 100);
            });
        });
    }
    
    // Close on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllMenus();
        }
    });
    
    // Handle window resize - close menus on desktop view
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 968) {
                if (mobileNav && mobileNav.classList.contains('active')) {
                    closeAllMenus();
                }
            }
        }, 250);
    });
    
    // Scroll effect for navbar
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // ==================== SET ACTIVE NAVIGATION LINKS ====================
    function setActiveLinks() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || 'home';
        const currentPath = window.location.pathname;
        
        // Desktop links
        const desktopLinks = document.querySelectorAll('.nav-links a');
        desktopLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href) {
                if (href.includes(`page=${currentPage}`)) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            }
        });
        
        // Mobile links
        const mobileLinksAll = document.querySelectorAll('.mobile-nav-links a');
        mobileLinksAll.forEach(link => {
            const href = link.getAttribute('href');
            if (href) {
                if (href.includes(`page=${currentPage}`)) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            }
        });
        
        // Handle direct links (my_requests.php, profile.php, etc.)
        if (currentPath.includes('my_requests.php')) {
            const myReqLinks = document.querySelectorAll('a[href*="my_requests.php"]');
            myReqLinks.forEach(link => link.classList.add('active'));
        }
        if (currentPath.includes('profile.php')) {
            const profileLinks = document.querySelectorAll('a[href*="profile.php"]');
            profileLinks.forEach(link => link.classList.add('active'));
        }
        if (currentPath.includes('settings.php')) {
            const settingsLinks = document.querySelectorAll('a[href*="settings.php"]');
            settingsLinks.forEach(link => link.classList.add('active'));
        }
        if (currentPath.includes('change_password.php')) {
            const changePwLinks = document.querySelectorAll('a[href*="change_password.php"]');
            changePwLinks.forEach(link => link.classList.add('active'));
        }
    }
    
    setActiveLinks();
    
    // ==================== FORM VALIDATION ====================
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // ==================== AUTO-HIDE ALERTS ====================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.style) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert && alert.remove) {
                        alert.remove();
                    }
                }, 500);
            }
        }, 5000);
    });
    
    // ==================== PASSWORD STRENGTH METER ====================
    const passwordInput = document.getElementById('password');
    const strengthMeter = document.getElementById('strength-meter');
    const strengthText = document.getElementById('strength-text');
    
    if (passwordInput && strengthMeter) {
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Character type checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            return Math.min(strength, 5);
        }
        
        function updateStrengthMeter() {
            const password = passwordInput.value;
            const strength = checkPasswordStrength(password);
            const percentage = (strength / 5) * 100;
            
            strengthMeter.style.width = percentage + '%';
            
            let color = '#dc3545';
            let text = 'Very Weak';
            
            if (strength >= 4) {
                color = '#28a745';
                text = 'Strong';
            } else if (strength >= 3) {
                color = '#ffc107';
                text = 'Medium';
            } else if (strength >= 2) {
                color = '#fd7e14';
                text = 'Weak';
            } else if (strength >= 1) {
                color = '#dc3545';
                text = 'Very Weak';
            } else {
                text = 'No Password';
            }
            
            strengthMeter.style.backgroundColor = color;
            if (strengthText) {
                strengthText.textContent = text;
                strengthText.style.color = color;
            }
        }
        
        passwordInput.addEventListener('keyup', updateStrengthMeter);
        passwordInput.addEventListener('change', updateStrengthMeter);
    }
    
    // ==================== CONFIRM DELETE MODAL ====================
    window.confirmDelete = function(formId, message) {
        if (confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.')) {
            document.getElementById(formId).submit();
        }
        return false;
    };
});

// ==================== EXPORT FUNCTIONS ====================
function exportToPDF(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Table not found:', tableId);
        return;
    }
    
    const html = table.outerHTML;
    const win = window.open('', '', 'height=700,width=700');
    win.document.write('<html><head><title>' + filename + '</title>');
    win.document.write('<style>');
    win.document.write('table {border-collapse: collapse; width: 100%;}');
    win.document.write('th, td {border: 1px solid #ddd; padding: 8px; text-align: left;}');
    win.document.write('th {background-color: #f2f2f2;}');
    win.document.write('</style>');
    win.document.write('</head><body>');
    win.document.write('<h2>' + filename + '</h2>');
    win.document.write(html);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}

function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Table not found:', tableId);
        return;
    }
    
    const html = table.outerHTML;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Table not found:', tableId);
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const rowData = [];
        const cols = row.querySelectorAll('th, td');
        cols.forEach(col => {
            let text = col.innerText;
            // Escape quotes and wrap in quotes if contains comma
            text = text.replace(/"/g, '""');
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = '"' + text + '"';
            }
            rowData.push(text);
        });
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// ==================== ADMIN FUNCTIONS ====================
const AdminFunctions = {
    updateOnlineStatus: function() {
        fetch('/testweb/admin/get_online_count.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.online_count !== undefined) {
                const onlineCountElement = document.getElementById('online-count');
                if (onlineCountElement) {
                    onlineCountElement.textContent = data.online_count;
                }
            }
        })
        .catch(error => console.error('Error updating online count:', error));
    },
    
    filterReports: function() {
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');
        const status = document.getElementById('report-status');
        
        // Build query string
        const params = new URLSearchParams();
        if (dateFrom && dateFrom.value) params.append('from', dateFrom.value);
        if (dateTo && dateTo.value) params.append('to', dateTo.value);
        if (status && status.value) params.append('status', status.value);
        
        // Reload with filters
        window.location.href = window.location.pathname + '?' + params.toString();
    },
    
    confirmAction: function(message) {
        return confirm(message || 'Are you sure you want to proceed?');
    },
    
    printElement: function(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print</title>');
            printWindow.document.write('<link rel="stylesheet" href="/testweb/css/style.css">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(element.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    }
};

// Initialize online status checker for admin pages
if (document.body && document.body.classList.contains('admin-page')) {
    setInterval(AdminFunctions.updateOnlineStatus, 30000); // Update every 30 seconds
}