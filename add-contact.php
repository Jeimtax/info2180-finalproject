<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Add New Contact';
$current_page = 'add-contact';
$users = getAllUsers();

ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h2>Add New Contact</h2>
        <p>Create a new contact in Dolphin CRM</p>
    </div>
    <div class="page-actions">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<div class="form-container">

    <div id="message-container"></div>
    
    <form id="add-contact-form">
        <div class="form-row">
            <div class="form-col" style="flex: 0 0 150px;">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <select id="title" name="title" required>
                        <option value="">Select</option>
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Ms">Ms</option>
                        <option value="Dr">Dr</option>
                    </select>
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label for="firstname">First Name *</label>
                    <input type="text" id="firstname" name="firstname" 
                           required placeholder="Enter first name">
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label for="lastname">Last Name *</label>
                    <input type="text" id="lastname" name="lastname" 
                           required placeholder="Enter last name">
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" 
                           required placeholder="Enter email address">
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label for="telephone">Telephone *</label>
                    <input type="tel" id="telephone" name="telephone" 
                           required placeholder="Enter telephone number">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="company">Company *</label>
            <input type="text" id="company" name="company" 
                   required placeholder="Enter company name">
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label for="type">Contact Type *</label>
                    <select id="type" name="type" required>
                        <option value="Sales Lead">Sales Lead</option>
                        <option value="Support">Support</option>
                    </select>
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label for="assigned_to">Assign To *</label>
                    <select id="assigned_to" name="assigned_to" required>
                        <option value="">Select a user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="reset" class="btn btn-secondary">Clear Form</button>
            <button type="submit" class="btn btn-primary">Add Contact</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add-contact-form');
    const messageContainer = document.getElementById('message-container');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous messages
        messageContainer.innerHTML = '';
        
        // Disable submit button
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Adding Contact...';
        
        // Collect form data
        const formData = {
            title: document.getElementById('title').value,
            firstname: document.getElementById('firstname').value,
            lastname: document.getElementById('lastname').value,
            email: document.getElementById('email').value,
            telephone: document.getElementById('telephone').value,
            company: document.getElementById('company').value,
            type: document.getElementById('type').value,
            assigned_to: document.getElementById('assigned_to').value
        };
        
        console.log('Sending data:', formData);
        
        try {
            const response = await fetch('api/add-contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData),
                credentials: 'same-origin'
            });
            
            console.log('Response status:', response.status);
            
            // Get response as text first for debugging
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON Parse Error:', jsonError);
                console.error('Response was:', responseText);
                throw new Error('Server returned invalid JSON. Check console for details.');
            }
            
            console.log('Parsed data:', data);
            
            if (data.success) {
                // Show success message
                messageContainer.innerHTML = `
                    <div class="alert alert-success">
                        <p><strong>${data.message}</strong></p>
                        <p>Redirecting to contact page...</p>
                    </div>
                `;
                
                // Scroll to top
                window.scrollTo(0, 0);
                
                // Clear form
                form.reset();
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = 'view-contact.php?id=' + data.contact_id;
                }, 1500);
                
            } else {
                // Show errors
                let errorHtml = '<div class="alert alert-error"><h4>Please fix the following:</h4><ul>';
                
                if (data.errors && Array.isArray(data.errors)) {
                    data.errors.forEach(error => {
                        errorHtml += `<li>${error}</li>`;
                    });
                } else {
                    errorHtml += `<li>${data.message || 'Unknown error occurred'}</li>`;
                }
                
                errorHtml += '</ul></div>';
                messageContainer.innerHTML = errorHtml;
                
                // Scroll to errors
                window.scrollTo(0, 0);
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
            
        } catch (error) {
            console.error('Caught error:', error);
            
            // Network or other error
            messageContainer.innerHTML = `
                <div class="alert alert-error">
                    <p><strong>Error: ${error.message}</strong></p>
                    <p>Please check the browser console (F12) for more details.</p>
                </div>
            `;
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
    
    // Clear form also clears messages
    const resetBtn = document.querySelector('button[type="reset"]');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 100);
        });
    }
});
</script>

<?php
$page_content = ob_get_clean();
require_once 'includes/header.php';
echo $page_content;
require_once 'includes/footer.php';
?>
