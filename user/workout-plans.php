<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/WorkoutPlan.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$workoutPlan = new WorkoutPlan($db);

// Get all workout plans
$workout_plans = $workoutPlan->getAllWorkoutPlans();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - User</title>
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
                        <a class="nav-link active" href="workout-plans.php">
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
            <h2><i class="fas fa-dumbbell"></i> Available Workout Plans</h2>
        </div>

        <?php if (empty($workout_plans)): ?>
            <div class="text-center">
                <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Workout Plans Available</h5>
                <p class="text-muted">Check back later for new workout plans!</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($workout_plans as $plan): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-dumbbell"></i> <?php echo $plan['name']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo $plan['description']; ?></p>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Duration:</strong><br>
                                        <span class="text-muted"><?php echo $plan['duration_minutes']; ?> minutes</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Difficulty:</strong><br>
                                        <?php echo getDifficultyBadge($plan['difficulty_level']); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Exercises:</strong>
                                    <ul class="list-unstyled mt-2">
                                        <?php 
                                        $exercises = explode(',', $plan['exercises']);
                                        foreach ($exercises as $exercise): 
                                        ?>
                                            <li><i class="fas fa-check text-success"></i> <?php echo trim($exercise); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-user"></i> Created by: <?php echo $plan['first_name'] . ' ' . $plan['last_name']; ?><br>
                                        <i class="fas fa-calendar"></i> Created: <?php echo formatDate($plan['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
