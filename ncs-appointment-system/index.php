<?php
// =============================================
// INDEX - Home Page
// NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include config first
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Welcome to NCS Appointment System';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================ -->
<!-- HERO SECTION -->
<!-- ============================================ -->
<section id="hero" class="hero-section">
    <div class="container">
        <div class="hero-wrapper">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                <div class="hero-badge">
                    <i class="bi bi-shield-check"></i>
                    Nigeria Customs Service
                </div>
                <h1 class="hero-title">
                    Streamlined <span>Appointment</span><br>
                    Management System
                </h1>
                <p class="hero-description">
                    Book appointments with Customs offices online, reduce waiting time, 
                    and track your application status in real-time. Efficient, transparent, 
                    and secure.
                </p>
                <div class="hero-buttons">
                    <?php if($isLoggedIn): ?>
                        <a href="<?php echo BASE_URL; ?>applicant/dashboard.php" class="btn-primary">
                            <i class="bi bi-speedometer2"></i>
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn-primary">
                            <i class="bi bi-person-plus"></i>
                            Get Started
                        </a>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn-secondary">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Login
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Appointments Daily</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Satisfaction Rate</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Customs Offices</span>
                    </div>
                </div>
            </div>
            <div class="hero-image" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                <div class="hero-illustration">
                    <div class="illustration-circle">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="floating-card card-1">
                        <i class="bi bi-shield-check"></i>
                        <span>Secure</span>
                    </div>
                    <div class="floating-card card-2">
                        <i class="bi bi-clock"></i>
                        <span>Fast</span>
                    </div>
                    <div class="floating-card card-3">
                        <i class="bi bi-graph-up"></i>
                        <span>Efficient</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,50 C360,80 720,20 1080,50 C1260,65 1380,50 1440,50 L1440,100 L0,100 Z" fill="currentColor"/>
        </svg>
    </div>
</section>

<!-- ============================================ -->
<!-- SERVICES / FEATURES SECTION -->
<!-- ============================================ -->
<section id="services" class="services-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-tag">Our Services</span>
            <h2 class="section-title">How We <span>Help You</span></h2>
            <p class="section-description">
                Our platform simplifies the appointment process with Customs offices across Nigeria.
            </p>
        </div>

        <div class="services-grid">
            <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                <div class="service-icon">
                    <i class="bi bi-calendar-plus"></i>
                </div>
                <h3 class="service-title">Book Appointment</h3>
                <p class="service-description">
                    Schedule appointments with any Customs office online. Choose your preferred date and time.
                </p>
                <a href="<?php echo $isLoggedIn ? BASE_URL . 'applicant/apply.php' : BASE_URL . 'login.php'; ?>" class="service-link">
                    Book Now <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                <div class="service-icon">
                    <i class="bi bi-cloud-upload"></i>
                </div>
                <h3 class="service-title">Upload Documents</h3>
                <p class="service-description">
                    Easily upload required identification documents for your appointment verification.
                </p>
                <a href="<?php echo $isLoggedIn ? BASE_URL . 'applicant/upload-document.php' : BASE_URL . 'login.php'; ?>" class="service-link">
                    Upload <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                <div class="service-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h3 class="service-title">Track Status</h3>
                <p class="service-description">
                    Monitor your appointment status in real-time. Get notified when approved or rejected.
                </p>
                <a href="<?php echo BASE_URL; ?>track-appointment.php" class="service-link">
                    Track <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="service-card" data-aos="fade-up" data-aos-delay="400">
                <div class="service-icon">
                    <i class="bi bi-printer"></i>
                </div>
                <h3 class="service-title">Print Slip</h3>
                <p class="service-description">
                    Generate and print your appointment confirmation slip for your visit.
                </p>
                <a href="<?php echo $isLoggedIn ? BASE_URL . 'applicant/print-slip.php' : BASE_URL . 'login.php'; ?>" class="service-link">
                    Print <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- ABOUT SECTION -->
<!-- ============================================ -->
<section id="about" class="about-section">
    <div class="container">
        <div class="about-wrapper">
            <div class="about-image" data-aos="fade-right">
                <div class="about-image-placeholder">
                    <div class="about-icon-large">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="about-image-text">
                        <h4>Nigeria Customs Service</h4>
                        <p>Service with Integrity</p>
                    </div>
                </div>
            </div>
            <div class="about-content" data-aos="fade-left">
                <span class="section-tag">About NCS</span>
                <h2 class="section-title">Why Choose <span>Our System</span></h2>
                <p class="about-text">
                    The Nigeria Customs Service Online Appointment System is designed to eliminate 
                    long queues and improve service delivery. We provide a seamless experience for 
                    all users.
                </p>
                <div class="about-features">
                    <div class="about-feature">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <h4>Reduced Waiting Time</h4>
                            <p>No more long queues. Schedule and arrive at your designated time.</p>
                        </div>
                    </div>
                    <div class="about-feature">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <h4>Transparent Process</h4>
                            <p>Track your application status from submission to approval.</p>
                        </div>
                    </div>
                    <div class="about-feature">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <h4>Secure & Reliable</h4>
                            <p>Your data is protected with industry-standard security measures.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- HOW IT WORKS SECTION -->
<!-- ============================================ -->
<section id="how-it-works" class="how-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-tag">How It Works</span>
            <h2 class="section-title">Simple <span>Steps</span></h2>
        </div>
        <div class="steps-grid">
            <div class="step-item" data-aos="fade-up" data-aos-delay="100">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="bi bi-person-plus"></i></div>
                <h4>Register</h4>
                <p>Create your account with basic information.</p>
            </div>
            <div class="step-arrow"><i class="bi bi-arrow-right"></i></div>
            <div class="step-item" data-aos="fade-up" data-aos-delay="200">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="bi bi-calendar-plus"></i></div>
                <h4>Book Appointment</h4>
                <p>Select office, date, and time that suits you.</p>
            </div>
            <div class="step-arrow"><i class="bi bi-arrow-right"></i></div>
            <div class="step-item" data-aos="fade-up" data-aos-delay="300">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="bi bi-cloud-upload"></i></div>
                <h4>Upload Documents</h4>
                <p>Submit required identification documents.</p>
            </div>
            <div class="step-arrow"><i class="bi bi-arrow-right"></i></div>
            <div class="step-item" data-aos="fade-up" data-aos-delay="400">
                <div class="step-number">04</div>
                <div class="step-icon"><i class="bi bi-check-circle"></i></div>
                <h4>Get Approved</h4>
                <p>Receive confirmation and print your slip.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- CTA SECTION -->
<!-- ============================================ -->
<section class="cta-section">
    <div class="container">
        <div class="cta-wrapper" data-aos="fade-up">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of users who have streamlined their Customs appointments.</p>
            </div>
            <div class="cta-buttons">
                <?php if($isLoggedIn): ?>
                    <a href="<?php echo BASE_URL; ?>applicant/dashboard.php" class="btn-primary">
                        <i class="bi bi-speedometer2"></i>
                        Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>register.php" class="btn-primary">
                        <i class="bi bi-person-plus"></i>
                        Create Account
                    </a>
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn-secondary">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>