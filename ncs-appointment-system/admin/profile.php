<?php
// =============================================
// ADMIN PROFILE - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'My Profile';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get user details
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, 
          DATE_FORMAT(u.date_of_birth, '%Y-%m-%d') as dob_formatted,
          DATE_FORMAT(u.created_at, '%b %d, %Y') as joined_date,
          DATE_FORMAT(u.last_login, '%b %d, %Y %h:%i %p') as last_login_formatted
          FROM users u 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get user stats
$appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ?";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments_total = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$pending_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_count = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$approved_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'approved'";
$stmt = $conn->prepare($approved_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$approved_count = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$rejected_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'rejected'";
$stmt = $conn->prepare($rejected_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rejected_count = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get audit log count for this user
$audit_query = "SELECT COUNT(*) as total FROM audit_logs WHERE user_id = ?";
$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$audit_count = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Get initials for avatar
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>

<!-- ============================================ -->
<!-- PROFILE CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">My Profile</h1>
                <p class="dashboard-subtitle">Manage your personal information and account settings</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Left Column - Profile Card -->
            <div class="profile-left">
                <div class="dashboard-card profile-card pt-4">
                    <div class="profile-avatar-wrapper">
                        <div class="profile-avatar admin-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="profile-status">
                            <span class="status-badge status-active">
                                <i class="bi bi-check-circle-fill"></i>
                                Active
                            </span>
                        </div>
                    </div>
                    <div class="profile-name">
                        <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                        <p class="profile-role">
                            <span class="status-badge status-admin">
                                <i class="bi bi-shield-check"></i>
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="profile-divider"></div>
                    
                    <div class="profile-info p-3">
                        <div class="profile-info-item">
                            <i class="bi bi-envelope"></i>
                            <div>
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <i class="bi bi-phone"></i>
                            <div>
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                        </div>
                        <div class="profile-info-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <span class="info-label">Joined</span>
                                <span class="info-value"><?php echo $user['joined_date']; ?></span>
                            </div>
                        </div>
                        <?php if ($user['last_login_formatted']): ?>
                        <div class="profile-info-item">
                            <i class="bi bi-clock-history"></i>
                            <div>
                                <span class="info-label">Last Login</span>
                                <span class="info-value"><?php echo $user['last_login_formatted']; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Edit Forms -->
            <div class="profile-right">
                <!-- Update Profile Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-person-gear"></i>
                            Personal Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="profileForm" class="profile-form">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="date_of_birth">Date of Birth</label>
                                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                               value="<?php echo $user['dob_formatted']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender" class="form-control">
                                            <option value="">-- Select gender --</option>
                                            <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo $user['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea id="address" name="address" class="form-control" rows="2" 
                                                  placeholder="Enter your full address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['city']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" id="state" name="state" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['state']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nin">NIN (National ID)</label>
                                        <input type="text" id="nin" name="nin" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['nin']); ?>" 
                                               placeholder="Enter your NIN">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" id="updateProfileBtn" class="btn-primary">
                                        <span class="btn-text">Update Profile</span>
                                        <span class="btn-loader" style="display:none;">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-key"></i>
                            Change Password
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="passwordForm" class="profile-form">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <div class="input-wrapper">
                                            <input type="password" id="current_password" name="current_password" class="form-control" 
                                                   placeholder="Enter your current password" required>
                                            <button type="button" class="toggle-password" data-target="current_password" aria-label="Toggle password visibility">
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                            <div class="input-focus-border"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <div class="input-wrapper">
                                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                                   placeholder="Min 8 characters" required>
                                            <button type="button" class="toggle-password" data-target="new_password" aria-label="Toggle password visibility">
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
                                        <label for="confirm_password">Confirm New Password</label>
                                        <div class="input-wrapper">
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                                   placeholder="Confirm your new password" required>
                                            <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Toggle password visibility">
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                            <div class="input-focus-border"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" id="changePasswordBtn" class="btn-primary">
                                        <span class="btn-text">Change Password</span>
                                        <span class="btn-loader" style="display:none;">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-shield-lock"></i>
                            Account Security
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="security-actions">
                            <div class="security-item">
                                <div class="security-info">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <div>
                                        <h4>Logout</h4>
                                        <p>Sign out of your account</p>
                                    </div>
                                </div>
                                <button type="button" id="logoutBtn" class="btn btn-danger btn-sm">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Logout
                                </button>
                            </div>
                            <div class="security-item">
                                <div class="security-info">
                                    <i class="bi bi-activity"></i>
                                    <div>
                                        <h4>Session Activity</h4>
                                        <p>Last active: <?php echo $user['last_login_formatted'] ?? 'Never'; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<!-- ============================================ -->
<!-- PROFILE SCRIPTS -->
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
    // UPDATE PROFILE
    // ============================================
    $('#profileForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update_profile',
            first_name: $('#first_name').val().trim(),
            last_name: $('#last_name').val().trim(),
            email: $('#email').val().trim(),
            phone: $('#phone').val().trim(),
            date_of_birth: $('#date_of_birth').val(),
            gender: $('#gender').val(),
            address: $('#address').val().trim(),
            city: $('#city').val().trim(),
            state: $('#state').val().trim(),
            nin: $('#nin').val().trim()
        };
        
        // Validate
        if (!formData.first_name || !formData.last_name || !formData.email || !formData.phone) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all required fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        if (!validateEmail(formData.email)) {
            iziToast.error({
                title: 'Error',
                message: 'Please enter a valid email address',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#updateProfileBtn').prop('disabled', true);
        $('#updateProfileBtn .btn-text').hide();
        $('#updateProfileBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/users-api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Update Profile Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: response.message || 'Something went wrong',
                        position: 'topRight',
                        timeout: 3000
                    });
                }
                
                $('#updateProfileBtn').prop('disabled', false);
                $('#updateProfileBtn .btn-text').show();
                $('#updateProfileBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Update Profile Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#updateProfileBtn').prop('disabled', false);
                $('#updateProfileBtn .btn-text').show();
                $('#updateProfileBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // CHANGE PASSWORD
    // ============================================
    $('#passwordForm').submit(function(e) {
        e.preventDefault();
        
        const currentPassword = $('#current_password').val();
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        // Validate
        if (!currentPassword || !newPassword || !confirmPassword) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all password fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        if (newPassword.length < 8) {
            iziToast.error({
                title: 'Error',
                message: 'New password must be at least 8 characters',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        if (newPassword !== confirmPassword) {
            iziToast.error({
                title: 'Error',
                message: 'Passwords do not match',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#changePasswordBtn').prop('disabled', true);
        $('#changePasswordBtn .btn-text').hide();
        $('#changePasswordBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/users-api.php',
            type: 'POST',
            data: {
                action: 'change_password',
                current_password: currentPassword,
                new_password: newPassword
            },
            dataType: 'json',
            success: function(response) {
                console.log('Change Password Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    $('#passwordForm')[0].reset();
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: response.message || 'Something went wrong',
                        position: 'topRight',
                        timeout: 3000
                    });
                }
                
                $('#changePasswordBtn').prop('disabled', false);
                $('#changePasswordBtn .btn-text').show();
                $('#changePasswordBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Change Password Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#changePasswordBtn').prop('disabled', false);
                $('#changePasswordBtn .btn-text').show();
                $('#changePasswordBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // PASSWORD STRENGTH INDICATOR
    // ============================================
    $('#new_password').on('keyup', function() {
        const password = $(this).val();
        const requirements = $(this).closest('.form-group').find('.password-requirements small');
        
        if (password.length === 0) {
            requirements.text('Password must be at least 8 characters').removeClass('text-success text-danger').addClass('text-muted');
        } else if (password.length < 8) {
            requirements.text('Password must be at least 8 characters').removeClass('text-muted text-success').addClass('text-danger');
        } else {
            requirements.text('✓ Password meets requirements').removeClass('text-muted text-danger').addClass('text-success');
        }
    });

    // ============================================
    // VALIDATE EMAIL
    // ============================================
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>
</body>
</html>