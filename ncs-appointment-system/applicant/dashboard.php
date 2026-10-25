<?php
// =============================================
// APPLICANT DASHBOARD - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is applicant
if (!isLoggedIn() || !isApplicant()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Applicant Dashboard';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get user details
$user_id = $_SESSION['user_id'];

// ============================================
// FETCH DASHBOARD STATISTICS
// ============================================

// Total Appointments
$total_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_appointments = $total_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending Appointments
$pending_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
$pending_appointments = $pending_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Approved Appointments
$approved_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'approved'";
$stmt = $conn->prepare($approved_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approved_result = $stmt->get_result();
$approved_appointments = $approved_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Rejected Appointments
$rejected_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'rejected'";
$stmt = $conn->prepare($rejected_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rejected_result = $stmt->get_result();
$rejected_appointments = $rejected_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Completed Appointments
$completed_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ? AND status = 'completed'";
$stmt = $conn->prepare($completed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();
$completed_appointments = $completed_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Upcoming Appointments (next 7 days)
$upcoming_query = "SELECT COUNT(*) as total FROM appointments 
                   WHERE user_id = ? 
                   AND status IN ('pending', 'approved') 
                   AND appointment_date >= CURDATE() 
                   AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_result = $stmt->get_result();
$upcoming_appointments = $upcoming_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// ============================================
// FETCH RECENT APPOINTMENTS
// ============================================
$recent_query = "SELECT a.*, 
                  o.office_name,
                  o.office_code,
                  DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
                  DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
                  DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted
                  FROM appointments a
                  LEFT JOIN offices o ON a.office_id = o.id
                  WHERE a.user_id = ?
                  ORDER BY a.created_at DESC 
                  LIMIT 5";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_result = $stmt->get_result();
$stmt->close();

// ============================================
// FETCH UPCOMING APPOINTMENTS
// ============================================
$upcoming_list_query = "SELECT a.*, 
                         o.office_name,
                         o.office_code,
                         DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
                         DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted
                         FROM appointments a
                         LEFT JOIN offices o ON a.office_id = o.id
                         WHERE a.user_id = ? 
                         AND a.status IN ('pending', 'approved')
                         AND a.appointment_date >= CURDATE()
                         ORDER BY a.appointment_date ASC, a.appointment_time ASC
                         LIMIT 5";
$stmt = $conn->prepare($upcoming_list_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_list_result = $stmt->get_result();
$stmt->close();

// Get user initials for avatar
$initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
?>

<!-- ============================================ -->
<!-- APPLICANT DASHBOARD CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">My Dashboard</h1>
                <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! Manage your appointments and track your requests.</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="btn-primary">
                    <i class="bi bi-plus-circle"></i>
                    Book New Appointment
                </a>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card" data-aos="fade-up" data-aos-delay="100">
            <div class="welcome-content">
                <div class="welcome-avatar">
                    <div class="user-avatar-lg">
                        <?php echo $initials; ?>
                    </div>
                    <div class="welcome-status">
                        <span class="status-badge status-active">
                            <i class="bi bi-check-circle-fill"></i>
                            Active
                        </span>
                    </div>
                </div>
                <div class="welcome-text">
                    <h2>Hello, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h2>
                    <p>You have <strong><?php echo $pending_appointments; ?></strong> pending appointment<?php echo $pending_appointments != 1 ? 's' : ''; ?> waiting for approval.</p>
                    <div class="welcome-actions">
                        <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Book Appointment
                        </a>
                        <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="btn-sm btn-secondary">
                            <i class="bi bi-list"></i> View All
                        </a>
                    </div>
                </div>
                <div class="welcome-quick-stats">
                    <div class="quick-stat">
                        <span class="quick-stat-number"><?php echo $total_appointments; ?></span>
                        <span class="quick-stat-label">Total</span>
                    </div>
                    <div class="quick-stat">
                        <span class="quick-stat-number"><?php echo $upcoming_appointments; ?></span>
                        <span class="quick-stat-label">Upcoming</span>
                    </div>
                    <div class="quick-stat">
                        <span class="quick-stat-number"><?php echo $approved_appointments; ?></span>
                        <span class="quick-stat-label">Approved</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon stat-icon-appointments">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_appointments); ?></span>
                    <span class="stat-label">Total Appointments</span>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>All time</span>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($pending_appointments); ?></span>
                    <span class="stat-label">Pending</span>
                </div>
                <?php if ($pending_appointments > 0): ?>
                    <div class="stat-change warning">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>Waiting for approval</span>
                    </div>
                <?php else: ?>
                    <div class="stat-change positive">
                        <i class="bi bi-check-circle"></i>
                        <span>All clear</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($approved_appointments); ?></span>
                    <span class="stat-label">Approved</span>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Confirmed</span>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="500">
                <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($rejected_appointments); ?></span>
                    <span class="stat-label">Rejected</span>
                </div>
                <div class="stat-change negative">
                    <i class="bi bi-x-circle-fill"></i>
                    <span>Not approved</span>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="dashboard-row" style="grid-template-columns: 1fr;">
            <!-- Upcoming Appointments -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="600">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-calendar-event"></i>
                        Upcoming Appointments
                    </h3>
                    <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($upcoming_list_result && $upcoming_list_result->num_rows > 0): ?>
                        <div class="upcoming-list">
                            <?php while ($appointment = $upcoming_list_result->fetch_assoc()): ?>
                                <div class="upcoming-item">
                                    <div class="upcoming-date">
                                        <span class="upcoming-day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></span>
                                        <span class="upcoming-month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></span>
                                    </div>
                                    <div class="upcoming-info">
                                        <div class="upcoming-title"><?php echo htmlspecialchars($appointment['purpose']); ?></div>
                                        <div class="upcoming-details">
                                            <span><i class="bi bi-building"></i> <?php echo htmlspecialchars($appointment['office_name']); ?></span>
                                            <span><i class="bi bi-clock"></i> <?php echo $appointment['appointment_time_formatted']; ?></span>
                                        </div>
                                    </div>
                                    <div class="upcoming-status">
                                        <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                            <i class="bi bi-<?php echo $appointment['status'] == 'pending' ? 'clock' : 'check-circle'; ?>"></i>
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class="bi bi-calendar"></i>
                            <p>No upcoming appointments</p>
                            <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="btn-sm btn-primary">
                                <i class="bi bi-plus-circle"></i> Book Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="700">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-lightning"></i>
                        Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="quick-action-item">
                            <i class="bi bi-plus-circle"></i>
                            <span>Book Appointment</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="quick-action-item">
                            <i class="bi bi-calendar-check"></i>
                            <span>My Appointments</span>
                            <?php if ($pending_appointments > 0): ?>
                                <span class="badge-pending" style="font-size: 10px;"><?php echo $pending_appointments; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/applicant/upload-document.php" class="quick-action-item">
                            <i class="bi bi-cloud-upload"></i>
                            <span>Upload Document</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/applicant/print-slip.php" class="quick-action-item">
                            <i class="bi bi-printer"></i>
                            <span>Print Slip</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/track-appointment.php" class="quick-action-item">
                            <i class="bi bi-search"></i>
                            <span>Track Appointment</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/applicant/profile.php" class="quick-action-item">
                            <i class="bi bi-person-gear"></i>
                            <span>My Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="800">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clock-history"></i>
                    Recent Appointments
                </h3>
                <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="card-link">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table-dashboard">
                            <thead>
                                <tr>
                                    <th>Appointment #</th>
                                    <th>Office</th>
                                    <th>Date & Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $recent_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="appointment-number">
                                                <strong><?php echo htmlspecialchars($appointment['appointment_number']); ?></strong>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($appointment['office_name'] ?? 'N/A'); ?></div>
                                                <small><?php echo htmlspecialchars($appointment['office_code'] ?? ''); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo $appointment['appointment_date_formatted']; ?></div>
                                                <small><?php echo $appointment['appointment_time_formatted']; ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <i class="bi bi-<?php 
                                                    echo $appointment['status'] == 'pending' ? 'clock' : 
                                                         ($appointment['status'] == 'approved' ? 'check-circle' : 
                                                         ($appointment['status'] == 'rejected' ? 'x-circle' : 
                                                         ($appointment['status'] == 'completed' ? 'check-circle-fill' : 'slash-circle'))); 
                                                ?>"></i>
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                            <?php if ($appointment['status'] == 'rejected' && $appointment['rejection_reason']): ?>
                                                <button type="button" class="btn-view-reason" 
                                                        data-reason="<?php echo htmlspecialchars($appointment['rejection_reason']); ?>"
                                                        title="View Rejection Reason">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state-small">
                        <i class="bi bi-inbox"></i>
                        <p>No appointments yet</p>
                        <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Book Your First Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Appointment Status Tips -->
        <div class="dashboard-card tips-card mt-4" data-aos="fade-up" data-aos-delay="900">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-info-circle"></i>
                    Appointment Status Guide
                </h3>
            </div>
            <div class="card-body">
                <div class="tips-grid">
                    <div class="tip-item">
                        <span class="status-badge status-pending">
                            <i class="bi bi-clock"></i> Pending
                        </span>
                        <p>Your appointment is waiting for review by an officer.</p>
                    </div>
                    <div class="tip-item">
                        <span class="status-badge status-approved">
                            <i class="bi bi-check-circle"></i> Approved
                        </span>
                        <p>Your appointment has been confirmed. Please arrive on time.</p>
                    </div>
                    <div class="tip-item">
                        <span class="status-badge status-rejected">
                            <i class="bi bi-x-circle"></i> Rejected
                        </span>
                        <p>Your appointment was not approved. Check the reason provided.</p>
                    </div>
                    <div class="tip-item">
                        <span class="status-badge status-completed">
                            <i class="bi bi-check-circle-fill"></i> Completed
                        </span>
                        <p>Your appointment has been successfully completed.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- REJECTION REASON MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-info-circle" style="color: #dc3545;"></i></div>
                    <h5 class="modal-title">Rejection Reason</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reasonContent" class="reason-content">
                    <p class="text-muted">No reason provided</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<!-- ============================================ -->
<!-- APPLICANT DASHBOARD SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // VIEW REJECTION REASON
    // ============================================
    $('.btn-view-reason').on('click', function() {
        const reason = $(this).data('reason');
        $('#reasonContent').html('<p>' + reason + '</p>');
        $('#reasonModal').modal('show');
    });
});
</script>
</body>
</html>