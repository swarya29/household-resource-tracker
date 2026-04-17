<?php
session_start();
include "db.php";

$error = "";
if(isset($_POST['register'])){
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if user or email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $error = "Username or Email already exists";
    } else {
        $stmt = $conn->prepare("INSERT INTO users(username, email, password) VALUES(?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        if($stmt->execute()) {
            header("Location: login.php?success=1");
            exit();
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Smart Resource Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="gravity.css">
</head>
<body class="gravity-theme" style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="glass-panel text-center" style="max-width: 450px; width: 90%; z-index: 10;">
        <div class="gravity-header mb-4">
            <h2 class="gravity-title" style="font-size: 2.5rem;">Join Gravity</h2>
            <p class="gravity-subtitle" style="font-size: 0.9rem;">Create a new account</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger" style="background: rgba(220,53,69,0.2); border-color: rgba(220,53,69,0.5); color: #ffb3b3;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label" style="color: var(--text-muted);">Username</label>
                <input class="form-control" name="username" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
            </div>
            
            <div class="mb-3 text-start">
                <label class="form-label" style="color: var(--text-muted);">Email Address</label>
                <input class="form-control" type="email" name="email" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
            </div>
            
            <div class="mb-4 text-start">
                <label class="form-label" style="color: var(--text-muted);">Password</label>
                <input class="form-control" type="password" name="password" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
            </div>

            <button class="btn w-100 mb-4" name="register" style="background: linear-gradient(to right, var(--accent-1), var(--accent-2)); border: none; font-weight: 600; color: #fff; padding: 0.75rem; transition: transform 0.2s;">Create Account</button>
            
            <a href="login.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.95rem; display: block;">Already have an account? <span style="color: var(--accent-1);">Log In</span></a>
        </form>
    </div>

</body>
</html>
