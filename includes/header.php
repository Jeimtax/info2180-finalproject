<?php
// Check if user is logged in, if not redirect to login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dolphin CRM - <?php echo $page_title ?? 'Dashboard'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <h1>🐬 Dolphin CRM</h1>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="add-contact.php" class="nav-link <?php echo ($current_page == 'add-contact') ? 'active' : ''; ?>">New Contact</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="users.php" class="nav-link <?php echo ($current_page == 'users') ? 'active' : ''; ?>">Users</a></li>
                            <li><a href="add-user.php" class="nav-link <?php echo ($current_page == 'add-user') ? 'active' : ''; ?>">Add User</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="user-info">
                    <div class="user-welcome">
                        Welcome, <strong><?php echo getCurrentUserName(); ?></strong>
                    </div>
                    <div class="user-role">
                        <?php echo getCurrentUserRole(); ?>
                    </div>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">