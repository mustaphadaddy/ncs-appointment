<?php
// =============================================
// TRACK APPOINTMENT - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Track Appointment';

// Include header
include_once __DIR__ . '/includes/header.php';

// Check if tracking number is provided
$tracking_number = isset($_GET['number']) ? trim($_GET['number']) : '';
$appointment = null;
$not_found = false;

if (!empty($tracking_number)) {
    // Fetch appointment details
    $query = "SELECT a.*, 
              CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
              u.email as applicant_email,
              u.phone as applicant_phone,
              o.office_name,
              o.office_code,
              o.address as office_address,
              o.city as office_city,
              o.state as office_state,
              o.phone as office_phone,
              CONCAT(r.first_name, ' ', r.last_name) as reviewed_by_name,
              DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
              DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
              DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted,
              DATE_FORMAT(a.reviewed_at, '%b %d, %Y %h:%i %p') as reviewed_at_formatted
              FROM appointments a
              LEFT JOIN users u ON a.user_id = u.id
              LEFT JOIN offices o ON a.office_id = o.id
              LEFT JOIN users r ON a.reviewed_by = r.id
              WHERE a.appointment_number = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $tracking_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
    } else {
        $not_found = true;
    }
    $stmt->close();
}

// Get status icon and color
function getStatusInfo($status) {
    $info = [
        'pending' => ['icon' => 'clock', 'color' => '#ffc107', 'bg' => 'rgba(255, 193, 7, 0.1)', 'label' => 'Pending'],
        'approved' => ['icon' => 'check-circle', 'color' => '#28a745', 'bg' => 'rgba(40, 167, 69, 0.1)', 'label' => 'Approved'],
        'rejected' => ['icon' => 'x-circle', 'color' => '#dc3545', 'bg' => 'rgba(220, 53, 69, 0.1)', 'label' => 'Rejected'],
        'completed' => ['icon' => 'check-circle-fill', 'color' => '#17a2b8', 'bg' => 'rgba(23, 162, 184, 0.1)', 'label' => 'Completed'],
        'cancelled' => ['icon' => 'slash-circle', 'color' => '#6c757d', 'bg' => 'rgba(108, 117, 125, 0.1)', 'label' => 'Cancelled'],
        'no_show' => ['icon' => 'exclamation-circle', 'color' => '#dc3545', 'bg' => 'rgba(220, 53, 69, 0.1)', 'label' => 'No Show']
    ];
    return $info[$status] ?? $info['pending'];
}
?>

<!-- ============================================ -->
<!-- TRACK APPOINTMENT CONTENT -->
<!-- ============================================ -->
<section class="track-section">
    <div class="container">
        <!-- Page Header -->
        <div class="track-header" data-aos="fade-up" data-aos-delay="100">
            <div class="track-header-content">
                <h1 class="track-title">
                    <i class="bi bi-search"></i>
                    Track Appointment
                </h1>
                <p class="track-subtitle">Enter your appointment number to check the status</p>
            </div>
        </div>

        <!-- Search Form -->
        <div class="track-search" data-aos="fade-up" data-aos-delay="200">
            <form id="trackForm" method="GET" action="">
                <div class="search-wrapper">
                    <div class="search-input-group">
                        <div class="search-icon">
                            <i class="bi bi-ticket"></i>
                        </div>
                        <input type="text" name="number" id="trackingNumber" class="search-input" 
                               placeholder="Enter your appointment number (e.g., NCS-2026-00001)"
                               value="<?php echo htmlspecialchars($tracking_number); ?>"
                               required>
                        <button type="submit" class="search-btn">
                            <i class="bi bi-search"></i>
                            Track
                        </button>
                    </div>
                    <div class="search-hint">
                        <i class="bi bi-info-circle"></i>
                        Enter the appointment number you received after booking
                    </div>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if (!empty($tracking_number)): ?>
            <?php if ($appointment): ?>
                <!-- Appointment Found -->
                <div class="track-result" data-aos="fade-up" data-aos-delay="300">
                    <!-- Status Header -->
                    <?php $statusInfo = getStatusInfo($appointment['status']); ?>
                    <div class="status-header" style="border-left-color: <?php echo $statusInfo['color']; ?>;">
                        <div class="status-icon" style="background: <?php echo $statusInfo['bg']; ?>; color: <?php echo $statusInfo['color']; ?>;">
                            <i class="bi bi-<?php echo $statusInfo['icon']; ?>"></i>
                        </div>
                        <div class="status-text">
                            <span class="status-label">Current Status</span>
                            <span class="status-badge status-<?php echo $appointment['status']; ?>" style="font-size: 18px; padding: 8px 24px;">
                                <i class="bi bi-<?php echo $statusInfo['icon']; ?>"></i>
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                            <?php if ($appointment['status'] == 'rejected' && $appointment['rejection_reason']): ?>
                                <div class="rejection-reason">
                                    <strong><i class="bi bi-info-circle"></i> Reason:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($appointment['rejection_reason'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Appointment Details -->
                    <div class="track-details-grid">
                        <!-- Left Column -->
                        <div class="track-details-left">
                            <div class="track-card">
                                <h3 class="track-card-title">
                                    <i class="bi bi-calendar-event"></i>
                                    Appointment Information
                                </h3>
                                <div class="track-detail-list">
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Appointment Number</span>
                                        <span class="track-detail-value highlight"><?php echo htmlspecialchars($appointment['appointment_number']); ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Date</span>
                                        <span class="track-detail-value"><?php echo $appointment['appointment_date_formatted']; ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Time</span>
                                        <span class="track-detail-value"><?php echo $appointment['appointment_time_formatted']; ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Duration</span>
                                        <span class="track-detail-value"><?php echo $appointment['duration_minutes']; ?> minutes</span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Purpose</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['purpose']); ?></span>
                                    </div>
                                    <?php if ($appointment['description']): ?>
                                    <div class="track-detail-item full-width">
                                        <span class="track-detail-label">Description</span>
                                        <span class="track-detail-value"><?php echo nl2br(htmlspecialchars($appointment['description'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="track-details-right">
                            <!-- Office Information -->
                            <div class="track-card">
                                <h3 class="track-card-title">
                                    <i class="bi bi-building"></i>
                                    Office Information
                                </h3>
                                <div class="track-detail-list">
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Office Name</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['office_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Office Code</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['office_code'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Address</span>
                                        <span class="track-detail-value">
                                            <?php 
                                            $office_address = [];
                                            if ($appointment['office_address']) $office_address[] = $appointment['office_address'];
                                            if ($appointment['office_city']) $office_address[] = $appointment['office_city'];
                                            if ($appointment['office_state']) $office_address[] = $appointment['office_state'];
                                            echo !empty($office_address) ? htmlspecialchars(implode(', ', $office_address)) : 'N/A';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Phone</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['office_phone'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Applicant Information -->
                            <div class="track-card">
                                <h3 class="track-card-title">
                                    <i class="bi bi-person"></i>
                                    Applicant Information
                                </h3>
                                <div class="track-detail-list">
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Name</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['applicant_name'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Email</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['applicant_email'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Phone</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['applicant_phone'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Information -->
                            <?php if ($appointment['reviewed_by_name']): ?>
                            <div class="track-card">
                                <h3 class="track-card-title">
                                    <i class="bi bi-check-circle"></i>
                                    Review Information
                                </h3>
                                <div class="track-detail-list">
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Reviewed By</span>
                                        <span class="track-detail-value"><?php echo htmlspecialchars($appointment['reviewed_by_name']); ?></span>
                                    </div>
                                    <div class="track-detail-item">
                                        <span class="track-detail-label">Reviewed On</span>
                                        <span class="track-detail-value"><?php echo $appointment['reviewed_at_formatted']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="track-actions">
                        <?php if ($appointment['status'] == 'approved'): ?>
                            <a href="<?php echo BASE_URL; ?>/applicant/print-slip.php?id=<?php echo $appointment['id']; ?>" class="btn-primary" target="_blank">
                                <i class="bi bi-printer"></i>
                                Print Slip
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/login.php" class="btn-secondary">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Login to Manage
                        </a>
                        <button onclick="window.location.reload();" class="btn-secondary">
                            <i class="bi bi-search"></i>
                            Track Another
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not Found -->
                <div class="track-not-found" data-aos="fade-up" data-aos-delay="300">
                    <div class="not-found-icon">
                        <i class="bi bi-ticket"></i>
                    </div>
                    <h3>Appointment Not Found</h3>
                    <p>We couldn't find an appointment with the number <strong>"<?php echo htmlspecialchars($tracking_number); ?>"</strong></p>
                    <ul class="not-found-tips">
                        <li><i class="bi bi-check-circle"></i> Check that you entered the correct appointment number</li>
                        <li><i class="bi bi-check-circle"></i> The appointment number format is NCS-YYYY-XXXXX (e.g., NCS-2026-00001)</li>
                        <li><i class="bi bi-check-circle"></i> If you just booked, please wait a few minutes for the system to process</li>
                    </ul>
                    <div class="not-found-actions">
                        <button onclick="document.getElementById('trackingNumber').focus();" class="btn-primary">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Try Again
                        </button>
                        <a href="<?php echo BASE_URL; ?>/apply-appointment.php" class="btn-secondary">
                            <i class="bi bi-calendar-plus"></i>
                            Book New Appointment
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Features / Tips -->
        <div class="track-features" data-aos="fade-up" data-aos-delay="400">
            <div class="track-features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h4>Real-time Status</h4>
                    <p>Check your appointment status anytime, anywhere</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4>Secure & Private</h4>
                    <p>Only you can view your appointment details</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-printer"></i>
                    </div>
                    <h4>Print Slip</h4>
                    <p>Print your appointment confirmation slip</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <h4>Stay Updated</h4>
                    <p>Get the latest status of your appointment</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/includes/footer.php'; ?>

<!-- ============================================ -->
<!-- TRACK APPOINTMENT SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // AUTO-FOCUS on tracking input
    // ============================================
    <?php if (empty($tracking_number)): ?>
        $('#trackingNumber').focus();
    <?php endif; ?>

    // ============================================
    // TRACK FORM - Enter key support
    // ============================================
    $('#trackingNumber').on('keypress', function(e) {
        if (e.which === 13) {
            $('#trackForm').submit();
        }
    });

    // ============================================
    // TRACKING NUMBER FORMAT HELP
    // ============================================
    $('#trackingNumber').on('blur', function() {
        const value = $(this).val().trim();
        if (value && !value.match(/^NCS-\d{4}-\d{5}$/i)) {
            iziToast.warning({
                title: 'Format Hint',
                message: 'Appointment number format: NCS-YYYY-XXXXX (e.g., NCS-2026-00001)',
                position: 'topRight',
                timeout: 5000
            });
        }
    });
});
</script>
</body>
</html>