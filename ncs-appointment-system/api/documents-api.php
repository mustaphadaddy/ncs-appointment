<?php
// =============================================
// DOCUMENTS API - NCS Online Appointment System
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

// Get action from POST or GET
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

error_log("Documents API Request - Action: " . $action);
error_log("Documents API POST Data: " . print_r($_POST, true));
error_log("Documents API FILES Data: " . print_r($_FILES, true));

switch($action) {
    
    case 'upload_document':
        uploadDocument($conn);
        break;
        
    case 'delete_document':
        deleteDocument($conn);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action: ' . $action
        ]);
        break;
}

// ============================================
// UPLOAD DOCUMENT
// ============================================
function uploadDocument($conn) {
    try {
        $user_id = $_SESSION['user_id'];
        $document_type = $_POST['document_type'] ?? '';
        $appointment_id = isset($_POST['appointment_id']) && $_POST['appointment_id'] ? (int)$_POST['appointment_id'] : null;
        $file_description = trim($_POST['file_description'] ?? '');
        
        // Validation
        if(empty($document_type)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please select a document type'
            ]);
            return;
        }
        
        if(!isset($_FILES['document_file']) || $_FILES['document_file']['error'] != 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please select a file to upload'
            ]);
            return;
        }
        
        $file = $_FILES['document_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_type = $file['type'];
        
        // Validate file size (10MB max)
        if($file_size > 10 * 1024 * 1024) {
            echo json_encode([
                'status' => 'error',
                'message' => 'File size exceeds 10MB limit'
            ]);
            return;
        }
        
        // Validate file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if(!in_array($file_type, $allowed_types) && !in_array($extension, $allowed_extensions)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid file type. Please upload PDF, JPG, JPEG, or PNG files only.'
            ]);
            return;
        }
        
        // Generate unique filename
        $unique_filename = uniqid() . '_' . time() . '.' . $extension;
        $upload_dir = '../uploads/documents/';
        
        // Create directory if not exists
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_path = $upload_dir . $unique_filename;
        $db_file_path = 'uploads/documents/' . $unique_filename;
        
        // Move uploaded file
        if(!move_uploaded_file($file_tmp, $file_path)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to upload file'
            ]);
            return;
        }
        
        // Insert into database
        $query = "INSERT INTO documents (user_id, appointment_id, document_type, file_name, file_path, file_size, mime_type, uploaded_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisssis", $user_id, $appointment_id, $document_type, $file_name, $db_file_path, $file_size, $file_type);
        
        if($stmt->execute()) {
            $doc_id = $stmt->insert_id;
            $stmt->close();
            
            // Update appointment has_document flag
            if($appointment_id) {
                $update_query = "UPDATE appointments SET has_document = 1, document_path = ?, document_uploaded_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $db_file_path, $appointment_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'document_uploaded', 'document', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'document_id' => $doc_id,
                'document_type' => $document_type,
                'file_name' => $file_name,
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Document uploaded successfully'
            ]);
        } else {
            error_log("Upload Document Error: " . $stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to save document record: ' . $stmt->error
            ]);
        }
    } catch (Exception $e) {
        error_log("Upload Document Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}

// ============================================
// DELETE DOCUMENT
// ============================================
function deleteDocument($conn) {
    try {
        $document_id = (int)$_POST['document_id'];
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['role'];
        
        // Get document details
        $get_query = "SELECT file_path, user_id, appointment_id FROM documents WHERE id = ?";
        $get_stmt = $conn->prepare($get_query);
        $get_stmt->bind_param("i", $document_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $document = $result->fetch_assoc();
        $get_stmt->close();
        
        if(!$document) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Document not found'
            ]);
            return;
        }
        
        // Check permission (user owns doc or is admin)
        if($document['user_id'] != $user_id && $user_role != 'admin') {
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission to delete this document'
            ]);
            return;
        }
        
        // Delete file from server
        $full_path = '../' . $document['file_path'];
        if(file_exists($full_path)) {
            unlink($full_path);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM documents WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $document_id);
        
        if($delete_stmt->execute()) {
            $delete_stmt->close();
            
            // Update appointment has_document flag
            if($document['appointment_id']) {
                $update_query = "UPDATE appointments SET has_document = 0, document_path = NULL, document_uploaded_at = NULL WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $document['appointment_id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Log activity
            $log_query = "INSERT INTO audit_logs (user_id, action, entity_type, details, ip_address) 
                          VALUES (?, 'document_deleted', 'document', ?, ?)";
            $log_stmt = $conn->prepare($log_query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $details = json_encode([
                'document_id' => $document_id,
                'by_user' => $user_id
            ]);
            $log_stmt->bind_param("iss", $user_id, $details, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Document deleted successfully'
            ]);
        } else {
            error_log("Delete Document Error: " . $delete_stmt->error);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete document'
            ]);
        }
    } catch (Exception $e) {
        error_log("Delete Document Exception: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
}
?>