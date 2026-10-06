<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'manager') {
  header("Location: login-reg-index.php");
  exit();
}

$message = '';
$messageClass = '';

if (isset($_POST['update_quotas'])) {
  $annual = intval($_POST['annual_days']);
  $annual_m = intval($_POST['annual_month_days']);
  $casual = intval($_POST['casual_days']);
  $casual_m = intval($_POST['casual_month_days']);
  $sick   = intval($_POST['sick_days']);
  $sick_m   = intval($_POST['sick_month_days']);

  $stmt1 = $conn->prepare("UPDATE leave_quotas SET max_days = ?, max_days_per_month = ? WHERE leave_type = 'Annual Leave'");
  $stmt1->bind_param("ii", $annual, $annual_m);
  $stmt1->execute();

  $stmt2 = $conn->prepare("UPDATE leave_quotas SET max_days = ?, max_days_per_month = ? WHERE leave_type = 'Casual Leave'");
  $stmt2->bind_param("ii", $casual, $casual_m);
  $stmt2->execute();

  $stmt3 = $conn->prepare("UPDATE leave_quotas SET max_days = ?, max_days_per_month = ? WHERE leave_type = 'Sick Leave'");
  $stmt3->bind_param("ii", $sick, $sick_m);
  $stmt3->execute();

  $message = "Yearly and monthly leave constraints updated successfully across the entire system!";
  $messageClass = "success-message";
}

$quotas = [];
$res = $conn->query("SELECT * FROM leave_quotas");
while($row = $res->fetch_assoc()) {
  $quotas[$row['leave_type']] = [
    'max' => $row['max_days'],
    'month' => $row['max_days_per_month']
  ];
}
$displayPic = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png') ? 'uploads/' . $_SESSION['profile_pic'] : 'https://unsplash.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Quota Configuration</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .form-wrapper { background: var(--card-bg); padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); max-width: 500px; }
    .form-group { display: flex; flex-direction: column; margin-bottom: 20px; gap: 6px; }
    .form-group label { font-weight: 600; color: var(--text-main); font-size: 0.9rem; }
    .form-group input { padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb; }
    .success-message { padding: 12px; background: #def7ec; border-radius: 6px; color: #03543f; margin-bottom: 20px; font-size: 0.95rem; }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="sidebar-header"><i class="fa-solid fa-calendar-check logo-icon"></i><h2>LeavePortal</h2></div>
      <nav class="sidebar-menu">
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        
        <?php if ($_SESSION['role'] !== 'manager'): ?>
          <a href="request.php"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
          <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'manager'): ?>
          <a href="approve-requests.php"><i class="fa-solid fa-file-signature"></i> Approve Requests</a>
          <a href="leave-quotas.php" class="active"><i class="fa-solid fa-sliders"></i> Leave Quotas</a>
          <a href="company-status.php"><i class="fa-solid fa-users-viewfinder"></i> Company Status</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>
     <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="#" onclick="event.preventDefault(); showPortalModal('System Logout', 'Are you sure you want to log out of your session?', 'danger', function(confirmed){ if(confirmed){ window.location.href='logout.php'; } });">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <h1>Configure System Leave Quotas</h1>
        <div class="user-badge">
          <img src="<?= $displayPic; ?>" alt="Avatar">
          <span><?= htmlspecialchars($_SESSION['name']); ?></span>
        </div>
      </header>
      
      <?php if(!empty($message)): ?>
        <div class="<?= $messageClass; ?>"><?= $message; ?></div>
      <?php endif; ?>

      <form action="leave-quotas.php" method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: start;">
          
          <div class="card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700; color: var(--text-main); border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
              <i class="fa-solid fa-calendar-days"></i> Global Yearly Limits
            </h3>
            
            <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 20px; gap: 6px;">
              <label style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Annual Leave Limit (Days / Year)</label>
              <input type="number" name="annual_days" value="<?= $quotas['Annual Leave']['max'] ?? 14; ?>" min="0" required style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb;">
            </div>

            <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 20px; gap: 6px;">
              <label style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Casual Leave Limit (Days / Year)</label>
              <input type="number" name="casual_days" value="<?= $quotas['Casual Leave']['max'] ?? 7; ?>" min="0" required style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb;">
            </div>

            <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 25px; gap: 6px;">
              <label style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Sick Leave Limit (Days / Year)</label>
              <input type="number" name="sick_days" value="<?= $quotas['Sick Leave']['max'] ?? 10; ?>" min="0" required style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb;">
            </div>
          </div>

          <div class="card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700; color: var(--text-main); border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
              <i class="fa-solid fa-calendar-minus"></i> Monthly Request Caps
            </h3>

            <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 20px; gap: 6px;">
              <label style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Annual Leave Cap (Max Days / Month)</label>
              <input type="number" name="annual_month_days" value="<?= $quotas['Annual Leave']['month'] ?? 3; ?>" min="0" required style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb;">
            </div>

            <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 20px; gap: 6px;">
              <label style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Casual Leave Cap (Max Days / Month)</label>
              <input type="number" name="casual_month_days" value="<?= $quotas['Casual Leave']['month'] ?? 2; ?>" min="0" required style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb;">
            </div>

            <div class="form-group" style="display: flex; flex-direction: column; margin-bottom: 25px; gap: 6px;">
              <label style="font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Sick Leave Cap (Max Days / Month)</label>
              <input type="number" name="sick_month_days" value="<?= $quotas['Sick Leave']['month'] ?? 3; ?>" min="0" required style="padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; background: #f9fafb;">
            </div>
          </div>

        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
          <button type="submit" name="update_quotas" class="btn-primary" style="padding: 12px 30px; border: none; border-radius: 6px; background: var(--primary-color); color: white; font-weight: 600; font-size: 1rem; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background 0.15s ease;">
            <i class="fa-solid fa-floppy-disk"></i> Save Quota Limits
          </button>
        </div>
      </form>
    </main>

  </div>
  <script src="dashboard-script.js"></script>
</body>
</html>
