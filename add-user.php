<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Only admins can add users
if (!canManageUsers()) {
    header('Location: dashboard.php');
    exit();
}

// Set page variables for header
$page_title = 'Add New User';
$current_page = 'add-user';

// Initialize variables
$errors = [];
$success = '';
$formData = [
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => '',
    'role' => 'Member'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $formData['firstname'] = sanitizeInput($_POST['firstname'] ?? '');
    $formData['lastname'] = sanitizeInput($_POST['lastname'] ?? '');
    $formData['email'] = sanitizeInput($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? ''; // Don't sanitize password
    $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
    $formData['role'] = sanitizeInput($_POST['role'] ?? 'Member');
    
    // Validate required fields
    if (empty($formData['firstname'])) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($formData['lastname'])) {
        $errors[] = 'Last name is required.';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmail($formData['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'Password is required.';
    } elseif (!isValidPassword($formData['password'])) {
        $errors[] = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Passwords do not match.';
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
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Hash the password
            $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO Users (firstname, lastname, email, password, role, created_at) 
                    VALUES (:firstname, :lastname, :email, :password, :role, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':firstname' => $formData['firstname'],
                ':lastname' => $formData['lastname'],
                ':email' => $formData['email'],
                ':password' => $hashedPassword,
                ':role' => $formData['role']
            ]);
            
            $userId = $conn->lastInsertId();
            
            // Set success message
            $success = 'User added successfully!';
            
            // Clear form
            $formData = [
                'firstname' => '',
                'lastname' => '',
                'email' => '',
                'password' => '',
                'confirm_password' => '',
                'role' => 'Member'
            ];
            
        } catch(PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Start output buffering
ob_start();
?>

<!-- Add User content starts here -->
<div class="page-header">
    <div class="page-title">
        <h2>Add New User</h2>
        <p>Create a new user account for Dolphin CRM</p>
    </div>
    <div class="page-actions">
        <a href="users.php" class="btn btn-secondary">← Back to Users List</a>
    </div>
</div>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <h4>Please fix the following errors:</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form id="add-user-form">
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="firstname">First Name *</label>
                    <input type="text" id="firstname" name="firstname" 
                           value="<?php echo htmlspecialchars($formData['firstname']); ?>" 
                           required placeholder="Enter first name">
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label for="lastname">Last Name *</label>
                    <input type="text" id="lastname" name="lastname" 
                           value="<?php echo htmlspecialchars($formData['lastname']); ?>" 
                           required placeholder="Enter last name">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($formData['email']); ?>" 
                   required placeholder="Enter email address">
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" 
                           value="<?php echo htmlspecialchars($formData['password']); ?>" 
                           required placeholder="Enter password">
                    <small class="text-muted">
                        Must be at least 8 characters with uppercase, lowercase, and number
                    </small>
                    <div id="password-strength" class="mt-1"></div>
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           value="<?php echo htmlspecialchars($formData['confirm_password']); ?>" 
                           required placeholder="Confirm password">
                    <div id="password-match" class="mt-1"></div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required>
                <option value="Member" <?php echo ($formData['role'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                <option value="Administrator" <?php echo ($formData['role'] == 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
            </select>
            <small class="text-muted">
                Administrators can manage users and access all features
            </small>
        </div>
        
        <div class="form-actions">
            <button type="reset" class="btn btn-secondary">Clear Form</button>
            <button type="submit" class="btn btn-primary">Add User</button>
        </div>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('add-user-form');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding User...';
            
            // Clear previous errors
            document.querySelectorAll('.field-error').forEach(el => el.remove());
            
            // Collect form data
            const formData = {
                firstname: document.getElementById('firstname').value,
                lastname: document.getElementById('lastname').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role').value
            };
            
            // Send via AJAX
            fetch('api/add-user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message at the top
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success';
                    successAlert.innerHTML = `
                        <p><strong>✓ ${data.message}</strong></p>
                        <p>The form has been cleared. You can add another user.</p>
                    `;
                    
                    const formContainer = document.querySelector('.form-container');
                    formContainer.insertBefore(successAlert, formContainer.firstChild);
                    
                    // Scroll to top
                    window.scrollTo(0, 0);
                    
                    // Clear form
                    form.reset();
                    
                    // Clear password strength indicators
                    document.getElementById('password-strength').innerHTML = '';
                    document.getElementById('password-match').innerHTML = '';
                    
                    // Remove success message after 5 seconds
                    setTimeout(() => {
                        successAlert.remove();
                    }, 5000);
                    
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    
                } else {
                    // Show errors at the top
                    if (data.errors) {
                        data.errors.forEach(error => {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'alert alert-error field-error';
                            errorDiv.textContent = error;
                            document.querySelector('.form-container').appendChild(errorDiv);
                        });
                    } else {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-error field-error';
                        errorDiv.textContent = 'Error: ' + (data.message || 'Unknown error');
                        document.querySelector('.form-container').appendChild(errorDiv);
                    }
                    
                    // Scroll to first error
                    const firstError = document.querySelector('.field-error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth' });
                    }
                    
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                // Network error
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-error field-error';
                errorDiv.textContent = 'Network error: ' + error.message;
                document.querySelector('.form-container').appendChild(errorDiv);
                
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
        
        // Clear button should clear form and errors
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            setTimeout(() => {
                document.querySelectorAll('.field-error').forEach(el => el.remove());
                document.getElementById('password-strength').innerHTML = '';
                document.getElementById('password-match').innerHTML = '';
            }, 100);
        });
    });
    </script>
</div>

<script>
// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strength = 0;
    let messages = [];
    
    // Check length
    if (password.length >= 8) strength++;
    else messages.push('At least 8 characters');
    
    // Check uppercase
    if (/[A-Z]/.test(password)) strength++;
    else messages.push('One uppercase letter');
    
    // Check lowercase
    if (/[a-z]/.test(password)) strength++;
    else messages.push('One lowercase letter');
    
    // Check number
    if (/\d/.test(password)) strength++;
    else messages.push('One number');
    
    // Determine strength level
    let strengthText = '';
    let strengthClass = '';
    
    switch(strength) {
        case 4:
            strengthText = 'Strong password ✓';
            strengthClass = 'text-success';
            break;
        case 3:
            strengthText = 'Good password';
            strengthClass = 'text-warning';
            break;
        case 2:
            strengthText = 'Fair password';
            strengthClass = 'text-warning';
            break;
        default:
            strengthText = 'Weak password';
            strengthClass = 'text-danger';
    }
    
    if (messages.length > 0) {
        strengthText += ' - Needs: ' + messages.join(', ');
    }
    
    strengthDiv.innerHTML = `<span class="${strengthClass}">${strengthText}</span>`;
});

// Password match indicator
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    const matchDiv = document.getElementById('password-match');
    
    if (confirm.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (password === confirm) {
        matchDiv.innerHTML = '<span class="text-success">✓ Passwords match</span>';
    } else {
        matchDiv.innerHTML = '<span class="text-danger">✗ Passwords do not match</span>';
    }
});
</script>

<?php
// Get the content and clean buffer
$page_content = ob_get_clean();

// Include header and footer templates
require_once 'includes/header.php';
echo $page_content;
require_once 'includes/footer.php';
?>