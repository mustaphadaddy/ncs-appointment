/* ============================================= */
/* NCS APPOINTMENT SYSTEM - MAIN JS             */
/* Nigeria Customs Service                      */
/* ============================================= */

// ==============================================
// DOM Ready
// ==============================================
document.addEventListener('DOMContentLoaded', function() {
    initThemeToggle();
    initMobileMenu();
    initDropdowns();
    initScrollTop();
    initHeaderScroll();
    initAOS();
});

// ==============================================
// Theme Toggle - FIXED
// ==============================================
function initThemeToggle() {
    const html = document.documentElement;
    
    // Get saved theme from localStorage or default to 'light'
    let savedTheme = localStorage.getItem('theme');
    
    // If no saved theme, default to light
    if (!savedTheme) {
        savedTheme = 'light';
        localStorage.setItem('theme', 'light');
    }
    
    // Apply theme
    html.setAttribute('data-theme', savedTheme);
    updateThemeIcons(savedTheme);

    // Theme toggle buttons
    document.querySelectorAll('#themeToggleBtn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Apply new theme
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icons
            updateThemeIcons(newTheme);
            
            // Update all toggle buttons
            document.querySelectorAll('#themeToggleBtn').forEach(function(b) {
                const icon = b.querySelector('#themeIcon');
                if (icon) {
                    icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
                }
            });
        });
    });
}

// Update theme icons
function updateThemeIcons(theme) {
    document.querySelectorAll('#themeIcon').forEach(function(icon) {
        if (theme === 'dark') {
            icon.className = 'bi bi-sun-fill';
        } else {
            icon.className = 'bi bi-moon-fill';
        }
    });
}

// ==============================================
// Mobile Menu
// ==============================================
function initMobileMenu() {
    const toggle = document.getElementById('mobileMenuToggle');
    const nav = document.getElementById('mobileNav');
    const overlay = document.getElementById('mobileOverlay');
    const close = document.getElementById('mobileNavClose');

    if (!toggle || !nav || !overlay || !close) return;

    function open() {
        nav.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        toggle.classList.add('active');
    }

    function closeMenu() {
        nav.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        toggle.classList.remove('active');
    }

    toggle.addEventListener('click', open);
    close.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    // Close on link click
    nav.querySelectorAll('a:not(.mobile-dropdown-toggle)').forEach(function(link) {
        link.addEventListener('click', closeMenu);
    });

    // Close on resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeMenu();
        }
    });
}

// ==============================================
// Dropdown Menus
// ==============================================
function initDropdowns() {
    const dropdownBtn = document.getElementById('userDropdownBtn');
    const dropdownMenu = document.getElementById('userDropdownMenu');
    const dropdownNav = document.getElementById('userDropdownNav');

    if (!dropdownBtn || !dropdownMenu || !dropdownNav) return;

    dropdownBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
        dropdownNav.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
        if (!dropdownNav.contains(e.target)) {
            dropdownMenu.classList.remove('show');
            dropdownNav.classList.remove('active');
        }
    });
}

// ==============================================
// Scroll to Top
// ==============================================
function initScrollTop() {
    const btn = document.getElementById('scrollTop');
    if (!btn) return;

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    });

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ==============================================
// Header Scroll Effect
// ==============================================
function initHeaderScroll() {
    const header = document.querySelector('.ncs-header');
    if (!header) return;

    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

// ==============================================
// AOS Initialization
// ==============================================
function initAOS() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    }
}

// ==============================================
// Logout Handler
// ==============================================
function handleLogout(e) {
    e.preventDefault();
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = BASE_URL + '/logout.php';
            }
        });
    } else {
        window.location.href = BASE_URL + '/logout.php';
    }
}

// Attach logout handlers
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');
    if (mobileLogoutBtn) {
        mobileLogoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const nav = document.getElementById('mobileNav');
            if (nav) nav.classList.remove('active');
            const overlay = document.getElementById('mobileOverlay');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(function() {
                handleLogout(e);
            }, 300);
        });
    }
});