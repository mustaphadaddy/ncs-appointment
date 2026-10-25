<?php
// =============================================
// OFFICES API - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Log the request for debugging
error_log("Offices API Request: " . print_r($_POST, true));

switch($action) {
    
    case 'add_office':
        addOffice($conn);
        break;
        
    case 'update_office':
        updateOffice($conn);
        break;
        
    case 'toggle_office_status':
        toggleOfficeStatus($conn);
        break;
        
    case 'delete_office':
        deleteOffice($conn);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action: ' . $action
        ]);
        break;
}

// ============================================
// ADD OFFICE
// ============================================
function addOffice($conn) {
    try {
        $office_code = trim($_POST['office_code'] ?? '');
        $office_name = trim($_POST['office_name'] ?? '');
        $office_type = $_POST['office_type'] ?? 'area_command';
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? 'Nigeria');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $opening_time = $_POST['opening_time'] ?? '08:00:00';
        $closing_time = $_POST['closing_time'] ?? '17:00:00';
        $max_appointments_per_day = (int)($_POST['max_appointments_per_day'] ?? 50);
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        // Validation
        if(empty($office_code) || empty($office_name) || empty($address) || 
           empty($city) || empty($state) || empty($phone)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        // Check if office code exists
        $check = "SELECT id FROM offices WHERE office_code = ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param("s", $office_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Office code already exists'
            ]);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Insert office
        $query = "INSERT INTO offices (office_code, office_name, office_type, address, city, state, country,
                  phone, email, opening_time, closing_time, max_appointments_per_day, is_active, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $created_by = $_SESSION['user_id'];
        $stmt->bind_param("sssssssssssiii", $office_code, $office_name, $office_type, $address, $city, $state, 
                         $country, $phone, $email, $opening_time, $closing_time, $max_appointments_per_day, 
                         $is_active, $created_by);
        
        if($stmt->execute()) {
            $office_id = $stmt->insert_id;
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_add_office', 'office', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'office_name' => $office_name,
                'office_code' => $office_code,
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Office added successfully'
            ]);
        } else {
            error_log("Add Office Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add office: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        error_log("Add Office Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// UPDATE OFFICE
// ============================================
function updateOffice($conn) {
    try {
        $office_id = (int)$_POST['office_id'];
        $office_code = trim($_POST['office_code'] ?? '');
        $office_name = trim($_POST['office_name'] ?? '');
        $office_type = $_POST['office_type'] ?? 'area_command';
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? 'Nigeria');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $opening_time = $_POST['opening_time'] ?? '08:00:00';
        $closing_time = $_POST['closing_time'] ?? '17:00:00';
        $max_appointments_per_day = (int)($_POST['max_appointments_per_day'] ?? 50);
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        // Validation
        if(empty($office_code) || empty($office_name) || empty($address) || 
           empty($city) || empty($state) || empty($phone)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        // Check if office code exists (excluding current)
        $check = "SELECT id FROM offices WHERE office_code = ? AND id != ?";
        $stmt = $conn->prepare($check);
        $stmt->bind_param("si", $office_code, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Office code already exists'
            ]);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Update office
        $query = "UPDATE offices SET office_code = ?, office_name = ?, office_type = ?, address = ?, 
                  city = ?, state = ?, country = ?, phone = ?, email = ?, opening_time = ?, 
                  closing_time = ?, max_appointments_per_day = ?, is_active = ? 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssssssiii", $office_code, $office_name, $office_type, $address, $city, $state, 
                         $country, $phone, $email, $opening_time, $closing_time, $max_appointments_per_day, 
                         $is_active, $office_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_update_office', 'office', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'office_id' => $office_id,
                'office_name' => $office_name,
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Office updated successfully'
            ]);
        } else {
            error_log("Update Office Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update office: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        error_log("Update Office Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// TOGGLE OFFICE STATUS
// ============================================
function toggleOfficeStatus($conn) {
    try {
        $office_id = (int)$_POST['office_id'];
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        $query = "UPDATE offices SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $is_active, $office_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            $status_text = $is_active ? 'activated' : 'deactivated';
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_toggle_office', 'office', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'office_id' => $office_id,
                'action' => $status_text,
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Office ' . $status_text . ' successfully'
            ]);
        } else {
            error_log("Toggle Office Status Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update office status'
            ]);
        }
    } catch (Exception $e) {
        error_log("Toggle Office Status Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// DELETE OFFICE
// ============================================
function deleteOffice($conn) {
    try {
        $office_id = (int)$_POST['office_id'];
        
        // Get office details for logging
        $get_office = "SELECT office_name FROM offices WHERE id = ?";
        $stmt = $conn->prepare($get_office);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $office = $result->fetch_assoc();
        $stmt->close();
        
        if(!$office) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Office not found'
            ]);
            return;
        }
        
        // Check if office has appointments
        $check_appointments = "SELECT COUNT(*) as count FROM appointments WHERE office_id = ?";
        $stmt = $conn->prepare($check_appointments);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointments = $result->fetch_assoc();
        $stmt->close();
        
        if($appointments['count'] > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot delete office with ' . $appointments['count'] . ' existing appointments'
            ]);
            return;
        }
        
        // Delete office
        $query = "DELETE FROM offices WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $office_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'admin_delete_office', 'office', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'office_id' => $office_id,
                'office_name' => $office['office_name'],
                'by_user' => $_SESSION['user_id']
            ]);
            $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Office deleted successfully'
            ]);
        } else {
            error_log("Delete Office Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete office'
            ]);
        }
    } catch (Exception $e) {
        error_log("Delete Office Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}
?>