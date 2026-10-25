<?php
// =============================================
// AUDIT LOGS - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Audit Logs';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$entity_filter = isset($_GET['entity']) ? trim($_GET['entity']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$user_filter = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(action LIKE ? OR entity_type LIKE ? OR details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($action_filter)) {
    $where_clauses[] = "action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if (!empty($entity_filter)) {
    $where_clauses[] = "entity_type = ?";
    $params[] = $entity_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($user_filter)) {
    $where_clauses[] = "user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total logs for pagination
$count_query = "SELECT COUNT(*) as total FROM audit_logs $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_logs = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $limit);
$count_stmt->close();

// Fetch audit logs with pagination
$query = "SELECT al.*, 
          CONCAT(u.first_name, ' ', u.last_name) as user_name,
          u.email as user_email,
          DATE_FORMAT(al.created_at, '%b %d, %Y %h:%i:%s %p') as created_at_formatted
          FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.id
          $where_sql 
          ORDER BY al.created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs_result = $stmt->get_result();
$stmt->close();

// Get distinct actions for filter
$actions_query = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
$actions_result = $conn->query($actions_query);

// Get distinct entity types for filter
$entities_query = "SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type";
$entities_result = $conn->query($entities_query);

// Get users for filter
$users_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users ORDER BY first_name";
$users_result = $conn->query($users_query);

// Get stats
$total_query = "SELECT COUNT(*) as total FROM audit_logs";
$total_count = $conn->query($total_query)->fetch_assoc()['total'] ?? 0;

// Today's logs
$today_query = "SELECT COUNT(*) as total FROM audit_logs WHERE DATE(created_at) = CURDATE()";
$today_count = $conn->query($today_query)->fetch_assoc()['total'] ?? 0;

// This week's logs
$week_query = "SELECT COUNT(*) as total FROM audit_logs WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())";
$week_count = $conn->query($week_query)->fetch_assoc()['total'] ?? 0;

// This month's logs
$month_query = "SELECT COUNT(*) as total FROM audit_logs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$month_count = $conn->query($month_query)->fetch_assoc()['total'] ?? 0;

// Get action counts for chart
$action_counts_query = "SELECT action, COUNT(*) as count FROM audit_logs GROUP BY action ORDER BY count DESC LIMIT 10";
$action_counts_result = $conn->query($action_counts_query);
$action_counts = [];
while ($row = $action_counts_result->fetch_assoc()) {
    $action_counts[] = $row;
}
?>

<!-- ============================================ -->
<!-- AUDIT LOGS CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Audit Logs</h1>
                <p class="dashboard-subtitle">Monitor all system activities and user actions</p>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-secondary" onclick="window.location.reload();">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh
                </button>
                <!--
                <a href="<?php echo BASE_URL; ?>/admin/audit-logs.php?export=csv" class="btn-primary">
                    <i class="bi bi-download"></i>
                    Export CSV
                </a>
                -->
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid stats-grid-sm">
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-users" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                    <i class="bi bi-list-ul"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_count); ?></span>
                    <span class="stat-label">Total Logs</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-appointments" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="bi bi-calendar-today"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($today_count); ?></span>
                    <span class="stat-label">Today</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-offices" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($week_count); ?></span>
                    <span class="stat-label">This Week</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-pending" style="background: rgba(201, 168, 76, 0.1); color: #c9a84c;">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($month_count); ?></span>
                    <span class="stat-label">This Month</span>
                </div>
            </div>
        </div>

        <!-- Action Distribution -->
        <?php if (!empty($action_counts)): ?>
        <div class="dashboard-card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-pie-chart"></i>
                    Top Actions
                </h3>
                <span class="card-badge">Last 10 actions</span>
            </div>
            <div class="card-body">
                <div class="action-distribution">
                    <?php foreach ($action_counts as $action): ?>
                        <div class="action-item">
                            <span class="action-label"><?php echo ucfirst(str_replace('_', ' ', $action['action'])); ?></span>
                            <div class="action-bar">
                                <div class="action-fill" style="width: <?php echo min(($action['count'] / max($action_counts[0]['count'], 1)) * 100, 100); ?>%;"></div>
                            </div>
                            <span class="action-count"><?php echo $action['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters-bar">
            <form id="filterForm" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" id="searchInput" class="form-control filter-search" 
                           placeholder="Search actions, entities, or details..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="action" id="actionFilter" class="form-control filter-select">
                        <option value="">All Actions</option>
                        <?php while ($action = $actions_result->fetch_assoc()): ?>
                            <option value="<?php echo $action['action']; ?>" <?php echo $action_filter == $action['action'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $action['action'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="entity" id="entityFilter" class="form-control filter-select">
                        <option value="">All Entities</option>
                        <?php while ($entity = $entities_result->fetch_assoc()): ?>
                            <option value="<?php echo $entity['entity_type']; ?>" <?php echo $entity_filter == $entity['entity_type'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($entity['entity_type']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="user" id="userFilter" class="form-control filter-select">
                        <option value="0">All Users</option>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endwhile; ?>
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
                <a href="<?php echo BASE_URL; ?>/admin/audit-logs.php" class="btn-filter-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- Audit Logs Table -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-table"></i>
                    Activity Logs
                </h3>
                <span class="card-badge"><?php echo $total_logs; ?> records</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-dashboard table-audit">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Details</th>
                                <th>IP Address</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                <?php 
                                $counter = $offset + 1;
                                while ($log = $logs_result->fetch_assoc()): 
                                    $action_color = 'secondary';
                                    if (strpos($log['action'], 'login') !== false) $action_color = 'info';
                                    elseif (strpos($log['action'], 'register') !== false) $action_color = 'success';
                                    elseif (strpos($log['action'], 'approve') !== false) $action_color = 'success';
                                    elseif (strpos($log['action'], 'reject') !== false) $action_color = 'danger';
                                    elseif (strpos($log['action'], 'delete') !== false) $action_color = 'danger';
                                    elseif (strpos($log['action'], 'update') !== false) $action_color = 'warning';
                                    elseif (strpos($log['action'], 'toggle') !== false) $action_color = 'primary';
                                    elseif (strpos($log['action'], 'add') !== false) $action_color = 'success';
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <span class="table-user">
                                                <span class="user-avatar-sm" style="background: <?php echo $log['user_id'] ? '#c9a84c' : '#6c757d'; ?>; color: <?php echo $log['user_id'] ? '#0a1628' : '#ffffff'; ?>;">
                                                    <?php echo $log['user_id'] ? strtoupper(substr($log['user_name'] ?? 'U', 0, 1)) : 'S'; ?>
                                                </span>
                                                <span>
                                                    <?php echo $log['user_id'] ? htmlspecialchars($log['user_name'] ?? 'Unknown') : 'System'; ?>
                                                    <?php if ($log['user_email']): ?>
                                                        <div><small><?php echo htmlspecialchars($log['user_email']); ?></small></div>
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $action_color; ?>" style="font-size: 11px; white-space: nowrap;">
                                                <i class="bi bi-<?php 
                                                    echo strpos($log['action'], 'login') !== false ? 'box-arrow-in-right' : 
                                                         (strpos($log['action'], 'register') !== false ? 'person-plus' : 
                                                         (strpos($log['action'], 'approve') !== false ? 'check-circle' : 
                                                         (strpos($log['action'], 'reject') !== false ? 'x-circle' : 
                                                         (strpos($log['action'], 'delete') !== false ? 'trash' : 
                                                         (strpos($log['action'], 'update') !== false ? 'pencil' : 
                                                         (strpos($log['action'], 'toggle') !== false ? 'arrow-repeat' : 
                                                         (strpos($log['action'], 'add') !== false ? 'plus-circle' : 'clock'))))))); 
                                                ?>"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="entity-badge">
                                                <?php echo ucfirst($log['entity_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="details-cell">
                                                <?php 
                                                $details = json_decode($log['details'], true);
                                                if ($details && is_array($details)) {
                                                    echo '<span class="details-preview">';
                                                    $first_key = array_key_first($details);
                                                    if ($first_key && isset($details[$first_key])) {
                                                        echo htmlspecialchars($first_key . ': ' . $details[$first_key]);
                                                    } else {
                                                        echo htmlspecialchars(json_encode($details));
                                                    }
                                                    echo '</span>';
                                                    if (count($details) > 1) {
                                                        echo ' <button type="button" class="btn-details-toggle" data-details=\'' . htmlspecialchars(json_encode($details)) . '\' title="View Full Details">';
                                                        echo '<i class="bi bi-eye"></i>';
                                                        echo '</button>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">' . htmlspecialchars($log['details'] ?? 'N/A') . '</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="ip-address"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo $log['created_at_formatted']; ?></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                        No audit logs found
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>&entity=<?php echo urlencode($entity_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                       aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>&entity=<?php echo urlencode($entity_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>&entity=<?php echo urlencode($entity_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>&entity=<?php echo urlencode($entity_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>&entity=<?php echo urlencode($entity_filter); ?>&user=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                       aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="pagination-info">
                            Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_logs); ?> of <?php echo $total_logs; ?> logs
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- DETAILS MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <h5 class="modal-title">Log Details</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent" class="details-content">
                    <pre id="detailsJson"></pre>
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
<!-- AUDIT LOGS SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // FILTER FORM - Auto submit on change
    // ============================================
    $('#actionFilter, #entityFilter, #userFilter').on('change', function() {
        $('#filterForm').submit();
    });

    // ============================================
    // VIEW DETAILS
    // ============================================
    $('.btn-details-toggle').on('click', function() {
        const details = $(this).data('details');
        const pretty = JSON.stringify(details, null, 2);
        $('#detailsJson').text(pretty);
        $('#detailsModal').modal('show');
    });
});
</script>
</body>
</html>