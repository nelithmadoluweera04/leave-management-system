<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email'])) {
  header("Location: login-reg-index.php");
  exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
  $currentPass = $_POST['current_password'];
  $newPass     = $_POST['new_password'];
  $confirmPass = $_POST['confirm_password'];

  if ($newPass !== $confirmPass) {
    $message = "Validation Error: Your new passwords do not match.";
    $messageClass = "error-message";
  } else {
    $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (password_verify($currentPass, $userRow['password'])) {
      $newHashedPass = password_hash($newPass, PASSWORD_DEFAULT);
      $updateStmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
      $updateStmt->bind_param("si", $newHashedPass, $userId);
      
      if ($updateStmt->execute()) {
        $message = "Security settings updated: Your password has been changed successfully.";
        $messageClass = "success-message";
      } else {
        $message = "An error occurred. Please try again.";
        $messageClass = "error-message";
      }
      $updateStmt->close();
    } else {
      $message = "Security Error: The current password you entered is incorrect.";
      $messageClass = "error-message";
    }
  }
}

$displayPic = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png') ? 'uploads/' . $_SESSION['profile_pic'] : 'https://unsplash.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Account Settings</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: start; }
    .settings-card { background: var(--card-bg, #ffffff); border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .settings-card h3 { font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; text-align: left; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
    .form-group input, .form-group select { padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb; }
    .form-group input:focus, .form-group select:focus { border-color: var(--primary-color); background: #fff; }
    .success-message { padding: 12px; background: #def7ec; border-radius: 6px; color: #03543f; margin-bottom: 25px; font-size: 0.95rem; }
    .error-message { padding: 12px; background: #fee2e2; border-radius: 6px; color: #a42834; margin-bottom: 25px; font-size: 0.95rem; }
    .toggle-group { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    @media (max-width: 992px) { .settings-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="sidebar-header"><i class="fa-solid fa-calendar-check logo-icon"></i><h2>LeavePortal</h2></div>
      <nav class="sidebar-menu">
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <?php if ($_SESSION['role'] !== 'manager'): ?>
          <a href="../request/request.php"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
          <a href="../history/history.php"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'manager'): ?>
          <a href="approve-requests.php"><i class="fa-solid fa-file-signature"></i> Approve Requests</a>
          <a href="leave-quotas.php"><i class="fa-solid fa-sliders"></i> Leave Quotas</a>
          <a href="company-status.php"><i class="fa-solid fa-users-viewfinder"></i> Company Status</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>
        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>
      <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="#" onclick="event.preventDefault(); showPortalModal('System Logout', 'Are you sure you want to log out?', 'danger', false, function(confirmed){ if(confirmed){ window.location.href='logout.php'; } });"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <h1>Account Settings</h1>
        <div class="user-badge"><img src="<?= $displayPic; ?>"><span><?= htmlspecialchars($_SESSION['name']); ?></span></div>
      </header>

      <?php if(!empty($message)): ?><div class="<?= $messageClass; ?>"><?= $message; ?></div><?php endif; ?>

      <div class="settings-grid">

        <div class="settings-card">
          <h3><i class="fa-solid fa-shield-halved"></i> Security & Password</h3>
          <form action="settings.php" method="POST">
            <div class="form-group">
              <label>Current Password</label>
              <input type="password" name="current_password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new_password" placeholder="Minimum 8 characters" required>
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" placeholder="Repeat new password" required>
            </div>
            <button type="submit" name="update_password" class="btn-primary" style="padding: 10px 20px; border:none; border-radius:6px; background:var(--primary-color); color:white; font-weight:600; cursor:pointer;"><i class="fa-solid fa-key"></i> Update Password</button>
          </form>
        </div>

        <div class="settings-card">
          <h3><i class="fa-solid fa-sliders"></i> System Preferences</h3>
          
          <div class="form-group">
            <label>Preferred Language</label>
            <select>
              <option value="en" selected>English (United States)</option>
              <option value="es">Español</option>
              <option value="fr">Français</option>
            </select>
          </div>

          <div class="form-group">
            <label>System Timezone</label>
            <select>
              <option value="GMT-5">Eastern Standard Time (GMT-5)</option>
              <option value="GMT+0">Greenwich Mean Time (GMT+0)</option>
              <option value="GMT+5.5" selected>Sri Lanka Time (GMT+5:30)</option>
            </select>
          </div>

          <?php if ($_SESSION['role'] === 'manager'): ?>
            <h3 style="margin-top: 25px;"><i class="fa-solid fa-bell"></i> Notification Controls</h3>
            <div class="toggle-group">
              <span style="font-size:0.9rem; font-weight:500; color:var(--text-main);">Email on Application Submission</span>
              <input type="checkbox" checked style="width:auto; cursor:pointer;">
            </div>
            <div class="toggle-group">
              <span style="font-size:0.9rem; font-weight:500; color:var(--text-main);">Weekly Leave Summary Digest</span>
              <input type="checkbox" style="width:auto; cursor:pointer;">
            </div>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>

  <script src="dashboard-script.js"></script>
</body>
</html>
