<?php
// =============================================
// LOGIN PAGE - NCS Online Appointment System
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
$page_title = 'Login';

// Include header
include_once __DIR__ . '/includes/header.php';

// Get remembered email
$remember_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
?>

<!-- ============================================ -->
<!-- LOGIN SECTION -->
<!-- ============================================ -->
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- ============================================ -->
            <!-- LOGIN CARD -->
            <!-- ============================================ -->
            <div class="auth-card" data-aos="fade-up" data-aos-duration="800">
                <div class="auth-header">
                    <div class="auth-logo">
                        <div class="logo-mark">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="logo-text">
                            NCS <span>Appointment</span>
                        </div>
                    </div>
                    <h2>Welcome Back</h2>
                    <p>Sign in to manage your appointments with Nigeria Customs Service</p>
                </div>

                <form id="loginForm" class="auth-form">
                    <div class="form-group">
                        <label for="email">
                            <i class="bi bi-envelope"></i>
                            Email Address
                        </label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="Enter your email address" 
                                   value="<?php echo htmlspecialchars($remember_email); ?>" 
                                   autofocus required>
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="bi bi-lock"></i>
                            Password
                        </label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" data-target="password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                            <div class="input-focus-border"></div>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" name="remember" id="remember" <?php echo $remember_email ? 'checked' : ''; ?>>
                            <label for="remember">Remember me</label>
                        </div>
                        <!--
                        <a href="#" class="forgot-link" data-bs-toggle="modal" data-bs-target="#forgotModal">
                            Forgot Password?
                        </a>
                        -->
                    </div>

                    <button type="submit" id="loginBtn" class="btn-auth-primary">
                        <span class="btn-text">Sign In</span>
                        <span class="btn-loader" style="display:none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <i class="bi bi-arrow-right btn-arrow"></i>
                    </button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Create Account <i class="bi bi-arrow-right"></i></a></p>
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
                        <a href="index.php#services" title="Services">
                            <i class="bi bi-grid-3x3-gap"></i>
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
<!-- FORGOT PASSWORD MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-key"></i></div>
                    <h5 class="modal-title">Reset Password</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="modal-text">Enter your email address and we'll send you a link to reset your password.</p>
                <form id="forgotForm">
                    <div class="form-group mb-3">
                        <label for="forgot_email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="forgot_email" name="email" class="form-control" 
                                   placeholder="Enter your email address" required>
                            <div class="input-focus-border"></div>
                        </div>
                    </div>
                    <button type="submit" id="resetBtn" class="btn-auth-primary btn-full">
                        <span class="btn-text">Send Reset Link</span>
                        <span class="btn-loader" style="display:none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/includes/footer.php'; ?>

<!-- ============================================ -->
<!-- LOGIN SPECIFIC SCRIPTS -->
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
    // LOGIN FORM
    // ============================================
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        
        const email = $('#email').val().trim();
        const password = $('#password').val();
        
        if (!email || !password) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Add loading state
        $('#loginBtn').prop('disabled', true);
        $('#loginBtn').addClass('loading');
        $('.btn-text').hide();
        $('.btn-loader').show();
        $('.btn-arrow').hide();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/auth-api.php',
            type: 'POST',
            data: {
                action: 'login',
                email: email,
                password: password,
                remember: $('input[name="remember"]').is(':checked') ? 1 : 0
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
                    
                    resetLoginButton();
                }
            },
            error: function(xhr, status, error) {
                console.error('Login Error:', error);
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please try again.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                resetLoginButton();
            }
        });
    });

    function resetLoginButton() {
        $('#loginBtn').prop('disabled', false);
        $('#loginBtn').removeClass('loading');
        $('.btn-text').show();
        $('.btn-loader').hide();
        $('.btn-arrow').show();
    }

    // ============================================
    // FORGOT PASSWORD
    // ============================================
    $('#forgotForm').submit(function(e) {
        e.preventDefault();
        
        const email = $('#forgot_email').val().trim();
        
        if (!email) {
            iziToast.error({
                title: 'Error',
                message: 'Please enter your email address',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        $('#resetBtn').prop('disabled', true);
        $('.btn-text').hide();
        $('.btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/auth-api.php',
            type: 'POST',
            data: {
                action: 'check_email',
                email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    iziToast.success({
                        title: 'Success',
                        message: 'Password reset link sent to your email',
                        position: 'topRight',
                        timeout: 3000
                    });
                    
                    setTimeout(function() {
                        $('#forgotModal').modal('hide');
                        $('#forgotForm')[0].reset();
                    }, 2000);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: 'Email address not found in our system',
                        position: 'topRight',
                        timeout: 3000
                    });
                }
                
                $('#resetBtn').prop('disabled', false);
                $('.btn-text').show();
                $('.btn-loader').hide();
            },
            error: function() {
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please try again.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#resetBtn').prop('disabled', false);
                $('.btn-text').show();
                $('.btn-loader').hide();
            }
        });
    });

    // ============================================
    // ENTER KEY SUPPORT
    // ============================================
    $('#password, #email').on('keypress', function(e) {
        if (e.which === 13) {
            $('#loginForm').submit();
        }
    });

    // ============================================
    // AUTO-FOCUS
    // ============================================
    if ($('#email').val() === '') {
        $('#email').focus();
    }
});
</script>
</body>
</html>