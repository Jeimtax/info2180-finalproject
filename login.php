<?php
// Include required files
require_once 'includes/config.php';
require_once 'includes/auth.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Check if we have a success message from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize password
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Attempt to log in
        $user = loginUser($email, $password);
        
        if ($user !== false) {
            // Login successful
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolphin CRM - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <h1>🐬 Dolphin CRM</h1>
            <p>Customer Relationship Management System</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" class="login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        
        <div class="demo-credentials">
            <h3>Demo Credentials:</h3>
            <p><strong>Email:</strong> admin@project2.com</p>
            <p><strong>Password:</strong> password123</p>
        </div>
        
        <div class="forgot-password">
            <p>Forgot your password? <a href="#">Contact administrator</a></p>
        </div>
    </div>
</body>
</html>