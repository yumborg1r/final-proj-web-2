<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';

requireRole('admin');

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
    $userModel->role = $current_user['role'];
    $userModel->status = $current_user['status'];
    $userModel->profile_photo = $current_user['profile_photo'];

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload = uploadFile($_FILES['profile_photo'], '../uploads/');
        if ($upload['success']) {
            $userModel->profile_photo = $upload['filename'];
        } else {
            $message = $upload['message'];
            $message_type = 'danger';
        }
    }

    if ($message_type !== 'danger' && $userModel->updateUser()) {
        $message = 'Profile updated successfully!';
        $message_type = 'success';
        $current_user = $userModel->getUserById(getUserId());
        $_SESSION['first_name'] = $current_user['first_name'];
        $_SESSION['last_name'] = $current_user['last_name'];
        $_SESSION['username'] = $current_user['username'];
        $_SESSION['email'] = $current_user['email'];
    } else if (!$message) {
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
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-dumbbell"></i> GymFit Pro - Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="subscriptions.php"><i class="fas fa-credit-card"></i> Subscriptions</a></li>
                    <li class="nav-item"><a class="nav-link" href="workout-plans.php"><i class="fas fa-dumbbell"></i> Workout Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
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
        <h2 class="mb-4"><i class="fas fa-user"></i> My Profile</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <?php if (!empty($current_user['profile_photo'])): ?>
                            <img src="../uploads/<?php echo $current_user['profile_photo']; ?>" class="profile-img" alt="Profile">
                        <?php else: ?>
                            <div class="profile-img d-flex align-items-center justify-content-center bg-secondary">
                                <i class="fas fa-user fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="mt-3"><?php echo $current_user['first_name'] . ' ' . $current_user['last_name']; ?></h4>
                        <p class="text-muted mb-0"><?php echo ucfirst($current_user['role']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Edit Profile</h5></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($current_user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($current_user['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Profile Photo</label>
                                    <input type="file" class="form-control" name="profile_photo" accept="image/*">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($current_user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>







