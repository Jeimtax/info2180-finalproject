<?php
// api/add-note.php - COMPLETE VERSION

header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['contact_id']) || !isset($data['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Contact ID and comment are required']);
    exit();
}

$contactId = (int)$data['contact_id'];
$comment = trim($data['comment']);
$userId = $_SESSION['user_id'];

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit();
}

try {
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    
    $conn = getDBConnection();
    
    // Check if contact exists
    $stmt = $conn->prepare("SELECT id FROM Contacts WHERE id = :contact_id");
    $stmt->execute([':contact_id' => $contactId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Contact not found']);
        exit();
    }
    
    // Add note
    $sql = "INSERT INTO Notes (contact_id, comment, created_by, created_at) 
            VALUES (:contact_id, :comment, :created_by, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':contact_id' => $contactId,
        ':comment' => htmlspecialchars($comment),
        ':created_by' => $userId
    ]);
    
    $noteId = $conn->lastInsertId();
    
    // Update contact's updated_at
    $stmt = $conn->prepare("UPDATE Contacts SET updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $contactId]);
    
    // Get the new note with user info
    $sql = "SELECT n.*, u.firstname, u.lastname 
            FROM Notes n 
            JOIN Users u ON n.created_by = u.id 
            WHERE n.id = :note_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':note_id' => $noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully!',
        'note' => [
            'id' => $note['id'],
            'comment' => htmlspecialchars($note['comment']),
            'created_by_name' => htmlspecialchars($note['firstname'] . ' ' . $note['lastname']),
            'created_at' => formatDateTime($note['created_at'])
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>