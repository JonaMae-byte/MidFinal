<?php
session_start();
include('db.php');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';

if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $message = "Username already exists";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user (default role_id = 2 for staff instead of 1 for admin)
            $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, RoleID) VALUES (?, ?, 2)");
            if ($stmt->execute([$username, $hashed_password])) {
                $message = "Account created successfully! You can now login.";
                // Redirect to login page after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                $message = "Error creating account";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Create an Account</h1>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo (strpos($message, 'successfully') !== false) ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="login-form">
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Choose a password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" name="signup" class="btn-primary">Sign Up</button>
            </form>
            
            <div class="nav-links">
                <p>Already have an account? <a href="index.php">Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>