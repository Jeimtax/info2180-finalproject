<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get filter from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'sales', 'support', 'assigned'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Get user ID
$userId = getCurrentUserId();

// Get contacts and stats
$contacts = getContacts($filter, $userId);
$stats = getDashboardStats($userId);

// Prepare response
$response = [
    'success' => true,
    'contacts' => $contacts,
    'stats' => $stats,
    'filter' => $filter
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>