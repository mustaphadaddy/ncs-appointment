<?php
// =============================================
// SETTINGS API - NCS Online Appointment System
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

error_log("Settings API Request: " . print_r($_POST, true));

switch($action) {
    
    case 'save_settings':
        saveSettings($conn);
        break;
        
    case 'reset_settings':
        resetSettings($conn);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action: ' . $action
        ]);
        break;
}

// ============================================
// SAVE SETTINGS
// ============================================
function saveSettings($conn) {
    try {
        $settings = $_POST['settings'] ?? [];
        $user_id = $_SESSION['user_id'];
        
        if (empty($settings)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No settings to save'
            ]);
            return;
        }
        
        // Default values for settings
        $defaults = [
            'site_name' => 'NCS Appointment System',
            'timezone' => 'Africa/Lagos',
            'appointment_max_per_day' => 50,
            'appointment_advance_days' => 30,
            'appointment_cancel_hours' => 24,
            'auto_approve_appointments' => 'false',
            'enable_notifications' => 'true',
            'enable_appointment_reminders' => 'true',
            'reminder_days_before' => 1,
            'max_login_attempts' => 5,
            'lockout_duration' => 30,
            'session_timeout' => 60,
            'maintenance_mode' => 'false',
            'debug_mode' => 'false',
            'enable_document_upload' => 'true',
            'max_upload_size' => 10
        ];
        
        $conn->begin_transaction();
        
        foreach ($settings as $key => $value) {
            // Sanitize and validate
            $key = trim($key);
            $value = trim($value);
            
            // Skip invalid keys
            if (!array_key_exists($key, $defaults)) {
                continue;
            }
            
            // Determine setting type
            $type = 'string';
            if (is_numeric($value) && strpos($value, '.') === false) {
                $type = 'integer';
            } elseif ($value === 'true' || $value === 'false') {
                $type = 'boolean';
            } elseif (strpos($value, '{') === 0 || strpos($value, '[') === 0) {
                $type = 'json';
            }
            
            // Check if setting exists
            $check_query = "SELECT id FROM system_settings WHERE setting_key = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $key);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing setting
                $update_query = "UPDATE system_settings SET setting_value = ?, setting_type = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssis", $value, $type, $user_id, $key);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new setting
                $insert_query = "INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_by) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sssi", $key, $value, $type, $user_id);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
        
        // Log activity
        $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                      VALUES (?, 'settings_updated', 'system', ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $details = json_encode([
            'updated_settings' => array_keys($settings),
            'by_user' => $user_id
        ]);
        $log_stmt->bind_param("iss", $user_id, $details, $ip);
        $log_stmt->execute();
        $log_stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Settings saved successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Save Settings Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// RESET SETTINGS
// ============================================
function resetSettings($conn) {
    try {
        $user_id = $_SESSION['user_id'];
        
        // Default values
        $defaults = [
            'site_name' => 'NCS Appointment System',
            'timezone' => 'Africa/Lagos',
            'appointment_max_per_day' => '50',
            'appointment_advance_days' => '30',
            'appointment_cancel_hours' => '24',
            'auto_approve_appointments' => 'false',
            'enable_notifications' => 'true',
            'enable_appointment_reminders' => 'true',
            'reminder_days_before' => '1',
            'max_login_attempts' => '5',
            'lockout_duration' => '30',
            'session_timeout' => '60',
            'maintenance_mode' => 'false',
            'debug_mode' => 'false',
            'enable_document_upload' => 'true',
            'max_upload_size' => '10'
        ];
        
        $conn->begin_transaction();
        
        foreach ($defaults as $key => $value) {
            // Determine setting type
            $type = 'string';
            if (is_numeric($value) && strpos($value, '.') === false) {
                $type = 'integer';
            } elseif ($value === 'true' || $value === 'false') {
                $type = 'boolean';
            }
            
            // Check if setting exists
            $check_query = "SELECT id FROM system_settings WHERE setting_key = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $key);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing setting
                $update_query = "UPDATE system_settings SET setting_value = ?, setting_type = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssis", $value, $type, $user_id, $key);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new setting
                $insert_query = "INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_by) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sssi", $key, $value, $type, $user_id);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
        
        // Log activity
        $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                      VALUES (?, 'settings_reset', 'system', ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $details = json_encode([
            'action' => 'reset_to_defaults',
            'by_user' => $user_id
        ]);
        $log_stmt->bind_param("iss", $user_id, $details, $ip);
        $log_stmt->execute();
        $log_stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Settings reset to defaults successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Reset Settings Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}
?>