<?php
// Always use Philippine time globally
date_default_timezone_set('Asia/Manila');

require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/WorkoutPlan.php';

requireRole('staff');

$database = new Database();
$db = $database->getConnection();
$workoutPlan = new WorkoutPlan($db);
$attendance = new Attendance($db);

$message = '';
$message_type = '';

// Handle workout plan actions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $workoutPlan->name = sanitizeInput($_POST['name']);
        $workoutPlan->description = sanitizeInput($_POST['description']);
        $workoutPlan->exercises = sanitizeInput($_POST['exercises']);
        $workoutPlan->duration_minutes = $_POST['duration_minutes'];
        $workoutPlan->difficulty_level = sanitizeInput($_POST['difficulty_level']);
        $workoutPlan->created_by = getUserId();
        $workoutPlan->status = 'active';
        
        if ($workoutPlan->createWorkoutPlan()) {
            $message = 'Workout plan added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to add workout plan.';
            $message_type = 'danger';
        }
    } elseif ($action == 'edit') {
        $workoutPlan->id = $_POST['workout_id'];
        $workoutPlan->name = sanitizeInput($_POST['name']);
        $workoutPlan->description = sanitizeInput($_POST['description']);
        $workoutPlan->exercises = sanitizeInput($_POST['exercises']);
        $workoutPlan->duration_minutes = $_POST['duration_minutes'];
        $workoutPlan->difficulty_level = sanitizeInput($_POST['difficulty_level']);
        $workoutPlan->status = sanitizeInput($_POST['status']);
        
        if ($workoutPlan->updateWorkoutPlan()) {
            $message = 'Workout plan updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update workout plan.';
            $message_type = 'danger';
        }
    }
}

// Handle attendance marking
if (isset($_POST['mark_attendance'])) {
    // Ensure Philippine timezone
    date_default_timezone_set('Asia/Manila');
    
    $user_ids = $_POST['user_ids'] ?? [];
    $workout_plan_id = $_POST['workout_plan_id'];
    $attendance_date = $_POST['attendance_date'];
    $notes = sanitizeInput($_POST['notes']);
    
    foreach ($user_ids as $user_id) {
        $attendance->user_id = $user_id;
        $attendance->workout_plan_id = $workout_plan_id;
        $attendance->attendance_date = $attendance_date;
        $attendance->check_in_time = date('H:i:s');
        $attendance->check_out_time = null;
        $attendance->notes = $notes;
        $attendance->marked_by = getUserId();
        
        $attendance->markAttendance();
    }
    
    $message = 'Attendance marked successfully!';
    $message_type = 'success';
}

// Get workout plans
$workout_plans = $workoutPlan->getAllWorkoutPlans();

// Get users for attendance
$user = new User($db);
$users = $user->getAllUsers();

// Get recent attendance
$recent_attendance = $attendance->getAllAttendance(date('Y-m-01'), date('Y-m-d'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Gym Membership System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell"></i> GymFit Pro - Staff
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
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
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-calendar-alt"></i> Bookings
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
            <h2><i class="fas fa-tachometer-alt"></i> Staff Dashboard</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#workoutModal">
                    <i class="fas fa-plus"></i> Add Workout Plan
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                    <i class="fas fa-calendar-check"></i> Mark Attendance
                </button>
                <a href="bookings.php" class="btn btn-warning text-dark">
                    <i class="fas fa-calendar-alt"></i> View Bookings
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3 fade-in delay-1">
                <div class="stats-card">
                    <i class="fas fa-clipboard-list stats-card-icon"></i>
                    <div class="stats-number"><?php echo count($workout_plans); ?></div>
                    <div class="stats-label">Workout Plans</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-users stats-card-icon"></i>
                    <div class="stats-number"><?php echo count($users); ?></div>
                    <div class="stats-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-1">
                <div class="stats-card">
                    <i class="fas fa-calendar-check stats-card-icon"></i>
                    <div class="stats-number"><?php echo count($recent_attendance); ?></div>
                    <div class="stats-label">Monthly Attendance</div>
                </div>
            </div>
            <div class="col-md-3 mb-3 fade-in delay-2">
                <div class="stats-card">
                    <i class="fas fa-user-check stats-card-icon"></i>
                    <div class="stats-number"><?php echo count(array_filter($users, function($u) { return $u['role'] == 'user'; })); ?></div>
                    <div class="stats-label">Active Members</div>
                </div>
            </div>
        </div>

        <div class="row fade-in delay-3">
            <!-- Workout Plans -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-dumbbell"></i> Recent Workout Plans
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($workout_plans)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-clipboard fa-3x mb-3"></i>
                                <p>No workout plans found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Difficulty</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($workout_plans, 0, 5) as $plan): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo $plan['name']; ?></td>
                                                <td><?php echo getDifficultyBadge($plan['difficulty_level']); ?></td>
                                                <td><?php echo $plan['duration_minutes']; ?> min</td>
                                                <td><?php echo getStatusBadge($plan['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="workout-plans.php" class="btn btn-outline-primary btn-sm rounded-pill">View All Plans</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check"></i> Recent Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attendance)): ?>
                             <div class="text-center py-4 text-muted">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <p>No attendance records found.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Workout</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($recent_attendance, 0, 5) as $att): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo $att['first_name'] . ' ' . $att['last_name']; ?></td>
                                                <td><small class="text-muted"><?php echo $att['workout_name']; ?></small></td>
                                                <td><?php echo date('M d', strtotime($att['attendance_date'])); ?></td>
                                                <td><span class="badge badge-secondary"><?php echo date('h:i A', strtotime($att['check_in_time'])); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="attendance.php" class="btn btn-outline-primary btn-sm rounded-pill">View All Records</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workout Plan Modal -->
    <div class="modal fade" id="workoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Workout Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Workout Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exercises" class="form-label">Exercises</label>
                            <textarea class="form-control" id="exercises" name="exercises" rows="4" required 
                                      placeholder="List exercises separated by commas (e.g., Squats, Push-ups, Burpees)"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="difficulty_level" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="difficulty_level" name="difficulty_level" required>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Workout Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="mark_attendance" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="workout_plan_id" class="form-label">Workout Plan</label>
                                <select class="form-select" id="workout_plan_id" name="workout_plan_id" required>
                                    <option value="">Select Workout Plan</option>
                                    <?php foreach ($workout_plans as $plan): ?>
                                        <option value="<?php echo $plan['id']; ?>"><?php echo $plan['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="attendance_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Users (Check all that apply)</label>
                            <div class="row">
                                <?php foreach ($users as $u): ?>
                                    <?php if ($u['role'] == 'user' && $u['status'] == 'active'): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="user_ids[]" value="<?php echo $u['id']; ?>" 
                                                       id="user_<?php echo $u['id']; ?>">
                                                <label class="form-check-label" for="user_<?php echo $u['id']; ?>">
                                                    <?php echo $u['first_name'] . ' ' . $u['last_name']; ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Mark Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
