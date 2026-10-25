<?php
// =============================================
// AUTHENTICATION API - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch($action) {
    
    case 'login':
        loginUser($conn);
        break;
        
    case 'register':
        registerUser($conn);
        break;
        
    case 'check_email':
        checkEmail($conn);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
        break;
}

// ============================================
// LOGIN FUNCTION
// ============================================
function loginUser($conn) {
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    // Validation
    if(empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter your email address'
        ]);
        return;
    }
    
    if(empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password is required'
        ]);
        return;
    }
    
    // Check if user exists
    $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.password_hash, u.role, u.phone, u.is_active,
                     CONCAT(u.first_name, ' ', u.last_name) AS full_name
              FROM users u
              WHERE u.email = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $conn->error
        ]);
        return;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if account is active
    if($user['is_active'] != 1) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Your account is inactive. Please contact administrator.'
        ]);
        return;
    }
    
    // Verify password
    $password_valid = false;
    $needs_rehash = false;
    
    if (strpos($user['password_hash'], '$2y$') === 0) {
        $password_valid = password_verify($password, $user['password_hash']);
        if ($password_valid) {
            $needs_rehash = password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT);
        }
    } else {
        $password_valid = ($password == $user['password_hash']);
        if ($password_valid) $needs_rehash = true;
    }
    
    if($password_valid) {
        
        if ($needs_rehash) {
            $new_hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_pass = "UPDATE users SET password_hash = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_pass);
            $update_stmt->bind_param("si", $new_hashed, $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['logged_in'] = true;
        
        // Remember me
        if ($remember) {
            setcookie('remember_email', $user['email'], time() + (86400 * 30), "/");
        } else {
            if (isset($_COOKIE['remember_email'])) {
                setcookie('remember_email', '', time() - 3600, "/");
            }
        }
        
        // Update last login
        $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Log activity - FIXED: Use JSON format for details
        $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                      VALUES (?, 'login', 'user', ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $details = json_encode([
            'email' => $user['email'],
            'role' => $user['role'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $log_stmt->bind_param("iss", $user['id'], $details, $ip);
        $log_stmt->execute();
        $log_stmt->close();
        
        // Determine redirect based on role
        if ($user['role'] === 'admin') {
            $redirect = 'admin/dashboard.php';
        } elseif ($user['role'] === 'officer') {
            $redirect = 'officer/dashboard.php';
        } else {
            $redirect = 'applicant/dashboard.php';
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful! Redirecting...',
            'redirect' => $redirect,
            'role' => $user['role']
        ]);
        
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
    }
}

// ============================================
// REGISTER FUNCTION - FIXED JSON handling
// ============================================
function registerUser($conn) {
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = 'applicant';
    
    // Validation
    if(empty($first_name)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter your first name'
        ]);
        return;
    }
    
    if(empty($last_name)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter your last name'
        ]);
        return;
    }
    
    if(empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter your email address'
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
    
    if(empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please create a password'
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
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email address already registered'
        ]);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate UUID
    $uuid = generateUUID();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert user
        $query = "INSERT INTO users (uuid, first_name, last_name, email, phone, password_hash, role, is_active, is_verified) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $uuid, $first_name, $last_name, $email, $phone, $hashed_password, $role);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to create user: " . $stmt->error);
        }
        
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        // Log the registration - FIXED: Use JSON format for details
        $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                      VALUES (?, 'register', 'user', ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $details = json_encode([
            'email' => $email,
            'full_name' => $first_name . ' ' . $last_name,
            'role' => $role,
            'registered_at' => date('Y-m-d H:i:s')
        ]);
        $log_stmt->bind_param("iss", $user_id, $details, $ip);
        
        if(!$log_stmt->execute()) {
            throw new Exception("Failed to log registration: " . $log_stmt->error);
        }
        $log_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Auto-login after registration
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['phone'] = $phone;
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Account created successfully! Redirecting...',
            'redirect' => 'applicant/dashboard.php'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Registration failed: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// CHECK EMAIL FUNCTION
// ============================================
function checkEmail($conn) {
    $email = trim($_POST['email'] ?? '');
    
    if(empty($email)) {
        echo json_encode(['exists' => false]);
        return;
    }
    
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
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
// CREATE DEFAULT ADMIN USER (if not exists)
// ============================================
function createDefaultAdmin($conn) {
    $email = 'admin@customs.gov.ng';
    $password = 'Admin@123';
    
    // Check if admin exists
    $check = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $uuid = generateUUID();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $insert = "INSERT INTO users (uuid, first_name, last_name, email, phone, password_hash, role, is_active, is_verified) 
                       VALUES (?, 'System', 'Administrator', ?, '08000000000', ?, 'admin', 1, 1)";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("sss", $uuid, $email, $hashed);
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to create admin");
            }
            
            $admin_id = $stmt->insert_id;
            $stmt->close();
            
            // Log admin creation - FIXED: Use JSON format
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_created', 'user', ?, '127.0.0.1')";
            $log_stmt = $conn->prepare($log_query);
            $details = json_encode([
                'email' => $email,
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $log_stmt->bind_param("is", $admin_id, $details);
            $log_stmt->execute();
            $log_stmt->close();
            
            $conn->commit();
            error_log("Default admin created: admin@customs.gov.ng / Admin@123");
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Failed to create default admin: " . $e->getMessage());
        }
    }
    $stmt->close();
}

// Create default admin if no users exist
$check_users = "SELECT COUNT(*) as count FROM users";
$result = $conn->query($check_users);
if ($result && $result->fetch_assoc()['count'] == 0) {
    createDefaultAdmin($conn);
}
?>