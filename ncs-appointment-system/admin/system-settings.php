<?php
// =============================================
// SYSTEM SETTINGS - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'System Settings';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get all system settings
$settings_query = "SELECT * FROM system_settings ORDER BY setting_key";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

// Get setting categories
$categories = [
    'general' => 'General Settings',
    'appointment' => 'Appointment Settings',
    'notification' => 'Notification Settings',
    'security' => 'Security Settings',
    'system' => 'System Settings'
];

// Group settings by category
$grouped_settings = [];
foreach ($settings as $key => $setting) {
    $category = 'general';
    if (strpos($key, 'appointment') !== false || strpos($key, 'max_per_day') !== false || strpos($key, 'advance_days') !== false) {
        $category = 'appointment';
    } elseif (strpos($key, 'notification') !== false || strpos($key, 'reminder') !== false) {
        $category = 'notification';
    } elseif (strpos($key, 'security') !== false || strpos($key, 'lockout') !== false) {
        $category = 'security';
    } elseif (strpos($key, 'maintenance') !== false || strpos($key, 'debug') !== false) {
        $category = 'system';
    }
    $grouped_settings[$category][] = $setting;
}
?>

<!-- ============================================ -->
<!-- SYSTEM SETTINGS CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">System Settings</h1>
                <p class="dashboard-subtitle">Configure and manage system-wide settings for the NCS Appointment System</p>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-secondary" onclick="window.location.reload();">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
                <button type="button" id="resetSettingsBtn" class="btn-primary">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Reset Defaults
                </button>
            </div>
        </div>

        <!-- Settings Tabs -->
        <ul class="settings-tabs" id="settingsTabs">
            <li class="settings-tab active" data-tab="general">
                <i class="bi bi-gear"></i>
                General
            </li>
            <li class="settings-tab" data-tab="appointment">
                <i class="bi bi-calendar-check"></i>
                Appointment
            </li>
            <li class="settings-tab" data-tab="notification">
                <i class="bi bi-bell"></i>
                Notifications
            </li>
            <li class="settings-tab" data-tab="security">
                <i class="bi bi-shield-lock"></i>
                Security
            </li>
            <li class="settings-tab" data-tab="system">
                <i class="bi bi-server"></i>
                System
            </li>
        </ul>

        <!-- Settings Content -->
        <div class="settings-content">
            <!-- General Settings -->
            <div class="settings-panel active" id="panel-general">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-gear"></i>
                            General Settings
                        </h3>
                        <span class="card-badge">Basic configuration</span>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm" class="settings-form">
                            <input type="hidden" name="category" value="general">
                            
                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" id="site_name" name="site_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['site_name']['setting_value'] ?? 'NCS Appointment System'); ?>">
                                    <small class="form-text text-muted">The name of the application displayed in the browser tab and header</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone" name="timezone" class="form-control">
                                        <?php
                                        $timezones = [
                                            'Africa/Lagos' => 'Africa/Lagos (GMT+1)',
                                            'Africa/Abidjan' => 'Africa/Abidjan (GMT+0)',
                                            'Africa/Cairo' => 'Africa/Cairo (GMT+2)',
                                            'Africa/Johannesburg' => 'Africa/Johannesburg (GMT+2)',
                                            'Africa/Nairobi' => 'Africa/Nairobi (GMT+3)',
                                            'UTC' => 'UTC (GMT+0)',
                                            'America/New_York' => 'America/New_York (GMT-5)',
                                            'Europe/London' => 'Europe/London (GMT+0)',
                                            'Europe/Paris' => 'Europe/Paris (GMT+1)'
                                        ];
                                        $current_timezone = $settings['timezone']['setting_value'] ?? 'Africa/Lagos';
                                        foreach ($timezones as $value => $label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo $current_timezone == $value ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Default timezone used throughout the system</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Appointment Settings -->
            <div class="settings-panel" id="panel-appointment">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-calendar-check"></i>
                            Appointment Settings
                        </h3>
                        <span class="card-badge">Booking configuration</span>
                    </div>
                    <div class="card-body">
                        <form id="settingsFormAppointment" class="settings-form">
                            <input type="hidden" name="category" value="appointment">
                            
                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="appointment_max_per_day">Max Appointments Per Day (per office)</label>
                                    <input type="number" id="appointment_max_per_day" name="appointment_max_per_day" class="form-control" 
                                           value="<?php echo $settings['appointment_max_per_day']['setting_value'] ?? 50; ?>" min="1" max="500">
                                    <small class="form-text text-muted">Maximum number of appointments allowed per day for each office</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="appointment_advance_days">Advance Booking Days</label>
                                    <input type="number" id="appointment_advance_days" name="appointment_advance_days" class="form-control" 
                                           value="<?php echo $settings['appointment_advance_days']['setting_value'] ?? 30; ?>" min="1" max="365">
                                    <small class="form-text text-muted">How many days in advance users can book appointments</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="appointment_cancel_hours">Cancellation Hours</label>
                                    <input type="number" id="appointment_cancel_hours" name="appointment_cancel_hours" class="form-control" 
                                           value="<?php echo $settings['appointment_cancel_hours']['setting_value'] ?? 24; ?>" min="1" max="168">
                                    <small class="form-text text-muted">Hours before appointment that users can cancel without penalty</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="auto_approve_appointments">Auto Approve Appointments</label>
                                    <select id="auto_approve_appointments" name="auto_approve_appointments" class="form-control">
                                        <option value="true" <?php echo ($settings['auto_approve_appointments']['setting_value'] ?? 'false') === 'true' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="false" <?php echo ($settings['auto_approve_appointments']['setting_value'] ?? 'false') === 'false' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="form-text text-muted">Automatically approve appointments without officer review</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="settings-panel" id="panel-notification">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-bell"></i>
                            Notification Settings
                        </h3>
                        <span class="card-badge">Alerts & reminders</span>
                    </div>
                    <div class="card-body">
                        <form id="settingsFormNotification" class="settings-form">
                            <input type="hidden" name="category" value="notification">
                            
                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="enable_notifications">Enable Notifications</label>
                                    <select id="enable_notifications" name="enable_notifications" class="form-control">
                                        <option value="true" <?php echo ($settings['enable_notifications']['setting_value'] ?? 'true') === 'true' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="false" <?php echo ($settings['enable_notifications']['setting_value'] ?? 'true') === 'false' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="form-text text-muted">Enable or disable all system notifications</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="enable_appointment_reminders">Appointment Reminders</label>
                                    <select id="enable_appointment_reminders" name="enable_appointment_reminders" class="form-control">
                                        <option value="true" <?php echo ($settings['enable_appointment_reminders']['setting_value'] ?? 'true') === 'true' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="false" <?php echo ($settings['enable_appointment_reminders']['setting_value'] ?? 'true') === 'false' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="form-text text-muted">Send reminder notifications before scheduled appointments</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="reminder_days_before">Reminder Days Before</label>
                                    <input type="number" id="reminder_days_before" name="reminder_days_before" class="form-control" 
                                           value="<?php echo $settings['reminder_days_before']['setting_value'] ?? 1; ?>" min="0" max="7">
                                    <small class="form-text text-muted">How many days before appointment to send reminders</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-panel" id="panel-security">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-shield-lock"></i>
                            Security Settings
                        </h3>
                        <span class="card-badge">Access & security</span>
                    </div>
                    <div class="card-body">
                        <form id="settingsFormSecurity" class="settings-form">
                            <input type="hidden" name="category" value="security">
                            
                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="max_login_attempts">Max Login Attempts</label>
                                    <input type="number" id="max_login_attempts" name="max_login_attempts" class="form-control" 
                                           value="<?php echo $settings['max_login_attempts']['setting_value'] ?? 5; ?>" min="1" max="20">
                                    <small class="form-text text-muted">Number of failed login attempts before account lockout</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="lockout_duration">Lockout Duration (minutes)</label>
                                    <input type="number" id="lockout_duration" name="lockout_duration" class="form-control" 
                                           value="<?php echo $settings['lockout_duration']['setting_value'] ?? 30; ?>" min="5" max="1440">
                                    <small class="form-text text-muted">How long to lock an account after max login attempts</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="session_timeout">Session Timeout (minutes)</label>
                                    <input type="number" id="session_timeout" name="session_timeout" class="form-control" 
                                           value="<?php echo $settings['session_timeout']['setting_value'] ?? 60; ?>" min="10" max="1440">
                                    <small class="form-text text-muted">Inactive session timeout in minutes</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="settings-panel" id="panel-system">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-server"></i>
                            System Settings
                        </h3>
                        <span class="card-badge">Advanced configuration</span>
                    </div>
                    <div class="card-body">
                        <form id="settingsFormSystem" class="settings-form">
                            <input type="hidden" name="category" value="system">
                            
                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="maintenance_mode">Maintenance Mode</label>
                                    <select id="maintenance_mode" name="maintenance_mode" class="form-control">
                                        <option value="false" <?php echo ($settings['maintenance_mode']['setting_value'] ?? 'false') === 'false' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="true" <?php echo ($settings['maintenance_mode']['setting_value'] ?? 'false') === 'true' ? 'selected' : ''; ?>>Enabled</option>
                                    </select>
                                    <small class="form-text text-muted">Put the system in maintenance mode (only admins can access)</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="debug_mode">Debug Mode</label>
                                    <select id="debug_mode" name="debug_mode" class="form-control">
                                        <option value="false" <?php echo ($settings['debug_mode']['setting_value'] ?? 'false') === 'false' ? 'selected' : ''; ?>>Disabled</option>
                                        <option value="true" <?php echo ($settings['debug_mode']['setting_value'] ?? 'false') === 'true' ? 'selected' : ''; ?>>Enabled</option>
                                    </select>
                                    <small class="form-text text-muted">Enable debug mode for error logging and development</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="enable_document_upload">Enable Document Upload</label>
                                    <select id="enable_document_upload" name="enable_document_upload" class="form-control">
                                        <option value="true" <?php echo ($settings['enable_document_upload']['setting_value'] ?? 'true') === 'true' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="false" <?php echo ($settings['enable_document_upload']['setting_value'] ?? 'true') === 'false' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                    <small class="form-text text-muted">Allow users to upload documents with their appointments</small>
                                </div>
                            </div>

                            <div class="settings-group">
                                <div class="form-group">
                                    <label for="max_upload_size">Max Upload Size (MB)</label>
                                    <input type="number" id="max_upload_size" name="max_upload_size" class="form-control" 
                                           value="<?php echo $settings['max_upload_size']['setting_value'] ?? 10; ?>" min="1" max="100">
                                    <small class="form-text text-muted">Maximum file size for document uploads in megabytes</small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="settings-actions">
            <button type="button" id="saveAllSettingsBtn" class="btn-primary btn-save-all">
                <i class="bi bi-check2-circle"></i>
                Save All Settings
                <span class="btn-loader" style="display:none;">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </span>
            </button>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<!-- ============================================ -->
<!-- SYSTEM SETTINGS SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // TAB SWITCHING
    // ============================================
    $('.settings-tab').on('click', function() {
        const tab = $(this).data('tab');
        
        // Update tabs
        $('.settings-tab').removeClass('active');
        $(this).addClass('active');
        
        // Update panels
        $('.settings-panel').removeClass('active');
        $('#panel-' + tab).addClass('active');
    });

    // ============================================
    // SAVE ALL SETTINGS
    // ============================================
    $('#saveAllSettingsBtn').on('click', function() {
        // Collect all form data
        const forms = ['#settingsForm', '#settingsFormAppointment', '#settingsFormNotification', '#settingsFormSecurity', '#settingsFormSystem'];
        let allData = {};
        let allValid = true;
        
        forms.forEach(function(formId) {
            const form = $(formId);
            const formData = form.serializeArray();
            const category = form.find('input[name="category"]').val();
            
            // Validate form fields
            form.find('.form-control').each(function() {
                if ($(this).prop('required') && !$(this).val()) {
                    allValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            // Process form data
            formData.forEach(function(item) {
                if (item.name !== 'category') {
                    allData[item.name] = item.value;
                }
            });
        });
        
        if (!allValid) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all required fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        const btn = $(this);
        btn.prop('disabled', true);
        btn.find('.btn-loader').show();
        btn.find('i').hide();
        btn.find('span:not(.btn-loader)').text('Saving...');
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/settings-api.php',
            type: 'POST',
            data: {
                action: 'save_settings',
                settings: allData
            },
            dataType: 'json',
            success: function(response) {
                console.log('Save Settings Response:', response);
                
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
                
                btn.prop('disabled', false);
                btn.find('.btn-loader').hide();
                btn.find('i').show();
                btn.find('span:not(.btn-loader)').text('Save All Settings');
            },
            error: function(xhr, status, error) {
                console.error('Save Settings Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                btn.prop('disabled', false);
                btn.find('.btn-loader').hide();
                btn.find('i').show();
                btn.find('span:not(.btn-loader)').text('Save All Settings');
            }
        });
    });

    // ============================================
    // RESET SETTINGS
    // ============================================
    $('#resetSettingsBtn').on('click', function() {
        Swal.fire({
            title: 'Reset Settings?',
            html: 'This will reset all settings to their default values.<br><small style="color: #ffc107;">This action cannot be undone!</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reset all',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#resetSettingsBtn');
                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm" role="status"></span> Resetting...');
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>/api/settings-api.php',
                    type: 'POST',
                    data: {
                        action: 'reset_settings'
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Reset Settings Response:', response);
                        
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
                            btn.prop('disabled', false);
                            btn.html('<i class="bi bi-arrow-counterclockwise"></i> Reset Defaults');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Reset Settings Error:', error);
                        console.error('Response Text:', xhr.responseText);
                        
                        iziToast.error({
                            title: 'Error',
                            message: 'Something went wrong. Please try again.',
                            position: 'topRight',
                            timeout: 3000
                        });
                        btn.prop('disabled', false);
                        btn.html('<i class="bi bi-arrow-counterclockwise"></i> Reset Defaults');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>