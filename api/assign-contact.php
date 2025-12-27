<?php
// api/assign-contact.php - COMPLETE WORKING VERSION

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['contact_id']) || !is_numeric($data['contact_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
    exit();
}

$contactId = (int)$data['contact_id'];
$userId = $_SESSION['user_id'];

try {
    require_once '../includes/config.php';
    
    $conn = getDBConnection();
    
    // Get current user info
    $sql = "SELECT firstname, lastname FROM Users WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $userName = $user['firstname'] . ' ' . $user['lastname'];
    
    // Update contact assignment
    $sql = "UPDATE Contacts SET assigned_to = :user_id, updated_at = NOW() WHERE id = :contact_id";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':user_id' => $userId,
        ':contact_id' => $contactId
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Contact assigned to you successfully!',
            'assigned_to_name' => $userName
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign contact']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>