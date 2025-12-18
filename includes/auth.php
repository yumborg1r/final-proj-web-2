<?php
session_start();

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($required_role) {
    requireLogin();
    if ($_SESSION['role'] !== $required_role) {
        header('Location: unauthorized.php');
        exit();
    }
}

function requireRoles($required_roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $required_roles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// File upload helper
function uploadFile($file, $upload_dir = 'uploads/') {
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $upload_path];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Utility functions
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 0);
}

function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge badge-success">Active</span>',
        'inactive' => '<span class="badge badge-secondary">Inactive</span>',
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'expired' => '<span class="badge badge-danger">Expired</span>',
        'cancelled' => '<span class="badge badge-dark">Cancelled</span>',
        'paid' => '<span class="badge badge-success">Paid</span>',
        'failed' => '<span class="badge badge-danger">Failed</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

function getDifficultyBadge($difficulty) {
    $badges = [
        'beginner' => '<span class="badge badge-success">Beginner</span>',
        'intermediate' => '<span class="badge badge-warning">Intermediate</span>',
        'advanced' => '<span class="badge badge-danger">Advanced</span>'
    ];
    
    return $badges[$difficulty] ?? '<span class="badge badge-secondary">' . ucfirst($difficulty) . '</span>';
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}
