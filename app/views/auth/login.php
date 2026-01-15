<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/app.css">
  <title>Invoice System - Login</title>
</head>

<body>
  <div class="login-container">
    <div class="login-header">
      <h1>Welcome Back</h1>
      <p>Please sign in to continue</p>
    </div>

    <div class="error-message" id="errorMessage">
      Invalid username or password
    </div>

    <div class="success-message" id="successMessage" style="display:none;">
      
    </div>

      <form id="loginForm" method="post" action="/Business%20project/api/auth/login_user.php">
      <div class="input-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <div class="remember-me">
        <input type="checkbox" id="remember" name="remember">
        <label for="remember">Remember me</label>
      </div>

      <button type="submit" class="login-button">Log In</button>

      <div class="forgot-password">
        <a href="#">Forgot your password?</a>
      </div>

      <div class="register-link">
        Don't have an account? <a href="/Business%20project/public/index.php?page=signup">Register Now</a>
      </div>
    </form>
  </div>

  <script type="module" src="../assets/js/main.js?v=20251224"></script>
  <script type="module" src="../assets/js/pages/login.js?v=20251224"></script>
</body>

</html>