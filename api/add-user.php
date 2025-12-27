<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if user is logged in AND is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
    exit();
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true);

$errors = [];
$formData = [
    'firstname' => $data['firstname'] ?? '',
    'lastname' => $data['lastname'] ?? '',
    'email' => $data['email'] ?? '',
    'password' => $data['password'] ?? '',
    'role' => $data['role'] ?? 'Member'
];

// Validate required fields
if (empty($formData['firstname'])) $errors[] = 'First name is required.';
if (empty($formData['lastname'])) $errors[] = 'Last name is required.';
if (empty($formData['email'])) {
    $errors[] = 'Email is required.';
} elseif (!isValidEmail($formData['email'])) {
    $errors[] = 'Please enter a valid email address.';
}
if (empty($formData['password'])) {
    $errors[] = 'Password is required.';
} elseif (!isValidPassword($formData['password'])) {
    $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
}

// Check if email already exists
if (empty($errors)) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $formData['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'A user with this email already exists.';
        }
    } catch(PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// If errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// If no errors, create user
try {
    $conn = getDBConnection();
    
    // Hash the password
    $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO Users (firstname, lastname, email, password, role, created_at) 
            VALUES (:firstname, :lastname, :email, :password, :role, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':firstname' => sanitizeInput($formData['firstname']),
        ':lastname' => sanitizeInput($formData['lastname']),
        ':email' => sanitizeInput($formData['email']),
        ':password' => $hashedPassword,
        ':role' => sanitizeInput($formData['role'])
    ]);
    
    $userId = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'User added successfully!',
        'user_id' => $userId
    ]);
    
} catch(PDOException $e) {
    error_log("Add user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>