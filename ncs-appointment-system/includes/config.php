<?php
// =============================================
// CONFIGURATION FILE
// NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Database Configuration
$host = 'localhost';
$password = '';
$username = 'root';
$dbname = 'ncs_appointment_db';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// =============================================
// LOAD SYSTEM SETTINGS FROM DATABASE
// =============================================
$settings = [];
$settings_result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($settings_result && $settings_result->num_rows > 0) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// =============================================
// DEFINE SYSTEM CONSTANTS
// =============================================
define('SITE_NAME', $settings['site_name'] ?? 'NCS Appointment System');
define('SITE_TAGLINE', 'Nigeria Customs Service - Streamlined Appointment Management');
define('SITE_VERSION', '1.0.0');
define('MAINTENANCE_MODE', ($settings['maintenance_mode'] ?? '0') === '1');
define('APPOINTMENT_ADVANCE_DAYS', intval($settings['appointment_advance_days'] ?? 30));
define('ENABLE_DOCUMENT_UPLOAD', ($settings['enable_document_upload'] ?? '1') === '1');

// Date/Time Settings
define('TIMEZONE', $settings['timezone'] ?? 'Africa/Lagos');
date_default_timezone_set(TIMEZONE);

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// BASE URL DETECTION
// =============================================
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

$base_path = $protocol . $host;
$folder_path = '';
$script_parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));

if (count($script_parts) > 1) {
    array_pop($script_parts);
    if (end($script_parts) === 'admin' || end($script_parts) === 'officer' || end($script_parts) === 'applicant') {
        array_pop($script_parts);
    }
    $folder_path = '/' . implode('/', $script_parts);
}
$base_url = $base_path . $folder_path;

define('BASE_URL', $base_url);
define('ASSETS_URL', $base_url . '/assets');

// =============================================
// USER DATA
// =============================================
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$fullName = $_SESSION['full_name'] ?? 'Guest';
$userInitial = strtoupper(substr($fullName, 0, 1));
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));

// Page titles mapping
$page_titles = [
    'index.php' => 'Home',
    'login.php' => 'Login',
    'register.php' => 'Register',
    'dashboard.php' => 'Dashboard',
    'apply.php' => 'Apply for Appointment',
    'my-appointments.php' => 'My Appointments',
    'upload-document.php' => 'Upload Document',
    'print-slip.php' => 'Appointment Slip',
    'track-appointment.php' => 'Track Appointment',
    'profile.php' => 'My Profile',
    'manage-users.php' => 'User Management',
    'manage-offices.php' => 'Office Management',
    'manage-appointments.php' => 'Manage Appointments',
    'audit-logs.php' => 'Audit Logs',
    'view-appointments.php' => 'View Appointments',
    'view-applicant.php' => 'Applicant Details',
];

$page_title_display = $page_titles[$current_page] ?? ucfirst(str_replace('-', ' ', $current_page));

// =============================================
// HELPER FUNCTIONS
// =============================================
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isOfficer() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'officer';
}

function isApplicant() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'applicant';
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($input) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string($input)));
}

function isActive($pageName, $current_page) {
    return $current_page == $pageName ? 'active' : '';
}

// Check if we're on a dashboard page
$isDashboardPage = isLoggedIn() && (
    basename(dirname($_SERVER['SCRIPT_NAME'])) == 'admin' || 
    basename(dirname($_SERVER['SCRIPT_NAME'])) == 'officer' ||
    basename(dirname($_SERVER['SCRIPT_NAME'])) == 'applicant' ||
    basename($_SERVER['PHP_SELF']) == 'dashboard.php'
);

// Get theme from cookie
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';

// Set page title
$page_title = isset($page_title) ? $page_title : SITE_NAME;

// Get appointment status counts (for notifications)
function getAppointmentCounts($user_id) {
    global $conn;
    $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $query = "SELECT status, COUNT(*) as count FROM appointments WHERE user_id = ? GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = $row['count'];
        }
    }
    return $counts;
}
?>