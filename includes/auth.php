<?php
/**
 * Authentication and authorization functions for Dolphin CRM
 */

// Include required files
require_once 'config.php';
require_once 'sanitize.php';

/**
 * Check if a user is currently logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user has Administrator role
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator';
}

/**
 * Check if the current user has Member role
 * @return bool True if user is member, false otherwise
 */
function isMember() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Member';
}

/**
 * Redirect to login page if user is not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the current URL to redirect back after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: login.php');
        exit();
    }
}

/**
 * Redirect to dashboard if user is not an administrator
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // User is logged in but not admin
        $_SESSION['error'] = 'Access denied. Administrator privileges required.';
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Get the current user's ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get the current user's full name
 * @return string|null User's full name or null if not logged in
 */
function getCurrentUserName() {
    if (isset($_SESSION['firstname']) && isset($_SESSION['lastname'])) {
        return trim($_SESSION['firstname'] . ' ' . $_SESSION['lastname']);
    }
    return null;
}

/**
 * Get the current user's email
 * @return string|null User's email or null if not logged in
 */
function getCurrentUserEmail() {
    return $_SESSION['email'] ?? null;
}

/**
 * Logout the current user and destroy the session
 */
function logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Verify user credentials and log them in
 * @param string $email User's email
 * @param string $password User's password
 * @return array|bool User data array on success, false on failure
 */
function loginUser($email, $password) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, firstname, lastname, password, role, email FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                // Update last login time (optional enhancement)
                // $updateStmt = $conn->prepare("UPDATE Users SET last_login = NOW() WHERE id = :id");
                // $updateStmt->bindParam(':id', $user['id']);
                // $updateStmt->execute();
                
                return $user;
            }
        }
        return false;
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has permission to view/edit a contact
 * @param int $contactId The contact ID to check
 * @return bool True if user has permission, false otherwise
 */
function canViewContact($contactId) {
    if (!isLoggedIn()) return false;
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT assigned_to, created_by FROM Contacts WHERE id = :id");
        $stmt->bindParam(':id', $contactId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = getCurrentUserId();
            
            // User can view if: they are admin, assigned to this contact, or created this contact
            return isAdmin() || 
                   $contact['assigned_to'] == $userId || 
                   $contact['created_by'] == $userId;
        }
        return false;
    } catch(PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user can add/edit users (Admin only)
 * @return bool True if user can manage users
 */
function canManageUsers() {
    return isAdmin();
}

/**
 * Check if user can view all users (Admin only)
 * @return bool True if user can view users list
 */
function canViewUsers() {
    return isAdmin();
}

?>