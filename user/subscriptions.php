<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Subscription.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);

$message = '';
$message_type = '';

// Handle subscription creation
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'subscribe') {
    $plan_id = $_POST['plan_id'];
    
    // Get plan details
    $plan_query = "SELECT * FROM subscription_plans WHERE id = :id";
    $plan_stmt = $db->prepare($plan_query);
    $plan_stmt->bindParam(':id', $plan_id);
    $plan_stmt->execute();
    $plan = $plan_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        $subscription->user_id = getUserId();
        $subscription->plan_id = $plan_id;
        $subscription->start_date = date('Y-m-d');
        $subscription->end_date = date('Y-m-d', strtotime('+' . $plan['duration_days'] . ' days'));
        $subscription->status = 'pending';
        $subscription->payment_status = 'pending';
        
        if ($subscription->createSubscription()) {
            $message = 'Subscription request submitted successfully! Please wait for admin approval.';
            $message_type = 'success';
        } else {
            $message = 'Failed to create subscription. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Handle subscription renewal
if (isset($_GET['renew'])) {
    $subscription_id = $_GET['renew'];
    if ($subscription->renewSubscription($subscription_id)) {
        $message = 'Subscription renewed successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to renew subscription.';
        $message_type = 'danger';
    }
}

// Get user's subscriptions
$user_subscriptions = $subscription->getUserSubscriptions(getUserId());

// Get available subscription plans
$plan_query = "SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY price ASC";
$plan_stmt = $db->prepare($plan_query);
$plan_stmt->execute();
$available_plans = $plan_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscriptions - Gym Membership System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell"></i> GymFit Pro
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="machines.php">
                            <i class="fas fa-cogs"></i> Machines
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">
                            <i class="fas fa-chart-line"></i> Track Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="subscriptions.php">
                            <i class="fas fa-credit-card"></i> My Subscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workout-plans.php">
                            <i class="fas fa-dumbbell"></i> Workout Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check"></i> My Attendance
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo getUserName(); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-credit-card"></i> My Subscriptions</h2>
            <?php if (isset($_GET['action']) && $_GET['action'] == 'subscribe'): ?>
                <a href="subscriptions.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Subscriptions
                </a>
            <?php else: ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subscribeModal">
                    <i class="fas fa-plus"></i> Subscribe to Plan
                </button>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['action']) && $_GET['action'] == 'subscribe'): ?>
            <!-- Subscription Plans -->
            <div class="row">
                <?php foreach ($available_plans as $plan): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header text-center">
                                <h4 class="text-warning"><?php echo $plan['name']; ?></h4>
                                <h2 class="text-primary"><?php echo formatCurrency($plan['price']); ?></h2>
                                <p class="text-muted">per month</p>
                            </div>
                            <div class="card-body">
                                <p class="text-muted"><?php echo $plan['description']; ?></p>
                                <ul class="list-unstyled">
                                    <?php 
                                    $features = explode(',', $plan['features']);
                                    foreach ($features as $feature): 
                                    ?>
                                        <li><i class="fas fa-check text-success"></i> <?php echo trim($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="text-muted">
                                    <i class="fas fa-calendar"></i> Duration: <?php echo $plan['duration_days']; ?> days
                                </p>
                            </div>
                            <div class="card-footer text-center">
                                <button class="btn btn-primary w-100" 
                                        onclick="subscribeToPlan(<?php echo $plan['id']; ?>, '<?php echo $plan['name']; ?>', <?php echo $plan['price']; ?>)">
                                    <i class="fas fa-credit-card"></i> Subscribe Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- My Subscriptions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Subscription History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($user_subscriptions)): ?>
                        <div class="text-center">
                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Subscriptions Found</h5>
                            <p class="text-muted">You haven't subscribed to any plans yet.</p>
                            <a href="subscriptions.php?action=subscribe" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Browse Plans
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Price</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_subscriptions as $sub): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $sub['plan_name']; ?></strong><br>
                                                <small class="text-muted"><?php echo $sub['duration_days']; ?> days</small>
                                            </td>
                                            <td><?php echo formatCurrency($sub['price']); ?></td>
                                            <td><?php echo formatDate($sub['start_date']); ?></td>
                                            <td><?php echo formatDate($sub['end_date']); ?></td>
                                            <td><?php echo getStatusBadge($sub['status']); ?></td>
                                            <td><?php echo getStatusBadge($sub['payment_status']); ?></td>
                                            <td>
                                                <?php if ($sub['status'] == 'expired'): ?>
                                                    <a href="subscriptions.php?renew=<?php echo $sub['id']; ?>" 
                                                       class="btn btn-warning btn-sm"
                                                       onclick="return confirm('Are you sure you want to renew this subscription?')">
                                                        <i class="fas fa-sync"></i> Renew
                                                    </a>
                                                <?php elseif ($sub['status'] == 'pending'): ?>
                                                    <span class="text-muted">Waiting for approval</span>
                                                <?php else: ?>
                                                    <span class="text-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Subscribe Modal -->
    <div class="modal fade" id="subscribeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subscribe to Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="subscribe">
                        <input type="hidden" name="plan_id" id="selected_plan_id">
                        
                        <div class="text-center mb-3">
                            <h4 id="selected_plan_name"></h4>
                            <h2 class="text-primary" id="selected_plan_price"></h2>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Your subscription will be pending until approved by an administrator. 
                            You will be notified once approved.
                        </div>
                        
                        <p>By subscribing, you agree to:</p>
                        <ul>
                            <li>Pay the monthly subscription fee</li>
                            <li>Follow gym rules and regulations</li>
                            <li>Maintain proper gym etiquette</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function subscribeToPlan(planId, planName, planPrice) {
            document.getElementById('selected_plan_id').value = planId;
            document.getElementById('selected_plan_name').textContent = planName;
            document.getElementById('selected_plan_price').textContent = '$' + planPrice.toFixed(2);
            
            new bootstrap.Modal(document.getElementById('subscribeModal')).show();
        }
    </script>
</body>
</html>
