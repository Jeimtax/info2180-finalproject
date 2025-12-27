<?php
/**
 * Database functions for Dolphin CRM
 */

require_once 'config.php';

/**
 * Get all contacts with optional filters
 * @param string $filter Filter type: 'all', 'sales', 'support', 'assigned'
 * @param int $userId Current user ID for 'assigned' filter
 * @return array Array of contacts
 */
function getContacts($filter = 'all', $userId = null) {
    try {
        $conn = getDBConnection();
        
        $sql = "SELECT c.*, 
                u1.firstname as assigned_firstname, 
                u1.lastname as assigned_lastname,
                u2.firstname as created_firstname,
                u2.lastname as created_lastname
                FROM Contacts c 
                LEFT JOIN Users u1 ON c.assigned_to = u1.id 
                LEFT JOIN Users u2 ON c.created_by = u2.id";
        
        $conditions = [];
        $params = [];
        
        // Apply filters
        switch ($filter) {
            case 'sales':
                $conditions[] = "c.type = 'Sales Lead'";
                break;
            case 'support':
                $conditions[] = "c.type = 'Support'";
                break;
            case 'assigned':
                if ($userId) {
                    $conditions[] = "c.assigned_to = :user_id";
                    $params[':user_id'] = $userId;
                }
                break;
            // 'all' shows everything
        }
        
        // Add WHERE clause if we have conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Order by most recent first
        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters if we have any
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error getting contacts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get contact by ID
 * @param int $contactId Contact ID
 * @return array|bool Contact data or false if not found
 */
function getContactById($contactId) {
    try {
        $conn = getDBConnection();
        
        $sql = "SELECT c.*, 
                u1.firstname as assigned_firstname, 
                u1.lastname as assigned_lastname,
                u2.firstname as created_firstname,
                u2.lastname as created_lastname
                FROM Contacts c 
                LEFT JOIN Users u1 ON c.assigned_to = u1.id 
                LEFT JOIN Users u2 ON c.created_by = u2.id
                WHERE c.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $contactId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
        
    } catch(PDOException $e) {
        error_log("Error getting contact: " . $e->getMessage());
        return false;
    }
}

/**
 * Get dashboard statistics
 * @param int $userId Current user ID
 * @return array Statistics array
 */
function getDashboardStats($userId) {
    try {
        $conn = getDBConnection();
        $stats = [];
        
        // Total contacts
        $stmt = $conn->query("SELECT COUNT(*) as count FROM Contacts");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Sales leads
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Contacts WHERE type = 'Sales Lead'");
        $stmt->execute();
        $stats['sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Support
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Contacts WHERE type = 'Support'");
        $stmt->execute();
        $stats['support'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Assigned to me
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Contacts WHERE assigned_to = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $stats['assigned'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
        
    } catch(PDOException $e) {
        error_log("Error getting stats: " . $e->getMessage());
        return ['total' => 0, 'sales' => 0, 'support' => 0, 'assigned' => 0];
    }
}

/**
 * Get all users for dropdowns
 * @return array Array of users
 */
function getAllUsers() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->query("SELECT id, firstname, lastname, role FROM Users ORDER BY firstname, lastname");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting users: " . $e->getMessage());
        return [];
    }
}

/**
 * Format date for display
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Format datetime for display
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    return date('M j, Y \a\t g:i A', strtotime($datetime));
}
?>