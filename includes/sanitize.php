<?php
/**
 * Sanitize input data to prevent XSS and SQL injection
 */
 
// Sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // Remove whitespace from beginning and end
    $data = trim($data);
    
    // Remove backslashes (if magic quotes is on - deprecated but safe)
    $data = stripslashes($data);
    
    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate password strength
// Requirements: At least 8 characters, one uppercase, one lowercase, one number
function isValidPassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

// Escape output for HTML display
function escapeOutput($data) {
    if (is_array($data)) {
        return array_map('escapeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Sanitize for SQL (use with prepared statements)
function sanitizeForSQL($data, $conn) {
    if (is_array($data)) {
        return array_map(function($item) use ($conn) {
            return sanitizeForSQL($item, $conn);
        }, $data);
    }
    
    // For PDO, we use prepared statements, but this adds extra safety
    return $conn->quote($data);
}
?>