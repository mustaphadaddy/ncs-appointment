<?php
// =============================================
// VIEW APPOINTMENT - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is applicant
if (!isLoggedIn() || !isApplicant()) {
    redirect(BASE_URL . '/login.php');
}

// Get appointment ID
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id <= 0) {
    redirect(BASE_URL . '/applicant/my-appointments.php');
}

// Set page title
$page_title = 'View Appointment';

// Include header
include_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Fetch appointment details
$query = "SELECT a.*, 
          CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
          u.email as applicant_email,
          u.phone as applicant_phone,
          u.address as applicant_address,
          u.city as applicant_city,
          u.state as applicant_state,
          u.nin as applicant_nin,
          u.date_of_birth as applicant_dob,
          u.gender as applicant_gender,
          o.office_name,
          o.office_code,
          o.office_type,
          o.address as office_address,
          o.city as office_city,
          o.state as office_state,
          o.phone as office_phone,
          o.email as office_email,
          o.opening_time,
          o.closing_time,
          CONCAT(r.first_name, ' ', r.last_name) as reviewed_by_name,
          DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
          DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
          DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted,
          DATE_FORMAT(a.reviewed_at, '%b %d, %Y %h:%i %p') as reviewed_at_formatted
          FROM appointments a
          LEFT JOIN users u ON a.user_id = u.id
          LEFT JOIN offices o ON a.office_id = o.id
          LEFT JOIN users r ON a.reviewed_by = r.id
          WHERE a.id = ? AND a.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(BASE_URL . '/applicant/my-appointments.php');
}

$appointment = $result->fetch_assoc();
$stmt->close();

// Get status icon
$status_icon = 'clock';
$status_color = 'secondary';
switch($appointment['status']) {
    case 'pending':
        $status_icon = 'clock';
        $status_color = 'warning';
        break;
    case 'approved':
        $status_icon = 'check-circle';
        $status_color = 'success';
        break;
    case 'rejected':
        $status_icon = 'x-circle';
        $status_color = 'danger';
        break;
    case 'completed':
        $status_icon = 'check-circle-fill';
        $status_color = 'success';
        break;
    case 'cancelled':
        $status_icon = 'slash-circle';
        $status_color = 'secondary';
        break;
    case 'no_show':
        $status_icon = 'exclamation-circle';
        $status_color = 'danger';
        break;
}
?>

<!-- ============================================ -->
<!-- VIEW APPOINTMENT CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Appointment Details</h1>
                <p class="dashboard-subtitle">View complete information for appointment #<?php echo htmlspecialchars($appointment['appointment_number']); ?></p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to My Appointments
                </a>
                <?php if ($appointment['status'] == 'approved'): ?>
                    <a href="<?php echo BASE_URL; ?>/applicant/print-slip.php?id=<?php echo $appointment['id']; ?>" class="btn-primary" target="_blank">
                        <i class="bi bi-printer"></i>
                        Print Slip
                    </a>
                <?php endif; ?>
                <?php if ($appointment['status'] == 'pending'): ?>
                    <button type="button" class="btn-action btn-cancel" 
                            data-id="<?php echo $appointment['id']; ?>"
                            data-number="<?php echo $appointment['appointment_number']; ?>"
                            title="Cancel Appointment">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Card -->
        <div class="dashboard-card status-card" data-aos="fade-up" data-aos-delay="100">
            <div class="card-body">
                <div class="status-display">
                    <div class="status-icon-large status-<?php echo $status_color; ?>">
                        <i class="bi bi-<?php echo $status_icon; ?>"></i>
                    </div>
                    <div class="status-info px-3 rounded-5 bg-transparent">
                        <span class="status-label">Current Status</span>
                        <span class="status-badge status-<?php echo $appointment['status']; ?>" style="font-size: 16px; padding: 8px 20px;">
                            <i class="bi bi-<?php echo $status_icon; ?>"></i>
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                        <?php if ($appointment['reviewed_by_name']): ?>
                            <span class="reviewed-by">
                                <i class="bi bi-person-check"></i>
                                Reviewed by: <?php echo htmlspecialchars($appointment['reviewed_by_name']); ?>
                                <?php if ($appointment['reviewed_at_formatted']): ?>
                                    <small>(<?php echo $appointment['reviewed_at_formatted']; ?>)</small>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($appointment['rejection_reason']): ?>
                            <div class="rejection-reason">
                                <strong><i class="bi bi-info-circle"></i> Rejection Reason:</strong>
                                <p><?php echo nl2br(htmlspecialchars($appointment['rejection_reason'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment Details Grid -->
        <div class="appointment-details-grid">
            <!-- Left Column - Appointment Info -->
            <div class="appointment-details-left">
                <div class="dashboard-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-calendar-event"></i>
                            Appointment Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Appointment #</span>
                                <span class="detail-value highlight"><?php echo htmlspecialchars($appointment['appointment_number']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?php echo $appointment['appointment_date_formatted']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time</span>
                                <span class="detail-value"><?php echo $appointment['appointment_time_formatted']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Duration</span>
                                <span class="detail-value"><?php echo $appointment['duration_minutes']; ?> minutes</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Purpose</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['purpose']); ?></span>
                            </div>
                            <?php if ($appointment['description']): ?>
                            <div class="detail-item full-width">
                                <span class="detail-label">Description</span>
                                <span class="detail-value"><?php echo nl2br(htmlspecialchars($appointment['description'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">Created</span>
                                <span class="detail-value"><?php echo $appointment['created_at_formatted']; ?></span>
                            </div>
                            <?php if ($appointment['status'] == 'approved' || $appointment['status'] == 'rejected'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Reviewed</span>
                                <span class="detail-value"><?php echo $appointment['reviewed_at_formatted']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Office Information -->
                <div class="dashboard-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-building"></i>
                            Office Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Office Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['office_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Office Code</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['office_code'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Type</span>
                                <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $appointment['office_type'] ?? 'N/A')); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Address</span>
                                <span class="detail-value">
                                    <?php 
                                    $office_address = [];
                                    if ($appointment['office_address']) $office_address[] = $appointment['office_address'];
                                    if ($appointment['office_city']) $office_address[] = $appointment['office_city'];
                                    if ($appointment['office_state']) $office_address[] = $appointment['office_state'];
                                    echo !empty($office_address) ? htmlspecialchars(implode(', ', $office_address)) : 'N/A';
                                    ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['office_phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['office_email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Operating Hours</span>
                                <span class="detail-value">
                                    <?php 
                                    if ($appointment['opening_time'] && $appointment['closing_time']) {
                                        echo date('h:i A', strtotime($appointment['opening_time'])) . ' - ' . date('h:i A', strtotime($appointment['closing_time']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Applicant Info & Actions -->
            <div class="appointment-details-right">
                <!-- Applicant Information -->
                <div class="dashboard-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-person"></i>
                            Your Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Full Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['applicant_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['applicant_email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['applicant_phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">NIN</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['applicant_nin'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date of Birth</span>
                                <span class="detail-value"><?php echo $appointment['applicant_dob'] ? date('M d, Y', strtotime($appointment['applicant_dob'])) : 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Gender</span>
                                <span class="detail-value"><?php echo ucfirst($appointment['applicant_gender'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">Address</span>
                                <span class="detail-value">
                                    <?php 
                                    $address = [];
                                    if ($appointment['applicant_address']) $address[] = $appointment['applicant_address'];
                                    if ($appointment['applicant_city']) $address[] = $appointment['applicant_city'];
                                    if ($appointment['applicant_state']) $address[] = $appointment['applicant_state'];
                                    echo !empty($address) ? htmlspecialchars(implode(', ', $address)) : 'N/A';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card" data-aos="fade-up" data-aos-delay="500">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-lightning"></i>
                            Quick Actions
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-vertical">
                            <?php if ($appointment['status'] == 'approved'): ?>
                                <a href="<?php echo BASE_URL; ?>/applicant/print-slip.php?id=<?php echo $appointment['id']; ?>" class="quick-action-btn" target="_blank">
                                    <i class="bi bi-printer"></i>
                                    <span>Print Appointment Slip</span>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($appointment['status'] == 'pending'): ?>
                                <button type="button" class="quick-action-btn btn-cancel" 
                                        data-id="<?php echo $appointment['id']; ?>"
                                        data-number="<?php echo $appointment['appointment_number']; ?>">
                                    <i class="bi bi-x-circle"></i>
                                    <span>Cancel Appointment</span>
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="quick-action-btn">
                                <i class="bi bi-plus-circle"></i>
                                <span>Book New Appointment</span>
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="quick-action-btn">
                                <i class="bi bi-list"></i>
                                <span>View All Appointments</span>
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/track-appointment.php" class="quick-action-btn">
                                <i class="bi bi-search"></i>
                                <span>Track Appointment Status</span>
                            </a>
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
<!-- VIEW APPOINTMENT SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // CANCEL APPOINTMENT
    // ============================================
    $('.btn-cancel').on('click', function() {
        const appointmentId = $(this).data('id');
        const appointmentNumber = $(this).data('number');
        const button = $(this);
        
        Swal.fire({
            title: 'Cancel Appointment?',
            text: 'Are you sure you want to cancel appointment ' + appointmentNumber + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel',
            cancelButtonText: 'No, keep it',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const originalIcon = button.find('i').attr('class');
                button.find('i').attr('class', 'bi bi-hourglass-split spinner');
                button.addClass('loading');
                button.prop('disabled', true);
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
                    type: 'POST',
                    data: {
                        action: 'cancel_appointment',
                        appointment_id: appointmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Cancel Response:', response);
                        
                        if (response.status === 'success') {
                            iziToast.success({
                                title: 'Success',
                                message: response.message,
                                position: 'topRight',
                                timeout: 2000
                            });
                            
                            setTimeout(function() {
                                window.location.href = '<?php echo BASE_URL; ?>/applicant/my-appointments.php';
                            }, 1500);
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Something went wrong',
                                position: 'topRight',
                                timeout: 3000
                            });
                            button.find('i').attr('class', originalIcon);
                            button.removeClass('loading');
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Cancel Error:', error);
                        console.error('Response Text:', xhr.responseText);
                        
                        iziToast.error({
                            title: 'Error',
                            message: 'Something went wrong. Please check console for details.',
                            position: 'topRight',
                            timeout: 3000
                        });
                        button.find('i').attr('class', originalIcon);
                        button.removeClass('loading');
                        button.prop('disabled', false);
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>