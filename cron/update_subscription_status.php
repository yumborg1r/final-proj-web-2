<?php
/**
 * Cron Job Script for Automatic Subscription Status Updates
 * 
 * This script should be run periodically (e.g., daily or hourly) via cron job
 * to automatically update subscription statuses in the database.
 * 
 * Example cron job (runs daily at 2:00 AM):
 * 0 2 * * * /usr/bin/php /path/to/cron/update_subscription_status.php
 * 
 * For Windows Task Scheduler, create a scheduled task to run:
 * php.exe "C:\xampp\htdocs\jm\cron\update_subscription_status.php"
 */

// Set Philippines timezone
date_default_timezone_set('Asia/Manila');

// Include required files
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Subscription.php';

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    // Initialize subscription class
    $subscription = new Subscription($db);
    
    // Log the start
    $log_message = "[" . date('Y-m-d H:i:s') . "] Starting automatic subscription status update...\n";
    error_log($log_message, 3, __DIR__ . '/../logs/subscription_updates.log');
    
    // Run the automatic status check
    $result = $subscription->checkExpiredSubscriptions();
    
    if ($result) {
        $log_message = "[" . date('Y-m-d H:i:s') . "] Subscription statuses updated successfully.\n";
        error_log($log_message, 3, __DIR__ . '/../logs/subscription_updates.log');
        echo "SUCCESS: Subscription statuses updated.\n";
    } else {
        $log_message = "[" . date('Y-m-d H:i:s') . "] No subscription statuses needed updating.\n";
        error_log($log_message, 3, __DIR__ . '/../logs/subscription_updates.log');
        echo "INFO: No subscription statuses needed updating.\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    $error_message = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    error_log($error_message, 3, __DIR__ . '/../logs/subscription_updates.log');
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}



