<?php
// =============================================
// MANAGE OFFICES - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Manage Offices';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(office_name LIKE ? OR office_code LIKE ? OR city LIKE ? OR state LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($type_filter)) {
    $where_clauses[] = "office_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where_clauses[] = "is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
    $types .= "i";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total offices for pagination
$count_query = "SELECT COUNT(*) as total FROM offices $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_offices = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_offices / $limit);
$count_stmt->close();

// Fetch offices with pagination
$query = "SELECT id, office_code, office_name, office_type, address, city, state, phone, email,
          opening_time, closing_time, max_appointments_per_day, is_active,
          DATE_FORMAT(created_at, '%b %d, %Y') as created_date
          FROM offices $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$offices_result = $stmt->get_result();
$stmt->close();

// Get office types for filter
$types_query = "SELECT DISTINCT office_type FROM offices ORDER BY office_type";
$types_result = $conn->query($types_query);

// Get stats
$total_query = "SELECT COUNT(*) as total FROM offices";
$total_count = $conn->query($total_query)->fetch_assoc()['total'] ?? 0;

$active_query = "SELECT COUNT(*) as total FROM offices WHERE is_active = 1";
$active_count = $conn->query($active_query)->fetch_assoc()['total'] ?? 0;

$inactive_query = "SELECT COUNT(*) as total FROM offices WHERE is_active = 0";
$inactive_count = $conn->query($inactive_query)->fetch_assoc()['total'] ?? 0;

// Get office type stats
$type_stats_query = "SELECT office_type, COUNT(*) as count FROM offices GROUP BY office_type";
$type_stats_result = $conn->query($type_stats_query);
$type_stats = [];
while ($row = $type_stats_result->fetch_assoc()) {
    $type_stats[$row['office_type']] = $row['count'];
}
?>

<!-- ============================================ -->
<!-- MANAGE OFFICES CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Manage Offices</h1>
                <p class="dashboard-subtitle">Manage all Customs offices, locations, and operating hours</p>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#addOfficeModal">
                    <i class="bi bi-building-add"></i>
                    Add Office
                </button>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid stats-grid-sm">
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-offices">
                    <i class="bi bi-buildings"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_count); ?></span>
                    <span class="stat-label">Total Offices</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-users">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($active_count); ?></span>
                    <span class="stat-label">Active</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($inactive_count); ?></span>
                    <span class="stat-label">Inactive</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-appointments">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo count($type_stats); ?></span>
                    <span class="stat-label">Office Types</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form id="filterForm" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" id="searchInput" class="form-control filter-search" 
                           placeholder="Search by name, code, city, or state..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="type" id="typeFilter" class="form-control filter-select">
                        <option value="">All Types</option>
                        <?php while ($type = $types_result->fetch_assoc()): ?>
                            <option value="<?php echo $type['office_type']; ?>" <?php echo $type_filter == $type['office_type'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $type['office_type'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="status" id="statusFilter" class="form-control filter-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-filter">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="<?php echo BASE_URL; ?>/admin/manage-offices.php" class="btn-filter-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- Offices Table -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-table"></i>
                    Offices List
                </h3>
                <span class="card-badge"><?php echo $total_offices; ?> total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-dashboard">
                        <thead>
                            <tr>
                                <th>Office</th>
                                <th>Code / Type</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($offices_result && $offices_result->num_rows > 0): ?>
                                <?php while ($office = $offices_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="table-user">
                                                <span class="user-avatar-sm office-avatar">
                                                    <i class="bi bi-building"></i>
                                                </span>
                                                <span>
                                                    <strong><?php echo htmlspecialchars($office['office_name']); ?></strong>
                                                    <div><small><?php echo htmlspecialchars($office['address']); ?></small></div>
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <span class="status-badge status-officer" style="font-size: 11px;">
                                                    <?php echo htmlspecialchars($office['office_code']); ?>
                                                </span>
                                                <small><?php echo ucfirst(str_replace('_', ' ', $office['office_type'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($office['city']); ?></div>
                                                <small><?php echo htmlspecialchars($office['state']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($office['phone']); ?></div>
                                                <small><?php echo htmlspecialchars($office['email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo date('h:i A', strtotime($office['opening_time'])); ?> - <?php echo date('h:i A', strtotime($office['closing_time'])); ?></div>
                                                <small>Max: <?php echo $office['max_appointments_per_day']; ?>/day</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="status-wrapper">
                                                <span class="status-badge status-<?php echo $office['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <i class="bi bi-<?php echo $office['is_active'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                                    <?php echo $office['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- Edit Button -->
                                                <button type="button" class="btn-action btn-edit" 
                                                        data-id="<?php echo $office['id']; ?>"
                                                        data-code="<?php echo htmlspecialchars($office['office_code']); ?>"
                                                        data-name="<?php echo htmlspecialchars($office['office_name']); ?>"
                                                        data-type="<?php echo $office['office_type']; ?>"
                                                        data-address="<?php echo htmlspecialchars($office['address']); ?>"
                                                        data-city="<?php echo htmlspecialchars($office['city']); ?>"
                                                        data-state="<?php echo htmlspecialchars($office['state']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($office['phone']); ?>"
                                                        data-email="<?php echo htmlspecialchars($office['email']); ?>"
                                                        data-opening="<?php echo $office['opening_time']; ?>"
                                                        data-closing="<?php echo $office['closing_time']; ?>"
                                                        data-max="<?php echo $office['max_appointments_per_day']; ?>"
                                                        data-active="<?php echo $office['is_active']; ?>"
                                                        title="Edit Office">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <!-- Toggle Status Button -->
                                                <button type="button" class="btn-action btn-toggle-status <?php echo $office['is_active'] ? 'active' : 'inactive'; ?>" 
                                                        data-id="<?php echo $office['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($office['office_name']); ?>"
                                                        data-active="<?php echo $office['is_active']; ?>"
                                                        title="<?php echo $office['is_active'] ? 'Deactivate Office' : 'Activate Office'; ?>">
                                                    <i class="bi bi-<?php echo $office['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                </button>
                                                
                                                <!-- Delete Button -->
                                                <button type="button" class="btn-action btn-delete" 
                                                        data-id="<?php echo $office['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($office['office_name']); ?>"
                                                        title="Delete Office">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                        No offices found
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <!-- Page numbers -->
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="pagination-info">
                            Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_offices); ?> of <?php echo $total_offices; ?> offices
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- ADD OFFICE MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="addOfficeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-building-add"></i></div>
                    <h5 class="modal-title">Add New Office</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addOfficeForm" class="modal-form">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_code">Office Code</label>
                                <input type="text" id="add_code" name="office_code" class="form-control" 
                                       placeholder="e.g., NCS-HQ" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_name">Office Name</label>
                                <input type="text" id="add_name" name="office_name" class="form-control" 
                                       placeholder="Enter office name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_type">Office Type</label>
                                <select id="add_type" name="office_type" class="form-control" required>
                                    <option value="headquarters">Headquarters</option>
                                    <option value="zonal">Zonal</option>
                                    <option value="area_command">Area Command</option>
                                    <option value="port">Port</option>
                                    <option value="border">Border</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_max_appointments">Max Appointments/Day</label>
                                <input type="number" id="add_max_appointments" name="max_appointments_per_day" class="form-control" 
                                       placeholder="e.g., 50" value="50" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="add_address">Address</label>
                                <textarea id="add_address" name="address" class="form-control" rows="2" 
                                          placeholder="Enter full address" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="add_city">City</label>
                                <input type="text" id="add_city" name="city" class="form-control" 
                                       placeholder="Enter city" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="add_state">State</label>
                                <input type="text" id="add_state" name="state" class="form-control" 
                                       placeholder="Enter state" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="add_country">Country</label>
                                <input type="text" id="add_country" name="country" class="form-control" 
                                       placeholder="Enter country" value="Nigeria" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_phone">Phone Number</label>
                                <input type="tel" id="add_phone" name="phone" class="form-control" 
                                       placeholder="Enter phone number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_email">Email Address</label>
                                <input type="email" id="add_email" name="email" class="form-control" 
                                       placeholder="Enter email address">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_opening">Opening Time</label>
                                <input type="time" id="add_opening" name="opening_time" class="form-control" value="08:00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_closing">Closing Time</label>
                                <input type="time" id="add_closing" name="closing_time" class="form-control" value="17:00" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="add_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="add_active">Active Office</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="addOfficeBtn" class="btn btn-primary">
                        <span class="btn-text">Create Office</span>
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
<!-- EDIT OFFICE MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="editOfficeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-pencil"></i></div>
                    <h5 class="modal-title">Edit Office</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editOfficeForm" class="modal-form">
                <input type="hidden" id="edit_office_id" name="office_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_code">Office Code</label>
                                <input type="text" id="edit_code" name="office_code" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name">Office Name</label>
                                <input type="text" id="edit_name" name="office_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_type">Office Type</label>
                                <select id="edit_type" name="office_type" class="form-control" required>
                                    <option value="headquarters">Headquarters</option>
                                    <option value="zonal">Zonal</option>
                                    <option value="area_command">Area Command</option>
                                    <option value="port">Port</option>
                                    <option value="border">Border</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_max_appointments">Max Appointments/Day</label>
                                <input type="number" id="edit_max_appointments" name="max_appointments_per_day" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="edit_address">Address</label>
                                <textarea id="edit_address" name="address" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_city">City</label>
                                <input type="text" id="edit_city" name="city" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_state">State</label>
                                <input type="text" id="edit_state" name="state" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_country">Country</label>
                                <input type="text" id="edit_country" name="country" class="form-control" value="Nigeria" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_phone">Phone Number</label>
                                <input type="tel" id="edit_phone" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email Address</label>
                                <input type="email" id="edit_email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_opening">Opening Time</label>
                                <input type="time" id="edit_opening" name="opening_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_closing">Closing Time</label>
                                <input type="time" id="edit_closing" name="closing_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="edit_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_active">Active Office</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="editOfficeBtn" class="btn btn-primary">
                        <span class="btn-text">Update Office</span>
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
<!-- MANAGE OFFICES SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // FILTER FORM - Auto submit on change
    // ============================================
    $('#typeFilter, #statusFilter').on('change', function() {
        $('#filterForm').submit();
    });

    // ============================================
    // ADD OFFICE
    // ============================================
    $('#addOfficeForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'add_office',
            office_code: $('#add_code').val().trim(),
            office_name: $('#add_name').val().trim(),
            office_type: $('#add_type').val(),
            address: $('#add_address').val().trim(),
            city: $('#add_city').val().trim(),
            state: $('#add_state').val().trim(),
            country: $('#add_country').val().trim(),
            phone: $('#add_phone').val().trim(),
            email: $('#add_email').val().trim(),
            opening_time: $('#add_opening').val(),
            closing_time: $('#add_closing').val(),
            max_appointments_per_day: $('#add_max_appointments').val(),
            is_active: $('#add_active').is(':checked') ? 1 : 0
        };
        
        // Validate
        if (!formData.office_code || !formData.office_name || !formData.address || 
            !formData.city || !formData.state || !formData.phone) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all required fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#addOfficeBtn').prop('disabled', true);
        $('#addOfficeBtn .btn-text').hide();
        $('#addOfficeBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/offices-api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Add Office Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    $('#addOfficeModal').modal('hide');
                    $('#addOfficeForm')[0].reset();
                    
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
                
                $('#addOfficeBtn').prop('disabled', false);
                $('#addOfficeBtn .btn-text').show();
                $('#addOfficeBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Add Office Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#addOfficeBtn').prop('disabled', false);
                $('#addOfficeBtn .btn-text').show();
                $('#addOfficeBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // EDIT OFFICE - Load data into modal
    // ============================================
    $('.btn-edit').on('click', function() {
        const id = $(this).data('id');
        const code = $(this).data('code');
        const name = $(this).data('name');
        const type = $(this).data('type');
        const address = $(this).data('address');
        const city = $(this).data('city');
        const state = $(this).data('state');
        const phone = $(this).data('phone');
        const email = $(this).data('email');
        const opening = $(this).data('opening');
        const closing = $(this).data('closing');
        const max = $(this).data('max');
        const active = $(this).data('active');
        
        $('#edit_office_id').val(id);
        $('#edit_code').val(code);
        $('#edit_name').val(name);
        $('#edit_type').val(type);
        $('#edit_address').val(address);
        $('#edit_city').val(city);
        $('#edit_state').val(state);
        $('#edit_country').val('Nigeria');
        $('#edit_phone').val(phone);
        $('#edit_email').val(email);
        $('#edit_opening').val(opening);
        $('#edit_closing').val(closing);
        $('#edit_max_appointments').val(max);
        $('#edit_active').prop('checked', active == 1);
        
        $('#editOfficeModal').modal('show');
    });

    // ============================================
    // UPDATE OFFICE
    // ============================================
    $('#editOfficeForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update_office',
            office_id: $('#edit_office_id').val(),
            office_code: $('#edit_code').val().trim(),
            office_name: $('#edit_name').val().trim(),
            office_type: $('#edit_type').val(),
            address: $('#edit_address').val().trim(),
            city: $('#edit_city').val().trim(),
            state: $('#edit_state').val().trim(),
            country: $('#edit_country').val().trim(),
            phone: $('#edit_phone').val().trim(),
            email: $('#edit_email').val().trim(),
            opening_time: $('#edit_opening').val(),
            closing_time: $('#edit_closing').val(),
            max_appointments_per_day: $('#edit_max_appointments').val(),
            is_active: $('#edit_active').is(':checked') ? 1 : 0
        };
        
        if (!formData.office_code || !formData.office_name || !formData.address || 
            !formData.city || !formData.state || !formData.phone) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all required fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#editOfficeBtn').prop('disabled', true);
        $('#editOfficeBtn .btn-text').hide();
        $('#editOfficeBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/offices-api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Update Office Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    $('#editOfficeModal').modal('hide');
                    
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
                
                $('#editOfficeBtn').prop('disabled', false);
                $('#editOfficeBtn .btn-text').show();
                $('#editOfficeBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Update Office Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#editOfficeBtn').prop('disabled', false);
                $('#editOfficeBtn .btn-text').show();
                $('#editOfficeBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // TOGGLE OFFICE STATUS
    // ============================================
    $('.btn-toggle-status').on('click', function() {
        const officeId = $(this).data('id');
        const officeName = $(this).data('name');
        const currentStatus = $(this).data('active');
        const newStatus = currentStatus == 1 ? 'deactivate' : 'activate';
        const actionText = newStatus === 'activate' ? 'activate' : 'deactivate';
        const button = $(this);
        const icon = button.find('i');
        const label = button.find('.status-label');
        
        const title = newStatus === 'activate' ? 'Activate Office?' : 'Deactivate Office?';
        const text = `Are you sure you want to ${actionText} "${officeName}"?`;
        const confirmColor = newStatus === 'activate' ? '#28a745' : '#dc3545';
        
        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${actionText}`,
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                button.addClass('loading');
                icon.attr('class', 'bi bi-hourglass-split spinner');
                label.text('Loading...');
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>/api/offices-api.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_office_status',
                        office_id: officeId,
                        is_active: newStatus === 'activate' ? 1 : 0
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Toggle Status Response:', response);
                        
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
                            button.removeClass('loading');
                            icon.attr('class', currentStatus == 1 ? 'bi bi-toggle-on' : 'bi bi-toggle-off');
                            label.text(currentStatus == 1 ? 'Active' : 'Inactive');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Toggle Status Error:', error);
                        console.error('Response Text:', xhr.responseText);
                        
                        iziToast.error({
                            title: 'Error',
                            message: 'Something went wrong. Please check console for details.',
                            position: 'topRight',
                            timeout: 3000
                        });
                        button.removeClass('loading');
                        icon.attr('class', currentStatus == 1 ? 'bi bi-toggle-on' : 'bi bi-toggle-off');
                        label.text(currentStatus == 1 ? 'Active' : 'Inactive');
                    }
                });
            }
        });
    });

    // ============================================
    // DELETE OFFICE
    // ============================================
    $('.btn-delete').on('click', function() {
        const officeId = $(this).data('id');
        const officeName = $(this).data('name');
        const button = $(this);
        
        Swal.fire({
            title: 'Delete Office?',
            html: `Are you sure you want to delete <strong>"${officeName}"</strong>?<br><small style="color: #dc3545;">This will also delete all appointments associated with this office!</small>`,
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
                    url: '<?php echo BASE_URL; ?>/api/offices-api.php',
                    type: 'POST',
                    data: {
                        action: 'delete_office',
                        office_id: officeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Delete Office Response:', response);
                        
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
                        console.error('Delete Office Error:', error);
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