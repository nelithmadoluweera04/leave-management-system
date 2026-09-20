<?php
session_start();

$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'register'=> $_SESSION['register_error'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($error){
  return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm){
  return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link rel="stylesheet" href="login-reg-style.css">
</head>
<body>
  <div class="card-body">
    <div class="card <?= isActiveForm('login', $activeForm) ?>" id="login-form">
      <form action="login-reg.php" method="post" id="form-login">
        <h1>Leave Management System</h1>
        <?= showError($errors['login']); ?>
        <div class="form-input">
          <input type="email" name="email" id="login-email" placeholder="E-mail" required>
          <input type="password" name="password" id="login-password" placeholder="Password" required>
        </div>
        <div class="login-btn">
          <button type="submit" name="login">Login</button>
        </div>
      </form>              
      <div class="reg-form">
        <p>Don't have an account? <a href="#" onclick="showForm('reg-form')">Register</a></p>
      </div>
    </div>

    <div class="card <?= isActiveForm('register', $activeForm) ?>" id="reg-form">
      <form action="login-reg.php" method="post" id="form-reg">
        <h1>Leave Management System</h1>
        <?= showError($errors['register']); ?>
        <div class="user-name"> 
          <input type="text" name="name" id="username" placeholder="Username" required>
        </div>
        <div class="email">
          <input type="email" name="email" id="email" placeholder="E-mail" required>
        </div>
        <div class="pass">
          <input type="password" name="password" id="password" placeholder="Enter Password" required>
        </div>      
        <div class="register-btn">
          <button type="submit" name="register">Register</button>
        </div>
      </form>
      <div>
        <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p> 
      </div>
    </div>
  </div> 
  
  <script src="login-reg-script.js"></script>
</body>
</html>
