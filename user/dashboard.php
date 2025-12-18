<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Subscription.php';
require_once '../includes/WorkoutPlan.php';
require_once '../includes/Booking.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);
$workoutPlan = new WorkoutPlan($db);
$attendance = new Attendance($db);
$booking = new Booking($db);

$message = '';
$message_type = '';

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

// Get workout plans
$workout_plans = $workoutPlan->getAllWorkoutPlans();

// Get user's attendance
$user_attendance = $attendance->getAttendanceByUser(getUserId());

// Get attendance stats
$attendance_stats = $attendance->getAttendanceStats(getUserId());

// Get upcoming bookings
$user_bookings = $booking->getUserBookings(getUserId());

// Check for expired subscriptions
$subscription->checkExpiredSubscriptions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Gym Membership System</title>
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscriptions.php">
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
                    <?php 
                    // Check if user has VIP plan
                    $active_sub = $subscription->getActiveUserSubscription(getUserId());
                    if ($active_sub && strtolower($active_sub['plan_name']) === 'vip plan'): 
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="coaching.php">
                            <i class="fas fa-user-tie"></i> Personal Coaching
                        </a>
                    </li>
                    <?php endif; ?>
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
            <h2><i class="fas fa-tachometer-alt"></i> Welcome, <?php echo getUserName(); ?>!</h2>
            <a href="subscriptions.php?action=subscribe" class="btn btn-primary">
                <i class="fas fa-plus"></i> Subscribe to Plan
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3 fade-in delay-1">
                <div class="stats-card">
                    <i class="fas fa-credit-card stats-card-icon"></i>
                    <div class="stats-number"><?php echo count($user_subscriptions); ?></div>
                    <div class="stats-label">My Subscriptions</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-running stats-card-icon"></i>
                    <div class="stats-number"><?php echo $attendance_stats['total_workouts'] ?? 0; ?></div>
                    <div class="stats-label">Workouts</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-1">
                <div class="stats-card">
                    <i class="fas fa-calendar-alt stats-card-icon"></i>
                    <div class="stats-number"><?php echo $attendance_stats['unique_days'] ?? 0; ?></div>
                    <div class="stats-label">Days Active</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-stopwatch stats-card-icon"></i>
                    <div class="stats-number"><?php echo round($attendance_stats['avg_duration'] ?? 0); ?></div>
                    <div class="stats-label">Avg Mins/Session</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Sessions -->
            <div class="col-md-12 mb-4 fade-in delay-2">
                 <div class="card bg-dark text-white border-warning">
                    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-warning">
                            <i class="fas fa-calendar-alt"></i> Upcoming Coaching Sessions
                        </h5>
                        <a href="coaches.php" class="btn btn-sm btn-outline-warning">Book New Session</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_bookings)): ?>
                            <p class="text-muted text-center my-3">No upcoming sessions booked.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Coach</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($user_bookings, 0, 3) as $bk): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($bk['booking_date'])); ?></td>
                                                <td><?php echo $bk['time_slot']; ?></td>
                                                <td><?php echo $bk['coach_first'] . ' ' . $bk['coach_last']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $bk['status'] == 'confirmed' ? 'success' : ($bk['status'] == 'cancelled' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($bk['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($bk['status'] == 'pending' || $bk['status'] == 'confirmed'): ?>
                                                        <form method="POST" action="coaches.php" class="d-inline">
                                                            <input type="hidden" name="action" value="cancel_booking">
                                                            <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                                           <!-- Cancellation would be handled in coaches.php or similar - adding button for UI completeness -->
                                                        </form>
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
            </div>
        </div>

        <div class="row">
            <!-- Current Subscription -->
            <div class="col-md-6 mb-4 fade-in delay-3">
                <div class="card h-100 border-0 bg-transparent">
                    <div class="card-header border-0 pb-0">
                        <h5 class="mb-0 text-white">
                            <i class="fas fa-id-card"></i> Membership Card
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $active_subscription = null;
                        foreach ($user_subscriptions as $sub) {
                            if ($sub['status'] == 'active') {
                                $active_subscription = $sub;
                                break;
                            }
                        }
                        ?>
                        
                        <?php if ($active_subscription): ?>
                            <div class="membership-card">
                                <div class="card-chip"></div>
                                <h3 class="membership-card-title mb-4"><?php echo strtoupper($active_subscription['plan_name']); ?> MEMBER</h3>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-white-50 d-block">MEMBER NAME</small>
                                        <span class="fw-bold"><?php echo strtoupper(getUserName()); ?></span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small class="text-white-50 d-block">EXPIRES</small>
                                        <span class="fw-bold"><?php echo date('m/y', strtotime($active_subscription['end_date'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-end">
                                    <div class="small text-white-50">Valid Thru: <?php echo formatDate($active_subscription['end_date']); ?></div>
                                    <div><i class="fas fa-dumbbell fa-2x text-white-50"></i></div>
                                </div>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <a href="subscriptions.php?renew=<?php echo $active_subscription['id']; ?>" 
                                   class="btn btn-warning w-100 rounded-pill"
                                   onclick="return confirm('Are you sure you want to renew this subscription?')">
                                    <i class="fas fa-sync"></i> Renew Membership
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="card shadow-sm p-4 text-center">
                                <div class="py-4">
                                    <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                                    <h5>No Active Membership</h5>
                                    <p class="text-muted mb-4">Join GymFit Pro today and start your journey!</p>
                                    <a href="subscriptions.php?action=subscribe" class="btn btn-primary rounded-pill px-4">
                                        Subscibe Now
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Workouts -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-dumbbell"></i> Available Workout Plans
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($workout_plans)): ?>
                            <p class="text-muted">No workout plans available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Workout</th>
                                            <th>Difficulty</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($workout_plans, 0, 5) as $plan): ?>
                                            <tr>
                                                <td><?php echo $plan['name']; ?></td>
                                                <td><?php echo getDifficultyBadge($plan['difficulty_level']); ?></td>
                                                <td><?php echo $plan['duration_minutes']; ?> min</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="workout-plans.php" class="btn btn-outline-primary">View All Plans</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check"></i> Recent Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_attendance)): ?>
                            <div class="text-center">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Attendance Records</h5>
                                <p class="text-muted">Start working out to see your attendance history!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Workout Plan</th>
                                            <th>Check In</th>
                                            <th>Check Out</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($user_attendance, 0, 10) as $att): ?>
                                            <tr>
                                                <td><?php echo formatDate($att['attendance_date']); ?></td>
                                                <td><?php echo $att['workout_name']; ?></td>
                                                <td><?php echo date('h:i:s A', strtotime($att['check_in_time'])); ?></td>
                                                <td><?php echo $att['check_out_time'] ? date('h:i:s A', strtotime($att['check_out_time'])) : 'N/A'; ?></td>
                                                <td><?php echo $att['notes'] ?: 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center">
                                <a href="attendance.php" class="btn btn-outline-primary">View All Attendance</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
