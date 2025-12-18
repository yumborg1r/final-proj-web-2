<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/WorkoutPlan.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$workoutPlan = new WorkoutPlan($db);

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

        // Handle cover photo upload
        $workoutPlan->cover_photo = null;
        if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
            $upload_result = uploadFile($_FILES['cover_photo'], '../uploads/');
            if ($upload_result['success']) {
                $workoutPlan->cover_photo = $upload_result['filename'];
            } else {
                $message = $upload_result['message'];
                $message_type = 'danger';
            }
        }
        
        if ($message_type !== 'danger' && $workoutPlan->createWorkoutPlan()) {
            $message = 'Workout plan added successfully!';
            $message_type = 'success';
        } elseif (!$message) {
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

        // Preserve existing photo unless new provided
        $workoutPlan->cover_photo = $_POST['existing_cover_photo'] ?? null;
        if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
            $upload_result = uploadFile($_FILES['cover_photo'], '../uploads/');
            if ($upload_result['success']) {
                $workoutPlan->cover_photo = $upload_result['filename'];
            } else {
                $message = $upload_result['message'];
                $message_type = 'danger';
            }
        }
        
        if ($message_type !== 'danger' && $workoutPlan->updateWorkoutPlan()) {
            $message = 'Workout plan updated successfully!';
            $message_type = 'success';
        } elseif (!$message) {
            $message = 'Failed to update workout plan.';
            $message_type = 'danger';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $workoutPlan->id = $_GET['delete'];
    if ($workoutPlan->deleteWorkoutPlan()) {
        $message = 'Workout plan deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete workout plan.';
        $message_type = 'danger';
    }
}

// Get all workout plans
$workout_plans = $workoutPlan->getAllWorkoutPlans();

// Get workout plan for editing
$edit_plan = null;
if (isset($_GET['edit'])) {
    $edit_plan = $workoutPlan->getWorkoutPlanById($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - Admin</title>
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
                        <a class="nav-link" href="subscriptions.php">
                            <i class="fas fa-credit-card"></i> Subscriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="workout-plans.php">
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
            <h2><i class="fas fa-dumbbell"></i> Workout Plans Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#workoutModal">
                <i class="fas fa-plus"></i> Add Workout Plan
            </button>
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
                <h5 class="mb-0">All Workout Plans</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Exercises</th>
                                <th>Duration</th>
                                <th>Difficulty</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workout_plans as $plan): ?>
                                <tr>
                                    <td><?php echo $plan['id']; ?></td>
                                    <td>
                                        <?php if (!empty($plan['cover_photo'])): ?>
                                            <img src="../uploads/<?php echo $plan['cover_photo']; ?>" class="profile-img-sm" alt="Cover">
                                        <?php else: ?>
                                            <div class="profile-img-sm bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $plan['name']; ?></strong></td>
                                    <td><?php echo substr($plan['description'], 0, 50) . '...'; ?></td>
                                    <td><?php echo substr($plan['exercises'], 0, 50) . '...'; ?></td>
                                    <td><?php echo $plan['duration_minutes']; ?> min</td>
                                    <td><?php echo getDifficultyBadge($plan['difficulty_level']); ?></td>
                                    <td><?php echo getStatusBadge($plan['status']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="editWorkoutPlan(<?php echo htmlspecialchars(json_encode($plan)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="workout-plans.php?delete=<?php echo $plan['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this workout plan?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Workout Plan Modal -->
    <div class="modal fade" id="workoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Workout Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="workout_id" id="workout_id">
                        <input type="hidden" name="existing_cover_photo" id="existing_cover_photo">
                        
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
                            <div class="col-md-4 mb-3">
                                <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="difficulty_level" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="difficulty_level" name="difficulty_level" required>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cover_photo" class="form-label">Cover Photo</label>
                                <input type="file" class="form-control" id="cover_photo" name="cover_photo" accept="image/*">
                            </div>
                        </div>

                        <div class="mb-3" id="status_field" style="display: none;">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Workout Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editWorkoutPlan(plan) {
            document.getElementById('action').value = 'edit';
            document.getElementById('workout_id').value = plan.id;
            document.getElementById('name').value = plan.name;
            document.getElementById('description').value = plan.description || '';
            document.getElementById('exercises').value = plan.exercises;
            document.getElementById('duration_minutes').value = plan.duration_minutes;
            document.getElementById('difficulty_level').value = plan.difficulty_level;
            document.getElementById('existing_cover_photo').value = plan.cover_photo || '';
            document.getElementById('status').value = plan.status;
            document.getElementById('status_field').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('workoutModal')).show();
        }
    </script>
</body>
</html>

