<?php
// api/toggle-type.php - COMPLETE WORKING VERSION

// Set JSON header FIRST
header('Content-Type: application/json');

// Start session
session_start();

// Enable error logging for debugging
error_reporting(0); // Turn off for production, but keep for debugging
ini_set('display_errors', 0);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$data = json_decode($input, true);
if (!$data || !isset($data['contact_id']) || !is_numeric($data['contact_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
    exit();
}

$contactId = (int)$data['contact_id'];
$userId = $_SESSION['user_id'];

try {
    // Include database configuration
    require_once '../includes/config.php';
    
    // Create database connection
    $conn = getDBConnection();
    
    // First, check if contact exists and user has permission
    $sql = "SELECT c.*, u.firstname, u.lastname 
            FROM Contacts c 
            LEFT JOIN Users u ON c.assigned_to = u.id 
            WHERE c.id = :contact_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':contact_id' => $contactId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Contact not found']);
        exit();
    }
    
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Optional: Check if user has permission to edit this contact
    // Users can edit if they are admin, assigned to the contact, or created the contact
    require_once '../includes/auth.php';
    if (!canViewContact($contactId)) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this contact']);
        exit();
    }
    
    // Determine new type
    $currentType = $contact['type'];
    $newType = ($currentType === 'Sales Lead') ? 'Support' : 'Sales Lead';
    $badgeClass = ($newType === 'Sales Lead') ? 'badge-sales' : 'badge-support';
    
    // Update the contact type
    $sql = "UPDATE Contacts SET type = :type, updated_at = NOW() WHERE id = :contact_id";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':type' => $newType,
        ':contact_id' => $contactId
    ]);
    
    if ($result) {
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Contact type updated successfully!',
            'new_type' => $newType,
            'badge_class' => $badgeClass
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update contact type']);
    }
    
} catch(PDOException $e) {
    // Log error and return friendly message
    error_log("Toggle type error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please try again.'
    ]);
} catch(Exception $e) {
    error_log("General error in toggle-type.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>