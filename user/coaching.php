<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Subscription.php';
require_once '../includes/Coach.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);
$coach = new Coach($db);

// Check if user has active VIP plan subscription
$active_subscription = $subscription->getActiveUserSubscription(getUserId());
$has_vip_access = false;

if ($active_subscription && strtolower($active_subscription['plan_name']) === 'vip plan') {
    $has_vip_access = true;
}

// Get all coaches
$coaches = $coach->getAllCoaches();
$specializations = $coach->getSpecializations();

// Filter by specialization if selected
$selected_specialization = isset($_GET['specialization']) ? sanitizeInput($_GET['specialization']) : '';

if (!empty($selected_specialization)) {
    $coaches = $coach->getCoachesBySpecialization($selected_specialization);
}

// Get coach details if viewing single coach
$coach_detail = null;
if (isset($_GET['coach_id'])) {
    $coach_id = (int) $_GET['coach_id'];
    $coach_detail = $coach->getCoachById($coach_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Coaching - VIP Members</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .coach-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .coach-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .coach-photo {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto;
            display: block;
        }
        .distinction-badge {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }
        .specialization-tag {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #495057;
        }
    </style>
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
                    <?php if ($has_vip_access): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="coaching.php">
                            <i class="fas fa-user-tie"></i> Personal Coaching
                        </a>
                    </li>
                    <?php endif; ?>
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
        <?php if (!$has_vip_access): ?>
            <!-- VIP Access Required Message -->
            <div class="alert alert-warning">
                <h4><i class="fas fa-crown"></i> VIP Plan Required</h4>
                <p>Personal coaching is exclusively available for VIP Plan subscribers. Upgrade to VIP Plan to access our expert personal trainers and coaches.</p>
                <a href="subscriptions.php" class="btn btn-primary">
                    <i class="fas fa-arrow-up"></i> Upgrade to VIP Plan
                </a>
            </div>
        <?php else: ?>
            <!-- VIP Access Granted -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-user-tie"></i> Personal Coaching</h2>
                    <p class="text-muted mb-0">Choose from our expert coaches, each with their own specialization</p>
                </div>
                <div>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-crown"></i> VIP Member
                    </span>
                </div>
            </div>

            <?php if ($coach_detail): ?>
                <!-- Coach Detail View -->
                <div class="row mb-4">
                    <div class="col-12">
                        <a href="coaching.php" class="btn btn-secondary mb-3">
                            <i class="fas fa-arrow-left"></i> Back to Coaches
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if (!empty($coach_detail['photo'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($coach_detail['photo']); ?>" 
                                         class="coach-photo mb-3" alt="Coach Photo">
                                <?php else: ?>
                                    <div class="coach-photo d-flex align-items-center justify-content-center bg-secondary mb-3 mx-auto">
                                        <i class="fas fa-user-tie fa-4x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($coach_detail['first_name'] . ' ' . $coach_detail['last_name']); ?></h3>
                                <div class="distinction-badge">
                                    <?php echo htmlspecialchars($coach_detail['distinction']); ?>
                                </div>
                                <p class="mt-3 mb-0">
                                    <span class="specialization-tag">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($coach_detail['specialization']); ?>
                                    </span>
                                </p>
                                <p class="text-muted mt-2">
                                    <i class="fas fa-calendar-alt"></i> <?php echo $coach_detail['experience_years']; ?> Years Experience
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> About</h5>
                            </div>
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($coach_detail['bio'])); ?></p>
                                
                                <?php if (!empty($coach_detail['certifications'])): ?>
                                    <hr>
                                    <h6><i class="fas fa-certificate"></i> Certifications</h6>
                                    <p><?php echo nl2br(htmlspecialchars($coach_detail['certifications'])); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($coach_detail['email']) || !empty($coach_detail['phone'])): ?>
                                    <hr>
                                    <h6><i class="fas fa-envelope"></i> Contact Information</h6>
                                    <?php if (!empty($coach_detail['email'])): ?>
                                        <p class="mb-1">
                                            <i class="fas fa-envelope"></i> 
                                            <a href="mailto:<?php echo htmlspecialchars($coach_detail['email']); ?>">
                                                <?php echo htmlspecialchars($coach_detail['email']); ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($coach_detail['phone'])): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-phone"></i> 
                                            <?php echo htmlspecialchars($coach_detail['phone']); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Coaches List View -->
                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="coaching.php" class="row g-3">
                            <div class="col-md-10">
                                <label for="specialization" class="form-label">Filter by Specialization</label>
                                <select class="form-select" id="specialization" name="specialization">
                                    <option value="">All Specializations</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo htmlspecialchars($spec); ?>" 
                                                <?php echo $selected_specialization === $spec ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($spec); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Coaches Grid -->
                <?php if (empty($coaches)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No coaches found for the selected specialization.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($coaches as $coach_item): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card coach-card">
                                    <div class="card-body text-center">
                                        <?php if (!empty($coach_item['photo'])): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($coach_item['photo']); ?>" 
                                                 class="coach-photo mb-3" alt="Coach Photo">
                                        <?php else: ?>
                                            <div class="coach-photo d-flex align-items-center justify-content-center bg-secondary mb-3 mx-auto">
                                                <i class="fas fa-user-tie fa-3x text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h5><?php echo htmlspecialchars($coach_item['first_name'] . ' ' . $coach_item['last_name']); ?></h5>
                                        
                                        <div class="distinction-badge">
                                            <?php echo htmlspecialchars($coach_item['distinction']); ?>
                                        </div>
                                        
                                        <p class="mt-2 mb-2">
                                            <span class="specialization-tag">
                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($coach_item['specialization']); ?>
                                            </span>
                                        </p>
                                        
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-calendar-alt"></i> <?php echo $coach_item['experience_years']; ?> Years Experience
                                        </p>
                                        
                                        <p class="text-muted small mb-3" style="min-height: 60px;">
                                            <?php echo htmlspecialchars(substr($coach_item['bio'], 0, 100)); ?>
                                            <?php echo strlen($coach_item['bio']) > 100 ? '...' : ''; ?>
                                        </p>
                                        
                                        <a href="coaching.php?coach_id=<?php echo $coach_item['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


