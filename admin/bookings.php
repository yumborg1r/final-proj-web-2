<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Booking.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$booking = new Booking($db);

$message = '';
$message_type = '';

// Handle Actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $booking_id = $_POST['booking_id'];
    
    if ($action === 'confirm') {
        if ($booking->updateStatus($booking_id, 'confirmed')) {
            $message = 'Booking confirmed successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to confirm booking.';
            $message_type = 'danger';
        }
    } elseif ($action === 'cancel') {
        if ($booking->updateStatus($booking_id, 'cancelled')) {
            $message = 'Booking cancelled successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to cancel booking.';
            $message_type = 'danger';
        }
    } elseif ($action === 'delete') {
         // We might want a delete method, but for now let's just mark as cancelled or actually delete if needed.
         // Let's implement actual delete for cleanup
         $query = "DELETE FROM coach_bookings WHERE id = :id";
         $stmt = $db->prepare($query);
         $stmt->bindParam(':id', $booking_id);
         if ($stmt->execute()) {
             $message = 'Booking deleted successfully!';
             $message_type = 'success';
         } else {
             $message = 'Failed to delete booking.';
             $message_type = 'danger';
         }
    }
}

$bookings = $booking->getAllBookings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin</title>
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
                        <a class="nav-link" href="coaches.php">
                            <i class="fas fa-user-tie"></i> Coaches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">
                            <i class="fas fa-calendar-alt"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscriptions.php">
                            <i class="fas fa-credit-card"></i> Subscriptions
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
            <h2><i class="fas fa-calendar-check"></i> Booking Management</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
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
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Coach</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $bk): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo date('M d, Y', strtotime($bk['booking_date'])); ?></div>
                                        <small class="text-muted"><?php echo $bk['time_slot']; ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo $bk['user_first'] . ' ' . $bk['user_last']; ?></div>
                                        <small class="text-muted"><?php echo $bk['user_email']; ?></small>
                                    </td>
                                    <td><?php echo $bk['coach_first'] . ' ' . $bk['coach_last']; ?></td>
                                    <td><?php echo $bk['notes'] ? substr($bk['notes'], 0, 50) . '...' : '-'; ?></td>
                                    <td><?php echo getStatusBadge($bk['status']); ?></td>
                                    <td>
                                        <?php if ($bk['status'] == 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="confirm">
                                                <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                                <button type="submit" class="btn btn-warning btn-sm" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this booking permanently?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
