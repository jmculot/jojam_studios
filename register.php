<?php
/**
 * JOJAM STUDIOS - User Registration Page
 * Handles new user account creation with validation
 */

require_once 'config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit();
}

// Initialize variables for form handling
$error = '';
$success = '';

// Process registration form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $band_name = trim($_POST['band_name']);
    $contact = trim($_POST['contact_number']);

    // Validation checks
    if (empty($username) || empty($email) || empty($password) || empty($band_name) || empty($contact)) {
        $error = 'All fields are required!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Username already exists!';
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Email already registered!';
                } else {
                    // Hash password for security
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user into database
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, band_name, contact_number) VALUES (?, ?, ?, 'user', ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $band_name, $contact]);
                    
                    $success = 'Registration successful! You can now login.';
                }
            }
        } catch(PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JOJAM STUDIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="jojam-styles.css">
    
    
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-lg-6 col-md-8">
                
                <!-- Registration form card -->
                <div class="glass-card">
                    <h2 class="neon-text text-center mb-4">CREATE ACCOUNT</h2>
                    
                    <!-- Display error messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Display success messages -->
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <a href="login.php" class="alert-link">Click here to login</a>
                        </div>
                    <?php endif; ?>

                    <!-- Registration form -->
                    <form method="POST" action="">
                        
                        <!-- Username field -->
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required 
                                   placeholder="Enter username"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <!-- Email field -->
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required
                                   placeholder="Enter email address"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <!-- Band name field -->
                        <div class="mb-3">
                            <label class="form-label">Band Name</label>
                            <input type="text" name="band_name" class="form-control" required
                                   placeholder="Enter your band name"
                                   value="<?php echo isset($_POST['band_name']) ? htmlspecialchars($_POST['band_name']) : ''; ?>">
                        </div>

                        <!-- Contact number field -->
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" required
                                   placeholder="e.g. 09123456789"
                                   value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                        </div>

                        <!-- Password field -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6"
                                   placeholder="Minimum 6 characters">
                            <small style="color: #888;">Must be at least 6 characters long</small>
                        </div>

                        <!-- Confirm password field -->
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required
                                   placeholder="Re-enter password">
                        </div>

                        <!-- Submit button -->
                        <button type="submit" class="btn btn-neon w-100 mb-3">REGISTER</button>
                        
                        <!-- Link to login page -->
                        <p class="text-center mb-0" style="color: #aaa;">
                            Already have an account? 
                            <a href="login.php" style="color: var(--neon-magenta);">Login here</a>
                        </p>
                        
                        <!-- Link back to home -->
                        <p class="text-center mt-2 mb-0" style="color: #aaa;">
                            <a href="index.php" style="color: var(--neon-blue);">← Back to Home</a>
                        </p>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>