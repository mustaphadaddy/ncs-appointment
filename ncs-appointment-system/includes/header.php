<?php
// =============================================
// HEADER - HTML Head (No Navigation)
// NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Welcome';

// Determine base path for assets
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/officer/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/applicant/') !== false) {
    $base_path = '../';
} else {
    $base_path = '';
}

// Get theme from session or cookie - default to light
$theme = 'light';
if (isset($_SESSION['theme'])) {
    $theme = $_SESSION['theme'];
} elseif (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'];
    $_SESSION['theme'] = $theme;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NCS Online Appointment System - Book appointments with Nigeria Customs Service">
    <meta name="author" content="Nigeria Customs Service">
    <title><?php echo SITE_NAME; ?> | <?php echo $page_title; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>assets/img/favicon.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    
    <!-- Vendor CSS Files -->
    <link href="<?php echo $base_path; ?>assets/vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    
    <!-- Plugin CSS Files -->
    <link href="<?php echo $base_path; ?>assets/plugin/iziToast/css/iziToast.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/plugin/sweetalert2/sweetalert2.all.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/plugin/datatables/css/dataTables.bootstrap5.css" rel="stylesheet">
    
    <!-- Main CSS File -->
    <link href="<?php echo $base_path; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php
// Include top navigation
include_once __DIR__ . '/topnav.php';
?>