<?php
session_start();
require_once 'login-reg-config.php';

if (isset($_POST['register'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $checkAllowed = $conn->prepare("SELECT role FROM allowed_emails WHERE email = ?");
  $checkAllowed->bind_param("s", $email);
  $checkAllowed->execute();
  $allowedResult = $checkAllowed->get_result();

  if ($allowedResult->num_rows === 0) {
    $_SESSION['register_error'] = 'Your email is not authorized to register. Please contact an Admin.';
    $_SESSION['active_form'] = 'register';
    $checkAllowed->close();
    header("Location: login-reg-index.php");
    exit();
  }

  $allowedUser = $allowedResult->fetch_assoc();
  $assignedRole = $allowedUser['role']; 
  $checkAllowed->close();

  $stmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['register_error'] = 'This email address is already registered!';
    $_SESSION['active_form'] = 'register';
    $stmt->close();
  } 
  else {
    $insertStmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $name, $email, $password, $assignedRole);
    $insertStmt->execute();
    $insertStmt->close();
    $stmt->close();
    
    $_SESSION['login_error'] = 'Registration successful! You can now log in.';
    $_SESSION['active_form'] = 'login';
  } 
  header("Location: login-reg-index.php");
  exit();
}

if (isset($_POST['login'])) {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id']; 
      $_SESSION['name'] = $user['name'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['profile_pic'] = $user['profile_pic'];

      $stmt->close();
      header("Location: dashboard.php");
      exit();
    }
  }

  $_SESSION['login_error'] = 'Incorrect email or password';
  $_SESSION['active_form'] = 'login';
  $stmt->close();
  header("Location: login-reg-index.php");
  exit();
}
?>
