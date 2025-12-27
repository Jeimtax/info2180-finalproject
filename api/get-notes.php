<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get contact ID
if (!isset($_GET['contact_id']) || !is_numeric($_GET['contact_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact ID']);
    exit();
}

$contactId = intval($_GET['contact_id']);

try {
    $conn = getDBConnection();
    
    // Get notes for this contact
    $stmt = $conn->prepare("SELECT n.*, u.firstname, u.lastname 
                            FROM Notes n 
                            JOIN Users u ON n.created_by = u.id 
                            WHERE n.contact_id = :contact_id 
                            ORDER BY n.created_at DESC");
    $stmt->bindParam(':contact_id', $contactId);
    $stmt->execute();
    
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the notes
    $formattedNotes = [];
    foreach ($notes as $note) {
        $formattedNotes[] = [
            'id' => $note['id'],
            'comment' => htmlspecialchars($note['comment']),
            'created_by_name' => htmlspecialchars($note['firstname'] . ' ' . $note['lastname']),
            'created_at' => formatDateTime($note['created_at'])
        ];
    }
    
    echo json_encode(['success' => true, 'notes' => $formattedNotes]);
    
} catch(PDOException $e) {
    error_log("Get notes error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>