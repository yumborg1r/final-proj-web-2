<?php
// Always use Philippine time globally
date_default_timezone_set('Asia/Manila');

require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/WorkoutPlan.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$attendance = new Attendance($db);
$workoutPlan = new WorkoutPlan($db);

// Handle self check-in
if ($_POST && isset($_POST['self_check_in'])) {
    // Ensure Philippine timezone
    date_default_timezone_set('Asia/Manila');
    
    $selected_plan = $_POST['workout_plan_id'] ?? '';
    if (!empty($selected_plan)) {
        $attendance->user_id = getUserId();
        $attendance->workout_plan_id = $selected_plan;
        $attendance->attendance_date = date('Y-m-d');
        $attendance->check_in_time = date('H:i:s');
        $attendance->check_out_time = null;
        $attendance->notes = sanitizeInput($_POST['notes'] ?? '');
        $attendance->marked_by = getUserId();
        $attendance->markAttendance();
        header('Location: attendance.php');
        exit();
    }
}

// Handle self check-out
if ($_POST && isset($_POST['self_check_out'])) {
    $open = $attendance->getOpenAttendanceForUserToday(getUserId());
    if ($open) {
        $attendance->checkOutById($open['id']);
    }
    header('Location: attendance.php');
    exit();
}

// Get user's attendance
$user_attendance = $attendance->getAttendanceByUser(getUserId());

// Get attendance stats
$attendance_stats = $attendance->getAttendanceStats(getUserId());

// Filter by date range if provided
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filtered_attendance = $attendance->getAttendanceByUser(getUserId(), $start_date, $end_date);

// Data for self check-in form
$open_att = $attendance->getOpenAttendanceForUserToday(getUserId());
$plans = $workoutPlan->getAllWorkoutPlans();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - User</title>
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
                        <a class="nav-link active" href="attendance.php">
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
            <h2><i class="fas fa-calendar-check"></i> My Attendance</h2>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $attendance_stats['total_workouts'] ?? 0; ?></div>
                    <div class="stats-label">Total Workouts</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $attendance_stats['unique_days'] ?? 0; ?></div>
                    <div class="stats-label">Days Attended</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo round($attendance_stats['avg_duration'] ?? 0); ?></div>
                    <div class="stats-label">Avg Duration (min)</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($filtered_attendance); ?></div>
                    <div class="stats-label">This Period</div>
                </div>
            </div>
        </div>

        <!-- Self Check-In / Check-Out -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Today's Attendance</h5>
            </div>
            <div class="card-body">
                <?php if ($open_att): ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div><strong>Checked in:</strong> <?php echo date('h:i:s A', strtotime($open_att['check_in_time'])); ?></div>
                            <div><strong>Workout:</strong> <?php echo $workoutPlan->getWorkoutPlanById($open_att['workout_plan_id'])['name']; ?></div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="self_check_out" value="1">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-sign-out-alt"></i> Check Out
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="self_check_in" value="1">
                        <div class="col-md-6">
                            <label for="workout_plan_id" class="form-label">Workout Plan</label>
                            <select class="form-select" id="workout_plan_id" name="workout_plan_id" required>
                                <option value="">Select Workout Plan</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo $plan['id']; ?>"><?php echo $plan['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <input type="text" class="form-control" id="notes" name="notes" placeholder="How are you feeling today?">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Check In
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="attendance.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Attendance History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($filtered_attendance)): ?>
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
                                    <th>Duration</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_attendance as $att): ?>
                                    <tr>
                                        <td><?php echo formatDate($att['attendance_date']); ?></td>
                                        <td><?php echo $att['workout_name']; ?></td>
                                        <td><?php echo date('h:i:s A', strtotime($att['check_in_time'])); ?></td>
                                        <td><?php echo $att['check_out_time'] ? date('h:i:s A', strtotime($att['check_out_time'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            if ($att['check_in_time'] && $att['check_out_time']) {
                                                $start = strtotime($att['check_in_time']);
                                                $end = strtotime($att['check_out_time']);
                                                $duration = round(($end - $start) / 60);
                                                echo $duration . ' min';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $att['notes'] ?: 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
