<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Set page variables for header
$page_title = 'Dashboard';
$current_page = 'dashboard';

// Get initial stats (we'll update these via AJAX too)
$userId = getCurrentUserId();
$stats = getDashboardStats($userId);

// Start output buffering
ob_start();
?>

<!-- Dashboard content starts here -->
<div class="page-header">
    <div class="page-title">
        <h2>Dashboard</h2>
        <p>Welcome to Dolphin CRM. Manage your contacts efficiently.</p>
    </div>
    <div class="page-actions">
        <a href="add-contact.php" class="btn btn-primary">+ Add New Contact</a>
    </div>
</div>

<!-- Filters -->
<div class="filter-section">
    <h3 class="mb-2">Filter Contacts</h3>
    <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">
            All Contacts
        </button>
        <button class="filter-btn" data-filter="sales">
            Sales Leads
        </button>
        <button class="filter-btn" data-filter="support">
            Support
        </button>
        <button class="filter-btn" data-filter="assigned">
            Assigned to Me
        </button>
    </div>
    <p class="mt-2 text-muted" id="filter-info">
        Loading contacts...
    </p>
</div>

<!-- Contacts Table -->
<div class="card">
    <h3 class="mb-2">Recent Contacts</h3>
    
    <div id="contacts-container">
        <div class="text-center p-4">
            <p>Loading contacts...</p>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col">
        <div class="stat-card">
            <h4>Total Contacts</h4>
            <p class="stat-number" id="stat-total"><?php echo $stats['total']; ?></p>
        </div>
    </div>
    <div class="col">
        <div class="stat-card">
            <h4>Sales Leads</h4>
            <p class="stat-number" id="stat-sales"><?php echo $stats['sales']; ?></p>
        </div>
    </div>
    <div class="col">
        <div class="stat-card">
            <h4>Support</h4>
            <p class="stat-number" id="stat-support"><?php echo $stats['support']; ?></p>
        </div>
    </div>
    <div class="col">
        <div class="stat-card">
            <h4>Assigned to Me</h4>
            <p class="stat-number" id="stat-assigned"><?php echo $stats['assigned']; ?></p>
        </div>
    </div>
</div>

<script>
// Load contacts via AJAX
function loadContacts(filter = 'all') {
    // Show loading state
    document.getElementById('contacts-container').innerHTML = 
        '<div class="text-center p-4"><p>Loading contacts...</p></div>';
    
    // Update filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-filter') === filter) {
            btn.classList.add('active');
        }
    });
    
    // Fetch contacts via AJAX
    fetch(`api/get-contacts.php?filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateContactsTable(data.contacts);
                updateFilterInfo(data.contacts.length, filter);
                updateStats(data.stats);
            } else {
                document.getElementById('contacts-container').innerHTML = 
                    `<div class="alert alert-error">Error loading contacts: ${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('contacts-container').innerHTML = 
                `<div class="alert alert-error">Network error: ${error.message}</div>`;
        });
}

// Update contacts table
function updateContactsTable(contacts) {
    const container = document.getElementById('contacts-container');
    
    if (contacts.length === 0) {
        container.innerHTML = `
            <div class="text-center p-4">
                <p>No contacts found. <a href="add-contact.php">Add your first contact</a></p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Type</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    contacts.forEach(contact => {
        const badgeClass = contact.type === 'Sales Lead' ? 'badge-sales' : 'badge-support';
        const assignedName = contact.assigned_firstname ? 
            `${contact.assigned_firstname} ${contact.assigned_lastname}` : 'Unassigned';
        
        html += `
            <tr>
                <td>
                    <strong>${escapeHtml(contact.title)} ${escapeHtml(contact.firstname)} ${escapeHtml(contact.lastname)}</strong>
                </td>
                <td>
                    <a href="mailto:${escapeHtml(contact.email)}">
                        ${escapeHtml(contact.email)}
                    </a>
                </td>
                <td>${escapeHtml(contact.company)}</td>
                <td>
                    <span class="badge ${badgeClass}">
                        ${escapeHtml(contact.type)}
                    </span>
                </td>
                <td>${escapeHtml(assignedName)}</td>
                <td>${formatDate(contact.created_at)}</td>
                <td class="actions">
                    <a href="view-contact.php?id=${contact.id}" class="btn btn-sm btn-primary">View</a>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

// Update filter info text
function updateFilterInfo(count, filter) {
    const filterText = filter !== 'all' ? `filtered by ${filter.charAt(0).toUpperCase() + filter.slice(1)}` : '';
    document.getElementById('filter-info').innerHTML = 
        `Showing ${count} contact(s) ${filterText}`;
}

// Update stats
function updateStats(stats) {
    document.getElementById('stat-total').textContent = stats.total;
    document.getElementById('stat-sales').textContent = stats.sales;
    document.getElementById('stat-support').textContent = stats.support;
    document.getElementById('stat-assigned').textContent = stats.assigned;
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load initial contacts
    loadContacts('all');
    
    // Add click handlers to filter buttons
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            loadContacts(filter);
            
            // Update URL without page reload (for bookmarking)
            const url = new URL(window.location);
            url.searchParams.set('filter', filter);
            window.history.pushState({}, '', url);
        });
    });
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const filter = urlParams.get('filter') || 'all';
        loadContacts(filter);
    });
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