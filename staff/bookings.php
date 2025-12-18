<?php
date_default_timezone_set('Asia/Manila');
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Booking.php';

requireRole('staff');

$database = new Database();
$db = $database->getConnection();
$booking = new Booking($db);

// Filter bookings
$status = isset($_GET['status']) ? $_GET['status'] : '';
$bookings = $booking->getAllBookings(); // We will filter array for simplicity or add method later

// Simple array filter if getAllBookings returns everything
if ($status) {
    $bookings = array_filter($bookings, function($b) use ($status) {
        return $b['status'] === $status;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings - GymFit Pro</title>
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
                        <a class="nav-link active" href="bookings.php">
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
            <h2><i class="fas fa-calendar-alt"></i> Coach Bookings</h2>
            <div>
                 <a href="bookings.php" class="btn btn-outline-light btn-sm <?php echo $status == '' ? 'active' : ''; ?>">All</a>
                 <a href="bookings.php?status=confirmed" class="btn btn-outline-success btn-sm <?php echo $status == 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                 <a href="bookings.php?status=pending" class="btn btn-outline-warning btn-sm <?php echo $status == 'pending' ? 'active' : ''; ?>">Pending</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Member</th>
                                <th>Coach</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No bookings found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $bk): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($bk['booking_date'])); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $bk['time_slot']; ?></span></td>
                                        <td>
                                            <div class="fw-bold"><?php echo $bk['user_first'] . ' ' . $bk['user_last']; ?></div>
                                            <small class="text-muted"><?php echo $bk['user_email']; ?></small>
                                        </td>
                                        <td><?php echo $bk['coach_first'] . ' ' . $bk['coach_last']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $bk['status'] == 'confirmed' ? 'success' : ($bk['status'] == 'cancelled' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($bk['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
