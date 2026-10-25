<?php
// =============================================
// REGISTER PAGE - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/includes/config.php';

// Check if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } elseif (isOfficer()) {
        redirect(BASE_URL . '/officer/dashboard.php');
    } elseif (isApplicant()) {
        redirect(BASE_URL . '/applicant/dashboard.php');
    }
}

// Set page title
$page_title = 'Register';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================ -->
<!-- REGISTER SECTION -->
<!-- ============================================ -->
<section class="auth-section">
    <div class="auth-container" style="min-width: 600px;">
        <div class="auth-wrapper">
            <!-- ============================================ -->
            <!-- REGISTER CARD -->
            <!-- ============================================ -->
            <div class="auth-card auth-card-register" data-aos="fade-up" data-aos-duration="800">
                <div class="auth-header">
                    <div class="auth-logo">
                        <div class="logo-mark">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="logo-text">
                            NCS <span>Appointment</span>
                        </div>
                    </div>
                    <h2>Create Account</h2>
                    <p>Register to book appointments with Nigeria Customs Service</p>
                </div>

                <form id="registerForm" class="auth-form">
                    <!-- Row 1: First Name & Last Name -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">
                                    <i class="bi bi-person"></i>
                                    First Name
                                </label>
                                <div class="input-wrapper">
                                    <input type="text" id="first_name" name="first_name" class="form-control" 
                                           placeholder="Enter your first name" required>
                                    <div class="input-focus-border"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">
                                    <i class="bi bi-person"></i>
                                    Last Name
                                </label>
                                <div class="input-wrapper">
                                    <input type="text" id="last_name" name="last_name" class="form-control" 
                                           placeholder="Enter your last name" required>
                                    <div class="input-focus-border"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Email -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="email">
                                    <i class="bi bi-envelope"></i>
                                    Email Address
                                </label>
                                <div class="input-wrapper">
                                    <input type="email" id="email" name="email" class="form-control" 
                                           placeholder="Enter your email address" required>
                                    <div class="input-focus-border"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Phone -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="phone">
                                    <i class="bi bi-phone"></i>
                                    Phone Number
                                </label>
                                <div class="input-wrapper">
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           placeholder="Enter your phone number (e.g., 08012345678)" required>
                                    <div class="input-focus-border"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Password & Confirm Password -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">
                                    <i class="bi bi-lock"></i>
                                    Password
                                </label>
                                <div class="input-wrapper">
                                    <input type="password" id="password" name="password" class="form-control" 
                                           placeholder="Create a password (min 8 chars)" required>
                                    <button type="button" class="toggle-password" data-target="password" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                    <div class="input-focus-border"></div>
                                </div>
                                <div class="password-requirements">
                                    <small class="text-muted">Password must be at least 8 characters</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="bi bi-check-circle"></i>
                                    Confirm Password
                                </label>
                                <div class="input-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                           placeholder="Confirm your password" required>
                                    <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Toggle password visibility">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                    <div class="input-focus-border"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Terms & Conditions -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-group terms-group px-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a> 
                                        and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 6: Submit Button -->
                    <div class="row g-3">
                        <div class="col-12">
                            <button type="submit" id="registerBtn" class="btn-auth-primary">
                                <span class="btn-text">Create Account</span>
                                <span class="btn-loader" style="display:none;">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </span>
                                <i class="bi bi-arrow-right btn-arrow"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign In <i class="bi bi-arrow-right"></i></a></p>
                </div>

                <!-- Quick Access Links -->
                <div class="auth-features">
                    <span class="features-label">Quick Access</span>
                    <div class="features-icons">
                        <a href="index.php" title="Home">
                            <i class="bi bi-house-door"></i>
                        </a>
                        <a href="track-appointment.php" title="Track Appointment">
                            <i class="bi bi-search"></i>
                        </a>
                        <a href="login.php" title="Login">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Decorations -->
    <div class="auth-bg-decor">
        <div class="bg-circle bg-circle-1"></div>
        <div class="bg-circle bg-circle-2"></div>
        <div class="bg-circle bg-circle-3"></div>
    </div>
</section>

<!-- ============================================ -->
<!-- TERMS & CONDITIONS MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-file-text"></i></div>
                    <h5 class="modal-title">Terms & Conditions</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="terms-content">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By using the NCS Online Appointment System, you agree to be bound by these terms and conditions.</p>
                    
                    <h6>2. User Accounts</h6>
                    <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
                    
                    <h6>3. Appointment Booking</h6>
                    <p>All appointments are subject to availability. NCS reserves the right to cancel or reschedule appointments when necessary.</p>
                    
                    <h6>4. Document Submission</h6>
                    <p>You agree to provide accurate and complete documentation. False information may result in cancellation of appointments.</p>
                    
                    <h6>5. Privacy</h6>
                    <p>Your personal information will be handled in accordance with our Privacy Policy and applicable data protection laws.</p>
                    
                    <h6>6. Termination</h6>
                    <p>NCS reserves the right to terminate or suspend access to the system for violation of these terms.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" style="background: var(--primary-gold); color: #0a1628; border: none;">I Agree</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- PRIVACY POLICY MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-shield-lock"></i></div>
                    <h5 class="modal-title">Privacy Policy</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="privacy-content">
                    <h6>1. Information We Collect</h6>
                    <p>We collect personal information including name, email, phone number, and identification documents necessary for appointment processing.</p>
                    
                    <h6>2. How We Use Information</h6>
                    <p>Your information is used to process appointments, verify identity, and communicate with you regarding your appointments.</p>
                    
                    <h6>3. Data Protection</h6>
                    <p>We implement appropriate security measures to protect your personal information from unauthorized access.</p>
                    
                    <h6>4. Data Sharing</h6>
                    <p>Your information is shared only with authorized NCS personnel and is not sold or disclosed to third parties.</p>
                    
                    <h6>5. Data Retention</h6>
                    <p>We retain your information for as long as necessary to fulfill the purposes outlined in this policy.</p>
                    
                    <h6>6. Your Rights</h6>
                    <p>You have the right to access, modify, or delete your personal information stored in our system.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" style="background: var(--primary-gold); color: #0a1628; border: none;">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/includes/footer.php'; ?>

<!-- ============================================ -->
<!-- REGISTER SPECIFIC SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // PASSWORD TOGGLE
    // ============================================
    $('.toggle-password').click(function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        }
    });

    // ============================================
    // INPUT FOCUS EFFECT
    // ============================================
    $('.form-control').on('focus', function() {
        $(this).closest('.input-wrapper').find('.input-focus-border').addClass('active');
    }).on('blur', function() {
        $(this).closest('.input-wrapper').find('.input-focus-border').removeClass('active');
    });

    // ============================================
    // PASSWORD STRENGTH INDICATOR
    // ============================================
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const requirements = $('.password-requirements small');
        
        if (password.length === 0) {
            requirements.text('Password must be at least 8 characters').removeClass('text-success text-danger').addClass('text-muted');
        } else if (password.length < 8) {
            requirements.text('Password must be at least 8 characters').removeClass('text-muted text-success').addClass('text-danger');
        } else {
            requirements.text('✓ Password meets requirements').removeClass('text-muted text-danger').addClass('text-success');
        }
    });

    // ============================================
    // REGISTER FORM
    // ============================================
    $('#registerForm').submit(function(e) {
        e.preventDefault();
        
        const first_name = $('#first_name').val().trim();
        const last_name = $('#last_name').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const password = $('#password').val();
        const confirm_password = $('#confirm_password').val();
        const terms = $('#terms').is(':checked');
        
        // Validate all fields
        if (!first_name || !last_name || !email || !phone || !password || !confirm_password) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            iziToast.error({
                title: 'Error',
                message: 'Please enter a valid email address',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Validate phone number (Nigerian format)
        const phoneRegex = /^(0[789][01]\d{8}|0[789][01]\d{8})$/;
        if (!phoneRegex.test(phone)) {
            iziToast.error({
                title: 'Error',
                message: 'Please enter a valid Nigerian phone number (e.g., 08012345678)',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Validate password length
        if (password.length < 8) {
            iziToast.error({
                title: 'Error',
                message: 'Password must be at least 8 characters',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Confirm password
        if (password !== confirm_password) {
            iziToast.error({
                title: 'Error',
                message: 'Passwords do not match',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Check terms
        if (!terms) {
            iziToast.error({
                title: 'Error',
                message: 'Please agree to the Terms & Conditions',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Add loading state
        $('#registerBtn').prop('disabled', true);
        $('#registerBtn').addClass('loading');
        $('.btn-text').hide();
        $('.btn-loader').show();
        $('.btn-arrow').hide();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/auth-api.php',
            type: 'POST',
            data: {
                action: 'register',
                first_name: first_name,
                last_name: last_name,
                email: email,
                phone: phone,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1500);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: response.message,
                        position: 'topRight',
                        timeout: 3000
                    });
                    
                    resetRegisterButton();
                }
            },
            error: function(xhr, status, error) {
                console.error('Registration Error:', error);
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please try again.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                resetRegisterButton();
            }
        });
    });

    function resetRegisterButton() {
        $('#registerBtn').prop('disabled', false);
        $('#registerBtn').removeClass('loading');
        $('.btn-text').show();
        $('.btn-loader').hide();
        $('.btn-arrow').show();
    }

    // ============================================
    // ENTER KEY SUPPORT
    // ============================================
    $('#first_name, #last_name, #email, #phone, #password, #confirm_password').on('keypress', function(e) {
        if (e.which === 13) {
            $('#registerForm').submit();
        }
    });

    // ============================================
    // AUTO-FOCUS
    // ============================================
    $('#first_name').focus();
});
</script>
</body>
</html>