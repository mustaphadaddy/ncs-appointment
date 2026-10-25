<?php
// =============================================
// APPLY FOR APPOINTMENT - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is applicant
if (!isLoggedIn() || !isApplicant()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Book Appointment';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get user details
$user_id = $_SESSION['user_id'];
$user_query = "SELECT u.*, 
               DATE_FORMAT(u.date_of_birth, '%Y-%m-%d') as dob_formatted
               FROM users u 
               WHERE u.id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if profile is complete
$profile_complete = true;
$missing_fields = [];

// Required fields for profile completion
$required_fields = [
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'email' => 'Email Address',
    'phone' => 'Phone Number',
    'date_of_birth' => 'Date of Birth',
    'gender' => 'Gender',
    'address' => 'Address',
    'city' => 'City',
    'state' => 'State',
    'nin' => 'NIN (National Identification Number)'
];

foreach ($required_fields as $field => $label) {
    if (empty($user[$field])) {
        $profile_complete = false;
        $missing_fields[] = $label;
    }
}

// Get all active offices for dropdown
$offices_query = "SELECT id, office_code, office_name, office_type, address, city, state, 
                  opening_time, closing_time, max_appointments_per_day 
                  FROM offices 
                  WHERE is_active = 1 
                  ORDER BY office_name";
$offices_result = $conn->query($offices_query);

// Get available time slots for selected office (if any)
// Get available time slots for selected office (if any)
$selected_office = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : '';

$available_slots = [];
if ($selected_office > 0 && !empty($selected_date)) {
    // Get all time slots for the office - FIXED
    $slots_query = "SELECT slot_time, max_appointments 
                    FROM time_slots 
                    WHERE office_id = ? AND is_active = 1 
                    AND (
                        (monday = 1 AND DAYOFWEEK(?) = 2)
                        OR (tuesday = 1 AND DAYOFWEEK(?) = 3)
                        OR (wednesday = 1 AND DAYOFWEEK(?) = 4)
                        OR (thursday = 1 AND DAYOFWEEK(?) = 5)
                        OR (friday = 1 AND DAYOFWEEK(?) = 6)
                        OR (saturday = 1 AND DAYOFWEEK(?) = 7)
                        OR (sunday = 1 AND DAYOFWEEK(?) = 1)
                    )
                    ORDER BY slot_time";
    $stmt = $conn->prepare($slots_query);
    
    // Bind parameters - 1 office_id + 7 date parameters = 8 total
    $stmt->bind_param("isssssss", $selected_office, $selected_date, $selected_date, $selected_date, $selected_date, $selected_date, $selected_date, $selected_date);
    $stmt->execute();
    $slots_result = $stmt->get_result();
    
    while ($slot = $slots_result->fetch_assoc()) {
        // Check if slot is already booked
        $check_query = "SELECT COUNT(*) as booked FROM appointments 
                        WHERE office_id = ? AND appointment_date = ? AND appointment_time = ? 
                        AND status NOT IN ('cancelled', 'rejected')";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iss", $selected_office, $selected_date, $slot['slot_time']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $booked = $check_result->fetch_assoc()['booked'] ?? 0;
        $check_stmt->close();
        
        $available_slots[] = [
            'time' => $slot['slot_time'],
            'max' => $slot['max_appointments'],
            'booked' => $booked,
            'available' => ($booked < $slot['max_appointments'])
        ];
    }
    $stmt->close();
}

// Get min and max dates for appointment booking
$min_date = date('Y-m-d', strtotime('+1 day'));
$max_date = date('Y-m-d', strtotime('+' . (APPOINTMENT_ADVANCE_DAYS ?? 30) . ' days'));
?>

<!-- ============================================ -->
<!-- APPLY FOR APPOINTMENT CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Book Appointment</h1>
                <p class="dashboard-subtitle">Schedule an appointment with any Customs office</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/applicant/dashboard.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Profile Completion Alert -->
        <?php if (!$profile_complete): ?>
            <div class="alert alert-warning profile-alert" data-aos="fade-up" data-aos-delay="100">
                <div class="alert-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="alert-content">
                    <h4>Complete Your Profile First</h4>
                    <p>Please update your profile before booking an appointment. The following fields are required:</p>
                    <ul>
                        <?php foreach ($missing_fields as $field): ?>
                            <li><i class="bi bi-x-circle"></i> <?php echo $field; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo BASE_URL; ?>/applicant/profile.php" class="btn-primary">
                        <i class="bi bi-pencil"></i>
                        Update Profile Now
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Booking Form -->
        <div class="dashboard-card booking-card" data-aos="fade-up" data-aos-delay="200">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-calendar-plus"></i>
                    Appointment Details
                </h3>
                <span class="card-badge">Required fields *</span>
            </div>
            <div class="card-body">
                <form id="bookingForm" class="booking-form">
                    <div class="row g-4">
                        <!-- Office Selection -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="office_id">Select Office <span class="text-danger">*</span></label>
                                <select id="office_id" name="office_id" class="form-control" required <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                    <option value="">-- Select an office --</option>
                                    <?php while ($office = $offices_result->fetch_assoc()): ?>
                                        <option value="<?php echo $office['id']; ?>" <?php echo $selected_office == $office['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($office['office_name'] . ' (' . $office['office_code'] . ') - ' . $office['city'] . ', ' . $office['state']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="form-text text-muted">Select the Customs office where you want to book your appointment</small>
                            </div>
                        </div>

                        <!-- Office Details (displayed when selected) -->
                        <?php if ($selected_office > 0): ?>
                            <?php 
                            $office_details_query = "SELECT * FROM offices WHERE id = ?";
                            $stmt = $conn->prepare($office_details_query);
                            $stmt->bind_param("i", $selected_office);
                            $stmt->execute();
                            $office_details = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            ?>
                            <?php if ($office_details): ?>
                                <div class="col-12">
                                    <div class="office-details-box">
                                        <h4><i class="bi bi-building"></i> <?php echo htmlspecialchars($office_details['office_name']); ?></h4>
                                        <div class="office-details-grid">
                                            <div class="office-detail">
                                                <span class="detail-label">Code</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($office_details['office_code']); ?></span>
                                            </div>
                                            <div class="office-detail">
                                                <span class="detail-label">Type</span>
                                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $office_details['office_type'])); ?></span>
                                            </div>
                                            <div class="office-detail">
                                                <span class="detail-label">Address</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($office_details['address'] . ', ' . $office_details['city'] . ', ' . $office_details['state']); ?></span>
                                            </div>
                                            <div class="office-detail">
                                                <span class="detail-label">Hours</span>
                                                <span class="detail-value"><?php echo date('h:i A', strtotime($office_details['opening_time'])); ?> - <?php echo date('h:i A', strtotime($office_details['closing_time'])); ?></span>
                                            </div>
                                            <div class="office-detail">
                                                <span class="detail-label">Phone</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($office_details['phone']); ?></span>
                                            </div>
                                            <div class="office-detail">
                                                <span class="detail-label">Email</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($office_details['email']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Date Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" id="appointment_date" name="appointment_date" 
                                       class="form-control" 
                                       min="<?php echo $min_date; ?>" 
                                       max="<?php echo $max_date; ?>"
                                       value="<?php echo $selected_date; ?>"
                                       required <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                <small class="form-text text-muted">Select a date between <?php echo date('M d, Y', strtotime($min_date)); ?> and <?php echo date('M d, Y', strtotime($max_date)); ?></small>
                            </div>
                        </div>

                        <!-- Time Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="appointment_time">Appointment Time <span class="text-danger">*</span></label>
                                <select id="appointment_time" name="appointment_time" class="form-control" required <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                    <option value="">-- Select time --</option>
                                    <?php if (!empty($available_slots)): ?>
                                        <?php foreach ($available_slots as $slot): ?>
                                            <option value="<?php echo $slot['time']; ?>" <?php echo !$slot['available'] ? 'disabled' : ''; ?>>
                                                <?php echo date('h:i A', strtotime($slot['time'])); ?>
                                                <?php echo !$slot['available'] ? ' (Fully Booked)' : ' (Available)'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No available slots</option>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">Select your preferred time slot</small>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="purpose">Purpose <span class="text-danger">*</span></label>
                                <select id="purpose" name="purpose" class="form-control" required <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                    <option value="">-- Select purpose --</option>
                                    <option value="Customs Clearance">Customs Clearance</option>
                                    <option value="Import Documentation">Import Documentation</option>
                                    <option value="Export Declaration">Export Declaration</option>
                                    <option value="Customs Verification">Customs Verification</option>
                                    <option value="Import Duty Payment">Import Duty Payment</option>
                                    <option value="Customs Enquiry">Customs Enquiry</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration_minutes">Duration (minutes)</label>
                                <select id="duration_minutes" name="duration_minutes" class="form-control" <?php echo !$profile_complete ? 'disabled' : ''; ?>>
                                    <option value="15">15 minutes</option>
                                    <option value="30" selected>30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes</option>
                                    <option value="90">90 minutes</option>
                                    <option value="120">120 minutes</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="description">Description / Additional Details</label>
                                <textarea id="description" name="description" class="form-control" 
                                          rows="3" placeholder="Provide any additional details about your appointment..."
                                          <?php echo !$profile_complete ? 'disabled' : ''; ?>></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <?php if ($profile_complete): ?>
                                <button type="submit" id="bookBtn" class="btn-primary btn-submit">
                                    <span class="btn-text"><i class="bi bi-check2-circle"></i> Book Appointment</span>
                                    <span class="btn-loader" style="display:none;">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </span>
                                </button>
                                <button type="reset" class="btn-secondary btn-reset">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Please complete your profile first to book an appointment.
                                    <a href="<?php echo BASE_URL; ?>/applicant/profile.php" class="btn-link">Update Profile</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Booking Tips -->
        <div class="dashboard-card tips-card mt-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-lightbulb"></i>
                    Booking Tips
                </h3>
            </div>
            <div class="card-body">
                <div class="tips-grid">
                    <div class="tip-item">
                        <i class="bi bi-check-circle"></i>
                        <h4>Complete Profile First</h4>
                        <p>Ensure all your profile information is up to date before booking.</p>
                    </div>
                    <div class="tip-item">
                        <i class="bi bi-clock"></i>
                        <h4>Arrive Early</h4>
                        <p>Please arrive at least 15 minutes before your scheduled appointment time.</p>
                    </div>
                    <div class="tip-item">
                        <i class="bi bi-file-text"></i>
                        <h4>Bring Documents</h4>
                        <p>Bring all required identification and supporting documents.</p>
                    </div>
                    <div class="tip-item">
                        <i class="bi bi-bell"></i>
                        <h4>Check Status</h4>
                        <p>Monitor your appointment status in the dashboard for updates.</p>
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
<!-- APPLY SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // OFFICE SELECTION - Load time slots
    // ============================================
    $('#office_id').on('change', function() {
        const officeId = $(this).val();
        const date = $('#appointment_date').val();
        
        if (officeId && date) {
            window.location.href = '?office_id=' + officeId + '&date=' + date;
        } else if (officeId) {
            window.location.href = '?office_id=' + officeId;
        } else {
            window.location.href = '?';
        }
    });

    // ============================================
    // DATE SELECTION - Reload with selected date
    // ============================================
    $('#appointment_date').on('change', function() {
        const date = $(this).val();
        const officeId = $('#office_id').val();
        
        if (officeId && date) {
            window.location.href = '?office_id=' + officeId + '&date=' + date;
        } else if (date) {
            window.location.href = '?date=' + date;
        }
    });

    // ============================================
    // BOOK APPOINTMENT
    // ============================================
    $('#bookingForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'book_appointment',
            office_id: $('#office_id').val(),
            appointment_date: $('#appointment_date').val(),
            appointment_time: $('#appointment_time').val(),
            purpose: $('#purpose').val(),
            description: $('#description').val().trim(),
            duration_minutes: $('#duration_minutes').val()
        };
        
        // Validate
        if (!formData.office_id || !formData.appointment_date || 
            !formData.appointment_time || !formData.purpose) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all required fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#bookBtn').prop('disabled', true);
        $('#bookBtn .btn-text').hide();
        $('#bookBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Book Appointment Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 3000
                    });
                    
                    setTimeout(function() {
                        window.location.href = '<?php echo BASE_URL; ?>/applicant/my-appointments.php';
                    }, 2000);
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: response.message || 'Something went wrong',
                        position: 'topRight',
                        timeout: 3000
                    });
                }
                
                $('#bookBtn').prop('disabled', false);
                $('#bookBtn .btn-text').show();
                $('#bookBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Book Appointment Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#bookBtn').prop('disabled', false);
                $('#bookBtn .btn-text').show();
                $('#bookBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // RESET FORM
    // ============================================
    $('.btn-reset').on('click', function(e) {
        e.preventDefault();
        $('#bookingForm')[0].reset();
        $('#appointment_time').html('<option value="">-- Select time --</option>');
        window.location.href = '<?php echo BASE_URL; ?>/applicant/apply.php';
    });
});
</script>
</body>
</html>