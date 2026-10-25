<?php
// =============================================
// VIEW APPOINTMENT - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Get appointment ID
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id <= 0) {
    redirect(BASE_URL . '/admin/manage-appointments.php');
}

// Set page title
$page_title = 'View Appointment';

// Include header
include_once __DIR__ . '/../includes/header.php';

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
          d.file_path,
          d.file_name,
          d.document_type,
          d.uploaded_at as document_uploaded_at,
          DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
          DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
          DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted,
          DATE_FORMAT(a.reviewed_at, '%b %d, %Y %h:%i %p') as reviewed_at_formatted
          FROM appointments a
          LEFT JOIN users u ON a.user_id = u.id
          LEFT JOIN offices o ON a.office_id = o.id
          LEFT JOIN users r ON a.reviewed_by = r.id
          LEFT JOIN documents d ON a.id = d.appointment_id
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(BASE_URL . '/admin/manage-appointments.php');
}

$appointment = $result->fetch_assoc();
$stmt->close();
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
                <a href="<?php echo BASE_URL; ?>/admin/manage-appointments.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </a>
                <?php if ($appointment['status'] == 'pending'): ?>
                    <button type="button" class="btn-approve-action" data-id="<?php echo $appointment['id']; ?>">
                        <i class="bi bi-check-lg"></i>
                        Approve
                    </button>
                    <button type="button" class="btn-reject-action" data-id="<?php echo $appointment['id']; ?>">
                        <i class="bi bi-x-lg"></i>
                        Reject
                    </button>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/applicant/print-slip.php?id=<?php echo $appointment['id']; ?>" class="btn-primary" target="_blank">
                    <i class="bi bi-printer"></i>
                    Print Slip
                </a>
            </div>
        </div>

        <!-- Appointment Details -->
        <div class="appointment-details-grid">
            <!-- Left Column -->
            <div class="appointment-details-left">
                <!-- Status Card -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-info-circle"></i>
                            Status Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="status-display">
                            <span class="status-badge status-<?php echo $appointment['status']; ?>" style="font-size: 16px; padding: 8px 20px;">
                                <i class="bi bi-<?php 
                                    echo $appointment['status'] == 'pending' ? 'clock' : 
                                         ($appointment['status'] == 'approved' ? 'check-circle' : 
                                         ($appointment['status'] == 'rejected' ? 'x-circle' : 
                                         ($appointment['status'] == 'completed' ? 'check-circle-fill' : 
                                         ($appointment['status'] == 'cancelled' ? 'slash-circle' : 'exclamation-circle')))); 
                                ?>"></i>
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
                                    <strong>Rejection Reason:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($appointment['rejection_reason'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Applicant Information -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-person"></i>
                            Applicant Information
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

                <!-- Document Information -->
                <?php if ($appointment['file_path']): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-file-earmark-text"></i>
                            Document
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="document-display">
                            <div class="document-icon">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>
                            <div class="document-info">
                                <span class="document-name"><?php echo htmlspecialchars($appointment['file_name']); ?></span>
                                <span class="document-type"><?php echo ucfirst(str_replace('_', ' ', $appointment['document_type'])); ?></span>
                                <span class="document-date">Uploaded: <?php echo $appointment['document_uploaded_at'] ? date('M d, Y h:i A', strtotime($appointment['document_uploaded_at'])) : 'N/A'; ?></span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/<?php echo $appointment['file_path']; ?>" target="_blank" class="btn-view-document">
                                <i class="bi bi-eye"></i>
                                View Document
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="appointment-details-right">
                <!-- Appointment Information -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-calendar-event"></i>
                            Appointment Details
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
                        </div>
                    </div>
                </div>

                <!-- Office Information -->
                <div class="dashboard-card">
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
<!-- VIEW APPOINTMENT SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // APPROVE APPOINTMENT
    // ============================================
    $('.btn-approve-action').on('click', function() {
        const appointmentId = $(this).data('id');
        const button = $(this);
        
        Swal.fire({
            title: 'Approve Appointment?',
            text: 'Are you sure you want to approve this appointment?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true);
                button.html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...');
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>/api/appointments-api.php',
                    type: 'POST',
                    data: {
                        action: 'approve_appointment',
                        appointment_id: appointmentId
                    },
                    dataType: 'json',
                    success: function(response) {
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
                            button.prop('disabled', false);
                            button.html('<i class="bi bi-check-lg"></i> Approve');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Approve Error:', error);
                        iziToast.error({
                            title: 'Error',
                            message: 'Something went wrong. Please try again.',
                            position: 'topRight',
                            timeout: 3000
                        });
                        button.prop('disabled', false);
                        button.html('<i class="bi bi-check-lg"></i> Approve');
                    }
                });
            }
        });
    });

    // ============================================
    // REJECT APPOINTMENT - Show modal
    // ============================================
    $('.btn-reject-action').on('click', function() {
        const appointmentId = $(this).data('id');
        $('#reject_appointment_id').val(appointmentId);
        $('#rejection_reason').val('');
        $('#rejectModal').modal('show');
    });

    // Submit rejection
    $('#rejectForm').submit(function(e) {
        e.preventDefault();
        
        const reason = $('#rejection_reason').val().trim();
        const appointmentId = $('#reject_appointment_id').val();
        
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
                appointment_id: appointmentId,
                rejection_reason: reason
            },
            dataType: 'json',
            success: function(response) {
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
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please try again.',
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