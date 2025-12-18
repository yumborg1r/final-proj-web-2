<?php
require_once 'includes/Database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error_message = '';
$success_message = '';

if ($_POST) {
    $user->username = sanitizeInput($_POST['username']);
    $user->email = sanitizeInput($_POST['email']);
    $user->password = $_POST['password'];
    $user->first_name = sanitizeInput($_POST['first_name']);
    $user->last_name = sanitizeInput($_POST['last_name']);
    $user->phone = sanitizeInput($_POST['phone']);
    $user->address = sanitizeInput($_POST['address']);
    // Allow selecting role: user or staff (staff requires admin approval)
    $user->role = isset($_POST['role']) && in_array($_POST['role'], ['user','staff']) ? $_POST['role'] : 'user';
    // Staff registrations are pending until admin approval
    $user->status = ($user->role === 'staff') ? 'inactive' : 'active';
    
    // Validate email
    if (!validateEmail($user->email)) {
        $error_message = 'Please enter a valid email address.';
    }
    // Check if username is unique
    elseif (!$user->checkUnique('username', $user->username)) {
        $error_message = 'Username already exists. Please choose a different username.';
    }
    // Check if email is unique
    elseif (!$user->checkUnique('email', $user->email)) {
        $error_message = 'Email already exists. Please use a different email address.';
    }
    // Validate password
    elseif (strlen($user->password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    }
    // Handle profile photo upload
    elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload_result = uploadFile($_FILES['profile_photo']);
        if ($upload_result['success']) {
            $user->profile_photo = $upload_result['filename'];
        } else {
            $error_message = $upload_result['message'];
        }
    }
    
    if (empty($error_message)) {
        if ($user->register()) {
            if ($user->role === 'staff') {
                $success_message = 'Registration submitted! Admin approval required before you can login as staff.';
            } else {
                $success_message = 'Registration successful! Please login with your credentials.';
            }
            // Clear form data
            $_POST = array();
        } else {
            $error_message = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Gym Membership System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card" style="max-width: 600px;">
            <div class="login-header">
                <h2><i class="fas fa-user-plus"></i> Join GymFit Pro</h2>
                <p class="mb-0">Create your account today!</p>
            </div>
            <div class="login-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">
                                <i class="fas fa-user"></i> First Name
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">
                                <i class="fas fa-user"></i> Last Name
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-at"></i> Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">
                            <i class="fas fa-users-cog"></i> Register as
                        </label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo (isset($_POST['role']) && $_POST['role']==='staff')?'':'selected'; ?>>Member</option>
                            <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role']==='staff')?'selected':''; ?>>Staff / Moderator (Admin approval required)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="profile_photo" class="form-label">
                            <i class="fas fa-camera"></i> Profile Photo
                        </label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                               accept="image/*">
                        <small class="text-muted">Optional: Upload a profile photo (JPEG, PNG, GIF - Max 5MB)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </form>
                
                <div class="text-center">
                    <p class="text-muted">Already have an account? 
                        <a href="login.php" class="text-warning">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
