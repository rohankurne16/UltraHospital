<?php
// ============================================================
// UPLOAD DOCUMENT HANDLER - upload_document.php
// ============================================================

session_start();
include "config/hospital.php";

// Debug - Check if session exists
// Uncomment below to debug
// error_log("Session ID: " . session_id());
// error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
// error_log("Register ID: " . ($_SESSION['register_id'] ?? 'not set'));

// Check if user is logged in - Check both possible session variables
$is_logged_in = false;
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0){
    $is_logged_in = true;
    $uploaded_by = $_SESSION['user_id'];
} elseif(isset($_SESSION['register_id']) && $_SESSION['register_id'] > 0){
    $is_logged_in = true;
    $uploaded_by = $_SESSION['register_id'];
} elseif(isset($_SESSION['id']) && $_SESSION['id'] > 0){
    $is_logged_in = true;
    $uploaded_by = $_SESSION['id'];
}

// If not logged in, try to get from cookies or other session variables
if(!$is_logged_in){
    // Check if user is set in any other session variable
    $session_keys = ['user_id', 'register_id', 'id', 'admin_id', 'doctor_id', 'staff_id'];
    foreach($session_keys as $key){
        if(isset($_SESSION[$key]) && $_SESSION[$key] > 0){
            $is_logged_in = true;
            $uploaded_by = $_SESSION[$key];
            break;
        }
    }
}

if(!$is_logged_in){
    $response = ['success' => false, 'message' => 'Please login first. Session expired or user not authenticated.'];
    echo json_encode($response);
    exit();
}

$response = ['success' => false, 'message' => ''];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])){
    
    $patient_id = intval($_POST['patient_id']);
    $document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
    $document_type = mysqli_real_escape_string($conn, $_POST['document_type']);
    $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
    
    // Validate inputs
    if(empty($patient_id) || empty($document_name) || empty($document_type)){
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit();
    }
    
    // Handle file upload
    if(isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0){
        
        $file = $_FILES['document_file'];
        $file_name = time() . '_' . basename($file['name']);
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file extensions
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'gif', 'webp'];
        
        if(!in_array($file_ext, $allowed_ext)){
            $response['message'] = 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX';
            echo json_encode($response);
            exit();
        }
        
        // Max file size: 10MB
        if($file_size > 10 * 1024 * 1024){
            $response['message'] = 'File size exceeds 10MB limit.';
            echo json_encode($response);
            exit();
        }
        
        // Create upload directory if not exists - try multiple paths
        $upload_dirs = [
            "uploads/documents/",
            "../uploads/documents/",
            "../../uploads/documents/",
            $_SERVER['DOCUMENT_ROOT'] . "/uploads/documents/"
        ];
        
        $target_file = '';
        foreach($upload_dirs as $dir){
            if(!file_exists($dir)){
                mkdir($dir, 0777, true);
            }
            if(is_writable($dir) || !file_exists($dir)){
                $target_file = $dir . $file_name;
                break;
            }
        }
        
        // If no writable directory found, use current directory
        if(empty($target_file)){
            $target_file = "documents/patients/document/" . $file_name;
            if(!file_exists("documents/patients/document/")){
                mkdir("documents/patients/document/", 0777, true);
            }
        }
        
        $file_size_formatted = formatFileSize($file_size);
        
        // Move uploaded file
        if(move_uploaded_file($file_tmp, $target_file)){
            
            // Determine category from document_type
            $doc_type_lower = strtolower($document_type);
            $category = 'General';
            if(strpos($doc_type_lower, 'pre') !== false || strpos($doc_type_lower, 'pre-operation') !== false){
                $category = 'Pre-Operation';
            } elseif(strpos($doc_type_lower, 'ot') !== false || strpos($doc_type_lower, 'operation') !== false){
                $category = 'OT';
            } elseif(strpos($doc_type_lower, 'post') !== false || strpos($doc_type_lower, 'post-operation') !== false){
                $category = 'Post-Operation';
            }
            
            // Insert into database
            $insert_query = "INSERT INTO patient_documents 
                            (patient_id, document_name, document_type, document_category, 
                             upload_file, file_size, uploaded_by, note, document_date, created_at) 
                            VALUES 
                            ('$patient_id', '$document_name', '$document_type', '$category',
                             '$target_file', '$file_size_formatted', '$uploaded_by', '$note', CURDATE(), NOW())";
            
            if(mysqli_query($conn, $insert_query)){
                $response['success'] = true;
                $response['message'] = 'Document uploaded successfully!';
                
                // Log the action if function exists
                if(function_exists('logAudit')){
                    logAudit('Patient Documents', "Document uploaded for patient ID: $patient_id - $document_name");
                }
            } else {
                $response['message'] = 'Database error: ' . mysqli_error($conn);
                // Delete uploaded file if database insert fails
                if(file_exists($target_file)){
                    unlink($target_file);
                }
            }
        } else {
            $response['message'] = 'Failed to upload file. Please check folder permissions.';
        }
    } else {
        $response['message'] = 'No file uploaded or file upload error.';
        if(isset($_FILES['document_file']['error'])){
            $error_messages = [
                0 => 'There is no error, the file uploaded with success.',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                3 => 'The uploaded file was only partially uploaded.',
                4 => 'No file was uploaded.',
                6 => 'Missing a temporary folder.',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.'
            ];
            $response['message'] .= ' Error: ' . ($error_messages[$_FILES['document_file']['error']] ?? 'Unknown error');
        }
    }
    
    echo json_encode($response);
    exit();
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}
?>