<?php
// =============================================
// MANAGE USERS - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect(BASE_URL . '/login.php');
}

// Set page title
$page_title = 'Manage Users';

// Include header
include_once __DIR__ . '/../includes/header.php';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where_clauses[] = "is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
    $types .= "i";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total users for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$count_stmt->close();

// Fetch users with pagination
$query = "SELECT id, uuid, first_name, last_name, email, phone, role, is_active, is_verified, 
          DATE_FORMAT(created_at, '%b %d, %Y') as created_date,
          DATE_FORMAT(last_login, '%b %d, %Y %h:%i %p') as last_login_formatted
          FROM users $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users_result = $stmt->get_result();
$stmt->close();

// Get roles for filter dropdown
$roles_query = "SELECT DISTINCT role FROM users ORDER BY role";
$roles_result = $conn->query($roles_query);

// Get role counts for stats
$role_counts_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$role_counts_result = $conn->query($role_counts_query);
$role_stats = [];
while ($row = $role_counts_result->fetch_assoc()) {
    $role_stats[$row['role']] = $row['count'];
}

// Get active/inactive counts
$active_count_query = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
$active_count = $conn->query($active_count_query)->fetch_assoc()['count'] ?? 0;
$inactive_count = $total_users - $active_count;
?>

<!-- ============================================ -->
<!-- MANAGE USERS CONTENT -->
<!-- ============================================ -->
<section class="dashboard-section">
    <div class="container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Manage Users</h1>
                <p class="dashboard-subtitle">Manage all users, their roles, and account status</p>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i>
                    Add User
                </button>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-grid stats-grid-sm">
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-users">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($total_users); ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-users">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($active_count); ?></span>
                    <span class="stat-label">Active</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-pending">
                    <i class="bi bi-person-x"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($inactive_count); ?></span>
                    <span class="stat-label">Inactive</span>
                </div>
            </div>
            <div class="stat-card stat-card-sm">
                <div class="stat-icon stat-icon-offices">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo number_format($role_stats['admin'] ?? 0); ?></span>
                    <span class="stat-label">Admins</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form id="filterForm" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="search" id="searchInput" class="form-control filter-search" 
                           placeholder="Search by name, email, or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <select name="role" id="roleFilter" class="form-control filter-select">
                        <option value="">All Roles</option>
                        <?php while ($role = $roles_result->fetch_assoc()): ?>
                            <option value="<?php echo $role['role']; ?>" <?php echo $role_filter == $role['role'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($role['role']); ?>
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
                <a href="<?php echo BASE_URL; ?>/admin/manage-users.php" class="btn-filter-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-table"></i>
                    Users List
                </h3>
                <span class="card-badge"><?php echo $total_users; ?> total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-dashboard">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email / Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users_result && $users_result->num_rows > 0): ?>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="table-user">
                                                <span class="user-avatar-sm">
                                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                </span>
                                                <span>
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                </span>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo htmlspecialchars($user['email']); ?></div>
                                                <small><?php echo htmlspecialchars($user['phone']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                        <span class="status-badge status-<?php echo $user['role']; ?>">
                                            <i class="bi bi-<?php 
                                                echo $user['role'] == 'admin' ? 'shield-check' : 
                                                     ($user['role'] == 'officer' ? 'person-badge' : 'person-check'); 
                                            ?>"></i>
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                        <td>
                                            <div class="status-wrapper">
                                                <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <i class="bi bi-<?php echo $user['is_active'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                                <?php if (!$user['is_verified']): ?>
                                                    <span class="status-badge status-pending" style="font-size: 9px;">
                                                        <i class="bi bi-clock-fill"></i> Unverified
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-contact">
                                                <div><?php echo $user['created_date']; ?></div>
                                                <?php if ($user['last_login_formatted']): ?>
                                                    <small>Last login: <?php echo $user['last_login_formatted']; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- Edit Button -->
                                                <button type="button" class="btn-action btn-edit" 
                                                        data-id="<?php echo $user['id']; ?>"
                                                        data-uuid="<?php echo $user['uuid']; ?>"
                                                        data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                                        data-lastname="<?php echo htmlspecialchars($user['last_name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                                                        data-role="<?php echo $user['role']; ?>"
                                                        data-active="<?php echo $user['is_active']; ?>"
                                                        data-verified="<?php echo $user['is_verified']; ?>"
                                                        title="Edit User">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <!-- Toggle Status Button -->
                                                <button type="button" class="btn-action btn-toggle-status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>" 
                                                        data-id="<?php echo $user['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                                        data-active="<?php echo $user['is_active']; ?>"
                                                        title="<?php echo $user['is_active'] ? 'Deactivate User' : 'Activate User'; ?>">
                                                    <i class="bi bi-<?php echo $user['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                                </button>
                                                
                                                <!-- Delete Button -->
                                                <button type="button" class="btn-action btn-delete" 
                                                        data-id="<?php echo $user['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                                        title="Delete User">
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
                                        No users found
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
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
                                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <div class="pagination-info">
                            Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_users); ?> of <?php echo $total_users; ?> users
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- ADD USER MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-person-plus"></i></div>
                    <h5 class="modal-title">Add New User</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm" class="modal-form">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_first_name">First Name</label>
                                <input type="text" id="add_first_name" name="first_name" class="form-control" 
                                       placeholder="Enter first name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_last_name">Last Name</label>
                                <input type="text" id="add_last_name" name="last_name" class="form-control" 
                                       placeholder="Enter last name" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="add_email">Email Address</label>
                                <input type="email" id="add_email" name="email" class="form-control" 
                                       placeholder="Enter email address" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="add_phone">Phone Number</label>
                                <input type="tel" id="add_phone" name="phone" class="form-control" 
                                       placeholder="Enter phone number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_password">Password</label>
                                <input type="password" id="add_password" name="password" class="form-control" 
                                       placeholder="Min 8 characters" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_role">Role</label>
                                <select id="add_role" name="role" class="form-control" required>
                                    <option value="applicant">Applicant</option>
                                    <option value="officer">Officer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="add_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="add_active">Active Account</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="addUserBtn" class="btn btn-primary">
                        <span class="btn-text">Create User</span>
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
<!-- EDIT USER MODAL -->
<!-- ============================================ -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content auth-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-icon"><i class="bi bi-pencil"></i></div>
                    <h5 class="modal-title">Edit User</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" class="modal-form">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_first_name">First Name</label>
                                <input type="text" id="edit_first_name" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_last_name">Last Name</label>
                                <input type="text" id="edit_last_name" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="edit_email">Email Address</label>
                                <input type="email" id="edit_email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="edit_phone">Phone Number</label>
                                <input type="tel" id="edit_phone" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_role">Role</label>
                                <select id="edit_role" name="role" class="form-control" required>
                                    <option value="applicant">Applicant</option>
                                    <option value="officer">Officer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_password">New Password (optional)</label>
                                <input type="password" id="edit_password" name="password" class="form-control" 
                                       placeholder="Leave blank to keep current">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="edit_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_active">Active Account</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="editUserBtn" class="btn btn-primary">
                        <span class="btn-text">Update User</span>
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
<!-- MANAGE USERS SCRIPTS -->
<!-- ============================================ -->
<script>
$(document).ready(function() {
    // ============================================
    // FILTER FORM - Auto submit on change
    // ============================================
    $('#roleFilter, #statusFilter').on('change', function() {
        $('#filterForm').submit();
    });

    // ============================================
    // ADD USER
    // ============================================
    $('#addUserForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'add_user',
            first_name: $('#add_first_name').val().trim(),
            last_name: $('#add_last_name').val().trim(),
            email: $('#add_email').val().trim(),
            phone: $('#add_phone').val().trim(),
            password: $('#add_password').val(),
            role: $('#add_role').val(),
            is_active: $('#add_active').is(':checked') ? 1 : 0
        };
        
        // Validate
        if (!formData.first_name || !formData.last_name || !formData.email || 
            !formData.phone || !formData.password) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        if (formData.password.length < 8) {
            iziToast.error({
                title: 'Error',
                message: 'Password must be at least 8 characters',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#addUserBtn').prop('disabled', true);
        $('#addUserBtn .btn-text').hide();
        $('#addUserBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/users-api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Add User Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    $('#addUserModal').modal('hide');
                    $('#addUserForm')[0].reset();
                    
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
                
                $('#addUserBtn').prop('disabled', false);
                $('#addUserBtn .btn-text').show();
                $('#addUserBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Add User Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#addUserBtn').prop('disabled', false);
                $('#addUserBtn .btn-text').show();
                $('#addUserBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // EDIT USER - Load data into modal
    // ============================================
    $('.btn-edit').on('click', function() {
        const id = $(this).data('id');
        const firstName = $(this).data('firstname');
        const lastName = $(this).data('lastname');
        const email = $(this).data('email');
        const phone = $(this).data('phone');
        const role = $(this).data('role');
        const active = $(this).data('active');
        const verified = $(this).data('verified');
        
        $('#edit_user_id').val(id);
        $('#edit_first_name').val(firstName);
        $('#edit_last_name').val(lastName);
        $('#edit_email').val(email);
        $('#edit_phone').val(phone);
        $('#edit_role').val(role);
        $('#edit_active').prop('checked', active == 1);
        $('#edit_password').val('');
        
        $('#editUserModal').modal('show');
    });

    // ============================================
    // UPDATE USER
    // ============================================
    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'update_user',
            user_id: $('#edit_user_id').val(),
            first_name: $('#edit_first_name').val().trim(),
            last_name: $('#edit_last_name').val().trim(),
            email: $('#edit_email').val().trim(),
            phone: $('#edit_phone').val().trim(),
            role: $('#edit_role').val(),
            is_active: $('#edit_active').is(':checked') ? 1 : 0,
            password: $('#edit_password').val()
        };
        
        if (!formData.first_name || !formData.last_name || !formData.email || !formData.phone) {
            iziToast.error({
                title: 'Error',
                message: 'Please fill in all required fields',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        if (formData.password && formData.password.length < 8) {
            iziToast.error({
                title: 'Error',
                message: 'Password must be at least 8 characters',
                position: 'topRight',
                timeout: 3000
            });
            return;
        }
        
        // Show loading
        $('#editUserBtn').prop('disabled', true);
        $('#editUserBtn .btn-text').hide();
        $('#editUserBtn .btn-loader').show();
        
        $.ajax({
            url: '<?php echo BASE_URL; ?>/api/users-api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Update User Response:', response);
                
                if (response.status === 'success') {
                    iziToast.success({
                        title: 'Success',
                        message: response.message,
                        position: 'topRight',
                        timeout: 2000
                    });
                    
                    $('#editUserModal').modal('hide');
                    
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
                
                $('#editUserBtn').prop('disabled', false);
                $('#editUserBtn .btn-text').show();
                $('#editUserBtn .btn-loader').hide();
            },
            error: function(xhr, status, error) {
                console.error('Update User Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                iziToast.error({
                    title: 'Error',
                    message: 'Something went wrong. Please check console for details.',
                    position: 'topRight',
                    timeout: 3000
                });
                
                $('#editUserBtn').prop('disabled', false);
                $('#editUserBtn .btn-text').show();
                $('#editUserBtn .btn-loader').hide();
            }
        });
    });

    // ============================================
    // TOGGLE USER STATUS - Fancy with colors
    // ============================================
    $('.btn-toggle-status').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        const currentStatus = $(this).data('active');
        const newStatus = currentStatus == 1 ? 'deactivate' : 'activate';
        const actionText = newStatus === 'activate' ? 'activate' : 'deactivate';
        const button = $(this);
        const icon = button.find('i');
        const label = button.find('.status-label');
        
        const title = newStatus === 'activate' ? 'Activate User?' : 'Deactivate User?';
        const text = `Are you sure you want to ${actionText} ${userName}?`;
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
                // Show loading state
                button.addClass('loading');
                icon.attr('class', 'bi bi-hourglass-split spinner');
                label.text('Loading...');
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>/api/users-api.php',
                    type: 'POST',
                    data: {
                        action: 'toggle_user_status',
                        user_id: userId,
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
    // DELETE USER
    // ============================================
    $('.btn-delete').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        const button = $(this);
        
        Swal.fire({
            title: 'Delete User?',
            html: `Are you sure you want to delete <strong>${userName}</strong>?<br><small style="color: #dc3545;">This action cannot be undone!</small>`,
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
                    url: '<?php echo BASE_URL; ?>/api/users-api.php',
                    type: 'POST',
                    data: {
                        action: 'delete_user',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Delete User Response:', response);
                        
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
                        console.error('Delete User Error:', error);
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