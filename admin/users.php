<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$message_type = '';

// Handle user actions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $user->username = sanitizeInput($_POST['username']);
        $user->email = sanitizeInput($_POST['email']);
        $user->password = $_POST['password'];
        $user->first_name = sanitizeInput($_POST['first_name']);
        $user->last_name = sanitizeInput($_POST['last_name']);
        $user->phone = sanitizeInput($_POST['phone']);
        $user->address = sanitizeInput($_POST['address']);
        $user->role = sanitizeInput($_POST['role']);
        $user->status = sanitizeInput($_POST['status']);
        
        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $upload_result = uploadFile($_FILES['profile_photo']);
            if ($upload_result['success']) {
                $user->profile_photo = $upload_result['filename'];
            }
        }
        
        if ($user->register()) {
            $message = 'User added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to add user.';
            $message_type = 'danger';
        }
    } elseif ($action == 'edit') {
        $user->id = $_POST['user_id'];
        $user->username = sanitizeInput($_POST['username']);
        $user->email = sanitizeInput($_POST['email']);
        $user->first_name = sanitizeInput($_POST['first_name']);
        $user->last_name = sanitizeInput($_POST['last_name']);
        $user->phone = sanitizeInput($_POST['phone']);
        $user->address = sanitizeInput($_POST['address']);
        $user->role = sanitizeInput($_POST['role']);
        $user->status = sanitizeInput($_POST['status']);
        
        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $upload_result = uploadFile($_FILES['profile_photo']);
            if ($upload_result['success']) {
                $user->profile_photo = $upload_result['filename'];
            }
        }
        
        if ($user->updateUser()) {
            $message = 'User updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update user.';
            $message_type = 'danger';
        }
    }
}

// Handle approve staff/user
if (isset($_GET['approve'])) {
    $approve_id = (int) $_GET['approve'];
    $u = $user->getUserById($approve_id);
    if ($u) {
        $user->id = $approve_id;
        $user->username = $u['username'];
        $user->email = $u['email'];
        $user->first_name = $u['first_name'];
        $user->last_name = $u['last_name'];
        $user->phone = $u['phone'];
        $user->address = $u['address'];
        $user->profile_photo = $u['profile_photo'];
        $user->role = $u['role'];
        $user->status = 'active';
        if ($user->updateUser()) {
            $message = 'User approved and activated!';
            $message_type = 'success';
        } else {
            $message = 'Failed to approve user.';
            $message_type = 'danger';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $user->id = $_GET['delete'];
    if ($user->deleteUser()) {
        $message = 'User deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete user.';
        $message_type = 'danger';
    }
}

// Get all users
$users = $user->getAllUsers();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_user = $user->getUserById($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin</title>
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
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users"></i> Users
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
            <h2><i class="fas fa-users"></i> Users Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="fas fa-plus"></i> Add User
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
                <h5 class="mb-0">All Users</h5>
            </div>
                            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td>
                                        <?php if ($u['profile_photo']): ?>
                                            <img src="../uploads/<?php echo $u['profile_photo']; ?>" 
                                                 class="profile-img-sm" alt="Profile">
                                        <?php else: ?>
                                            <div class="profile-img-sm bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></td>
                                    <td><?php echo $u['username']; ?></td>
                                    <td><?php echo $u['email']; ?></td>
                                    <td><?php echo $u['phone']; ?></td>
                                    <td><?php echo ucfirst($u['role']); ?></td>
                                    <td><?php echo getStatusBadge($u['status']); ?></td>
                                    <td><?php echo formatDate($u['created_at']); ?></td>
                                                    <td>
                                                        <?php if ($u['status'] !== 'active'): ?>
                                                            <a href="users.php?approve=<?php echo $u['id']; ?>" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="users.php?delete=<?php echo $u['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this user?')">
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

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="user_id" id="user_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Leave blank to keep current password (for editing)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="profile_photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('action').value = 'edit';
            document.getElementById('user_id').value = user.id;
            document.getElementById('first_name').value = user.first_name;
            document.getElementById('last_name').value = user.last_name;
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('address').value = user.address || '';
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
            document.getElementById('password').required = false;
            
            new bootstrap.Modal(document.getElementById('userModal')).show();
        }
    </script>
</body>
</html>
