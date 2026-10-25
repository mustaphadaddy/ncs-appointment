<?php
// =============================================
// MANAGE APPOINTMENTS - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Manage Appointments';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(a.appointment_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($status_filter)) {
    $where_clauses[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $where_clauses[] = "a.appointment_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_clauses[] = "a.appointment_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total appointments for pagination
$count_query = "SELECT COUNT(*) as total FROM appointments a 
                LEFT JOIN users u ON a.user_id = u.id 
                $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_appointments = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_appointments / $limit);
$count_stmt->close();

// Fetch appointments with pagination
$query = "SELECT a.*, 
          CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
          u.email as applicant_email,
          u.phone as applicant_phone,
          o.office_name,
          o.office_code,
          CONCAT(r.first_name, ' ', r.last_name) as reviewed_by_name,
          DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
          DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
          DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted
          FROM appointments a
          LEFT JOIN users u ON a.user_id = u.id
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
$status_counts_query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$status_counts_result = $conn->query($status_counts_query);
$status_stats = [];
while ($row = $status_counts_result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}

// Get total appointments
$total_query = "SELECT COUNT(*) as total FROM appointments";
$total_count = $conn->query($total_query)->fetch_assoc()['total'] ?? 0;

// Get today's appointments
$today_query = "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()";
$today_count = $conn->query($today_query)->fetch_assoc()['total'] ?? 0;
?>

<!-- ============================================ -->
<!-- MANAGE APPOINTMENTS CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Manage Appointments</h1>
                <p class="dashboard-subtitle">View and manage all appointment requests across all offices</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/admin/reports.php?type=appointments" class="btn-secondary">
                    <i class="bi bi-file-earmark-text"></i>
                    Export Report
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
                    <span class="stat-label">Total Appointments</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($status_stats['pending'] ?? 0); ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-users">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($status_stats['approved'] ?? 0); ?></span>
                    <span class="stat-label">Approved</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-offices">
                    <i class="bi bi-calendar-date"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($today_count); ?></span>
                    <span class="stat-label">Today's Appointments</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form id="filterForm" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" id="searchInput" class="form-control filter-search" 
                           placeholder="Search by number, name, or email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
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
                <a href="<?php echo BASE_URL; ?>/admin/manage-appointments.php" class="btn-filter-reset">
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
                <span class="card-badge"><?php echo $total_appointments; ?> total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-dashboard">
                        <thead>
                            <tr>
                                <th>Appointment #</th>
                                <th>Applicant</th>
                                <th>Office</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments_result && $appointments_result->num_rows > 0): ?>
                                <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="appointment-number">
                                                <strong><?php echo htmlspecialchars($appointment['appointment_number']); ?></strong>
                                            </span>
                                        </td>
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
                                        <td>
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
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- View Details -->
                                                <a href="<?php echo BASE_URL; ?>/admin/view-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                   class="btn-action btn-view" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                <!-- Approve Button -->
                                                <?php if ($appointment['status'] == 'pending'): ?>
                                                    <button type="button" class="btn-action btn-approve" 
                                                            data-id="<?php echo $appointment['id']; ?>"
                                                            data-number="<?php echo $appointment['appointment_number']; ?>"
                                                            data-name="<?php echo htmlspecialchars($appointment['applicant_name'] ?? 'Unknown'); ?>"
                                                            title="Approve Appointment">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Reject Button -->
                                                <?php if ($appointment['status'] == 'pending'): ?>
                                                    <button type="button" class="btn-action btn-reject" 
                                                            data-id="<?php echo $appointment['id']; ?>"
                                                            data-number="<?php echo $appointment['appointment_number']; ?>"
                                                            data-name="<?php echo htmlspecialchars($appointment['applicant_name'] ?? 'Unknown'); ?>"
                                                            title="Reject Appointment">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Button -->
                                                <button type="button" class="btn-action btn-delete" 
                                                        data-id="<?php echo $appointment['id']; ?>"
                                                        data-number="<?php echo $appointment['appointment_number']; ?>"
                                                        title="Delete Appointment">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                        No appointments found
                                    </td>
                                </tr>
                            <?php endif; ?>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                       aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
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
<!-- MANAGE APPOINTMENTS SCRIPTS -->
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
    // APPROVE APPOINTMENT
    // ============================================
    $('.btn-approve').on('click', function() {
        const appointmentId = $(this).data('id');
        const appointmentNumber = $(this).data('number');
        const applicantName = $(this).data('name');
        const button = $(this);
        
        Swal.fire({
            title: 'Approve Appointment?',
            html: `Are you sure you want to approve <strong>${appointmentNumber}</strong> for <strong>${applicantName}</strong>?`,
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
    // REJECT APPOINTMENT - Show modal for reason
    // ============================================
    let rejectAppointmentId = null;
    let rejectButton = null;
    
    $('.btn-reject').on('click', function() {
        rejectAppointmentId = $(this).data('id');
        rejectButton = $(this);
        const appointmentNumber = $(this).data('number');
        const applicantName = $(this).data('name');
        
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
        
        // Show loading
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

    // ============================================
    // DELETE APPOINTMENT
    // ============================================
    $('.btn-delete').on('click', function() {
        const appointmentId = $(this).data('id');
        const appointmentNumber = $(this).data('number');
        const button = $(this);
        
        Swal.fire({
            title: 'Delete Appointment?',
            html: `Are you sure you want to delete <strong>${appointmentNumber}</strong>?<br><small style="color: #dc3545;">This action cannot be undone!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
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
                        action: 'delete_appointment',
                        appointment_id: appointmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Delete Response:', response);
                        
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
                        console.error('Delete Error:', error);
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