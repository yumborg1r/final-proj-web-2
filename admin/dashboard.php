<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Subscription.php';
require_once '../includes/WorkoutPlan.php';
require_once '../includes/Booking.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$user = new User($db);
$subscription = new Subscription($db);
$workoutPlan = new WorkoutPlan($db);
$attendance = new Attendance($db);
$booking = new Booking($db);

// Get stats
$total_users = count($user->getAllUsers());
$pending_subscriptions = count($subscription->getPendingSubscriptions());
$total_workout_plans = count($workoutPlan->getAllWorkoutPlans());
$recent_attendance = count($attendance->getAllAttendance(date('Y-m-01'), date('Y-m-d')));
$pending_bookings = 0; // Initialize
$all_bookings = $booking->getAllBookings(); // Create this method in Booking.php if not exists or use direct query if needed, but we added getAllBookings in Step 332.
$pending_bookings_count = 0;
foreach($all_bookings as $b) { if($b['status'] == 'pending') $pending_bookings_count++; }

// Get recent activities
$recent_subscriptions = $subscription->getPendingSubscriptions();
$recent_bookings = array_slice($all_bookings, 0, 5);
$recent_users = array_slice($user->getAllUsers(), 0, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gym Membership System</title>
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
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="coaches.php">
                            <i class="fas fa-user-tie"></i> Coaches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-calendar-alt"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscriptions.php">
                            <i class="fas fa-credit-card"></i> Subscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workout-plans.php">
                            <i class="fas fa-dumbbell"></i> Workout Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="machines.php">
                            <i class="fas fa-cogs"></i> Machines
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
        <!-- Dashboard Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3 fade-in delay-1">
                <div class="stats-card">
                    <i class="fas fa-users stats-card-icon"></i>
                    <div class="stats-number"><?php echo $total_users; ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-file-contract stats-card-icon"></i>
                    <div class="stats-number"><?php echo $pending_subscriptions; ?></div>
                    <div class="stats-label">Pending Subs</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-clock stats-card-icon"></i>
                    <div class="stats-number"><?php echo $pending_bookings_count; ?></div>
                    <div class="stats-label">Pending Bookings</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-1">
                <div class="stats-card">
                    <i class="fas fa-dumbbell stats-card-icon"></i>
                    <div class="stats-number"><?php echo $total_workout_plans; ?></div>
                    <div class="stats-label">Workout Plans</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-calendar-check stats-card-icon"></i>
                    <div class="stats-number"><?php echo $recent_attendance; ?></div>
                    <div class="stats-label">Monthly Attendance</div>
                </div>
            </div>
        </div>

        <div class="row fade-in delay-3">
            <!-- Pending Subscriptions -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-warning"></i> Pending Subscriptions
                        </h5>
                        <span class="badge badge-warning"><?php echo count($recent_subscriptions); ?> New</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_subscriptions)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p>No pending subscriptions to review.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Plan</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($recent_subscriptions, 0, 5) as $sub): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo $sub['first_name'] . ' ' . $sub['last_name']; ?></div>
                                                    <small class="text-muted"><?php echo $sub['email']; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $sub['plan_name']; ?></span><br>
                                                    <small><?php echo formatCurrency($sub['price']); ?></small>
                                                </td>
                                                <td>
                                                    <a href="subscriptions.php?approve=<?php echo $sub['id']; ?>" 
                                                       class="btn btn-success btn-sm rounded-pill px-3">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="subscriptions.php" class="btn btn-outline-primary btn-sm rounded-pill">View All Pending</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
             <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check text-success"></i> Recent Bookings
                        </h5>
                        <a href="bookings.php" class="btn btn-sm btn-outline-success">Manage</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_bookings)): ?>
                            <p class="text-muted text-center py-4">No recent bookings.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Coach</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $bk): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo date('M d', strtotime($bk['booking_date'])); ?></div>
                                                    <small class="text-muted"><?php echo $bk['time_slot']; ?></small>
                                                </td>
                                                <td><?php echo $bk['coach_first'] . ' ' . $bk['coach_last']; ?></td>
                                                <td><?php echo getStatusBadge($bk['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-info"></i> New Members
                        </h5>
                        <a href="users.php" class="btn btn-sm btn-outline-primary">Manage</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_users)): ?>
                            <p class="text-muted text-center py-4">No users found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user_data): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                            <i class="fas fa-user text-white small"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></div>
                                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo $user_data['email']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge badge-secondary"><?php echo ucfirst($user_data['role']); ?></span></td>
                                                <td><?php echo getStatusBadge($user_data['status']); ?></td>
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

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="users.php?action=add" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus"></i> Add User
                                </a>
                            </div>
                             <div class="col-md-3 mb-2">
                                <a href="bookings.php" class="btn btn-purple w-100" style="background-color: #6f42c1; color: white;">
                                    <i class="fas fa-calendar-plus"></i> Manage Bookings
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="workout-plans.php?action=add" class="btn btn-warning w-100">
                                    <i class="fas fa-plus"></i> Add Workout Plan
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="subscriptions.php" class="btn btn-success w-100">
                                    <i class="fas fa-credit-card"></i> Manage Subscriptions
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="attendance.php" class="btn btn-info w-100">
                                    <i class="fas fa-calendar-check"></i> View Attendance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
