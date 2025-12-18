<?php
date_default_timezone_set('Asia/Manila');
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Coach.php';
require_once '../includes/Booking.php';
require_once '../includes/Subscription.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$coach = new Coach($db);
$booking = new Booking($db);
$subscription = new Subscription($db);

// Check for VIP or Premium Plan
$is_eligible = false;
$active_sub = $subscription->getActiveUserSubscription(getUserId());
if ($active_sub) {
    $plan_name = strtolower($active_sub['plan_name']);
    if ($plan_name === 'vip plan' || $plan_name === 'premium plan') {
        $is_eligible = true;
    }
}

$message = '';
$message_type = '';

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    if (!$is_eligible) {
        $message = 'You must have a Premium or VIP Plan to book a coach.';
        $message_type = 'danger';
    } else {
        try {
            $booking->user_id = getUserId();
            $booking->coach_id = $_POST['coach_id'];
            $booking->booking_date = $_POST['booking_date'];
            $booking->time_slot = $_POST['time_slot'];
            $booking->notes = sanitizeInput($_POST['notes']);
            
            if ($booking->create()) {
                $message = 'Booking request sent successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to create booking.';
                $message_type = 'danger';
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}

$coaches = $coach->getAllCoaches();

// Generate next 7 days for date selection
$dates = [];
for ($i = 1; $i <= 7; $i++) {
    $dates[] = date('Y-m-d', strtotime("+$i days"));
}

// Static Time Slots
$time_slots = [
    '07:00 AM - 08:00 AM',
    '08:00 AM - 09:00 AM',
    '09:00 AM - 10:00 AM',
    '10:00 AM - 11:00 AM',
    '01:00 PM - 02:00 PM',
    '02:00 PM - 03:00 PM',
    '03:00 PM - 04:00 PM',
    '04:00 PM - 05:00 PM',
    '05:00 PM - 06:00 PM',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Coach - GymFit Pro</title>
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
                        <a class="nav-link" href="workout-plans.php">
                            <i class="fas fa-clipboard-list"></i> My Workouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">
                            <i class="fas fa-chart-line"></i> Track Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="coaches.php">
                            <i class="fas fa-user-friends"></i> Coaches
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
        <h2 class="mb-4 text-warning"><i class="fas fa-user-friends"></i> Our Expert Coaches</h2>
        
        <?php if (!$is_eligible): ?>
            <div class="alert alert-info shadow-sm fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-crown fa-3x text-warning me-3"></i>
                    <div>
                        <h4 class="alert-heading mb-1">Exclusive Feature!</h4>
                        <p class="mb-0">Personal coaching is exclusively available for <strong>Premium</strong> and <strong>VIP</strong> members. Upgrade your subscription today to book sessions with our expert coaches!</p>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end">
                    <a href="subscriptions.php" class="btn btn-warning fw-bold"><i class="fas fa-arrow-up"></i> Upgrade Plan</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row fade-in">
            <?php foreach ($coaches as $c): ?>
                <?php if ($c['status'] == 'active'): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 coach-card" style="border: none; background: #2c2c2c;">
                        <div style="height: 250px; overflow: hidden; position: relative;">
                            <?php if ($c['photo']): ?>
                                <img src="../uploads/coaches/<?php echo $c['photo']; ?>" class="card-img-top" alt="<?php echo $c['first_name']; ?>" style="object-fit: cover; height: 100%; width: 100%;">
                            <?php else: ?>
                                <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                    <i class="fas fa-user-tie fa-4x text-light"></i>
                                </div>
                            <?php endif; ?>
                            <div class="position-absolute bottom-0 start-0 w-100 p-2" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                                <h5 class="card-title text-white mb-0"><?php echo $c['first_name'] . ' ' . $c['last_name']; ?></h5>
                                <small class="text-warning"><?php echo $c['specialization']; ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted small"><?php echo substr($c['bio'], 0, 100); ?>...</p>
                            <p class="mb-2"><i class="fas fa-award text-warning"></i> <small class="text-light"><?php echo $c['distinction']; ?></small></p>
                            <button class="btn btn-warning w-100 mt-2" 
                                <?php echo $is_eligible ? 'data-bs-toggle="modal" data-bs-target="#bookModal'.$c['id'].'"' : 'disabled'; ?>>
                                <i class="fas fa-calendar-plus"></i> <?php echo $is_eligible ? 'Book Session' : 'Premium/VIP Only'; ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Booking Modal -->
                <div class="modal fade" id="bookModal<?php echo $c['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content bg-dark text-light border-warning">
                            <div class="modal-header border-secondary">
                                <h5 class="modal-title text-warning">Book Session with <?php echo $c['first_name']; ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="book">
                                    <input type="hidden" name="coach_id" value="<?php echo $c['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Select Date</label>
                                        <select class="form-select bg-dark text-light border-secondary" name="booking_date" required>
                                            <option value="">Choose a date...</option>
                                            <?php foreach ($dates as $date): ?>
                                                <option value="<?php echo $date; ?>"><?php echo date('D, M d', strtotime($date)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Select Time Slot</label>
                                        <select class="form-select bg-dark text-light border-secondary" name="time_slot" required>
                                            <option value="">Choose a time...</option>
                                            <?php foreach ($time_slots as $slot): ?>
                                                <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notes for Coach</label>
                                        <textarea class="form-control bg-dark text-light border-secondary" name="notes" rows="3" placeholder="What do you want to focus on?"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-secondary">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning">Confirm Booking</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
