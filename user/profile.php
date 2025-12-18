<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$message = '';
$message_type = '';

$current_user = $userModel->getUserById(getUserId());

if ($_POST) {
    $userModel->id = getUserId();
    $userModel->username = sanitizeInput($_POST['username']);
    $userModel->email = sanitizeInput($_POST['email']);
    $userModel->first_name = sanitizeInput($_POST['first_name']);
    $userModel->last_name = sanitizeInput($_POST['last_name']);
    $userModel->phone = sanitizeInput($_POST['phone']);
    $userModel->address = sanitizeInput($_POST['address']);
    $userModel->role = $current_user['role']; // Don't allow role changes
    $userModel->status = $current_user['status']; // Don't allow status changes
    $userModel->profile_photo = $current_user['profile_photo']; // Preserve existing photo

    // Validate email
    if (!validateEmail($userModel->email)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    }
    // Check if username is unique (excluding current user)
    elseif (!$userModel->checkUnique('username', $userModel->username, getUserId())) {
        $message = 'Username already exists. Please choose a different username.';
        $message_type = 'danger';
    }
    // Check if email is unique (excluding current user)
    elseif (!$userModel->checkUnique('email', $userModel->email, getUserId())) {
        $message = 'Email already exists. Please use a different email address.';
        $message_type = 'danger';
    }
    // Handle profile photo upload
    elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload = uploadFile($_FILES['profile_photo'], '../uploads/');
        if ($upload['success']) {
            $userModel->profile_photo = $upload['filename'];
        } else {
            $message = $upload['message'];
            $message_type = 'danger';
        }
    }
    
    // Handle password update if provided
    if (empty($message) && !empty($_POST['password'])) {
        if (strlen($_POST['password']) < 6) {
            $message = 'Password must be at least 6 characters long.';
            $message_type = 'danger';
        } else {
            // Update password
            $password_query = "UPDATE users SET password = :password WHERE id = :id";
            $password_stmt = $db->prepare($password_query);
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_stmt->bindParam(':password', $hashed_password);
            $password_stmt->bindParam(':id', $userModel->id, PDO::PARAM_INT);
            
            if (!$password_stmt->execute()) {
                $message = 'Failed to update password.';
                $message_type = 'danger';
            }
        }
    }

    // Update user profile if no errors
    if (empty($message) && $userModel->updateUser()) {
        $message = 'Profile updated successfully!';
        $message_type = 'success';
        $current_user = $userModel->getUserById(getUserId());
        
        // Update session data
        $_SESSION['first_name'] = $current_user['first_name'];
        $_SESSION['last_name'] = $current_user['last_name'];
        $_SESSION['username'] = $current_user['username'];
        $_SESSION['email'] = $current_user['email'];
    } else if (empty($message)) {
        $message = 'Failed to update profile.';
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GymFit Pro</title>
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
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
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
            <h2><i class="fas fa-user"></i> My Profile</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Photo Section -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (!empty($current_user['profile_photo'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($current_user['profile_photo']); ?>" 
                                 class="profile-img mb-3" alt="Profile Photo">
                        <?php else: ?>
                            <div class="profile-img d-flex align-items-center justify-content-center bg-secondary mb-3 mx-auto">
                                <i class="fas fa-user fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mt-3"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($current_user['username']); ?></p>
                        <p class="text-muted mb-0">
                            <span class="badge bg-primary"><?php echo ucfirst($current_user['role']); ?></span>
                            <span class="badge bg-<?php echo $current_user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($current_user['status']); ?>
                            </span>
                        </p>
                        <hr>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-calendar"></i> Member since<br>
                            <?php echo formatDate($current_user['created_at']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($current_user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($current_user['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="profile_photo" class="form-label">Profile Photo</label>
                                    <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                                    <small class="text-muted">Max size: 5MB. Allowed: JPEG, PNG, GIF</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($current_user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Change Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Leave blank to keep current password">
                                <small class="text-muted">Minimum 6 characters. Leave blank if you don't want to change it.</small>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Account Status:</strong><br>
                                <?php echo getStatusBadge($current_user['status']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Role:</strong><br>
                                <span class="badge bg-primary"><?php echo ucfirst($current_user['role']); ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Member Since:</strong><br>
                                <?php echo formatDate($current_user['created_at']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Last Updated:</strong><br>
                                <?php echo formatDate($current_user['updated_at']); ?>
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

