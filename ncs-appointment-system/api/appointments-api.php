<?php
// =============================================
// APPOINTMENTS API - NCS Online Appointment System
// Nigeria Customs Service
// =============================================

session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if logged in
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

error_log("Appointments API Request: " . print_r($_POST, true));

switch($action) {
    
    case 'approve_appointment':
        approveAppointment($conn);
        break;
        
    case 'reject_appointment':
        rejectAppointment($conn);
        break;
        
    case 'delete_appointment':
        deleteAppointment($conn);
        break;
        
    case 'get_appointment_details':
        getAppointmentDetails($conn);
        break;
        
    case 'update_appointment':
        updateAppointment($conn);
        break;
        
        case 'book_appointment':
    bookAppointment($conn);
    break;

    case 'cancel_appointment':
    cancelAppointment($conn);
    break;


    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action: ' . $action
        ]);
        break;
}

// ============================================
// APPROVE APPOINTMENT - FIXED
// ============================================
function approveAppointment($conn) {
    try {
        $appointment_id = (int)$_POST['appointment_id'];
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Check if user has permission (admin or officer)
        if (!in_array($user_role, ['admin', 'officer'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to approve appointments'
            ]);
            return;
        }
        
        // Get appointment details
        $get = "SELECT appointment_number, user_id, status FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($get);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$appointment) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment not found'
            ]);
            return;
        }
        
        if ($appointment['status'] != 'pending') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment is already ' . $appointment['status']
            ]);
            return;
        }
        
        // Update appointment
        $query = "UPDATE appointments SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $appointment_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'appointment_approved', 'appointment', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'appointment_id' => $appointment_id,
                'appointment_number' => $appointment['appointment_number'],
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Create notification for applicant - FIXED
            $notif_query = "INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id) 
                            VALUES (?, 'in_app', 'Appointment Approved', ?, 'appointment', ?)";
            $notif_stmt = $conn->prepare($notif_query);
            $message = 'Your appointment #' . $appointment['appointment_number'] . ' has been approved.';
            $notif_stmt->bind_param("isi", $appointment['user_id'], $message, $appointment_id);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment approved successfully'
            ]);
        } else {
            error_log("Approve Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to approve appointment'
            ]);
        }
    } catch (Exception $e) {
        error_log("Approve Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// REJECT APPOINTMENT - FIXED
// ============================================
function rejectAppointment($conn) {
    try {
        $appointment_id = (int)$_POST['appointment_id'];
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Check if user has permission (admin or officer)
        if (!in_array($user_role, ['admin', 'officer'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to reject appointments'
            ]);
            return;
        }
        
        if(empty($rejection_reason)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Rejection reason is required'
            ]);
            return;
        }
        
        // Get appointment details
        $get = "SELECT appointment_number, user_id, status FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($get);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$appointment) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment not found'
            ]);
            return;
        }
        
        if ($appointment['status'] != 'pending') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment is already ' . $appointment['status']
            ]);
            return;
        }
        
        // Update appointment
        $query = "UPDATE appointments SET status = 'rejected', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $rejection_reason, $user_id, $appointment_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'appointment_rejected', 'appointment', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'appointment_id' => $appointment_id,
                'appointment_number' => $appointment['appointment_number'],
                'reason' => $rejection_reason,
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Create notification for applicant - FIXED
            $notif_query = "INSERT INTO notifications (user_id, type, title, message, entity_type, entity_id) 
                            VALUES (?, 'in_app', 'Appointment Rejected', ?, 'appointment', ?)";
            $notif_stmt = $conn->prepare($notif_query);
            $message = 'Your appointment #' . $appointment['appointment_number'] . ' has been rejected. Reason: ' . $rejection_reason;
            $notif_stmt->bind_param("isi", $appointment['user_id'], $message, $appointment_id);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment rejected successfully'
            ]);
        } else {
            error_log("Reject Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to reject appointment'
            ]);
        }
    } catch (Exception $e) {
        error_log("Reject Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// DELETE APPOINTMENT
// ============================================
function deleteAppointment($conn) {
    try {
        $appointment_id = (int)$_POST['appointment_id'];
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Only admin can delete
        if ($user_role != 'admin') {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to delete appointments'
            ]);
            return;
        }
        
        // Get appointment details for logging
        $get = "SELECT appointment_number FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($get);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$appointment) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment not found'
            ]);
            return;
        }
        
        // Delete appointment
        $query = "DELETE FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $appointment_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'appointment_deleted', 'appointment', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'appointment_id' => $appointment_id,
                'appointment_number' => $appointment['appointment_number'],
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment deleted successfully'
            ]);
        } else {
            error_log("Delete Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete appointment'
            ]);
        }
    } catch (Exception $e) {
        error_log("Delete Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// GET APPOINTMENT DETAILS
// ============================================
function getAppointmentDetails($conn) {
    try {
        $appointment_id = (int)$_POST['appointment_id'];
        
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
                  WHERE a.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment not found'
            ]);
            $stmt->close();
            return;
        }
        
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'data' => $appointment
        ]);
        
    } catch (Exception $e) {
        error_log("Get Appointment Details Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// UPDATE APPOINTMENT
// ============================================
function updateAppointment($conn) {
    try {
        $appointment_id = (int)$_POST['appointment_id'];
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Only admin can update
        if ($user_role != 'admin') {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to update appointments'
            ]);
            return;
        }
        
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';
        $purpose = trim($_POST['purpose'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $office_id = (int)($_POST['office_id'] ?? 0);
        $duration_minutes = (int)($_POST['duration_minutes'] ?? 30);
        
        // Validation
        if (empty($appointment_date) || empty($appointment_time) || empty($purpose) || $office_id <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        // Update appointment
        $query = "UPDATE appointments SET 
                  appointment_date = ?, 
                  appointment_time = ?, 
                  purpose = ?, 
                  description = ?, 
                  office_id = ?, 
                  duration_minutes = ? 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssiii", $appointment_date, $appointment_time, $purpose, $description, $office_id, $duration_minutes, $appointment_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'appointment_updated', 'appointment', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'appointment_id' => $appointment_id,
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment updated successfully'
            ]);
        } else {
            error_log("Update Appointment Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update appointment'
            ]);
        }
    } catch (Exception $e) {
        error_log("Update Appointment Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}










// ============================================
// BOOK APPOINTMENT (Applicant)
// ============================================
function bookAppointment($conn) {
    try {
        $user_id = $_SESSION['user_id'];
        $office_id = (int)$_POST['office_id'];
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';
        $purpose = trim($_POST['purpose'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration_minutes = (int)($_POST['duration_minutes'] ?? 30);
        
        // Validation
        if(empty($office_id) || empty($appointment_date) || empty($appointment_time) || empty($purpose)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields'
            ]);
            return;
        }
        
        // Check if office exists and is active
        $office_check = "SELECT id FROM offices WHERE id = ? AND is_active = 1";
        $stmt = $conn->prepare($office_check);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows == 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Selected office is not available'
            ]);
            $stmt->close();
            return;
        }
        $stmt->close();
        
        // Check if slot is available
        $check_query = "SELECT COUNT(*) as booked FROM appointments 
                        WHERE office_id = ? AND appointment_date = ? AND appointment_time = ? 
                        AND status NOT IN ('cancelled', 'rejected')";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iss", $office_id, $appointment_date, $appointment_time);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $booked = $check_result->fetch_assoc()['booked'] ?? 0;
        $check_stmt->close();
        
        // Get max appointments per slot
        $max_query = "SELECT max_appointments FROM time_slots 
                      WHERE office_id = ? AND slot_time = ? AND is_active = 1";
        $max_stmt = $conn->prepare($max_query);
        $max_stmt->bind_param("is", $office_id, $appointment_time);
        $max_stmt->execute();
        $max_result = $max_stmt->get_result();
        $max_slot = $max_result->fetch_assoc();
        $max_stmt->close();
        
        $max_appointments = $max_slot['max_appointments'] ?? 1;
        
        if($booked >= $max_appointments) {
            echo json_encode([
                'status' => 'error',
                'message' => 'This time slot is fully booked. Please select another time.'
            ]);
            return;
        }
        
        // Check if user already has a pending appointment on same day
        $duplicate_query = "SELECT COUNT(*) as count FROM appointments 
                            WHERE user_id = ? AND appointment_date = ? 
                            AND status IN ('pending', 'approved')";
        $duplicate_stmt = $conn->prepare($duplicate_query);
        $duplicate_stmt->bind_param("is", $user_id, $appointment_date);
        $duplicate_stmt->execute();
        $duplicate_result = $duplicate_stmt->get_result();
        $duplicate_count = $duplicate_result->fetch_assoc()['count'] ?? 0;
        $duplicate_stmt->close();
        
        if($duplicate_count > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You already have a pending or approved appointment on this day.'
            ]);
            return;
        }
        
        // Insert appointment
        $query = "INSERT INTO appointments (user_id, office_id, appointment_date, appointment_time, 
                  duration_minutes, purpose, description, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iississ", $user_id, $office_id, $appointment_date, $appointment_time, 
                         $duration_minutes, $purpose, $description);
        
        if($stmt->execute()) {
            $appointment_id = $stmt->insert_id;
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'appointment_booked', 'appointment', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'appointment_id' => $appointment_id,
                'office_id' => $office_id,
                'date' => $appointment_date,
                'time' => $appointment_time,
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment booked successfully! You will be notified once it is reviewed.'
            ]);
        } else {
            error_log("Book Appointment Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to book appointment'
            ]);
        }
    } catch (Exception $e) {
        error_log("Book Appointment Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}











// ============================================
// CANCEL APPOINTMENT (Applicant)
// ============================================
function cancelAppointment($conn) {
    try {
        $appointment_id = (int)$_POST['appointment_id'];
        $user_id = $_SESSION['user_id'];
        
        // Get appointment details
        $get = "SELECT appointment_number, user_id, status FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($get);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$appointment) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Appointment not found'
            ]);
            return;
        }
        
        // Check if user owns this appointment
        if ($appointment['user_id'] != $user_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to cancel this appointment'
            ]);
            return;
        }
        
        // Check if appointment can be cancelled
        if ($appointment['status'] != 'pending') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only pending appointments can be cancelled'
            ]);
            return;
        }
        
        // Update appointment
        $query = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $appointment_id);
        
        if($stmt->execute()) {
            $stmt->close();
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'appointment_cancelled', 'appointment', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'appointment_id' => $appointment_id,
                'appointment_number' => $appointment['appointment_number'],
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Appointment cancelled successfully'
            ]);
        } else {
            error_log("Cancel Appointment Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to cancel appointment'
            ]);
        }
    } catch (Exception $e) {
        error_log("Cancel Appointment Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}
















?>