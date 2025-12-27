<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Alows only admins to view user list
if (!canViewUsers()) {
    header('Location: dashboard.php');
    exit();
}

// Set page variables for header
$page_title = 'Users';
$current_page = 'users';

// Get all users from database
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT id, firstname, lastname, email, role, created_at FROM Users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $users = [];
    $error = 'Database error: ' . $e->getMessage();
}

// Start output buffering
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h2>Users</h2>
        <p>Manage system users and permissions</p>
    </div>
    <div class="page-actions">
        <a href="add-user.php" class="btn btn-primary">+ Add New User</a>
    </div>
</div>

<div class="card">
    <h3 class="mb-2">All Users</h3>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($users)): ?>
        <div class="text-center p-4">
            <p>No users found. <a href="add-user.php">Add your first user</a></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></strong>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?php echo ($user['role'] == 'Administrator') ? 'badge-admin' : 'badge-member'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-secondary" 
                                        onclick="alert('Edit feature coming soon!')">
                                    Edit
                                </button>
                                <?php if ($user['email'] != 'admin@project2.com'): ?>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="alert('Delete feature coming soon!')">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Get the content and clean buffer
$page_content = ob_get_clean();

require_once 'includes/header.php';
echo $page_content;
require_once 'includes/footer.php';
?>
