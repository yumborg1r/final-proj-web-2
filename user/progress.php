<?php
// Always use Philippine time globally
date_default_timezone_set('Asia/Manila');

require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Progress.php';
require_once '../includes/WorkoutPlan.php'; // For attendance stats if needed

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$progress = new Progress($db);
$attendance = new Attendance($db); // Reuse existing class for attendance stats

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_progress'])) {
        $progress->user_id = getUserId();
        $progress->weight = $_POST['weight'];
        $progress->body_fat_percent = !empty($_POST['body_fat']) ? $_POST['body_fat'] : null;
        $progress->notes = $_POST['notes'];
        $progress->recorded_date = $_POST['date'];
        
        // Handle photo upload if needed (simplifying for now, just text/metrics)
        $progress->photo_path = null; 

        if ($progress->addProgress()) {
            $message = 'Progress recorded successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to record progress.';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['delete_progress'])) {
        // Handle deletion
        $id = $_POST['progress_id'];
        if ($progress->deleteProgress($id, getUserId())) {
             $message = 'Record deleted.';
             $message_type = 'success';
        } else {
             $message = 'Failed to delete record.';
             $message_type = 'danger';
        }
    }
}

// Get Data for Charts
// 1. Weight History
$history = $progress->getProgressHistory(getUserId());
$dates = [];
$weights = [];
$body_fats = [];

// Sort history ascending for chart
$chart_history = array_reverse($history);
foreach ($chart_history as $record) {
    $dates[] = date('M d', strtotime($record['recorded_date']));
    $weights[] = $record['weight'];
    if ($record['body_fat_percent']) {
        $body_fats[] = $record['body_fat_percent'];
    }
}

// 2. Attendance/Consistency (Last 7 days vs Previous 7 days? Or just weekly counts)
// Let's get raw attendance and process it for a "Workouts per Week" chart
$raw_attendance = $attendance->getAttendanceByUser(getUserId());
// Group by week
$weekly_attendance = [];
foreach ($raw_attendance as $att) {
    $week_start = date('Y-m-d', strtotime('monday this week', strtotime($att['attendance_date'])));
    if (!isset($weekly_attendance[$week_start])) {
        $weekly_attendance[$week_start] = 0;
    }
    $weekly_attendance[$week_start]++;
}
ksort($weekly_attendance);
// Limit to last 8 weeks for readability
$chart_weeks = array_slice(array_keys($weekly_attendance), -8);
$chart_week_counts = [];
foreach ($chart_weeks as $week) {
    $chart_week_counts[] = $weekly_attendance[$week];
}
// Format week labels
$chart_week_labels = array_map(function($date) {
    return date('M d', strtotime($date));
}, $chart_weeks);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Progress - GymFit Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell"></i> GymFit Pro
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="progress.php">
                            <i class="fas fa-chart-line"></i> Track Progress
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
            <h2><i class="fas fa-chart-line"></i> My Progress</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#logProgressModal">
                <i class="fas fa-plus"></i> Log Today's Stats
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Weight Chart -->
            <div class="col-md-8 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Weight Progression</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($weights)): ?>
                            <div class="text-center py-5">
                                <p class="text-muted">No data recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="weightChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Consistency Chart -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Consistency (Weekly Workouts)</h5>
                    </div>
                    <div class="card-body">
                         <?php if (empty($chart_week_counts)): ?>
                            <div class="text-center py-5">
                                <p class="text-muted">No workouts recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="consistencyChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">History Log</h5>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <p class="text-muted text-center">No logs found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Weight (kg)</th>
                                    <th>Body Fat %</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($row['recorded_date'])); ?></td>
                                        <td><?php echo $row['weight']; ?> kg</td>
                                        <td><?php echo $row['body_fat_percent'] ? $row['body_fat_percent'] . '%' : '-'; ?></td>
                                        <td><?php echo $row['notes']; ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="delete_progress" value="1">
                                                <input type="hidden" name="progress_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

    <!-- Log Progress Modal -->
    <div class="modal fade" id="logProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content text-dark"> <!-- Ensure text is dark for modal -->
                <div class="modal-header">
                    <h5 class="modal-title">Log Progress</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="add_progress" value="1">
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" class="form-control" required placeholder="e.g. 75.5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Body Fat % (Optional)</label>
                            <input type="number" step="0.1" name="body_fat" class="form-control" placeholder="e.g. 18.5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="How do you feel?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize Weight Chart
    <?php if (!empty($weights)): ?>
        const weightCtx = document.getElementById('weightChart').getContext('2d');
        new Chart(weightCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Weight (kg)',
                    data: <?php echo json_encode($weights); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    <?php endif; ?>

    // Initialize Consistency Chart
    <?php if (!empty($chart_week_counts)): ?>
        const consCtx = document.getElementById('consistencyChart').getContext('2d');
        new Chart(consCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_week_labels); ?>,
                datasets: [{
                    label: 'Workouts/Week',
                    data: <?php echo json_encode($chart_week_counts); ?>,
                    backgroundColor: '#198754',
                    borderColor: '#198754',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    <?php endif; ?>
    </script>
</body>
</html>
