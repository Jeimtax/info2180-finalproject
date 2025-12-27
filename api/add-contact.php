<?php
// api/add-contact.php - FINAL VERSION

// Set JSON header FIRST
header('Content-Type: application/json');

// Start session
session_start();

// Set session cookie parameters to match main site
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// For debugging
error_log("=== API CALL START ===");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Check if it's an AJAX request (optional but good practice)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    error_log("This is an AJAX request");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in. Session dump: " . print_r($_SESSION, true));
    
    // For AJAX debugging
    if (isset($_SERVER['HTTP_COOKIE'])) {
        error_log("Cookies sent: " . $_SERVER['HTTP_COOKIE']);
    }
    
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

error_log("User is logged in with ID: " . $_SESSION['user_id']);

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

error_log("Data received: " . print_r($data, true));

// Simple validation
$errors = [];
$required = ['title', 'firstname', 'lastname', 'email', 'company', 'assigned_to'];

foreach ($required as $field) {
    if (empty($data[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    }
}

if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Database connection
try {
    // Include your existing config
    require_once '../includes/config.php';
    
    $conn = getDBConnection();
    
    $sql = "INSERT INTO Contacts (title, firstname, lastname, email, telephone, company, type, assigned_to, created_by, created_at, updated_at) 
            VALUES (:title, :firstname, :lastname, :email, :telephone, :company, :type, :assigned_to, :created_by, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    
    $result = $stmt->execute([
        ':title' => htmlspecialchars(trim($data['title'])),
        ':firstname' => htmlspecialchars(trim($data['firstname'])),
        ':lastname' => htmlspecialchars(trim($data['lastname'])),
        ':email' => htmlspecialchars(trim($data['email'])),
        ':telephone' => htmlspecialchars(trim($data['telephone'] ?? '')),
        ':company' => htmlspecialchars(trim($data['company'])),
        ':type' => htmlspecialchars(trim($data['type'] ?? 'Sales Lead')),
        ':assigned_to' => (int)$data['assigned_to'],
        ':created_by' => $_SESSION['user_id']
    ]);
    
    if ($result) {
        $contactId = $conn->lastInsertId();
        error_log("Contact inserted with ID: " . $contactId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Contact added successfully!',
            'contact_id' => $contactId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert contact']);
    }
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

error_log("=== API CALL END ===");
?>