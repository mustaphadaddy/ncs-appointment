<?php
// =============================================
// USERS API - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if logged in and is admin
if (!isLoggedIn() ) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Log the request for debugging
error_log("Users API Request: " . print_r($_POST, true));

switch($action) {
    
    case 'add_user':
        addUser($conn);
        break;
        
    case 'update_user':
        updateUser($conn);
        break;
        
    case 'toggle_user_status':
        toggleUserStatus($conn);
        break;
        
    case 'delete_user':
        deleteUser($conn);
        break;




// Add to the switch statement
case 'update_profile':
    updateProfile($conn);
    break;
    
case 'change_password':
    changePassword($conn);
    break;
    
case 'logout_all':
    logoutAll($conn);
    break;

            
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action: ' . $action
        ]);
        break;
}

// ============================================
// ADD USER
// ============================================
function addUser($conn) {
    try {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'applicant';
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        // Validation
        if(empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address'
            ]);
            return;
        }
        
        if(strlen($password) < 8) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters'
            ]);
            return;
        }
        
        // Check if email exists
        $check = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email address already exists'
            ]);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $uuid = generateUUID();
        
        // Insert user
        $query = "INSERT INTO users (uuid, first_name, last_name, email, phone, password_hash, role, is_active, is_verified) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", $uuid, $first_name, $last_name, $email, $phone, $hashed_password, $role, $is_active);
        
        if($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_add_user', 'user', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'added_user' => $email,
                'role' => $role,
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'User added successfully'
            ]);
        } else {
            error_log("Add User Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add user: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        error_log("Add User Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// UPDATE USER
// ============================================
function updateUser($conn) {
    try {
        $user_id = (int)$_POST['user_id'];
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'applicant';
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $password = trim($_POST['password'] ?? '');
        
        // Validation
        if(empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address'
            ]);
            return;
        }
        
        if(!empty($password) && strlen($password) < 8) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters'
            ]);
            return;
        }
        
        // Check if email exists (excluding current user)
        $check = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email address already exists'
            ]);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Build update query
        $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, is_active = ?";
        $params = [$first_name, $last_name, $email, $phone, $role, $is_active];
        $types = "sssssi";
        
        if(!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query .= ", password_hash = ?";
            $params[] = $hashed_password;
            $types .= "s";
        }
        
        $query .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_update_user', 'user', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'updated_user_id' => $user_id,
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'User updated successfully'
            ]);
        } else {
            error_log("Update User Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update user: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        error_log("Update User Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// TOGGLE USER STATUS
// ============================================
function toggleUserStatus($conn) {
    try {
        $user_id = (int)$_POST['user_id'];
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        $query = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $is_active, $user_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            $status_text = $is_active ? 'activated' : 'deactivated';
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_toggle_user', 'user', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'user_id' => $user_id,
                'action' => $status_text,
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'User ' . $status_text . ' successfully'
            ]);
        } else {
            error_log("Toggle Status Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update user status'
            ]);
        }
    } catch (Exception $e) {
        error_log("Toggle Status Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// DELETE USER
// ============================================
function deleteUser($conn) {
    try {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting self
        if($user_id == $_SESSION['user_id']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You cannot delete your own account'
            ]);
            return;
        }
        
        // Get user details for logging
        $get_user = "SELECT email FROM users WHERE id = ?";
        $stmt = $conn->prepare($get_user);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if(!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]);
            return;
        }
        
        // Delete user (cascade will handle related records)
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_delete_user', 'user', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'deleted_user_id' => $user_id,
                'deleted_user_email' => $user['email'],
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ]);
        } else {
            error_log("Delete User Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete user'
            ]);
        }
    } catch (Exception $e) {
        error_log("Delete User Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// GENERATE UUID
// ============================================
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}









// ============================================
// UPDATE PROFILE
// ============================================
// ============================================
// UPDATE PROFILE - FIXED with all fields
// ============================================
function updateProfile($conn) {
    try {
        $user_id = $_SESSION['user_id'];
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $nin = trim($_POST['nin'] ?? '');
        
        // Validation
        if(empty($first_name) || empty($last_name) || empty($email) || empty($phone) ||
           empty($date_of_birth) || empty($gender) || empty($address) || 
           empty($city) || empty($state) || empty($nin)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address'
            ]);
            return;
        }
        
        // Check if email exists (excluding current user)
        $check = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Email address already exists'
            ]);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Update user
        $query = "UPDATE users SET 
                  first_name = ?, 
                  last_name = ?, 
                  email = ?, 
                  phone = ?, 
                  date_of_birth = ?, 
                  gender = ?, 
                  address = ?, 
                  city = ?, 
                  state = ?, 
                  nin = ? 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssi", $first_name, $last_name, $email, $phone, 
                         $date_of_birth, $gender, $address, $city, $state, $nin, $user_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Update session
            $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'profile_update', 'user', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'updated_fields' => ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'gender', 'address', 'city', 'state', 'nin'],
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully'
            ]);
        } else {
            error_log("Update Profile Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update profile'
            ]);
        }
    } catch (Exception $e) {
        error_log("Update Profile Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// CHANGE PASSWORD
// ============================================
function changePassword($conn) {
    try {
        $user_id = $_SESSION['user_id'];
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        // Validation
        if(empty($current_password) || empty($new_password)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all password fields'
            ]);
            return;
        }
        
        if(strlen($new_password) < 8) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters'
            ]);
            return;
        }
        
        // Get current password hash
        $query = "SELECT password_hash FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if(!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]);
            return;
        }
        
        // Verify current password
        $password_valid = false;
        if (strpos($user['password_hash'], '$2y$') === 0) {
            $password_valid = password_verify($current_password, $user['password_hash']);
        } else {
            $password_valid = ($current_password == $user['password_hash']);
        }
        
        if(!$password_valid) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ]);
            return;
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'password_change', 'user', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'password_changed' => true,
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } else {
            error_log("Change Password Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to change password'
            ]);
        }
    } catch (Exception $e) {
        error_log("Change Password Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// LOGOUT ALL DEVICES
// ============================================
function logoutAll($conn) {
    try {
        $user_id = $_SESSION['user_id'];
        
        // Log the logout all action
        $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                      VALUES (?, 'logout_all', 'user', ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $details = json_encode([
            'action' => 'logout_all_devices',
            'by_user' => $user_id
        ]);
        $log_stmt->bind_param("iss", $user_id, $details, $ip);
        $log_stmt->execute();
        $log_stmt->close();
        
        // Clear session
        session_destroy();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Logged out from all devices'
        ]);
    } catch (Exception $e) {
        error_log("Logout All Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}











?>