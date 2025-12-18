<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Subscription.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$planModel = new SubscriptionPlan($db);

$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'];
    if ($action === 'add') {
        $planModel->name = sanitizeInput($_POST['name']);
        $planModel->description = sanitizeInput($_POST['description']);
        $planModel->price = $_POST['price'];
        $planModel->duration_days = $_POST['duration_days'];
        $planModel->features = sanitizeInput($_POST['features']);
        $planModel->status = sanitizeInput($_POST['status']);
        if ($planModel->createPlan()) {
            $message = 'Plan created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to create plan.';
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $planModel->id = $_POST['plan_id'];
        $planModel->name = sanitizeInput($_POST['name']);
        $planModel->description = sanitizeInput($_POST['description']);
        $planModel->price = $_POST['price'];
        $planModel->duration_days = $_POST['duration_days'];
        $planModel->features = sanitizeInput($_POST['features']);
        $planModel->status = sanitizeInput($_POST['status']);
        if ($planModel->updatePlan()) {
            $message = 'Plan updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to update plan.';
            $message_type = 'danger';
        }
    }
}

if (isset($_GET['delete'])) {
    $planModel->id = $_GET['delete'];
    if ($planModel->deletePlan()) {
        $message = 'Plan deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete plan.';
        $message_type = 'danger';
    }
}

// Fetch all plans (including inactive for management)
$stmt = $db->prepare("SELECT * FROM subscription_plans ORDER BY created_at DESC");
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$edit_plan = null;
if (isset($_GET['edit'])) {
    $edit_plan = $planModel->getPlanById($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell"></i> GymFit Pro - Admin
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link active" href="subscriptions.php"><i class="fas fa-credit-card"></i> Subscriptions</a></li>
                    <li class="nav-item"><a class="nav-link" href="workout-plans.php"><i class="fas fa-dumbbell"></i> Workout Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cog"></i> Subscription Plans</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#planModal">
                <i class="fas fa-plus"></i> Add Plan
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">All Plans</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td>
                                        <strong><?php echo $p['name']; ?></strong><br>
                                        <small class="text-muted"><?php echo substr($p['description'], 0, 60); ?></small>
                                    </td>
                                    <td><?php echo formatCurrency($p['price']); ?></td>
                                    <td><?php echo $p['duration_days']; ?> days</td>
                                    <td><?php echo getStatusBadge($p['status']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick='editPlan(<?php echo json_encode($p); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a class="btn btn-danger btn-sm" href="subscription-plans.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('Delete this plan?')">
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

    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="plan_id" id="plan_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="duration_days" class="form-label">Duration (days)</label>
                                <input type="number" class="form-control" id="duration_days" name="duration_days" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="features" class="form-label">Features</label>
                            <textarea class="form-control" id="features" name="features" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPlan(plan) {
            document.getElementById('action').value = 'edit';
            document.getElementById('plan_id').value = plan.id;
            document.getElementById('name').value = plan.name;
            document.getElementById('price').value = plan.price;
            document.getElementById('duration_days').value = plan.duration_days;
            document.getElementById('description').value = plan.description || '';
            document.getElementById('features').value = plan.features || '';
            document.getElementById('status').value = plan.status;
            new bootstrap.Modal(document.getElementById('planModal')).show();
        }
    </script>
</body>
</html>


