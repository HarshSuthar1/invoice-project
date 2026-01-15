<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <title>Invoice System - Register</title>
</head>

<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Fill in your details to register</p>
        </div>

        <div class="error-message" id="errorMessage">
            Registration failed. Please try again.
        </div>

        <form id="registerForm" method="post" action="/Business%20project/api/auth/register_user.php">
            <div class="form-grid">
                <div class="input-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" required>
                </div>

                <div class="input-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" required>
                </div>
            </div>

            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="input-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>

            <button type="submit" class="register-button">Create Account</button>

            <div class="login-link">
                Already have an account? <a href="/Business%20project/public/index.php?page=login">Sign In</a>
            </div> 
        </form>
    </div>

    <script type="module" src="../assets/js/main.js?v=20251224"></script>
    <script type="module" src="../assets/js/pages/signin.js?v=20251224"></script>
</body>

</html> 