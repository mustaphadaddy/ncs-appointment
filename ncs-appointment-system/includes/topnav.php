<?php
// =============================================
// TOPNAV - Navigation (Custom for NCS)
// Nigeria Customs Service Appointment System
// =============================================

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config if not already included
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Determine base path for links
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/officer/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/applicant/') !== false) {
    $base_path = '../';
} else {
    $base_path = '';
}

// Get user data
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$user_fullname = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userInitial = strtoupper(substr($user_fullname, 0, 1));

// Get theme from session or cookie - default to light
$theme = 'light';
if (isset($_SESSION['theme'])) {
    $theme = $_SESSION['theme'];
} elseif (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'];
    $_SESSION['theme'] = $theme;
}

// Check if on dashboard page
$isDashboardPage = $isLoggedIn;

// Function to check if a nav item is active
function isNavActive($page, $current_page, $current_dir) {
    if ($page == 'dashboard') {
        return ($current_page == 'dashboard.php' || $current_page == 'index.php' || 
                $current_dir == 'admin' || $current_dir == 'officer' || $current_dir == 'applicant') ? 'active' : '';
    }
    return ($current_page == $page) ? 'active' : '';
}

// Get pending appointment count for notifications
$pendingCount = 0;
if ($isLoggedIn && $user_role == 'applicant') {
    global $conn;
    $query = "SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pendingCount = $row['count'];
    }
}
?>
<!-- ============================================ -->
<!-- TOP NAVIGATION - NCS STYLE -->
<!-- ============================================ -->
<header id="header" class="header ncs-header">
    <div class="container">
        <div class="header-inner">
            <!-- Logo with NCS Branding -->
            <div class="logo ncs-logo">
                <a href="<?php echo $base_path; ?>index.php" class="logo-link">
                    <div class="logo-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">NCS</span>
                        <span class="logo-sub">Appointment</span>
                    </div>
                </a>
                <div class="logo-badge">Official</div>
            </div>

            <!-- Desktop Navigation -->
            <nav class="nav-menu ncs-nav">
                <ul class="nav-list">
                    
                    <!-- ============================================ -->
                    <!-- ADMIN DASHBOARD NAVIGATION -->
                    <!-- ============================================ -->
                    <?php if($isLoggedIn && $user_role == 'admin'): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/dashboard.php" class="nav-link <?php echo isNavActive('dashboard', $current_page, $current_dir); ?>">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/manage-users.php" class="nav-link">
                                <i class="bi bi-people-fill"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/manage-offices.php" class="nav-link">
                                <i class="bi bi-building"></i>
                                <span>Offices</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/manage-appointments.php" class="nav-link">
                                <i class="bi bi-calendar-check"></i>
                                <span>Appointments</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>admin/audit-logs.php" class="nav-link">
                                <i class="bi bi-clock-history"></i>
                                <span>Audit Logs</span>
                            </a>
                        </li>

                        <!-- Theme Toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle-btn me-2" id="themeToggleBtn" aria-label="Toggle theme">
                                <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill'; ?>" id="themeIcon"></i>
                            </button>
                        </li>

                        <!-- Admin Profile Dropdown -->
                        <li class="nav-item dropdown" id="userDropdownNav">
                            <a href="javascript:void(0);" class="nav-link user-profile" id="userDropdownBtn">
                                <div class="user-avatar">
                                    <?php echo $userInitial; ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($user_fullname); ?></span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <ul class="dropdown-menu" id="userDropdownMenu">
                                <li><a href="<?php echo $base_path; ?>admin/profile.php" class="dropdown-item"><i class="bi bi-person-circle"></i> Profile</a></li>
                                <li><a href="<?php echo $base_path; ?>admin/system-settings.php" class="dropdown-item"><i class="bi bi-gear"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" class="dropdown-item logout-item text-danger" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>

                    <!-- ============================================ -->
                    <!-- OFFICER DASHBOARD NAVIGATION -->
                    <!-- ============================================ -->
                    <?php elseif($isLoggedIn && $user_role == 'officer'): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>officer/dashboard.php" class="nav-link <?php echo isNavActive('dashboard', $current_page, $current_dir); ?>">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>officer/view-appointments.php" class="nav-link">
                                <i class="bi bi-calendar-event"></i>
                                <span>All Appointments</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>officer/pending.php" class="nav-link">
                                <i class="bi bi-hourglass-split"></i>
                                <span>Pending</span>
                                <?php if($pendingCount > 0): ?>
                                    <span class="badge-pending"><?php echo $pendingCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- Theme Toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle-btn me-2" id="themeToggleBtn" aria-label="Toggle theme">
                                <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill'; ?>" id="themeIcon"></i>
                            </button>
                        </li>

                        <!-- Officer Profile Dropdown -->
                        <li class="nav-item dropdown" id="userDropdownNav">
                            <a href="javascript:void(0);" class="nav-link user-profile" id="userDropdownBtn">
                                <div class="user-avatar">
                                    <?php echo $userInitial; ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($user_fullname); ?></span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <ul class="dropdown-menu" id="userDropdownMenu">
                                <li><a href="<?php echo $base_path; ?>officer/profile.php" class="dropdown-item"><i class="bi bi-person-circle"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" class="dropdown-item logout-item text-danger" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>

                    <!-- ============================================ -->
                    <!-- APPLICANT DASHBOARD NAVIGATION -->
                    <!-- ============================================ -->
                    <?php elseif($isLoggedIn && $user_role == 'applicant'): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>applicant/dashboard.php" class="nav-link <?php echo isNavActive('dashboard', $current_page, $current_dir); ?>">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>applicant/apply.php" class="nav-link">
                                <i class="bi bi-plus-circle"></i>
                                <span>Apply</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>applicant/my-appointments.php" class="nav-link">
                                <i class="bi bi-calendar-check"></i>
                                <span>My Appointments</span>
                                <?php if($pendingCount > 0): ?>
                                    <span class="badge-pending"><?php echo $pendingCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>applicant/upload-document.php" class="nav-link">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Documents</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>applicant/print-slip.php" class="nav-link">
                                <i class="bi bi-printer"></i>
                                <span>Print Slip</span>
                            </a>
                        </li>

                        <!-- Theme Toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle-btn me-2" id="themeToggleBtn" aria-label="Toggle theme">
                                <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill'; ?>" id="themeIcon"></i>
                            </button>
                        </li>

                        <!-- Applicant Profile Dropdown -->
                        <li class="nav-item dropdown" id="userDropdownNav">
                            <a href="javascript:void(0);" class="nav-link user-profile" id="userDropdownBtn">
                                <div class="user-avatar">
                                    <?php echo $userInitial; ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($user_fullname); ?></span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <ul class="dropdown-menu" id="userDropdownMenu">
                                <li><a href="<?php echo $base_path; ?>applicant/profile.php" class="dropdown-item"><i class="bi bi-person-circle"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" class="dropdown-item logout-item text-danger" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>

                    <!-- ============================================ -->
                    <!-- LOGGED IN - LANDING PAGE VIEW -->
                    <!-- ============================================ -->
                    <?php elseif($isLoggedIn): ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                                <i class="bi bi-house-door"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>index.php#services" class="nav-link">
                                <i class="bi bi-grid-3x3-gap"></i>
                                <span>Services</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>index.php#about" class="nav-link">
                                <i class="bi bi-info-circle"></i>
                                <span>About NCS</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>track-appointment.php" class="nav-link">
                                <i class="bi bi-search"></i>
                                <span>Track</span>
                            </a>
                        </li>

                        <!-- Theme Toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle-btn me-2" id="themeToggleBtn" aria-label="Toggle theme">
                                <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill'; ?>" id="themeIcon"></i>
                            </button>
                        </li>

                        <!-- Dashboard Shortcut -->
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>applicant/dashboard.php" class="btn-dashboard-nav">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <!-- User Dropdown -->
                        <li class="nav-item dropdown" id="userDropdownNav">
                            <a href="javascript:void(0);" class="nav-link user-profile" id="userDropdownBtn">
                                <div class="user-avatar">
                                    <?php echo $userInitial; ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($user_fullname); ?></span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <ul class="dropdown-menu" id="userDropdownMenu">
                                <li><a href="<?php echo $base_path; ?>applicant/profile.php" class="dropdown-item"><i class="bi bi-person-circle"></i> My Profile</a></li>
                                <li><a href="<?php echo $base_path; ?>applicant/my-appointments.php" class="dropdown-item"><i class="bi bi-calendar"></i> My Appointments</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" class="dropdown-item logout-item text-danger" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>

                    <!-- ============================================ -->
                    <!-- GUEST / PUBLIC NAVIGATION -->
                    <!-- ============================================ -->
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                                <i class="bi bi-house-door"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>index.php#services" class="nav-link">
                                <i class="bi bi-grid-3x3-gap"></i>
                                <span>Services</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>index.php#about" class="nav-link">
                                <i class="bi bi-info-circle"></i>
                                <span>About NCS</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>track-appointment.php" class="nav-link">
                                <i class="bi bi-search"></i>
                                <span>Track</span>
                            </a>
                        </li>

                        <!-- Theme Toggle -->
                        <li class="nav-item">
                            <button class="theme-toggle-btn me-2" id="themeToggleBtn" aria-label="Toggle theme">
                                <i class="bi <?php echo $theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill'; ?>" id="themeIcon"></i>
                            </button>
                        </li>

                        <!-- CTA Buttons -->
                        <li class="nav-item me-2">
                            <a href="<?php echo $base_path; ?>register.php" class="btn-register-nav">
                                <i class="bi bi-person-plus"></i>
                                <span>Register</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>login.php" class="btn-login-nav">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Login</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle mobile menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MOBILE NAVIGATION - NCS STYLE -->
    <!-- ============================================ -->
    <div class="mobile-nav ncs-mobile-nav" id="mobileNav">
        <div class="mobile-nav-header">
            <div class="mobile-logo">
                <i class="bi bi-shield-check"></i>
                <span>NCS <span>Appointment</span></span>
            </div>
            <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close mobile menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <ul class="mobile-nav-list">
            <?php if($isLoggedIn && $user_role == 'admin'): ?>
                <!-- Admin Mobile -->
                <li><a href="<?php echo $base_path; ?>admin/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage-users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage-offices.php"><i class="bi bi-building"></i> Offices</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage-appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a></li>
                <li><a href="<?php echo $base_path; ?>admin/audit-logs.php"><i class="bi bi-clock-history"></i> Audit Logs</a></li>
                <li><hr class="mobile-divider"></li>
                <li><a href="<?php echo $base_path; ?>admin/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                <li><a href="#" class="logout-mobile text-danger" id="mobileLogoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>

            <?php elseif($isLoggedIn && $user_role == 'officer'): ?>
                <!-- Officer Mobile -->
                <li><a href="<?php echo $base_path; ?>officer/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>officer/view-appointments.php"><i class="bi bi-calendar-event"></i> All Appointments</a></li>
                <li><a href="<?php echo $base_path; ?>officer/pending.php"><i class="bi bi-hourglass-split"></i> Pending</a></li>
                <li><hr class="mobile-divider"></li>
                <li><a href="<?php echo $base_path; ?>officer/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                <li><a href="#" class="logout-mobile text-danger" id="mobileLogoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>

            <?php elseif($isLoggedIn && $user_role == 'applicant'): ?>
                <!-- Applicant Mobile -->
                <li><a href="<?php echo $base_path; ?>applicant/dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>applicant/apply.php"><i class="bi bi-plus-circle"></i> Apply</a></li>
                <li><a href="<?php echo $base_path; ?>applicant/my-appointments.php"><i class="bi bi-calendar-check"></i> My Appointments</a></li>
                <li><a href="<?php echo $base_path; ?>applicant/upload-document.php"><i class="bi bi-cloud-upload"></i> Documents</a></li>
                <li><a href="<?php echo $base_path; ?>applicant/print-slip.php"><i class="bi bi-printer"></i> Print Slip</a></li>
                <li><hr class="mobile-divider"></li>
                <li><a href="<?php echo $base_path; ?>applicant/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                <li><a href="#" class="logout-mobile text-danger" id="mobileLogoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>

            <?php elseif($isLoggedIn): ?>
                <!-- Logged In Guest -->
                <li><a href="<?php echo $base_path; ?>index.php"><i class="bi bi-house-door"></i> Home</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#services"><i class="bi bi-grid-3x3-gap"></i> Services</a></li>
                <li><a href="<?php echo $base_path; ?>track-appointment.php"><i class="bi bi-search"></i> Track</a></li>
                <li><hr class="mobile-divider"></li>
                <li><a href="<?php echo $base_path; ?>applicant/dashboard.php" class="mobile-apply-btn"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>applicant/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                <li><a href="#" class="logout-mobile text-danger" id="mobileLogoutBtn"><i class="bi bi-box-arrow-right"></i> Logout</a></li>

            <?php else: ?>
                <!-- Guest Mobile -->
                <li><a href="<?php echo $base_path; ?>index.php"><i class="bi bi-house-door"></i> Home</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#services"><i class="bi bi-grid-3x3-gap"></i> Services</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#about"><i class="bi bi-info-circle"></i> About NCS</a></li>
                <li><a href="<?php echo $base_path; ?>track-appointment.php"><i class="bi bi-search"></i> Track</a></li>
                <li><hr class="mobile-divider"></li>
                <li><a href="<?php echo $base_path; ?>register.php" class="mobile-apply-btn"><i class="bi bi-person-plus"></i> Register</a></li>
                <li><a href="<?php echo $base_path; ?>login.php" class="mobile-login-btn"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="mobile-overlay" id="mobileOverlay"></div>
</header>