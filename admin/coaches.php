<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Coach.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$coach = new Coach($db);

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $coach->first_name = sanitizeInput($_POST['first_name']);
        $coach->last_name = sanitizeInput($_POST['last_name']);
        $coach->email = sanitizeInput($_POST['email']);
        $coach->phone = sanitizeInput($_POST['phone']);
        $coach->specialization = sanitizeInput($_POST['specialization']);
        $coach->distinction = sanitizeInput($_POST['distinction']);
        $coach->bio = sanitizeInput($_POST['bio']);
        $coach->experience_years = (int)$_POST['experience_years'];
        $coach->certifications = sanitizeInput($_POST['certifications']);
        $coach->status = $_POST['status'];
        
        // Handle photo upload or placeholder
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $upload = uploadFile($_FILES['photo'], '../uploads/coaches/');
            if ($upload['success']) {
                $coach->photo = $upload['filename'];
            } else {
                $message = $upload['message'];
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] === 'create') {
            // Set placeholder for new coaches if no photo uploaded
            $coach->photo = 'https://ui-avatars.com/api/?name=' . urlencode($coach->first_name . '+' . $coach->last_name) . '&background=random&size=200';
        } else {
            // Keep existing photo for updates
            $coach->photo = $_POST['current_photo'];
        }

        if (empty($message)) {
            if ($_POST['action'] === 'create') {
                if ($coach->createCoach()) {
                    $message = 'Coach added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to add coach.';
                    $message_type = 'danger';
                }
            } elseif ($_POST['action'] === 'update') {
                $coach->id = $_POST['coach_id'];
                if ($coach->updateCoach()) {
                    $message = 'Coach updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to update coach.';
                    $message_type = 'danger';
                }
            }
        }
    } elseif (isset($_POST['delete_id'])) {
        $coach->id = $_POST['delete_id'];
        if ($coach->deleteCoach()) {
            $message = 'Coach deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete coach.';
            $message_type = 'danger';
        }
    }
}

$coaches = $coach->getAllCoaches();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coaches - GymFit Pro</title>
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
                        <a class="nav-link active" href="coaches.php">
                            <i class="fas fa-user-tie"></i> Coaches
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-tie"></i> Manage Coaches</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCoachModal">
                <i class="fas fa-plus"></i> Add New Coach
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coaches as $c): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo str_starts_with($c['photo'], 'http') ? $c['photo'] : '../' . $c['photo']; ?>" 
                                             class="rounded-circle" width="50" height="50" alt="Coach Photo">
                                    </td>
                                    <td>
                                        <?php echo $c['first_name'] . ' ' . $c['last_name']; ?><br>
                                        <small class="text-muted"><?php echo $c['email']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo $c['specialization']; ?><br>
                                        <small class="text-muted"><?php echo $c['distinction']; ?></small>
                                    </td>
                                    <td><?php echo $c['experience_years']; ?> Years</td>
                                    <td><?php echo getStatusBadge($c['status']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info text-white me-1" 
                                                onclick="editCoach(<?php echo htmlspecialchars(json_encode($c)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this coach?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Coach Modal -->
    <div class="modal fade" id="addCoachModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Coach</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="coach_id" id="coachId">
                        <input type="hidden" name="current_photo" id="currentPhoto">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="lastName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <input type="text" class="form-control" name="specialization" id="specialization" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Distinction</label>
                                <input type="text" class="form-control" name="distinction" id="distinction" placeholder="e.g. Senior Coach">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" class="form-control" name="experience_years" id="experienceYears" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bio</label>
                                <textarea class="form-control" name="bio" id="bio" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Certifications</label>
                                <input type="text" class="form-control" name="certifications" id="certifications" placeholder="Comma separated">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Photo (Leave empty for auto-generated placeholder)</label>
                                <input type="file" class="form-control" name="photo" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Coach</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCoach(coach) {
            document.getElementById('modalTitle').textContent = 'Edit Coach';
            document.getElementById('formAction').value = 'update';
            document.getElementById('saveBtn').textContent = 'Update Coach';
            
            document.getElementById('coachId').value = coach.id;
            document.getElementById('currentPhoto').value = coach.photo;
            document.getElementById('firstName').value = coach.first_name;
            document.getElementById('lastName').value = coach.last_name;
            document.getElementById('email').value = coach.email;
            document.getElementById('phone').value = coach.phone;
            document.getElementById('specialization').value = coach.specialization;
            document.getElementById('distinction').value = coach.distinction;
            document.getElementById('experienceYears').value = coach.experience_years;
            document.getElementById('status').value = coach.status;
            document.getElementById('bio').value = coach.bio;
            document.getElementById('certifications').value = coach.certifications;
            
            new bootstrap.Modal(document.getElementById('addCoachModal')).show();
        }

        // Reset modal when closed
        document.getElementById('addCoachModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Add New Coach';
            document.getElementById('formAction').value = 'create';
            document.getElementById('saveBtn').textContent = 'Save Coach';
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>
