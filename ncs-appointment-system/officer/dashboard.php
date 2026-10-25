<?php
// =============================================
// OFFICER DASHBOARD - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is officer
if (!isLoggedIn() || !isOfficer()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Officer Dashboard';

// Include header
include_once __DIR__ . '/../includes/header.php';

// ============================================
// FETCH DASHBOARD STATISTICS
// ============================================

// Total Appointments
$total_query = "SELECT COUNT(*) as total FROM appointments";
$total_result = $conn->query($total_query);
$total_appointments = $total_result->fetch_assoc()['total'] ?? 0;

// Pending Appointments
$pending_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_appointments = $pending_result->fetch_assoc()['total'] ?? 0;

// Approved Appointments
$approved_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'approved'";
$approved_result = $conn->query($approved_query);
$approved_appointments = $approved_result->fetch_assoc()['total'] ?? 0;

// Rejected Appointments
$rejected_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'rejected'";
$rejected_result = $conn->query($rejected_query);
$rejected_appointments = $rejected_result->fetch_assoc()['total'] ?? 0;

// Today's Appointments
$today_query = "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()";
$today_result = $conn->query($today_query);
$today_appointments = $today_result->fetch_assoc()['total'] ?? 0;

// Total Offices
$offices_query = "SELECT COUNT(*) as total FROM offices WHERE is_active = 1";
$offices_result = $conn->query($offices_query);
$total_offices = $offices_result->fetch_assoc()['total'] ?? 0;

// ============================================
// FETCH RECENT APPOINTMENTS
// ============================================
$recent_query = "SELECT a.*, 
                  CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                  u.email as applicant_email,
                  u.phone as applicant_phone,
                  o.office_name,
                  o.office_code,
                  DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
                  DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
                  DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted
                  FROM appointments a
                  LEFT JOIN users u ON a.user_id = u.id
                  LEFT JOIN offices o ON a.office_id = o.id
                  ORDER BY a.created_at DESC 
                  LIMIT 10";
$recent_result = $conn->query($recent_query);

// ============================================
// FETCH PENDING APPOINTMENTS FOR QUICK ACTION
// ============================================
$pending_list_query = "SELECT a.*, 
                        CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                        u.email as applicant_email,
                        o.office_name,
                        o.office_code,
                        DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
                        DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted
                        FROM appointments a
                        LEFT JOIN users u ON a.user_id = u.id
                        LEFT JOIN offices o ON a.office_id = o.id
                        WHERE a.status = 'pending'
                        ORDER BY a.appointment_date ASC 
                        LIMIT 5";
$pending_list_result = $conn->query($pending_list_query);

// ============================================
// FETCH TODAY'S APPOINTMENTS
// ============================================
$today_list_query = "SELECT a.*, 
                      CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                      u.email as applicant_email,
                      u.phone as applicant_phone,
                      o.office_name,
                      o.office_code,
                      DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted
                      FROM appointments a
                      LEFT JOIN users u ON a.user_id = u.id
                      LEFT JOIN offices o ON a.office_id = o.id
                      WHERE a.appointment_date = CURDATE()
                      ORDER BY a.appointment_time ASC";
$today_list_result = $conn->query($today_list_query);

// ============================================
// STATUS COUNTS FOR CHART
// ============================================
$status_query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$status_result = $conn->query($status_query);
$status_counts = [];
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}

// ============================================
// MONTHLY DATA FOR CHART
// ============================================
$monthly_query = "SELECT DATE_FORMAT(appointment_date, '%b') as month, 
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                   FROM appointments 
                   WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                   GROUP BY MONTH(appointment_date)
                   ORDER BY appointment_date ASC";
$monthly_result = $conn->query($monthly_query);

$chart_months = [];
$chart_total = [];
$chart_approved = [];
$chart_pending = [];
$chart_rejected = [];

while ($row = $monthly_result->fetch_assoc()) {
    $chart_months[] = $row['month'];
    $chart_total[] = $row['total'];
    $chart_approved[] = $row['approved'];
    $chart_pending[] = $row['pending'];
    $chart_rejected[] = $row['rejected'];
}
?>

<!-- ============================================ -->
<!-- OFFICER DASHBOARD CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Officer Dashboard</h1>
                <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! Manage appointments and view statistics.</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/officer/view-appointments.php" class="btn-primary">
                    <i class="bi bi-calendar-check"></i>
                    View All Appointments
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon stat-icon-appointments">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_appointments); ?></span>
                    <span class="stat-label">Total Appointments</span>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span><?php echo $today_appointments; ?> today</span>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($pending_appointments); ?></span>
                    <span class="stat-label">Pending Approvals</span>
                </div>
                <div class="stat-change warning">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>Needs attention</span>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($approved_appointments); ?></span>
                    <span class="stat-label">Approved</span>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Completed</span>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
                <div class="stat-icon stat-icon-offices">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_offices); ?></span>
                    <span class="stat-label">Active Offices</span>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-check-circle"></i>
                    <span>Available</span>
                </div>
            </div>
        </div>

        <!-- Chart Row -->
        <div class="dashboard-row">
            <!-- Appointment Status Distribution -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="500">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-pie-chart"></i>
                        Appointment Status
                    </h3>
                </div>
                <div class="card-body">
                    <div class="status-distribution">
                        <div class="status-item">
                            <span class="status-label">Pending</span>
                            <div class="status-bar">
                                <div class="status-fill pending-fill" style="width: <?php echo $total_appointments > 0 ? ($pending_appointments / $total_appointments * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="status-count"><?php echo $pending_appointments; ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Approved</span>
                            <div class="status-bar">
                                <div class="status-fill approved-fill" style="width: <?php echo $total_appointments > 0 ? ($approved_appointments / $total_appointments * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="status-count"><?php echo $approved_appointments; ?></span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Rejected</span>
                            <div class="status-bar">
                                <div class="status-fill rejected-fill" style="width: <?php echo $total_appointments > 0 ? ($rejected_appointments / $total_appointments * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="status-count"><?php echo $rejected_appointments; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend Chart -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="600">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-graph-up"></i>
                        Monthly Trends
                    </h3>
                    <span class="card-badge">Last 6 months</span>
                </div>
                <div class="card-body">
                    <canvas id="appointmentChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Pending Appointments - Quick Action -->
        <?php if ($pending_list_result && $pending_list_result->num_rows > 0): ?>
        <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="700">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clock-history"></i>
                    Pending Appointments
                    <span class="badge-pending" style="margin-left: 8px;"><?php echo $pending_list_result->num_rows; ?></span>
                </h3>
                <a href="<?php echo BASE_URL; ?>/officer/pending.php" class="card-link">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-dashboard">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Office</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $pending_list_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="table-user">
                                            <span class="user-avatar-sm">
                                                <?php echo strtoupper(substr($appointment['applicant_name'] ?? 'U', 0, 1)); ?>
                                            </span>
                                            <span>
                                                <?php echo htmlspecialchars($appointment['applicant_name'] ?? 'Unknown'); ?>
                                                <div><small><?php echo htmlspecialchars($appointment['applicant_email'] ?? ''); ?></small></div>
                                            </span>
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
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action btn-approve" 
                                                    data-id="<?php echo $appointment['id']; ?>"
                                                    data-number="<?php echo $appointment['appointment_number']; ?>"
                                                    title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn-action btn-reject" 
                                                    data-id="<?php echo $appointment['id']; ?>"
                                                    data-number="<?php echo $appointment['appointment_number']; ?>"
                                                    title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>/officer/view-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                               class="btn-action btn-view" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Today's Appointments -->
        <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="800">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-calendar-today"></i>
                    Today's Appointments
                </h3>
                <span class="card-badge"><?php echo $today_appointments; ?> today</span>
            </div>
            <div class="card-body">
                <?php if ($today_list_result && $today_list_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table-dashboard">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Applicant</th>
                                    <th>Office</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $today_list_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $appointment['appointment_time_formatted']; ?></strong></td>
                                        <td>
                                            <span class="table-user">
                                                <span class="user-avatar-sm">
                                                    <?php echo strtoupper(substr($appointment['applicant_name'] ?? 'U', 0, 1)); ?>
                                                </span>
                                                <span>
                                                    <?php echo htmlspecialchars($appointment['applicant_name'] ?? 'Unknown'); ?>
                                                    <div><small><?php echo htmlspecialchars($appointment['applicant_phone'] ?? ''); ?></small></div>
                                                </span>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['office_name'] ?? 'N/A'); ?></td>
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
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                        No appointments scheduled for today
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card mb-4 quick-actions" data-aos="fade-up" data-aos-delay="900">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-lightning"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="<?php echo BASE_URL; ?>/officer/view-appointments.php" class="quick-action-item">
                        <i class="bi bi-calendar-event"></i>
                        <span>View All Appointments</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/officer/pending.php" class="quick-action-item">
                        <i class="bi bi-clock"></i>
                        <span>Pending Approvals</span>
                        <?php if ($pending_appointments > 0): ?>
                            <span class="badge-pending" style="font-size: 10px;"><?php echo $pending_appointments; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/officer/approved.php" class="quick-action-item">
                        <i class="bi bi-check-circle"></i>
                        <span>Approved Appointments</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/officer/rejected.php" class="quick-action-item">
                        <i class="bi bi-x-circle"></i>
                        <span>Rejected Appointments</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/officer/view-applicant.php" class="quick-action-item">
                        <i class="bi bi-person"></i>
                        <span>View Applicants</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/officer/profile.php" class="quick-action-item">
                        <i class="bi bi-person-gear"></i>
                        <span>My Profile</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- REJECTION REASON MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-x-circle" style="color: #dc3545;"></i></div>
                    <h5 class="modal-title">Reject Appointment</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" class="modal-form">
                <input type="hidden" id="reject_appointment_id" name="appointment_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Rejection Reason</label>
                        <textarea id="rejection_reason" name="rejection_reason" class="form-control" 
                                  rows="4" placeholder="Please provide a reason for rejecting this appointment..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="rejectBtn" class="btn btn-danger">
                        <span class="btn-text">Reject Appointment</span>
                        <span class="btn-loader" style="display:none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FOOTER -->
<!-- ============================================ -->
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<!-- ============================================ -->
<!-- OFFICER DASHBOARD SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // APPOINTMENT CHART - Chart.js
    // ============================================
    const ctx = document.getElementById('appointmentChart');
    
    if (ctx) {
        const chartData = {
            labels: <?php echo json_encode($chart_months); ?>,
            datasets: [
                {
                    label: 'Total',
                    data: <?php echo json_encode($chart_total); ?>,
                    borderColor: '#1a3a5c',
                    backgroundColor: 'rgba(26, 58, 92, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Approved',
                    data: <?php echo json_encode($chart_approved); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode($chart_pending); ?>,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Rejected',
                    data: <?php echo json_encode($chart_rejected); ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        };

        const chartConfig = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'var(--bg-card)',
                        titleColor: 'var(--text-primary)',
                        bodyColor: 'var(--text-secondary)',
                        borderColor: 'var(--border-color)',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: 'Inter, sans-serif',
                                size: 10
                            }
                        },
                        grid: {
                            color: 'var(--border-color)',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter, sans-serif',
                                size: 10
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        };

        const appointmentChart = new Chart(ctx, chartConfig);

        // Theme change observer
        const observer = new MutationObserver(function() {
            appointmentChart.update();
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }

    // ============================================
    // APPROVE APPOINTMENT
    // ============================================
    $('.btn-approve').on('click', function() {
        const appointmentId = $(this).data('id');
        const appointmentNumber = $(this).data('number');
        const button = $(this);
        
        Swal.fire({
            title: 'Approve Appointment?',
            text: 'Are you sure you want to approve appointment ' + appointmentNumber + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const originalIcon = button.find('i').attr('class');
                button.find('i').attr('class', 'bi bi-hourglass-split spinner');
                button.addClass('loading');
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
                    type: 'POST',
                    data: {
                        action: 'approve_appointment',
                        appointment_id: appointmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Approve Response:', response);
                        
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
                            button.find('i').attr('class', originalIcon);
                            button.removeClass('loading');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Approve Error:', error);
                        console.error('Response Text:', xhr.responseText);
                        
                        iziToast.error({
                            title: 'Error',
                            message: 'Something went wrong. Please check console for details.',
                            position: 'topRight',
                            timeout: 3000
                        });
                        button.find('i').attr('class', originalIcon);
                        button.removeClass('loading');
                    }
                });
            }
        });
    });

    // ============================================
    // REJECT APPOINTMENT - Show modal
    // ============================================
    let rejectAppointmentId = null;
    
    $('.btn-reject').on('click', function() {
        rejectAppointmentId = $(this).data('id');
        $('#reject_appointment_id').val(rejectAppointmentId);
        $('#rejection_reason').val('');
        $('#rejectModal').modal('show');
    });

    // Submit rejection
    $('#rejectForm').submit(function(e) {
        e.preventDefault();
        
        const reason = $('#rejection_reason').val().trim();
        
        if (!reason) {
            iziToast.error({
                title: 'Error',
                message: 'Please provide a reason for rejection',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        $('#rejectBtn').prop('disabled', true);
        $('#rejectBtn .btn-text').hide();
        $('#rejectBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
            type: 'POST',
            data: {
                action: 'reject_appointment',
                appointment_id: rejectAppointmentId,
                rejection_reason: reason
            },
            dataType: 'json',
            success: function(response) {
                console.log('Reject Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    $('#rejectModal').modal('hide');
                    
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
                
                $('#rejectBtn').prop('disabled', false);
                $('#rejectBtn .btn-text').show();
                $('#rejectBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Reject Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#rejectBtn').prop('disabled', false);
                $('#rejectBtn .btn-text').show();
                $('#rejectBtn .btn-loader').hide();
            }
        });
    });

    // Reset modal on close
    $('#rejectModal').on('hidden.bs.modal', function() {
        $('#rejectForm')[0].reset();
        $('#rejectBtn').prop('disabled', false);
        $('#rejectBtn .btn-text').show();
        $('#rejectBtn .btn-loader').hide();
    });
});
</script>
</body>
</html>