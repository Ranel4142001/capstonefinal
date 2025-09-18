<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="public/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>POS System Login</h2>
            <p class="text-muted">Please log in to continue.</p>
        </div>
        
        <?php
        session_start();
        if (isset($_SESSION['login_error'])) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            unset($_SESSION['login_error']);
        }
        if (isset($_SESSION['logout_success'])) {
            echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($_SESSION['logout_success']) . '</div>';
            unset($_SESSION['logout_success']);
        }
        ?>

        <form action="process_login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    <span class="input-group-text">
                        <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/login.js"></script>
</body>
</html>