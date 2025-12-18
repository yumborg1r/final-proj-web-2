<?php
require_once '../includes/Database.php';
require_once '../includes/auth.php';
require_once '../includes/Machine.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$machine = new Machine($db);

$message = '';
$message_type = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $machine->name = sanitizeInput($_POST['name']);
        $machine->description = sanitizeInput($_POST['description']);
        $machine->status = sanitizeInput($_POST['status']);
        $machine->photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload = uploadFile($_FILES['photo'], '../uploads/');
            if ($upload['success']) {
                $machine->photo = $upload['filename'];
            } else {
                $message = $upload['message'];
                $message_type = 'danger';
            }
        }
        if ($message_type !== 'danger' && $machine->createMachine()) {
            $message = 'Machine added successfully!';
            $message_type = 'success';
        } elseif (!$message) {
            $message = 'Failed to add machine.';
            $message_type = 'danger';
        }
    } elseif ($action === 'edit') {
        $machine->id = $_POST['machine_id'];
        $machine->name = sanitizeInput($_POST['name']);
        $machine->description = sanitizeInput($_POST['description']);
        $machine->status = sanitizeInput($_POST['status']);
        $machine->photo = $_POST['existing_photo'] ?? null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload = uploadFile($_FILES['photo'], '../uploads/');
            if ($upload['success']) {
                $machine->photo = $upload['filename'];
            } else {
                $message = $upload['message'];
                $message_type = 'danger';
            }
        }
        if ($message_type !== 'danger' && $machine->updateMachine()) {
            $message = 'Machine updated successfully!';
            $message_type = 'success';
        } elseif (!$message) {
            $message = 'Failed to update machine.';
            $message_type = 'danger';
        }
    }
}

if (isset($_GET['delete'])) {
    $machine->id = $_GET['delete'];
    if ($machine->deleteMachine()) {
        $message = 'Machine deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete machine.';
        $message_type = 'danger';
    }
}

$machines = $machine->getAllMachines();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machines - Admin</title>
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
                    <li class="nav-item"><a class="nav-link" href="subscriptions.php"><i class="fas fa-credit-card"></i> Subscriptions</a></li>
                    <li class="nav-item"><a class="nav-link" href="workout-plans.php"><i class="fas fa-dumbbell"></i> Workout Plans</a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                    <li class="nav-item"><a class="nav-link active" href="machines.php"><i class="fas fa-cogs"></i> Machines</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cogs"></i> Machines</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#machineModal"><i class="fas fa-plus"></i> Add Machine</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">All Machines</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($machines as $m): ?>
                                <tr>
                                    <td><?php echo $m['id']; ?></td>
                                    <td>
                                        <?php if (!empty($m['photo'])): ?>
                                            <img src="../uploads/<?php echo $m['photo']; ?>" class="profile-img-sm" alt="Machine">
                                        <?php else: ?>
                                            <div class="profile-img-sm bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $m['name']; ?></strong></td>
                                    <td><?php echo substr($m['description'] ?? '', 0, 60); ?></td>
                                    <td><?php echo getStatusBadge($m['status']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick='editMachine(<?php echo json_encode($m); ?>)'><i class="fas fa-edit"></i></button>
                                        <a href="machines.php?delete=<?php echo $m["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this machine?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="machineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add/Edit Machine</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="machine_id" id="machine_id">
                        <input type="hidden" name="existing_photo" id="existing_photo">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" name="photo" id="photo" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMachine(m) {
            document.getElementById('action').value = 'edit';
            document.getElementById('machine_id').value = m.id;
            document.getElementById('name').value = m.name;
            document.getElementById('description').value = m.description || '';
            document.getElementById('status').value = m.status;
            document.getElementById('existing_photo').value = m.photo || '';
            new bootstrap.Modal(document.getElementById('machineModal')).show();
        }
    </script>
</body>
</html>

