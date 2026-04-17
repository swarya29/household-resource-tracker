<?php
session_start();
include "db.php";

$error = "";
if(isset($_POST['login'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query="SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result=mysqli_query($conn,$query);
    
    if(mysqli_num_rows($result)>0){
        $user_data = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid login credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Smart Resource Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="gravity.css">
</head>
<body class="gravity-theme" style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="glass-panel text-center" style="max-width: 450px; width: 90%; z-index: 10;">
        <div class="gravity-header mb-4">
            <h2 class="gravity-title" style="font-size: 2.5rem;">Welcome Back</h2>
            <p class="gravity-subtitle" style="font-size: 0.9rem;">Sign in to Gravity Dashboard</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger" style="background: rgba(220,53,69,0.2); border-color: rgba(220,53,69,0.5); color: #ffb3b3;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success" style="background: rgba(25,135,84,0.2); border-color: rgba(25,135,84,0.5); color: #a3ffc2;">Account created! Please login.</div>
        <?php endif; ?>

        <?php if(isset($_GET['reset_success'])): ?>
            <div class="alert alert-success" style="background: rgba(25,135,84,0.2); border-color: rgba(25,135,84,0.5); color: #a3ffc2;">Password updated! You can now login.</div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label" style="color: var(--text-muted);">Username</label>
                <input class="form-control" name="username" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
            </div>
            
            <div class="mb-4 text-start">
                <label class="form-label" style="color: var(--text-muted);">Password</label>
                <input class="form-control" type="password" name="password" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
            </div>

            <button class="btn w-100 mb-2" name="login" style="background: linear-gradient(to right, var(--accent-1), var(--accent-2)); border: none; font-weight: 600; color: #fff; padding: 0.75rem; transition: transform 0.2s;">Login to Dashboard</button>
            
            <div class="mb-4 text-center">
                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" style="color: var(--accent-1); text-decoration: none; font-size: 0.9rem;">Forgot your password?</a>
            </div>
            
            <a href="register.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.95rem; display: block;">Don't have an account? <span style="color: var(--accent-1);">Register Here</span></a>
        </form>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(0, 240, 255, 0.2); color: #fff;">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" style="color: #00f0ff;">Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-4">Enter your registered email address and we'll send you a link to reset your password.</p>
                    <form id="forgotForm">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="resetEmail" required placeholder="name@example.com" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                        </div>
                        <div id="resetStatus"></div>
                        <button type="submit" class="btn btn-warning w-100 text-dark" id="resetBtn" style="font-weight: 600;">Send Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('resetEmail').value;
            const statusDiv = document.getElementById('resetStatus');
            const btn = document.getElementById('resetBtn');

            btn.disabled = true;
            btn.innerText = 'Sending...';
            statusDiv.innerHTML = '';

            try {
                const res = await fetch('api/request_reset.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const result = await res.json();
                
                if (result.status === 'success') {
                    statusDiv.innerHTML = `<div class="alert alert-success mt-3" style="font-size: 0.85rem; background: rgba(25,135,84,0.1); color: #afffd5; border-color: rgba(25,135,84,0.3);">${result.message}</div>`;
                    btn.style.display = 'none';
                } else {
                    statusDiv.innerHTML = `<div class="alert alert-danger mt-3" style="font-size: 0.85rem; background: rgba(220,53,69,0.1); color: #ffb3b3; border-color: rgba(220,53,69,0.3);">${result.message}</div>`;
                    btn.disabled = false;
                    btn.innerText = 'Send Reset Link';
                }
            } catch (err) {
                statusDiv.innerHTML = '<div class="alert alert-danger mt-3">Network error. Please try again.</div>';
                btn.disabled = false;
                btn.innerText = 'Send Reset Link';
            }
        });
    </script>
</body>
</html>
