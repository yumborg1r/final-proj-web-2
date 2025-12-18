<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Machine.php';

requireRole('user');

$database = new Database();
$db = $database->getConnection();
$machine = new Machine($db);

$machines = $machine->getAllMachines();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Machines - GymFit Pro</title>
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
                        <a class="nav-link active" href="machines.php">
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
        <h2 class="mb-4 text-warning"><i class="fas fa-cogs"></i> Gym Equipment Status</h2>

        <div class="row">
            <?php foreach ($machines as $m): ?>
                <div class="col-md-4 mb-4 fade-in">
                    <div class="card h-100 bg-dark text-white border-secondary">
                        <?php if(!empty($m['photo'])): ?>
                            <img src="../uploads/<?php echo $m['photo']; ?>" class="card-img-top" alt="<?php echo $m['name']; ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-secondary text-center py-5" style="height: 200px;">
                                <i class="fas fa-dumbbell fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title text-warning">
                                <?php echo $m['name']; ?>
                            </h5>
                            <p class="card-text text-muted small">
                                <?php echo $m['description']; ?>
                            </p>
                            <div class="mt-3">
                                <?php if ($m['status'] == 'active'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Available</span>
                                <?php elseif ($m['status'] == 'maintenance'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-tools"></i> Maintenance</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
