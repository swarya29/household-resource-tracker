<?php
session_start();
include "db.php";

$token = $_GET['token'] ?? '';
$error = "";
$success = false;

if (empty($token)) {
    header("Location: login.php");
    exit();
}

// Check if token is valid and not expired
$stmt = $conn->prepare("SELECT id, username FROM users WHERE auth_token = ? AND token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $error = "Invalid or expired reset link. Please request a new one.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | EcoTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="gravity.css">
</head>
<body class="gravity-theme" style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="glass-panel text-center" style="max-width: 450px; width: 90%; z-index: 10;">
        <div class="gravity-header mb-4">
            <h2 class="gravity-title" style="font-size: 2rem;">Secure Reset</h2>
            <p class="gravity-subtitle" style="font-size: 0.9rem;">Resetting password for <b><?php echo htmlspecialchars($user['username'] ?? ''); ?></b></p>
        </div>

        <div id="statusAlert"></div>

        <?php if($error): ?>
            <div class="alert alert-danger" style="background: rgba(220,53,69,0.2); border-color: rgba(220,53,69,0.5); color: #ffb3b3;"><?php echo $error; ?></div>
            <a href="login.php" class="btn btn-outline-light w-100">Back to Login</a>
        <?php else: ?>
            <form id="resetForm">
                <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3 text-start">
                    <label class="form-label" style="color: var(--text-muted);">New Password</label>
                    <input class="form-control" type="password" id="newPassword" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
                </div>

                <div class="mb-4 text-start">
                    <label class="form-label" style="color: var(--text-muted);">Confirm New Password</label>
                    <input class="form-control" type="password" id="confirmPassword" required style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
                </div>

                <button type="submit" class="btn w-100 mb-2" id="submitBtn" style="background: linear-gradient(to right, var(--accent-1), var(--accent-2)); border: none; font-weight: 600; color: #fff; padding: 0.75rem;">Update Password</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('resetForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = document.getElementById('resetToken').value;
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const statusDiv = document.getElementById('statusAlert');
            const btn = document.getElementById('submitBtn');

            if (password !== confirm) {
                statusDiv.innerHTML = '<div class="alert alert-warning" style="background: rgba(255,193,7,0.15); color: #ffda6a; border-color: rgba(255,193,7,0.3);">Passwords do not match.</div>';
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Updating...';

            try {
                const res = await fetch('api/reset_password_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, password })
                });
                const result = await res.json();
                
                if (result.status === 'success') {
                    statusDiv.innerHTML = `<div class="alert alert-success" style="background: rgba(25,135,84,0.2); color: #a3ffc2; border-color: rgba(25,135,84,0.3);">${result.message} Redirecting to login...</div>`;
                    setTimeout(() => window.location.href = 'login.php?reset_success=1', 2000);
                } else {
                    statusDiv.innerHTML = `<div class="alert alert-danger" style="background: rgba(220,53,69,0.2); color: #ffb3b3; border-color: rgba(220,53,69,0.3);">${result.message}</div>`;
                    btn.disabled = false;
                    btn.innerText = 'Update Password';
                }
            } catch (err) {
                statusDiv.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                btn.disabled = false;
                btn.innerText = 'Update Password';
            }
        });
    </script>
</body>
</html>
