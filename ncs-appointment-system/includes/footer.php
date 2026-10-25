<?php
// =============================================
// FOOTER - All Pages
// NCS Online Appointment System
// Nigeria Customs Service
// =============================================

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
    <!-- ======= FOOTER ======= -->
    <footer class="footer ncs-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="<?php echo $base_path; ?>index.php" class="logo">
                        <i class="bi bi-shield-check"></i>
                        <span>NCS <span>Appointment</span></span>
                    </a>
                    <p><?php echo SITE_TAGLINE ?? 'Nigeria Customs Service - Streamlined Appointment Management'; ?></p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo $base_path; ?>index.php"><i class="bi bi-chevron-right"></i> Home</a></li>
                        <li><a href="#services"><i class="bi bi-chevron-right"></i> Services</a></li>
                        <li><a href="#about"><i class="bi bi-chevron-right"></i> About NCS</a></li>
                        <li><a href="#contact"><i class="bi bi-chevron-right"></i> Contact</a></li>
                        <li><a href="<?php echo $base_path; ?>track-appointment.php"><i class="bi bi-chevron-right"></i> Track Appointment</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="#services"><i class="bi bi-chevron-right"></i> Book Appointment</a></li>
                        <li><a href="#services"><i class="bi bi-chevron-right"></i> Document Upload</a></li>
                        <li><a href="#services"><i class="bi bi-chevron-right"></i> Status Tracking</a></li>
                        <li><a href="#services"><i class="bi bi-chevron-right"></i> Print Slip</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4>Contact NCS</h4>
                    <ul>
                        <li><i class="bi bi-geo-alt"></i> Nigeria Customs Service Headquarters, Abuja</li>
                        <li><i class="bi bi-envelope"></i> info@customs.gov.ng</li>
                        <li><i class="bi bi-phone"></i> +234 800 287 8677</li>
                        <li><i class="bi bi-clock"></i> Mon-Fri: 8AM - 5PM</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <p>Nigeria Customs Service - <i class="bi bi-shield-check"></i> Integrity, Transparency, Efficiency</p>
            </div>
        </div>
    </footer>

    <!-- ======= SCROLL TO TOP ======= -->
    <button id="scrollTop" class="scroll-top" aria-label="Scroll to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- ======= VENDOR JS FILES ======= -->
    <script src="<?php echo $base_path; ?>assets/vendor/aos/aos.js"></script>
    <script src="<?php echo $base_path; ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/vendor/swiper/swiper-bundle.min.js"></script>

    <!-- Plugin JS Files -->
    <script src="<?php echo $base_path; ?>assets/plugin/jquery/jquery-3.7.0.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/plugin/iziToast/js/iziToast.js"></script>
    <script src="<?php echo $base_path; ?>assets/plugin/chart/chart.js"></script>
    <script src="<?php echo $base_path; ?>assets/plugin/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="<?php echo $base_path; ?>assets/plugin/datatables/js/jquery.dataTables.js"></script>
    <script src="<?php echo $base_path; ?>assets/plugin/datatables/js/dataTables.bootstrap5.js"></script>
    <!-- In your footer.php or the page -->
    <script>
        // Define base URL for JavaScript
        var BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <!-- Main JS -->
    <script src="<?php echo $base_path; ?>assets/js/main.js"></script>
    
</body>
</html>