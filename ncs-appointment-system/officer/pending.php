<?php
// =============================================
// PENDING APPOINTMENTS - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is officer
if (!isLoggedIn() || !isOfficer()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Pending Appointments';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build WHERE clause
$where_clauses = ["a.status = 'pending'"];
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

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Count total pending appointments for pagination
$count_query = "SELECT COUNT(*) as total FROM appointments a 
                LEFT JOIN users u ON a.user_id = u.id 
                $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_pending = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_pending / $limit);
$count_stmt->close();

// Fetch pending appointments with pagination
$query = "SELECT a.*, 
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
          $where_sql 
          ORDER BY a.appointment_date ASC, a.appointment_time ASC
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$pending_result = $stmt->get_result();
$stmt->close();

// Get pending stats
$pending_count_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pending_count = $conn->query($pending_count_query)->fetch_assoc()['total'] ?? 0;

// Get today's pending
$today_pending_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending' AND appointment_date = CURDATE()";
$today_pending = $conn->query($today_pending_query)->fetch_assoc()['total'] ?? 0;

// Get this week's pending
$week_pending_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending' AND YEARWEEK(appointment_date) = YEARWEEK(CURDATE())";
$week_pending = $conn->query($week_pending_query)->fetch_assoc()['total'] ?? 0;
?>

<!-- ============================================ -->
<!-- PENDING APPOINTMENTS CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Pending Appointments</h1>
                <p class="dashboard-subtitle">Review and process pending appointment requests</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/officer/dashboard.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button type="button" class="btn-primary" onclick="window.location.reload();">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid stats-grid-sm">
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #ffc107;">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($pending_count); ?></span>
                    <span class="stat-label">Total Pending</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #28a745;">
                <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="bi bi-calendar-today"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($today_pending); ?></span>
                    <span class="stat-label">Today</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #0d6efd;">
                <div class="stat-icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($week_pending); ?></span>
                    <span class="stat-label">This Week</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm" style="border-left: 4px solid #c9a84c;">
                <div class="stat-icon" style="background: rgba(201, 168, 76, 0.1); color: #c9a84c;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_pending); ?></span>
                    <span class="stat-label">Needs Review</span>
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
                <a href="<?php echo BASE_URL; ?>/officer/pending.php" class="btn-filter-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- Pending Appointments Table -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-clock-history"></i>
                    Pending Appointments
                    <span class="badge-pending" style="margin-left: 8px; font-size: 12px;"><?php echo $total_pending; ?></span>
                </h3>
                <span class="card-badge"><?php echo $total_pending; ?> waiting</span>
            </div>
            <div class="card-body">
                <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table-dashboard">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Appointment #</th>
                                    <th>Applicant</th>
                                    <th>Office</th>
                                    <th>Date & Time</th>
                                    <th>Purpose</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = $offset + 1;
                                while ($appointment = $pending_result->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
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
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($appointment['purpose']); ?></div>
                                                <?php if ($appointment['description']): ?>
                                                    <small><?php echo htmlspecialchars(substr($appointment['description'], 0, 50)) . (strlen($appointment['description']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/officer/view-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                   class="btn-action btn-view" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
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
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                           aria-label="Previous">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php 
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next -->
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                           aria-label="Next">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <div class="pagination-info">
                                Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_pending); ?> of <?php echo $total_pending; ?> pending appointments
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3>All Caught Up!</h3>
                        <p>There are no pending appointments to review.</p>
                        <a href="<?php echo BASE_URL; ?>/officer/dashboard.php" class="btn-primary">
                            <i class="bi bi-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bulk Actions -->
        <?php if ($pending_result && $pending_result->num_rows > 0): ?>
            <div class="dashboard-card bulk-actions-card">
                <div class="card-body">
                    <div class="bulk-actions">
                        <span class="bulk-label"><i class="bi bi-lightning"></i> Bulk Actions</span>
                        <button type="button" id="bulkApproveBtn" class="btn-action btn-approve w-25">
                            <i class="bi bi-check-all"></i>
                            Approve All Pending
                        </button>
                        <!--
                        <button type="button" id="bulkRejectBtn" class="btn-action btn-reject w-25">
                            <i class="bi bi-x-all"></i>
                            Reject All Pending
                        </button>
                        -->
                        <small class="text-muted">(This will process all pending appointments on this page)</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
<!-- PENDING APPOINTMENTS SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // FILTER FORM - Auto submit on change
    // ============================================
    // No auto-submit for date inputs to allow range selection

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

    // ============================================
    // BULK APPROVE
    // ============================================
    $('#bulkApproveBtn').on('click', function() {
        const button = $(this);
        const pendingIds = [];
        
        // Collect all pending appointment IDs from the table
        $('.btn-approve').each(function() {
            pendingIds.push($(this).data('id'));
        });
        
        if (pendingIds.length === 0) {
            iziToast.warning({
                title: 'No Pending',
                message: 'No pending appointments to approve',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        Swal.fire({
            title: 'Approve All Pending?',
            html: 'Are you sure you want to approve all <strong>' + pendingIds.length + '</strong> pending appointments?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve all',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true);
                button.html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');
                
                // Process each appointment
                let processed = 0;
                let successCount = 0;
                
                pendingIds.forEach(function(id) {
                    $.ajax({
                        url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
                        type: 'POST',
                        data: {
                            action: 'approve_appointment',
                            appointment_id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            processed++;
                            if (response.status === 'success') successCount++;
                            
                            if (processed === pendingIds.length) {
                                iziToast.success({
                                    title: 'Success',
                                    message: successCount + ' out of ' + pendingIds.length + ' appointments approved',
                                    position: 'topRight',
                                    timeout: 3000
                                });
                                
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            }
                        },
                        error: function() {
                            processed++;
                            if (processed === pendingIds.length) {
                                iziToast.error({
                                    title: 'Error',
                                    message: 'Some appointments could not be approved',
                                    position: 'topRight',
                                    timeout: 3000
                                });
                                button.prop('disabled', false);
                                button.html('<i class="bi bi-check-all"></i> Approve All Pending');
                            }
                        }
                    });
                });
            }
        });
    });

    // ============================================
    // BULK REJECT
    // ============================================
    $('#bulkRejectBtn').on('click', function() {
        const button = $(this);
        const pendingIds = [];
        
        // Collect all pending appointment IDs
        $('.btn-reject').each(function() {
            pendingIds.push($(this).data('id'));
        });
        
        if (pendingIds.length === 0) {
            iziToast.warning({
                title: 'No Pending',
                message: 'No pending appointments to reject',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        Swal.fire({
            title: 'Reject All Pending?',
            html: 'Are you sure you want to reject all <strong>' + pendingIds.length + '</strong> pending appointments?<br><small style="color: #dc3545;">This action requires a reason for each rejection.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reject all',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show a reason input for bulk rejection
                Swal.fire({
                    title: 'Rejection Reason',
                    text: 'Enter a reason for rejecting these appointments:',
                    input: 'textarea',
                    inputPlaceholder: 'Enter rejection reason...',
                    inputAttributes: {
                        'aria-label': 'Rejection reason'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Reject All',
                    cancelButtonText: 'Cancel'
                }).then((result2) => {
                    if (result2.isConfirmed && result2.value) {
                        const reason = result2.value.trim();
                        
                        if (!reason) {
                            iziToast.error({
                                title: 'Error',
                                message: 'Please provide a rejection reason',
                                position: 'topRight',
                                timeout: 3000
                            });
                            return;
                        }
                        
                        button.prop('disabled', true);
                        button.html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');
                        
                        let processed = 0;
                        let successCount = 0;
                        
                        pendingIds.forEach(function(id) {
                            $.ajax({
                                url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
                                type: 'POST',
                                data: {
                                    action: 'reject_appointment',
                                    appointment_id: id,
                                    rejection_reason: reason
                                },
                                dataType: 'json',
                                success: function(response) {
                                    processed++;
                                    if (response.status === 'success') successCount++;
                                    
                                    if (processed === pendingIds.length) {
                                        iziToast.success({
                                            title: 'Success',
                                            message: successCount + ' out of ' + pendingIds.length + ' appointments rejected',
                                            position: 'topRight',
                                            timeout: 3000
                                        });
                                        
                                        setTimeout(function() {
                                            location.reload();
                                        }, 1500);
                                    }
                                },
                                error: function() {
                                    processed++;
                                    if (processed === pendingIds.length) {
                                        iziToast.error({
                                            title: 'Error',
                                            message: 'Some appointments could not be rejected',
                                            position: 'topRight',
                                            timeout: 3000
                                        });
                                        button.prop('disabled', false);
                                        button.html('<i class="bi bi-x-all"></i> Reject All Pending');
                                    }
                                }
                            });
                        });
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>