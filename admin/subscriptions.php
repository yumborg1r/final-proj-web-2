<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Subscription.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$subscription = new Subscription($db);

$message = '';
$message_type = '';

// Handle subscription approval
if (isset($_GET['approve'])) {
    $subscription_id = $_GET['approve'];
    if ($subscription->approveSubscription($subscription_id, getUserId())) {
        $message = 'Subscription approved successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to approve subscription.';
        $message_type = 'danger';
    }
}

// Handle subscription cancellation
if (isset($_GET['cancel'])) {
    $subscription_id = $_GET['cancel'];
    if ($subscription->cancelSubscription($subscription_id, getUserId())) {
        $message = 'Subscription cancelled successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to cancel subscription.';
        $message_type = 'danger';
    }
}

// Get all subscriptions with user and plan details
$query = "SELECT us.*, u.first_name, u.last_name, u.email, u.phone, 
                 sp.name as plan_name, sp.price, sp.duration_days,
                 approver.first_name as approver_first, approver.last_name as approver_last
          FROM user_subscriptions us 
          JOIN users u ON us.user_id = u.id 
          JOIN subscription_plans sp ON us.plan_id = sp.id 
          LEFT JOIN users approver ON us.approved_by = approver.id
          ORDER BY us.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell"></i> GymFit Pro - Admin
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="subscriptions.php">
                            <i class="fas fa-credit-card"></i> Subscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workout-plans.php">
                            <i class="fas fa-dumbbell"></i> Workout Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check"></i> Attendance
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
            <h2><i class="fas fa-credit-card"></i> Subscriptions Management</h2>
            <div>
                <a href="subscription-plans.php" class="btn btn-warning">
                    <i class="fas fa-cog"></i> Manage Plans
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Subscriptions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Price</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Approved By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><?php echo $sub['id']; ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo $sub['first_name'] . ' ' . $sub['last_name']; ?></strong><br>
                                            <small class="text-muted"><?php echo $sub['email']; ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo $sub['plan_name']; ?></td>
                                    <td><?php echo formatCurrency($sub['price']); ?></td>
                                    <td><?php echo formatDate($sub['start_date']); ?></td>
                                    <td><?php echo formatDate($sub['end_date']); ?></td>
                                    <td><?php echo getStatusBadge($sub['status']); ?></td>
                                    <td><?php echo getStatusBadge($sub['payment_status']); ?></td>
                                    <td>
                                        <?php if ($sub['approver_first']): ?>
                                            <?php echo $sub['approver_first'] . ' ' . $sub['approver_last']; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not approved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                    <td>
                                        <?php if ($sub['status'] == 'pending'): ?>
                                            <a href="subscriptions.php?approve=<?php echo $sub['id']; ?>" 
                                               class="btn btn-success btn-sm" 
                                               onclick="return confirm('Are you sure you want to approve this subscription?')">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                            <a href="subscriptions.php?cancel=<?php echo $sub['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to cancel this subscription?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php elseif ($sub['status'] == 'active'): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Active</span>
                                            <a href="subscriptions.php?cancel=<?php echo $sub['id']; ?>" 
                                               class="btn btn-danger btn-sm ms-2" 
                                               onclick="return confirm('Are you sure you want to cancel this ACTIVE subscription? This action cannot be undone.')">
                                                <i class="fas fa-ban"></i> Cancel
                                            </a>
                                        <?php elseif ($sub['status'] == 'cancelled'): ?>
                                            <span class="text-muted"><i class="fas fa-ban"></i> Cancelled</span>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo ucfirst($sub['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
