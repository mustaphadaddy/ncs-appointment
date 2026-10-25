<?php
// =============================================
// ADMIN DASHBOARD - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Admin Dashboard';

// Include header
include_once __DIR__ . '/../includes/header.php';

// ============================================
// FETCH DASHBOARD STATISTICS
// ============================================

// Total Users
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total'] ?? 0;

// Users by role
$users_by_role_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$users_by_role_result = $conn->query($users_by_role_query);
$users_by_role = [];
while ($row = $users_by_role_result->fetch_assoc()) {
    $users_by_role[$row['role']] = $row['count'];
}

// Total Appointments
$total_appointments_query = "SELECT COUNT(*) as total FROM appointments";
$total_appointments_result = $conn->query($total_appointments_query);
$total_appointments = $total_appointments_result->fetch_assoc()['total'] ?? 0;

// Appointments by status
$appointments_by_status_query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$appointments_by_status_result = $conn->query($appointments_by_status_query);
$appointments_by_status = [];
while ($row = $appointments_by_status_result->fetch_assoc()) {
    $appointments_by_status[$row['status']] = $row['count'];
}

// Total Offices
$total_offices_query = "SELECT COUNT(*) as total FROM offices WHERE is_active = 1";
$total_offices_result = $conn->query($total_offices_query);
$total_offices = $total_offices_result->fetch_assoc()['total'] ?? 0;

// Today's Appointments
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()";
$today_appointments_result = $conn->query($today_appointments_query);
$today_appointments = $today_appointments_result->fetch_assoc()['total'] ?? 0;

// Pending Appointments
$pending_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$pending_appointments_result = $conn->query($pending_appointments_query);
$pending_appointments = $pending_appointments_result->fetch_assoc()['total'] ?? 0;

// Recent Appointments (last 10)
$recent_appointments_query = "SELECT a.*, 
                              CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                              o.office_name
                              FROM appointments a
                              LEFT JOIN users u ON a.user_id = u.id
                              LEFT JOIN offices o ON a.office_id = o.id
                              ORDER BY a.created_at DESC LIMIT 10";
$recent_appointments_result = $conn->query($recent_appointments_query);

// Recent Users (last 5)
$recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);

// Monthly Appointments Data (for chart)
$monthly_data_query = "SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM appointments 
                        WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
                        ORDER BY month ASC";
$monthly_data_result = $conn->query($monthly_data_query);
$monthly_data = [];
while ($row = $monthly_data_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// Chart data preparation
$chart_months = [];
$chart_total = [];
$chart_approved = [];
$chart_pending = [];
$chart_rejected = [];

foreach ($monthly_data as $data) {
    $chart_months[] = date('M Y', strtotime($data['month'] . '-01'));
    $chart_total[] = $data['total'];
    $chart_approved[] = $data['approved'];
    $chart_pending[] = $data['pending'];
    $chart_rejected[] = $data['rejected'];
}

// If no data, add placeholder
if (empty($chart_months)) {
    $chart_months = ['No Data'];
    $chart_total = [0];
    $chart_approved = [0];
    $chart_pending = [0];
    $chart_rejected = [0];
}
?>

<!-- ============================================ -->
<!-- ADMIN DASHBOARD CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Admin Dashboard</h1>
                <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! Here's what's happening with your system.</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo BASE_URL; ?>/admin/manage-users.php" class="btn-primary">
                    <i class="bi bi-person-plus"></i>
                    Add User
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/manage-offices.php" class="btn-secondary">
                    <i class="bi bi-building"></i>
                    Manage Offices
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon stat-icon-users">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_users); ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="stat-change positive">
                    <i class="bi bi-arrow-up"></i>
                    <span>Active</span>
                </div>
            </div>

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
                    <span>+<?php echo $today_appointments; ?> today</span>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
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
                    <span>Active</span>
                </div>
            </div>
        </div>

        <!-- Role Distribution & Chart Row -->
        <div class="dashboard-row">
            <!-- Users by Role -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="500">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-pie-chart"></i>
                        Users by Role
                    </h3>
                </div>
                <div class="card-body">
                    <div class="role-distribution">
                        <div class="role-item">
                            <span class="role-label">Admins</span>
                            <div class="role-bar">
                                <div class="role-fill admin-fill" style="width: <?php echo isset($users_by_role['admin']) ? ($users_by_role['admin'] / max($total_users, 1) * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="role-count"><?php echo isset($users_by_role['admin']) ? $users_by_role['admin'] : 0; ?></span>
                        </div>
                        <div class="role-item">
                            <span class="role-label">Officers</span>
                            <div class="role-bar">
                                <div class="role-fill officer-fill" style="width: <?php echo isset($users_by_role['officer']) ? ($users_by_role['officer'] / max($total_users, 1) * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="role-count"><?php echo isset($users_by_role['officer']) ? $users_by_role['officer'] : 0; ?></span>
                        </div>
                        <div class="role-item">
                            <span class="role-label">Applicants</span>
                            <div class="role-bar">
                                <div class="role-fill applicant-fill" style="width: <?php echo isset($users_by_role['applicant']) ? ($users_by_role['applicant'] / max($total_users, 1) * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="role-count"><?php echo isset($users_by_role['applicant']) ? $users_by_role['applicant'] : 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appointment Chart -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="600">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-graph-up"></i>
                        Appointment Trends
                    </h3>
                    <span class="card-badge">Last 6 months</span>
                </div>
                <div class="card-body">
                    <canvas id="appointmentChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity Row -->
        <div class="dashboard-row">
            <!-- Recent Appointments -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="700">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-clock-history"></i>
                        Recent Appointments
                    </h3>
                    <a href="<?php echo BASE_URL; ?>/admin/manage-appointments.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table-dashboard">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Office</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_appointments_result && $recent_appointments_result->num_rows > 0): ?>
                                    <?php while ($appointment = $recent_appointments_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="table-user">
                                                    <span class="user-avatar-sm">
                                                        <?php echo strtoupper(substr($appointment['applicant_name'] ?? 'U', 0, 1)); ?>
                                                    </span>
                                                    <?php echo htmlspecialchars($appointment['applicant_name'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['office_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No recent appointments</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="800">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-person-plus"></i>
                        Recent Users
                    </h3>
                    <a href="<?php echo BASE_URL; ?>/admin/manage-users.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="recent-users-list">
                        <?php if ($recent_users_result && $recent_users_result->num_rows > 0): ?>
                            <?php while ($user = $recent_users_result->fetch_assoc()): ?>
                                <div class="recent-user-item">
                                    <div class="recent-user-avatar">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="recent-user-info">
                                        <span class="recent-user-name">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </span>
                                        <span class="recent-user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                    <span class="status-badge status-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                    <span class="recent-user-time">
                                        <?php echo time_ago(strtotime($user['created_at'])); ?>
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">No recent users</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card quick-actions" data-aos="fade-up" data-aos-delay="900">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-lightning"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="<?php echo BASE_URL; ?>/admin/manage-users.php" class="quick-action-item">
                        <i class="bi bi-people"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/manage-offices.php" class="quick-action-item">
                        <i class="bi bi-building"></i>
                        <span>Manage Offices</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/manage-appointments.php" class="quick-action-item">
                        <i class="bi bi-calendar-check"></i>
                        <span>Manage Appointments</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/audit-logs.php" class="quick-action-item">
                        <i class="bi bi-clock-history"></i>
                        <span>View Audit Logs</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/system-settings.php" class="quick-action-item">
                        <i class="bi bi-gear"></i>
                        <span>System Settings</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="quick-action-item">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Generate Reports</span>
                    </a>
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
<!-- DASHBOARD SPECIFIC SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // APPOINTMENT CHART - Chart.js
    // ============================================
    const ctx = document.getElementById('appointmentChart').getContext('2d');
    
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

    // Create chart
    const appointmentChart = new Chart(ctx, chartConfig);

    // ============================================
    // THEME CHANGE - Update chart colors
    // ============================================
    const observer = new MutationObserver(function() {
        appointmentChart.update();
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
});
</script>

<!-- ============================================ -->
<!-- TIME AGO FUNCTION (PHP Helper) -->
<!-- ============================================ -->
<?php
function time_ago($timestamp) {
    $time_ago = time() - $timestamp;
    
    if ($time_ago < 60) {
        return 'Just now';
    } elseif ($time_ago < 3600) {
        $mins = floor($time_ago / 60);
        return $mins . 'm ago';
    } elseif ($time_ago < 86400) {
        $hours = floor($time_ago / 3600);
        return $hours . 'h ago';
    } elseif ($time_ago < 604800) {
        $days = floor($time_ago / 86400);
        return $days . 'd ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>