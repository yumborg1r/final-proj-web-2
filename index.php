<?php
require_once 'includes/Database.php';
require_once 'includes/Coach.php';

$database = new Database();
$db = $database->getConnection();
$coach = new Coach($db);
$coaches = $coach->getAllCoaches();
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $name = strip_tags($_POST['name']);
    $email = strip_tags($_POST['email']);
    $subject = strip_tags($_POST['subject']);
    $msg = strip_tags($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($msg)) {
        $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $msg);

        if ($stmt->execute()) {
            $message = "Thank you! Your message has been sent.";
            $message_type = "success";
        } else {
            $message = "Error sending message. Please try again.";
            $message_type = "danger";
        }
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymFit Pro - Gym Membership System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-dumbbell"></i> GymFit Pro
            </a>
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-warning mb-4">
                        Transform Your Body with <span class="text-primary">GymFit Pro</span>
                    </h1>
                    <p class="lead text-muted mb-4">
                        Join our premium gym membership system and take control of your fitness journey. 
                        Track your workouts, manage subscriptions, and achieve your goals with our comprehensive platform.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-info-circle"></i> Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-dumbbell fa-10x text-primary glow"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-warning">Why Choose GymFit Pro?</h2>
                <p class="lead text-muted">Comprehensive features for members, staff, and administrators</p>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">For Members</h5>
                            <p class="card-text">
                                Subscribe to plans, track attendance, view workout plans, 
                                and manage your fitness journey with ease.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-user-tie fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">For Staff</h5>
                            <p class="card-text">
                                Manage workout plans, track member attendance, 
                                and provide personalized fitness guidance.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-crown fa-3x text-success mb-3"></i>
                            <h5 class="card-title">For Administrators</h5>
                            <p class="card-text">
                                Approve subscriptions, manage users, oversee operations, 
                                and maintain complete system control.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Coaches Section -->
    <section id="coaches" class="py-5">
        <div class="container">
             <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-warning">Meet Our Experts</h2>
                <p class="lead text-muted">Guided by the best in the industry</p>
            </div>

            <div class="row">
                <?php foreach (array_slice($coaches, 0, 3) as $c): ?>
                    <?php if ($c['status'] == 'active'): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div style="height: 300px; overflow: hidden; background: #333;">
                                    <?php if ($c['photo']): ?>
                                        <img src="uploads/coaches/<?php echo $c['photo']; ?>" class="card-img-top" alt="<?php echo $c['first_name']; ?>" style="object-fit: cover; height: 100%; width: 100%;">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 text-white icon-placeholder">
                                            <i class="fas fa-user-tie fa-5x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body text-center">
                                    <h5 class="card-title fw-bold text-white"><?php echo $c['first_name'] . ' ' . $c['last_name']; ?></h5>
                                    <p class="text-warning mb-2"><?php echo $c['specialization']; ?></p>
                                    <p class="card-text text-white-50 small"><?php echo substr($c['bio'], 0, 80); ?>...</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
             <div class="text-center mt-4">
                <a href="register.php" class="btn btn-outline-primary btn-lg rounded-pill">Train with Them</a>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-warning">Choose Your Plan</h2>
                <p class="lead text-muted">Flexible pricing options to fit your fitness goals</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header text-center">
                            <h4 class="text-primary">Basic Plan</h4>
                            <h2 class="text-warning">₱599</h2>
                            <p class="text-muted">per month</p>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Gym access</li>
                                <li><i class="fas fa-check text-success"></i> Basic equipment</li>
                                <li><i class="fas fa-check text-success"></i> Locker room access</li>
                                <li><i class="fas fa-check text-success"></i> Member support</li>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="register.php" class="btn btn-outline-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-warning">
                        <div class="card-header text-center bg-warning text-dark">
                            <h4 class="text-dark">Premium Plan</h4>
                            <h2 class="text-dark">₱999</h2>
                            <p class="text-dark">per month</p>
                            <span class="badge bg-dark">Most Popular</span>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Everything in Basic</li>
                                <li><i class="fas fa-check text-success"></i> All equipment access</li>
                                <li><i class="fas fa-check text-success"></i> Group classes</li>
                                <li><i class="fas fa-check text-success"></i> Personal training sessions</li>
                                <li><i class="fas fa-check text-success"></i> Nutrition consultation</li>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="register.php" class="btn btn-warning w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header text-center">
                            <h4 class="text-primary">VIP Plan</h4>
                            <h2 class="text-warning">₱1999</h2>
                            <p class="text-muted">per month</p>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Everything in Premium</li>
                                <li><i class="fas fa-check text-success"></i> Dedicated personal trainer</li>
                                <li><i class="fas fa-check text-success"></i> Custom meal plans</li>
                                <li><i class="fas fa-check text-success"></i> Priority booking</li>
                                <li><i class="fas fa-check text-success"></i> 24/7 gym access</li>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="register.php" class="btn btn-outline-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-warning">Get In Touch</h2>
                <p class="lead text-muted">Have questions? We're here to help!</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-4"><i class="fas fa-envelope text-primary"></i> Send us a Message</h5>
                            <form method="POST" action="index.php#contact">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" name="subject">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" name="message" rows="4" required></textarea>
                                </div>
                                <button type="submit" name="send_message" class="btn btn-primary w-100">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-map-marker-alt text-primary"></i> Address</h5>
                            <p class="card-text">123 Fitness Street<br>Paraiso, HC 12345</p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-phone text-primary"></i> Contact Info</h5>
                            <p class="card-text">
                                <strong>Phone:</strong> (+63) 123-4567<br>
                                <strong>Email:</strong> info@gymfitpro.com<br>
                                <strong>Hours:</strong> Mon-Sun, 6:00 AM - 10:00 PM
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 text-center">
        <div class="container">
            <p class="text-muted mb-0">&copy; 2024 GymFit Pro. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
