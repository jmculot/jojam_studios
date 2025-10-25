<?php
/**
 * JOJAM STUDIOS - Login Page (Fixed for MySQLi)
 * Handles user authentication and redirects based on role
 */

require_once 'config.php'; // Includes database + session + helper functions

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit();
}

// Initialize error variable
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password!';
    } else {
        // Prepare secure query (MySQLi)
        $stmt = $conn->prepare("SELECT id, username, password, role, band_name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Save session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['band_name'] = $user['band_name'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: user_dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid username or password!';
            }
        } else {
            $error = 'User not found!';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JOJAM STUDIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="jojam-styles.css">

    
</head>
<body>
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="glass-card">
                    <h2 class="neon-text text-center mb-4">ACCESS SYSTEM</h2>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-neon w-100 mb-3">LOGIN</button>

                        <div class="text-center">
                            <p style="color: #aaa;">Don't have an account?
                                <a href="register.php" style="color: var(--neon-magenta);">Register here</a>
                            </p>
                            <p style="color: #aaa;">
                                <a href="index.php" style="color: var(--neon-blue);">← Back to Home</a>
                            </p>
                        </div>
                    </form>

                    <div class="mt-4 p-3" style="background: rgba(0, 243, 255, 0.1); border-radius: 10px; border: 1px solid var(--neon-blue);">
                        <p class="mb-2" style="color: var(--neon-yellow); font-size: 0.9rem;">
                            <strong>Test Credentials:</strong>
                        </p>
                        <p class="mb-1" style="color: #aaa; font-size: 0.85rem;">
                            Admin: <strong>admin</strong> / password
                        </p>
                        <p class="mb-0" style="color: #aaa; font-size: 0.85rem;">
                            User: <strong>testuser</strong> / password
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
