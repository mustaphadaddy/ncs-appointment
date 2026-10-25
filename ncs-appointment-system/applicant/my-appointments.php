<?php
// =============================================
// MY APPOINTMENTS - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is applicant
if (!isLoggedIn() || !isApplicant()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'My Appointments';

// Include header
include_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build WHERE clause
$where_clauses = ["user_id = ?"];
$params = [$user_id];
$types = "i";

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $where_clauses[] = "appointment_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_clauses[] = "appointment_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Count total appointments for pagination
$count_query = "SELECT COUNT(*) as total FROM appointments $where_sql";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_appointments = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_appointments / $limit);
$count_stmt->close();

// Fetch appointments with pagination
$query = "SELECT a.*, 
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
          LEFT JOIN offices o ON a.office_id = o.id
          LEFT JOIN users r ON a.reviewed_by = r.id
          $where_sql 
          ORDER BY a.created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments_result = $stmt->get_result();
$stmt->close();

// Get status counts for stats
$status_counts_query = "SELECT status, COUNT(*) as count FROM appointments WHERE user_id = ? GROUP BY status";
$stmt = $conn->prepare($status_counts_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$status_counts_result = $stmt->get_result();
$status_stats = [];
while ($row = $status_counts_result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
$stmt->close();

// Get total counts
$total_query = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ?";
$stmt = $conn->prepare($total_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$pending_count = $status_stats['pending'] ?? 0;
$approved_count = $status_stats['approved'] ?? 0;
$rejected_count = $status_stats['rejected'] ?? 0;
$completed_count = $status_stats['completed'] ?? 0;
$cancelled_count = $status_stats['cancelled'] ?? 0;
$no_show_count = $status_stats['no_show'] ?? 0;
?>

<!-- ============================================ -->
<!-- MY APPOINTMENTS CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">My Appointments</h1>
                <p class="dashboard-subtitle">View and manage all your appointment requests</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="btn-primary">
                    <i class="bi bi-plus-circle"></i>
                    Book New Appointment
                </a>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid stats-grid-sm">
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-appointments">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_count); ?></span>
                    <span class="stat-label">Total</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #ffc107;">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($pending_count); ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #28a745;">
                <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($approved_count); ?></span>
                    <span class="stat-label">Approved</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #dc3545;">
                <div class="stat-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($rejected_count); ?></span>
                    <span class="stat-label">Rejected</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form id="filterForm" class="filters-form">
                <div class="filter-group">
                    <select name="status" id="statusFilter" class="form-control filter-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="no_show" <?php echo $status_filter == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                <div class="filter-group">
                    <input type="date" name="date_from" id="dateFrom" class="form-control" 
                           placeholder="Date From" value="<?php echo $date_from; ?>">
                </div>
                <div class="filter-group">
                    <input type="date" name="date_to" id="dateTo" class="form-control" 
                           placeholder="Date To" value="<?php echo $date_to; ?>">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="btn-filter-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-table"></i>
                    Appointments List
                </h3>
                <span class="card-badge"><?php echo $total_appointments; ?> records</span>
            </div>
            <div class="card-body">
                <?php if ($appointments_result && $appointments_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table-dashboard">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Appointment #</th>
                                    <th>Office</th>
                                    <th>Date & Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = $offset + 1;
                                while ($appointment = $appointments_result->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
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
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($appointment['purpose']); ?></div>
                                                <?php if ($appointment['description']): ?>
                                                    <small><?php echo htmlspecialchars(substr($appointment['description'], 0, 30)) . (strlen($appointment['description']) > 30 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="status-wrapper">
                                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                    <i class="bi bi-<?php 
                                                        echo $appointment['status'] == 'pending' ? 'clock' : 
                                                             ($appointment['status'] == 'approved' ? 'check-circle' : 
                                                             ($appointment['status'] == 'rejected' ? 'x-circle' : 
                                                             ($appointment['status'] == 'completed' ? 'check-circle-fill' : 
                                                             ($appointment['status'] == 'cancelled' ? 'slash-circle' : 'exclamation-circle')))); 
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
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/applicant/view-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                   class="btn-action btn-view" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                <?php if ($appointment['status'] == 'pending'): ?>
                                                    <button type="button" class="btn-action btn-cancel" 
                                                            data-id="<?php echo $appointment['id']; ?>"
                                                            data-number="<?php echo $appointment['appointment_number']; ?>"
                                                            title="Cancel Appointment">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($appointment['status'] == 'approved'): ?>
                                                    <a href="<?php echo BASE_URL; ?>/applicant/print-slip.php?id=<?php echo $appointment['id']; ?>" 
                                                       class="btn-action btn-print" target="_blank" title="Print Slip">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <!-- Previous -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                           aria-label="Previous">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next -->
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                           aria-label="Next">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <div class="pagination-info">
                                Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_appointments); ?> of <?php echo $total_appointments; ?> appointments
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar"></i>
                        </div>
                        <h3>No Appointments Yet</h3>
                        <p>You haven't booked any appointments yet. Schedule your first appointment now.</p>
                        <a href="<?php echo BASE_URL; ?>/applicant/apply.php" class="btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            Book Your First Appointment
                        </a>
                    </div>
                <?php endif; ?>
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
<!-- MY APPOINTMENTS SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // FILTER FORM - Auto submit on change
    // ============================================
    $('#statusFilter').on('change', function() {
        $('#filterForm').submit();
    });

    // ============================================
    // VIEW REJECTION REASON
    // ============================================
    $('.btn-view-reason').on('click', function() {
        const reason = $(this).data('reason');
        $('#reasonContent').html('<p>' + reason + '</p>');
        $('#reasonModal').modal('show');
    });

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
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>