<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if contact ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$contactId = $_GET['id'];
$contact = getContactById($contactId);

if (!$contact) {
    header('Location: dashboard.php');
    exit();
}

// Check if user has permission to view this contact
if (!canViewContact($contactId)) {
    header('Location: dashboard.php');
    exit();
}

// Set page variables for header
$page_title = 'View Contact';
$current_page = 'dashboard';

// Check for success message from add-contact
$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h2>Contact Details</h2>
        <p>Viewing contact information</p>
    </div>
    <div class="page-actions">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="add-contact.php" class="btn btn-primary">+ Add Another Contact</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?php echo htmlspecialchars($contact['title'] . ' ' . $contact['firstname'] . ' ' . $contact['lastname']); ?></h3>
        <span class="badge <?php echo ($contact['type'] == 'Sales Lead') ? 'badge-sales' : 'badge-support'; ?>" id="contact-type-badge">
            <?php echo htmlspecialchars($contact['type']); ?>
        </span>
    </div>
    
    <div class="card-body">
        <div class="contact-info">
            <div class="info-row">
                <strong>Email:</strong> 
                <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                    <?php echo htmlspecialchars($contact['email']); ?>
                </a>
            </div>
            
            <div class="info-row">
                <strong>Telephone:</strong> 
                <?php echo htmlspecialchars($contact['telephone']); ?>
            </div>
            
            <div class="info-row">
                <strong>Company:</strong> 
                <?php echo htmlspecialchars($contact['company']); ?>
            </div>
            
            <div class="info-row" id="assigned-to-info">
                <strong>Assigned To:</strong> 
                <?php echo htmlspecialchars($contact['assigned_firstname'] . ' ' . $contact['assigned_lastname']); ?>
            </div>
            
            <div class="info-row">
                <strong>Created:</strong> 
                <?php echo formatDateTime($contact['created_at']); ?> 
                by <?php echo htmlspecialchars($contact['created_firstname'] . ' ' . $contact['created_lastname']); ?>
            </div>
            
            <div class="info-row" id="last-updated-info">
                <strong>Last Updated:</strong> 
                <?php echo formatDateTime($contact['updated_at']); ?>
            </div>
        </div>
        
        <div class="action-buttons mt-4">
            <button class="btn btn-primary" onclick="assignToMe(<?php echo $contactId; ?>)">
                Assign to Me
            </button>
            <button class="btn btn-secondary" onclick="toggleContactType(<?php echo $contactId; ?>)" id="toggle-type-btn">
                Toggle Type (Current: <?php echo $contact['type']; ?>)
            </button>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h3>Notes</h3>
    </div>
    <div class="card-body">
        <div id="notes-container">
            <p class="text-muted">Loading notes...</p>
        </div>
        
        <form id="add-note-form" class="mt-4">
            <div class="form-group">
                <label for="note">Add a Note</label>
                <textarea id="note" name="note" rows="4" 
                          placeholder="Enter note about this contact..." required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Note</button>
            </div>
            <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
        </form>
    </div>
</div>

<script>
// Assign to Me function
function assignToMe(contactId) {
    if (confirm('Assign this contact to yourself?')) {
        const button = event.target;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Assigning...';
        button.classList.add('loading');
        
        fetch('api/assign-contact.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({contact_id: contactId}),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the "Assigned To" display
                document.getElementById('assigned-to-info').innerHTML = 
                    `<strong>Assigned To:</strong> ${data.assigned_to_name}`;
                
                // Update last updated time
                updateLastUpdatedTime();
                
                // Update button
                button.textContent = '✓ Assigned to Me';
                button.classList.remove('btn-primary', 'loading');
                button.classList.add('btn-success');
                
                // Show success message
                showMessage('success', data.message);
            } else {
                showMessage('error', data.message);
                resetButton(button, originalText);
            }
        })
        .catch(error => {
            showMessage('error', 'Network error: ' + error.message);
            resetButton(button, originalText);
        });
    }
}

// Toggle Type function
function toggleContactType(contactId) {
    if (confirm('Toggle contact type?')) {
        const button = document.getElementById('toggle-type-btn');
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Toggling...';
        button.classList.add('loading');
        
        fetch('api/toggle-type.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({contact_id: contactId}),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the badge
                const badge = document.getElementById('contact-type-badge');
                badge.className = 'badge ' + data.badge_class;
                badge.textContent = data.new_type;
                
                // Update the button text
                button.textContent = 'Toggle Type (Current: ' + data.new_type + ')';
                
                // Update last updated time
                updateLastUpdatedTime();
                
                // Show success message
                showMessage('success', data.message);
                
                // Re-enable button
                button.disabled = false;
                button.classList.remove('loading');
            } else {
                showMessage('error', data.message);
                resetButton(button, originalText);
            }
        })
        .catch(error => {
            showMessage('error', 'Network error: ' + error.message);
            resetButton(button, originalText);
        });
    }
}

// Update last updated time
function updateLastUpdatedTime() {
    const now = new Date();
    const formattedDate = now.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    document.getElementById('last-updated-info').innerHTML = 
        `<strong>Last Updated:</strong> ${formattedDate}`;
}

// Load notes via AJAX
function loadNotes(contactId) {
    const container = document.getElementById('notes-container');
    
    fetch(`api/get-notes.php?contact_id=${contactId}`, {
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.notes && data.notes.length > 0) {
            let html = '';
            data.notes.forEach(note => {
                html += `
                <div class="note-item mb-3 p-3 border rounded">
                    <div class="note-header d-flex justify-content-between align-items-center">
                        <strong>${note.created_by_name}</strong>
                        <small class="text-muted">${note.created_at}</small>
                    </div>
                    <div class="note-body mt-2">
                        ${note.comment}
                    </div>
                </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-muted">No notes yet. Add a note to track interactions with this contact.</p>';
        }
    })
    .catch(error => {
        container.innerHTML = `<p class="text-danger">Error loading notes: ${error.message}</p>`;
    });
}

// Handle note submission
document.getElementById('add-note-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    const formData = new FormData(form);
    const noteData = {
        contact_id: formData.get('contact_id'),
        comment: formData.get('note')
    };
    
    fetch('api/add-note.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(noteData),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear form
            form.reset();
            
            // Show success message
            showMessage('success', data.message);
            
            // Reload notes
            loadNotes(noteData.contact_id);
            
            // Update last updated time
            updateLastUpdatedTime();
        } else {
            showMessage('error', data.message);
        }
        
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    })
    .catch(error => {
        showMessage('error', 'Network error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

// Helper functions
function showMessage(type, text) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.alert-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type} alert-message mt-3`;
    messageDiv.textContent = text;
    
    // Insert after page header
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.parentNode.insertBefore(messageDiv, pageHeader.nextSibling);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

function resetButton(button, originalText) {
    button.disabled = false;
    button.textContent = originalText;
    button.classList.remove('loading');
}

// Load notes on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotes(<?php echo $contactId; ?>);
});
</script>

<?php
$page_content = ob_get_clean();
require_once 'includes/header.php';
echo $page_content;
require_once 'includes/footer.php';
?>