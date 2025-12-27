<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// Checks if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in. Please refresh and try again.']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$data = json_decode($input, true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validation
$errors = [];
$required = ['title', 'firstname', 'lastname', 'email', 'telephone', 'company', 'type', 'assigned_to'];

foreach ($required as $field) {
    if (empty($data[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    }
}

// Validates email format
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Validates assigned_to is numeric
if (!empty($data['assigned_to']) && !is_numeric($data['assigned_to'])) {
    $errors[] = 'Invalid user selected.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Database operation
try {
    require_once '../includes/config.php';
    
    $conn = getDBConnection();
    
    $sql = "INSERT INTO Contacts (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at, updated_at) 
            VALUES (:title, :firstname, :lastname, :email, :telephone, :company, :type, :assigned_to, :created_by, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    
    $result = $stmt->execute([
        ':title' => trim($data['title']),
        ':firstname' => trim($data['firstname']),
        ':lastname' => trim($data['lastname']),
        ':email' => trim($data['email']),
        ':telephone' => trim($data['telephone']),
        ':company' => trim($data['company']),
        ':type' => trim($data['type']),
        ':assigned_to' => (int)$data['assigned_to'],
        ':created_by' => $_SESSION['user_id']
    ]);
    
    if ($result) {
        $contactId = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Contact added successfully!',
            'contact_id' => $contactId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert contact']);
    }
    
} catch(PDOException $e) {
    error_log("Database error in add-contact.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
}
?>
