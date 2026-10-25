<?php
// =============================================
// PRINT SLIP - NCS Online Appointment System
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
          o.office_name,
          o.office_code,
          o.office_type,
          o.address as office_address,
          o.city as office_city,
          o.state as office_state,
          o.phone as office_phone,
          o.email as office_email,
          CONCAT(r.first_name, ' ', r.last_name) as reviewed_by_name,
          DATE_FORMAT(a.appointment_date, '%b %d, %Y') as appointment_date_formatted,
          DATE_FORMAT(a.appointment_time, '%h:%i %p') as appointment_time_formatted,
          DATE_FORMAT(a.created_at, '%b %d, %Y %h:%i %p') as created_at_formatted,
          DATE_FORMAT(a.reviewed_at, '%b %d, %Y %h:%i %p') as reviewed_at_formatted
          FROM appointments a
          LEFT JOIN users u ON a.user_id = u.id
          LEFT JOIN offices o ON a.office_id = o.id
          LEFT JOIN users r ON a.reviewed_by = r.id
          WHERE a.id = ? AND a.user_id = ? AND a.status = 'approved'";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect(BASE_URL . '/applicant/my-appointments.php');
}

$appointment = $result->fetch_assoc();
$stmt->close();

// Generate QR code data (simple text format for printing)
$qr_data = "NCS Appointment\n";
$qr_data .= "Number: " . $appointment['appointment_number'] . "\n";
$qr_data .= "Name: " . $appointment['applicant_name'] . "\n";
$qr_data .= "Date: " . $appointment['appointment_date_formatted'] . "\n";
$qr_data .= "Time: " . $appointment['appointment_time_formatted'] . "\n";
$qr_data .= "Office: " . $appointment['office_name'];

// Set page title
$page_title = 'Print Slip';

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- ============================================ -->
<!-- PRINT SLIP CONTENT -->
<!-- ============================================ -->
<section class="print-section">
    <div class="container">
        <!-- Print Controls -->
        <div class="print-controls">
            <div class="print-controls-left">
                <h2><i class="bi bi-printer"></i> Appointment Slip</h2>
                <p>Print this page or save as PDF</p>
            </div>
            <div class="print-controls-right">
                <button onclick="window.print()" class="btn-primary">
                    <i class="bi bi-printer"></i> Print / PDF
                </button>
                <a href="<?php echo BASE_URL; ?>/applicant/my-appointments.php" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Slip Content -->
        <div id="slipContent" class="slip-container">
            <!-- Slip Header -->
            <div class="slip-header">
                <div class="slip-logo">
                    <div class="logo-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">NCS</span>
                        <span class="logo-sub">Nigeria Customs Service</span>
                    </div>
                </div>
                <div class="slip-title">
                    <h1>Appointment Slip</h1>
                    <p>Official Confirmation of Appointment</p>
                </div>
                <div class="slip-badge">
                    <span class="status-badge status-approved">
                        <i class="bi bi-check-circle-fill"></i>
                        APPROVED
                    </span>
                </div>
            </div>

            <!-- Slip Body -->
            <div class="slip-body">
                <!-- Appointment Details -->
                <div class="slip-section">
                    <h3 class="section-title">
                        <i class="bi bi-calendar-event"></i> Appointment Details
                    </h3>
                    <div class="slip-grid">
                        <div class="slip-item">
                            <span class="slip-label">Appointment Number</span>
                            <span class="slip-value highlight"><?php echo htmlspecialchars($appointment['appointment_number']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Date</span>
                            <span class="slip-value"><?php echo $appointment['appointment_date_formatted']; ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Time</span>
                            <span class="slip-value"><?php echo $appointment['appointment_time_formatted']; ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Duration</span>
                            <span class="slip-value"><?php echo $appointment['duration_minutes']; ?> minutes</span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Purpose</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['purpose']); ?></span>
                        </div>
                        <?php if ($appointment['description']): ?>
                        <div class="slip-item">
                            <span class="slip-label">Description</span>
                            <span class="slip-value"><?php echo nl2br(htmlspecialchars($appointment['description'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Office Details -->
                <div class="slip-section">
                    <h3 class="section-title">
                        <i class="bi bi-building"></i> Office Information
                    </h3>
                    <div class="slip-grid">
                        <div class="slip-item">
                            <span class="slip-label">Office Name</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['office_name']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Office Code</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['office_code']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Address</span>
                            <span class="slip-value">
                                <?php 
                                $office_address = [];
                                if ($appointment['office_address']) $office_address[] = $appointment['office_address'];
                                if ($appointment['office_city']) $office_address[] = $appointment['office_city'];
                                if ($appointment['office_state']) $office_address[] = $appointment['office_state'];
                                echo !empty($office_address) ? htmlspecialchars(implode(', ', $office_address)) : 'N/A';
                                ?>
                            </span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Phone</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['office_phone']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Email</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['office_email']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Applicant Details -->
                <div class="slip-section">
                    <h3 class="section-title">
                        <i class="bi bi-person"></i> Applicant Information
                    </h3>
                    <div class="slip-grid">
                        <div class="slip-item">
                            <span class="slip-label">Full Name</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['applicant_name']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Email</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['applicant_email']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Phone</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['applicant_phone']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">NIN</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['applicant_nin'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Review Information -->
                <div class="slip-section">
                    <h3 class="section-title">
                        <i class="bi bi-check-circle"></i> Approval Information
                    </h3>
                    <div class="slip-grid">
                        <div class="slip-item">
                            <span class="slip-label">Approved By</span>
                            <span class="slip-value"><?php echo htmlspecialchars($appointment['reviewed_by_name']); ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Approved On</span>
                            <span class="slip-value"><?php echo $appointment['reviewed_at_formatted']; ?></span>
                        </div>
                        <div class="slip-item">
                            <span class="slip-label">Status</span>
                            <span class="slip-value">
                                <span class="status-badge status-approved">
                                    <i class="bi bi-check-circle-fill"></i> Approved
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- QR Code Placeholder -->
                <div class="slip-qr">
                    <div class="qr-placeholder">
                        <div class="qr-grid">
                            <?php for ($i = 0; $i < 25; $i++): ?>
                                <div class="qr-cell <?php echo rand(0, 1) ? 'filled' : ''; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="qr-info">
                        <p><strong>Scan to verify</strong></p>
                        <p class="qr-text"><?php echo htmlspecialchars($appointment['appointment_number']); ?></p>
                        <p class="qr-text" style="font-size: 11px; margin-top: 4px;">Present this slip at the office</p>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="slip-footer">
                    <div class="slip-footer-content">
                        <p><i class="bi bi-info-circle"></i> Please arrive at least 15 minutes before your scheduled appointment time.</p>
                        <p><i class="bi bi-file-text"></i> Bring this slip and valid identification to the appointment.</p>
                    </div>
                    <div class="slip-footer-meta">
                        <p>Generated on: <?php echo date('M d, Y h:i A'); ?></p>
                        <p class="footer-watermark">NCS Appointment System v1.0</p>
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
<!-- PRINT SLIP STYLES -->
<!-- ============================================ -->
<style>
/* ============================================ */
/* PRINT SLIP STYLES                           */
/* ============================================ */

.print-section {
    padding: 30px 0 60px;
    background: var(--bg-body);
    min-height: 100vh;
}

/* Print Controls */
.print-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 30px;
    padding: 16px 24px;
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.print-controls-left h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.print-controls-left h2 i {
    color: var(--primary-gold);
}

.print-controls-left p {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 0;
}

.print-controls-right {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.print-controls-right .btn-primary,
.print-controls-right .btn-secondary {
    padding: 10px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: var(--transition);
}

.print-controls-right .btn-primary {
    background: var(--primary-gold);
    color: #0a1628;
    border: none;
    cursor: pointer;
}

.print-controls-right .btn-primary:hover {
    background: var(--primary-gold-dark);
    transform: translateY(-2px);
}

.print-controls-right .btn-secondary {
    background: var(--bg-body);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.print-controls-right .btn-secondary:hover {
    background: var(--border-color);
    transform: translateY(-2px);
}

/* Slip Container */
.slip-container {
    background: var(--bg-card);
    border-radius: 16px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    max-width: 900px;
    margin: 0 auto;
}

/* Slip Header */
.slip-header {
    background: linear-gradient(135deg, #0a1628, #1a3a5c);
    padding: 30px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    border-bottom: 4px solid var(--primary-gold);
}

.slip-logo {
    display: flex;
    align-items: center;
    gap: 14px;
}

.slip-logo .logo-icon {
    width: 52px;
    height: 52px;
    background: rgba(201, 168, 76, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: var(--primary-gold);
    border: 1px solid rgba(201, 168, 76, 0.2);
}

.slip-logo .logo-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.slip-logo .logo-main {
    font-size: 24px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 1px;
}

.slip-logo .logo-sub {
    font-size: 11px;
    font-weight: 400;
    color: var(--primary-gold);
    letter-spacing: 1px;
}

.slip-title {
    text-align: center;
}

.slip-title h1 {
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    letter-spacing: 2px;
}

.slip-title p {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    margin: 4px 0 0 0;
}

.slip-badge .status-badge {
    font-size: 14px;
    padding: 8px 20px;
}

/* Slip Body */
.slip-body {
    padding: 32px 40px;
}

/* Slip Section */
.slip-section {
    margin-bottom: 28px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-light);
}

.slip-section:last-of-type {
    border-bottom: none;
    margin-bottom: 16px;
    padding-bottom: 0;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title i {
    color: var(--primary-gold);
}

/* Slip Grid */
.slip-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 32px;
}

.slip-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.slip-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.slip-value {
    font-size: 14px;
    color: var(--text-primary);
    font-weight: 500;
}

.slip-value.highlight {
    color: var(--primary-gold);
    font-weight: 700;
    font-size: 16px;
}

/* QR Code */
.slip-qr {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 16px 20px;
    background: var(--bg-body);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    margin-top: 8px;
}

.qr-placeholder {
    width: 80px;
    height: 80px;
    background: #ffffff;
    border-radius: 8px;
    padding: 6px;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}

.qr-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 2px;
    width: 100%;
    height: 100%;
}

.qr-cell {
    border-radius: 2px;
    background: #f0f0f0;
}

.qr-cell.filled {
    background: #0a1628;
}

.qr-info p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 13px;
}

.qr-info .qr-text {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

/* Slip Footer */
.slip-footer {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.slip-footer-content p {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 4px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.slip-footer-content p i {
    color: var(--primary-gold);
}

.slip-footer-meta {
    text-align: right;
}

.slip-footer-meta p {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
}

.footer-watermark {
    font-size: 10px;
    color: var(--text-light);
    margin-top: 4px !important;
}

/* ============================================ */
/* PRINT STYLES - For printing                 */
/* ============================================ */
@media print {
    /* Hide everything except the slip */
    header, .header, .ncs-header, .topnav, 
    .dashboard-header, .dashboard-actions, 
    .print-controls, .footer, .scroll-top,
    .settings-tabs, .filters-bar, .stats-grid,
    .dashboard-card, .tips-card, .alert,
    .mobile-nav, .mobile-overlay {
        display: none !important;
    }
    
    body {
        background: #ffffff !important;
        padding-top: 0 !important;
        margin: 0 !important;
    }
    
    .print-section {
        padding: 0 !important;
        background: #ffffff !important;
        min-height: auto !important;
    }
    
    .container {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .slip-container {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        max-width: 100% !important;
        margin: 0 !important;
    }
    
    .slip-header {
        padding: 20px 30px !important;
    }
    
    .slip-body {
        padding: 24px 30px !important;
    }
    
    .slip-section {
        page-break-inside: avoid !important;
    }
    
    .slip-grid {
        gap: 8px 24px !important;
    }
    
    .slip-value {
        font-size: 13px !important;
    }
    
    .qr-placeholder {
        width: 70px !important;
        height: 70px !important;
    }
    
    .slip-footer {
        page-break-inside: avoid !important;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .slip-header {
        flex-direction: column;
        text-align: center;
        padding: 24px 20px;
    }
    
    .slip-title h1 {
        font-size: 22px;
    }
    
    .slip-body {
        padding: 24px 20px;
    }
    
    .slip-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .slip-qr {
        flex-direction: column;
        text-align: center;
    }
    
    .slip-footer {
        flex-direction: column;
        text-align: center;
    }
    
    .slip-footer-meta {
        text-align: center;
    }
    
    .print-controls {
        flex-direction: column;
        text-align: center;
    }
    
    .print-controls-right {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .slip-header {
        padding: 16px;
    }
    
    .slip-title h1 {
        font-size: 18px;
    }
    
    .slip-body {
        padding: 16px;
    }
    
    .slip-section {
        margin-bottom: 16px;
        padding-bottom: 12px;
    }
    
    .section-title {
        font-size: 14px;
    }
    
    .slip-logo .logo-main {
        font-size: 18px;
    }
    
    .slip-logo .logo-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
}
</style>
</body>
</html>