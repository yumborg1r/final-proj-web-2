<?php
// Always use Philippine time globally
date_default_timezone_set('Asia/Manila');

// Subscription management class
class Subscription {
    private $conn;
    private $table_name = "user_subscriptions";

    public $id;
    public $user_id;
    public $plan_id;
    public $start_date;
    public $end_date;
    public $status;
    public $payment_status;
    public $approved_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new subscription
    public function createSubscription() {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            
            // Validate required fields
            if (empty($this->user_id) || empty($this->plan_id) || empty($this->start_date)) {
                throw new Exception("Missing required subscription fields");
            }

            // Set default values if not provided
            if (empty($this->status)) {
                $this->status = 'pending';
            }
            if (empty($this->payment_status)) {
                $this->payment_status = 'pending';
            }

            // Validate dates if end_date is provided - check for 30-day limit
            if (!empty($this->start_date) && !empty($this->end_date)) {
                $start = new DateTime($this->start_date);
                $end = new DateTime($this->end_date);
                $diff = $start->diff($end);
                $days_diff = $diff->days;
                
                // If subscription period exceeds 30 days, set status to expired
                if ($days_diff > 30) {
                    $this->status = 'expired';
                }
            }

            $query = "INSERT INTO {$this->table_name} 
                      SET user_id=:user_id, plan_id=:plan_id, start_date=:start_date, 
                          end_date=:end_date, status=:status, payment_status=:payment_status,
                          created_at=NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':plan_id', $this->plan_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $this->start_date);
            $stmt->bindParam(':end_date', $this->end_date);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':payment_status', $this->payment_status);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Subscription creation error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Subscription creation validation error: " . $e->getMessage());
            return false;
        }
    }

    // Approve a subscription
    public function approveSubscription($subscription_id, $approved_by) {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            
            // Validate input
            if (empty($subscription_id) || empty($approved_by)) {
                throw new Exception("Subscription ID and approver are required");
            }

            // Check if subscription dates are valid (not exceeding 30 days)
            $check_query = "SELECT start_date, end_date FROM {$this->table_name} WHERE id = :id";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $subscription = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            $status = 'active';
            if ($subscription && !empty($subscription['start_date']) && !empty($subscription['end_date'])) {
                $start = new DateTime($subscription['start_date']);
                $end = new DateTime($subscription['end_date']);
                $diff = $start->diff($end);
                $days_diff = $diff->days;
                
                // If subscription period exceeds 30 days, set status to expired
                if ($days_diff > 30) {
                    $status = 'expired';
                }
                
                // Also check if end date has passed
                $today = new DateTime(date('Y-m-d'));
                if ($end < $today) {
                    $status = 'expired';
                }
            }

            $query = "UPDATE {$this->table_name} 
                      SET status=:status, payment_status='paid', approved_by=:approved_by, 
                          approved_at=NOW() 
                      WHERE id=:id AND status = 'pending'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            $stmt->bindParam(':approved_by', $approved_by, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Subscription approval error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Subscription approval validation error: " . $e->getMessage());
            return false;
        }
    }

    // Cancel a subscription
    public function cancelSubscription($subscription_id, $cancelled_by) {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            
            // Validate input
            if (empty($subscription_id) || empty($cancelled_by)) {
                throw new Exception("Subscription ID and canceller ID are required");
            }

            $query = "UPDATE {$this->table_name} 
                      SET status='cancelled', updated_at=NOW() 
                      WHERE id=:id AND status NOT IN ('expired', 'cancelled')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Subscription cancellation error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Subscription cancellation validation error: " . $e->getMessage());
            return false;
        }
    }

    // Get all pending subscriptions
    public function getPendingSubscriptions() {
        try {
            $query = "SELECT us.*, u.first_name, u.last_name, u.email, sp.name AS plan_name, sp.price 
                      FROM {$this->table_name} us
                      JOIN users u ON us.user_id = u.id 
                      JOIN subscription_plans sp ON us.plan_id = sp.id 
                      WHERE us.status = 'pending' 
                      ORDER BY us.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get pending subscriptions error: " . $e->getMessage());
            return [];
        }
    }

    // Get all subscriptions for a user
    public function getUserSubscriptions($user_id) {
        try {
            // Validate user_id
            if (empty($user_id)) {
                throw new Exception("User ID is required");
            }

            // Always refresh subscription statuses before showing
            $this->checkExpiredSubscriptions();

            $query = "SELECT us.*, sp.name AS plan_name, sp.price, sp.duration_days 
                      FROM {$this->table_name} us
                      JOIN subscription_plans sp ON us.plan_id = sp.id 
                      WHERE us.user_id = :user_id 
                      ORDER BY us.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user subscriptions error: " . $e->getMessage());
            return [];
        }
    }

    // Get active subscription for a user
    public function getActiveUserSubscription($user_id) {
        try {
            $this->checkExpiredSubscriptions(); // Refresh statuses first
            
            $query = "SELECT us.*, sp.name AS plan_name, sp.price, sp.duration_days, sp.features
                      FROM {$this->table_name} us
                      JOIN subscription_plans sp ON us.plan_id = sp.id 
                      WHERE us.user_id = :user_id AND us.status = 'active'
                      ORDER BY us.end_date DESC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get active subscription error: " . $e->getMessage());
            return false;
        }
    }

    // Renew a subscription safely
    public function renewSubscription($subscription_id) {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            
            // Validate subscription_id
            if (empty($subscription_id)) {
                throw new Exception("Subscription ID is required");
            }

            // Start transaction for data consistency
            $this->conn->beginTransaction();

            $query = "SELECT us.*, sp.duration_days 
                      FROM {$this->table_name} us
                      JOIN subscription_plans sp ON us.plan_id = sp.id
                      WHERE us.id = :id FOR UPDATE";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            $stmt->execute();
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$subscription) {
                $this->conn->rollBack();
                return false;
            }

            $new_start_date = date('Y-m-d');
            $new_end_date = date('Y-m-d', strtotime($new_start_date . ' + ' . $subscription['duration_days'] . ' days'));

            $update_query = "UPDATE {$this->table_name} 
                             SET start_date=:start_date, end_date=:end_date, 
                                 status='active', payment_status='paid',
                                 updated_at=NOW() 
                             WHERE id=:id";
            
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':start_date', $new_start_date);
            $update_stmt->bindParam(':end_date', $new_end_date);
            $update_stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            
            $result = $update_stmt->execute();
            
            if ($result) {
                $this->conn->commit();
                // Automatically check and update status after renewal
                $this->checkExpiredSubscriptions();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Renew subscription error: " . $e->getMessage());
            return false;
        }
    }

    // Update subscription dates (with validation for 30-day limit)
    public function updateSubscriptionDates($subscription_id, $start_date, $end_date) {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            
            // Validate required fields
            if (empty($subscription_id) || empty($start_date) || empty($end_date)) {
                throw new Exception("Subscription ID, start date, and end date are required");
            }

            // Convert dates to DateTime objects for comparison
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $today = new DateTime(date('Y-m-d'));
            
            // Calculate days difference
            $diff = $start->diff($end);
            $days_diff = $diff->days;

            // Determine status based on dates
            $status = 'active';
            
            // If end date is in the past, mark as expired
            if ($end < $today) {
                $status = 'expired';
            }
            // If start date is in the future, mark as inactive
            else if ($start > $today) {
                $status = 'inactive';
            }
            // If subscription period exceeds 30 days, mark as expired
            else if ($days_diff > 30) {
                $status = 'expired';
            }

            $this->conn->beginTransaction();

            $query = "UPDATE {$this->table_name} 
                      SET start_date=:start_date, end_date=:end_date, status=:status,
                          updated_at=NOW()
                      WHERE id=:id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->conn->commit();
                // Automatically check and update status after date modification
                $this->checkExpiredSubscriptions();
                return true;
            } else {
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Update subscription dates error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Update subscription dates validation error: " . $e->getMessage());
            return false;
        }
    }

    // ✅ Automatically update subscription statuses (handles manual date edits + time travel)
    public function checkExpiredSubscriptions() {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            $today = date('Y-m-d');

            $query = "UPDATE {$this->table_name}
                      SET 
                          status = CASE
                              WHEN end_date < :today THEN 'expired'
                              WHEN start_date > :today THEN 'inactive'
                              WHEN DATEDIFF(end_date, start_date) > 30 THEN 'expired'
                              WHEN status = 'pending' THEN 'pending'
                              ELSE 'active'
                          END,
                          updated_at = NOW()
                      WHERE status <> CASE
                              WHEN end_date < :today THEN 'expired'
                              WHEN start_date > :today THEN 'inactive'
                              WHEN DATEDIFF(end_date, start_date) > 30 THEN 'expired'
                              WHEN status = 'pending' THEN 'pending'
                              ELSE 'active'
                          END";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Check expired subscriptions error: " . $e->getMessage());
            return false;
        }
    }

    // Check if user has active subscription
    public function hasActiveSubscription($user_id) {
        try {
            $this->checkExpiredSubscriptions();
            
            $query = "SELECT COUNT(*) as count 
                      FROM {$this->table_name} 
                      WHERE user_id = :user_id AND status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Check active subscription error: " . $e->getMessage());
            return false;
        }
    }

    // Method to ensure status is automatically updated - call this whenever dates might change
    public function autoUpdateStatus($subscription_id = null) {
        try {
            // Ensure Philippines timezone
            date_default_timezone_set('Asia/Manila');
            
            if ($subscription_id) {
                // Update specific subscription
                $today = date('Y-m-d');
                $query = "UPDATE {$this->table_name}
                          SET status = CASE
                              WHEN end_date < :today THEN 'expired'
                              WHEN start_date > :today THEN 'inactive'
                              WHEN DATEDIFF(end_date, start_date) > 30 THEN 'expired'
                              WHEN status = 'pending' THEN 'pending'
                              WHEN status = 'cancelled' THEN 'cancelled'
                              ELSE 'active'
                          END,
                          updated_at = NOW()
                          WHERE id = :id AND status NOT IN ('pending', 'cancelled')";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':today', $today);
                $stmt->bindParam(':id', $subscription_id, PDO::PARAM_INT);
                return $stmt->execute();
            } else {
                // Update all subscriptions
                return $this->checkExpiredSubscriptions();
            }
        } catch (PDOException $e) {
            error_log("Auto update status error: " . $e->getMessage());
            return false;
        }
    }
}

// Subscription Plan management class
class SubscriptionPlan {
    private $conn;
    private $table_name = "subscription_plans";

    public $id;
    public $name;
    public $description;
    public $price;
    public $duration_days;
    public $features;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllPlans() {
        try {
            $query = "SELECT * FROM {$this->table_name} 
                      WHERE status = 'active' 
                      ORDER BY price ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all plans error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllPlansWithSubscriptionCount() {
        try {
            $query = "SELECT sp.*, COUNT(us.id) as active_subscriptions
                      FROM {$this->table_name} sp
                      LEFT JOIN user_subscriptions us ON sp.id = us.plan_id AND us.status = 'active'
                      WHERE sp.status = 'active'
                      GROUP BY sp.id
                      ORDER BY sp.price ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get plans with count error: " . $e->getMessage());
            return [];
        }
    }

    public function getPlanById($id) {
        try {
            if (empty($id)) {
                throw new Exception("Plan ID is required");
            }

            $query = "SELECT * FROM {$this->table_name} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get plan by ID error: " . $e->getMessage());
            return false;
        }
    }

    public function createPlan() {
        try {
            // Validate required fields
            if (empty($this->name) || empty($this->price) || empty($this->duration_days)) {
                throw new Exception("Name, price, and duration are required");
            }

            // Set default status if not provided
            if (empty($this->status)) {
                $this->status = 'active';
            }

            $query = "INSERT INTO {$this->table_name} 
                      SET name=:name, description=:description, price=:price, 
                          duration_days=:duration_days, features=:features, status=:status,
                          created_at=NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':price', $this->price, PDO::PARAM_STR);
            $stmt->bindParam(':duration_days', $this->duration_days, PDO::PARAM_INT);
            $stmt->bindParam(':features', $this->features);
            $stmt->bindParam(':status', $this->status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Create plan error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePlan() {
        try {
            if (empty($this->id)) {
                throw new Exception("Plan ID is required for update");
            }

            $query = "UPDATE {$this->table_name} 
                      SET name=:name, description=:description, price=:price, 
                          duration_days=:duration_days, features=:features, status=:status,
                          updated_at=NOW()
                      WHERE id=:id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':price', $this->price, PDO::PARAM_STR);
            $stmt->bindParam(':duration_days', $this->duration_days, PDO::PARAM_INT);
            $stmt->bindParam(':features', $this->features);
            $stmt->bindParam(':status', $this->status);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update plan error: " . $e->getMessage());
            return false;
        }
    }

    public function deletePlan() {
        try {
            if (empty($this->id)) {
                throw new Exception("Plan ID is required for deletion");
            }

            // Check if plan has active subscriptions
            $check_query = "SELECT COUNT(*) as count FROM user_subscriptions 
                           WHERE plan_id = :id AND status = 'active'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                throw new Exception("Cannot delete plan with active subscriptions");
            }

            // Soft delete by setting status to inactive instead of actual deletion
            $query = "UPDATE {$this->table_name} SET status = 'inactive' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete plan error: " . $e->getMessage());
            return false;
        }
    }
}
?>