<?php
// =============================================
// UPLOAD DOCUMENT - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is applicant
if (!isLoggedIn() || !isApplicant()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Upload Document';

// Include header
include_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get user's appointments for dropdown
$appointments_query = "SELECT id, appointment_number, appointment_date, status 
                       FROM appointments 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments_result = $stmt->get_result();
$stmt->close();

// Get user's existing documents
$documents_query = "SELECT d.*, 
                    DATE_FORMAT(d.uploaded_at, '%b %d, %Y %h:%i %p') as uploaded_at_formatted,
                    CONCAT(u.first_name, ' ', u.last_name) as verified_by_name
                    FROM documents d
                    LEFT JOIN users u ON d.verified_by = u.id
                    WHERE d.user_id = ?
                    ORDER BY d.uploaded_at DESC";
$stmt = $conn->prepare($documents_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documents_result = $stmt->get_result();
$stmt->close();

// Count documents
$doc_count = $documents_result->num_rows;
?>

<!-- ============================================ -->
<!-- UPLOAD DOCUMENT CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Upload Document</h1>
                <p class="dashboard-subtitle">Upload and manage your identification documents</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/applicant/dashboard.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid stats-grid-sm">
            <div class="stat-card stat-card-sm">
                <div class="stat-icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                    <i class="bi bi-file-earmark"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $doc_count; ?></span>
                    <span class="stat-label">Total Documents</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php 
                        $verified_count = 0;
                        $documents_result->data_seek(0);
                        while ($doc = $documents_result->fetch_assoc()) {
                            if ($doc['is_verified']) $verified_count++;
                        }
                        echo $verified_count;
                    ?></span>
                    <span class="stat-label">Verified</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php 
                        $pending_count = 0;
                        $documents_result->data_seek(0);
                        while ($doc = $documents_result->fetch_assoc()) {
                            if (!$doc['is_verified']) $pending_count++;
                        }
                        echo $pending_count;
                    ?></span>
                    <span class="stat-label">Pending Verification</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon" style="background: rgba(201, 168, 76, 0.1); color: #c9a84c;">
                    <i class="bi bi-upload"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $doc_count > 0 ? '✅' : '📤'; ?></span>
                    <span class="stat-label">Upload Status</span>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-cloud-upload"></i>
                    Upload New Document
                </h3>
                <span class="card-badge">Max 10MB</span>
            </div>
            <div class="card-body">
                <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="document_type">Document Type <span class="text-danger">*</span></label>
                                <select id="document_type" name="document_type" class="form-control" required>
                                    <option value="">-- Select document type --</option>
                                    <option value="identification">Identification</option>
                                    <option value="passport_photo">Passport Photo</option>
                                    <option value="nin_slip">NIN Slip</option>
                                    <option value="proof_of_address">Proof of Address</option>
                                    <option value="letter_of_introduction">Letter of Introduction</option>
                                    <option value="customs_clearance">Customs Clearance</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="appointment_id">Related Appointment (Optional)</label>
                                <select id="appointment_id" name="appointment_id" class="form-control">
                                    <option value="">-- No appointment --</option>
                                    <?php 
                                    $appointments_result->data_seek(0);
                                    while ($appointment = $appointments_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $appointment['id']; ?>">
                                            <?php echo htmlspecialchars($appointment['appointment_number'] . ' - ' . $appointment['appointment_date'] . ' (' . ucfirst($appointment['status']) . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="document_file">Select File <span class="text-danger">*</span></label>
                                <div class="file-upload-wrapper">
                                    <div class="file-drop-zone" id="fileDropZone">
                                        <div class="file-drop-icon">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div class="file-drop-text">
                                            <h4>Drag & drop your file here</h4>
                                            <p>or <span class="browse-link">browse files</span></p>
                                            <small>Supported formats: PDF, JPG, PNG, JPEG (Max 10MB)</small>
                                        </div>
                                        <input type="file" id="document_file" name="document_file" class="file-input" 
                                               accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>
                                </div>
                                <div id="filePreview" class="file-preview" style="display:none;">
                                    <div class="file-preview-content">
                                        <div class="file-preview-icon">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </div>
                                        <div class="file-preview-info">
                                            <span class="file-name" id="fileName">document.pdf</span>
                                            <span class="file-size" id="fileSize">0 KB</span>
                                        </div>
                                        <button type="button" class="btn-remove-file" id="removeFile">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="file_description">Description (Optional)</label>
                                <textarea id="file_description" name="file_description" class="form-control" 
                                          rows="2" placeholder="Add a brief description of this document..."></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" id="uploadBtn" class="btn-primary btn-upload">
                                <span class="btn-text"><i class="bi bi-upload"></i> Upload Document</span>
                                <span class="btn-loader" style="display:none;">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </span>
                            </button>
                            <button type="reset" class="btn-secondary btn-reset-upload">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- My Documents -->
        <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-file-earmark-text"></i>
                    My Documents
                </h3>
                <span class="card-badge"><?php echo $doc_count; ?> documents</span>
            </div>
            <div class="card-body">
                <?php if ($doc_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table-dashboard">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Document Type</th>
                                    <th>File Name</th>
                                    <th>Related Appointment</th>
                                    <th>Status</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                $documents_result->data_seek(0);
                                while ($doc = $documents_result->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <span class="doc-type-badge">
                                                <i class="bi bi-<?php 
                                                    echo $doc['document_type'] == 'identification' ? 'person-badge' : 
                                                         ($doc['document_type'] == 'passport_photo' ? 'camera' : 
                                                         ($doc['document_type'] == 'nin_slip' ? 'card-text' : 
                                                         ($doc['document_type'] == 'proof_of_address' ? 'house' : 
                                                         ($doc['document_type'] == 'letter_of_introduction' ? 'envelope' : 
                                                         ($doc['document_type'] == 'customs_clearance' ? 'file-check' : 'file-earmark'))))); 
                                                ?>"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($doc['file_name']); ?></div>
                                                <small><?php echo number_format($doc['file_size'] / 1024, 2); ?> KB</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($doc['appointment_id']): ?>
                                                <?php 
                                                $appt_query = "SELECT appointment_number FROM appointments WHERE id = ?";
                                                $appt_stmt = $conn->prepare($appt_query);
                                                $appt_stmt->bind_param("i", $doc['appointment_id']);
                                                $appt_stmt->execute();
                                                $appt_result = $appt_stmt->get_result();
                                                $appt = $appt_result->fetch_assoc();
                                                $appt_stmt->close();
                                                ?>
                                                <?php if ($appt): ?>
                                                    <?php echo htmlspecialchars($appt['appointment_number']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not linked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $doc['is_verified'] ? 'approved' : 'pending'; ?>">
                                                <i class="bi bi-<?php echo $doc['is_verified'] ? 'check-circle' : 'clock'; ?>"></i>
                                                <?php echo $doc['is_verified'] ? 'Verified' : 'Pending'; ?>
                                            </span>
                                            <?php if ($doc['is_verified'] && $doc['verified_by_name']): ?>
                                                <div>
                                                    <small>by <?php echo htmlspecialchars($doc['verified_by_name']); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo $doc['uploaded_at_formatted']; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/<?php echo $doc['file_path']; ?>" 
                                                   class="btn-action btn-view" target="_blank" title="View Document">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button type="button" class="btn-action btn-delete-doc" 
                                                        data-id="<?php echo $doc['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($doc['file_name']); ?>"
                                                        title="Delete Document">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state-small">
                        <i class="bi bi-file-earmark"></i>
                        <p>No documents uploaded yet</p>
                        <p class="text-muted" style="font-size: 13px;">Upload your identification documents for verification</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload Tips -->
        <div class="dashboard-card mb-4 tips-card" data-aos="fade-up" data-aos-delay="300">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-info-circle"></i>
                    Document Guidelines
                </h3>
            </div>
            <div class="card-body">
                <div class="tips-grid">
                    <div class="tip-item">
                        <i class="bi bi-file-earmark-pdf"></i>
                        <h4>File Format</h4>
                        <p>Upload PDF, JPG, JPEG, or PNG files only</p>
                    </div>
                    <div class="tip-item">
                        <i class="bi bi-hdd"></i>
                        <h4>File Size</h4>
                        <p>Maximum file size is 10MB</p>
                    </div>
                    <div class="tip-item">
                        <i class="bi bi-image"></i>
                        <h4>Image Quality</h4>
                        <p>Ensure images are clear and readable</p>
                    </div>
                    <div class="tip-item">
                        <i class="bi bi-shield-check"></i>
                        <h4>Verification</h4>
                        <p>Documents will be verified by an officer</p>
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
<!-- UPLOAD DOCUMENT SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // FILE PREVIEW - jQuery
    // ============================================
    const fileInput = $('#document_file');
    const fileDropZone = $('#fileDropZone');
    const filePreview = $('#filePreview');
    const fileName = $('#fileName');
    const fileSize = $('#fileSize');
    const removeFileBtn = $('#removeFile');

    // File input change
    fileInput.on('change', function() {
        const file = this.files[0];
        if (file) {
            displayFilePreview(file);
        } else {
            hideFilePreview();
        }
    });

    // Drag and drop events
    fileDropZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });

    fileDropZone.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });

    fileDropZone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            fileInput.trigger('change');
        }
    });

    // Click to browse
    fileDropZone.on('click', function(e) {
        if (!$(e.target).closest('.browse-link').length) {
            fileInput.click();
        }
    });

    // Browse link click
    $('.browse-link').on('click', function(e) {
        e.stopPropagation();
        fileInput.click();
    });

    // Remove file
    removeFileBtn.on('click', function() {
        fileInput.val('');
        hideFilePreview();
        fileDropZone.show();
    });

    function displayFilePreview(file) {
        const fileType = file.type;
        const icon = fileType.includes('pdf') ? 'bi-file-earmark-pdf' :
                     fileType.includes('image') ? 'bi-file-earmark-image' :
                     'bi-file-earmark';
        
        fileDropZone.hide();
        filePreview.show();
        $('.file-preview-icon i').attr('class', 'bi ' + icon);
        fileName.text(file.name);
        fileSize.text((file.size / 1024).toFixed(2) + ' KB');
        
        // Validate file size
        if (file.size > 10 * 1024 * 1024) {
            iziToast.warning({
                title: 'File Too Large',
                message: 'File size exceeds 10MB limit. Please choose a smaller file.',
                position: 'topRight',
                timeout: 5000
            });
            fileInput.val('');
            hideFilePreview();
            fileDropZone.show();
        }
    }

    function hideFilePreview() {
        filePreview.hide();
        fileDropZone.show();
    }

    // ============================================
    // UPLOAD DOCUMENT
    // ============================================
    // ============================================
// UPLOAD DOCUMENT - FIXED
// ============================================
$('#uploadForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const file = $('#document_file')[0].files[0];
    
    // Validate
    if (!file) {
        iziToast.error({
            title: 'Error',
            message: 'Please select a file to upload',
            position: 'topRight',
            timeout: 3000
        });
        return;
    }
    
    // Check file size
    if (file.size > 10 * 1024 * 1024) {
        iziToast.error({
            title: 'Error',
            message: 'File size exceeds 10MB limit',
            position: 'topRight',
            timeout: 3000
        });
        return;
    }
    
    // Validate document type
    const docType = $('#document_type').val();
    if (!docType) {
        iziToast.error({
            title: 'Error',
            message: 'Please select a document type',
            position: 'topRight',
            timeout: 3000
        });
        return;
    }
    
    // Add action to formData
    formData.append('action', 'upload_document');
    
    // Show loading
    $('#uploadBtn').prop('disabled', true);
    $('#uploadBtn .btn-text').hide();
    $('#uploadBtn .btn-loader').show();
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>/api/documents-api.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response) {
            console.log('Upload Response:', response);
            
            if (response.status === 'success') {
                iziToast.success({
                    title: 'Success',
                    message: response.message,
                    position: 'topRight',
                    timeout: 3000
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
            
            $('#uploadBtn').prop('disabled', false);
            $('#uploadBtn .btn-text').show();
            $('#uploadBtn .btn-loader').hide();
        },
        error: function(xhr, status, error) {
            console.error('Upload Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Status:', status);
            console.error('XHR:', xhr);
            
            let errorMessage = 'Something went wrong. Please check console for details.';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMessage = response.message;
                }
            } catch (e) {}
            
            iziToast.error({
                title: 'Error',
                message: errorMessage,
                position: 'topRight',
                timeout: 3000
            });
            
            $('#uploadBtn').prop('disabled', false);
            $('#uploadBtn .btn-text').show();
            $('#uploadBtn .btn-loader').hide();
        }
    });
});

    // ============================================
    // DELETE DOCUMENT
    // ============================================
    $('.btn-delete-doc').on('click', function() {
        const docId = $(this).data('id');
        const docName = $(this).data('name');
        const button = $(this);
        
        Swal.fire({
            title: 'Delete Document?',
            text: 'Are you sure you want to delete "' + docName + '"?',
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
                    url: '<?php echo BASE_URL; ?>/api/documents-api.php',
                    type: 'POST',
                    data: {
                        action: 'delete_document',
                        document_id: docId
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

    // ============================================
    // RESET FORM
    // ============================================
    $('.btn-reset-upload').on('click', function(e) {
        e.preventDefault();
        $('#uploadForm')[0].reset();
        hideFilePreview();
        fileDropZone.show();
    });
});
</script>
</body>
</html>