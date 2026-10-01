<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email'])) {
  header("Location: login-reg-index.php");
  exit();
}

$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
  $userId    = $_SESSION['user_id'];
  $leaveType = $_POST['leave_type'];
  $fromDate  = $_POST['from_date'];
  $toDate    = $_POST['to_date'];
  $reason    = trim($_POST['reason']);

  if (!empty($leaveType) && !empty($fromDate) && !empty($toDate) && !empty($reason)) {
    $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, from_date, to_date, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $leaveType, $fromDate, $toDate, $reason);

    if ($stmt->execute()) {
      $message = "Leave request submitted successfully!";
      $messageClass = "success-message";
    } else {
      $message = "Something went wrong. Please try again.";
      $messageClass = "error-message";
    }
    $stmt->close();
  } else {
    $message = "Please fill in all fields.";
    $messageClass = "error-message";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Apply Leave</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
  .leave-request-heading {
    color: #172b4d;
    font-size: 2rem;
    line-height: 1.2;
    margin: 0;
  }

  .form-container {
    max-width: 760px;
    width: 100%;
    margin-top: 20px;
  }

  .card {
    background-color: var(--card-bg, #ffffff);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }

  .form-card { 
    max-width: 650px; 
    margin: 0;
  }

  .form-row { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 15px; 
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;            
    margin-bottom: 25px;  
  }

  .form-group label {
    font-weight: 500;
    color: var(--text-main, #1f2937);
    font-size: 0.95rem;
    margin-bottom: 2px;  
  }

  .form-group input, 
  .form-group select, 
  .form-group textarea { 
    width: 100%;
    padding: 12px; 
    border: 1px solid var(--border-color, #e5e7eb); 
    border-radius: 6px; 
    font-size: 0.95rem; 
    outline: none;
    background-color: #f9fafb;
    color: var(--text-main, #1f2937);
    transition: border-color 0.2s ease, background-color 0.2s ease;
  }

  .form-group input:focus, 
  .form-group select:focus, 
  .form-group textarea:focus {
    border-color: var(--primary-color, #4f46e5);
    background-color: #ffffff;
  }
  .btn-primary { 
    background: var(--primary-color, #4f46e5); 
    color: #fff; 
    border: none; 
    padding: 12px 24px; 
    border-radius: 6px; 
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer; 
    transition: background-color 0.2s ease;
    margin-top: 10px;
  }

  .btn-primary:hover { 
    background: var(--primary-hover, #4338ca); 
  }

  @media (max-width: 560px) {
    .form-row {
      grid-template-columns: 1fr;
      gap: 0;
    }
  }
  .success-message { 
    padding: 12px; 
    background: #def7ec; 
    border-radius: 6px; 
    color: #03543f; 
    margin-bottom: 20px; 
    font-size: 0.95rem; 
  }

  .error-message { 
    padding: 12px; 
    background: #f8d7da; 
    border-radius: 6px; 
    color: #a42834; 
    margin-bottom: 20px; 
    font-size: 0.95rem; 
  }
</style>

</head>
<body>

  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <i class="fa-solid fa-calendar-check logo-icon"></i>
        <h2>LeavePortal</h2>
      </div>
      
      <nav class="sidebar-menu">
        <a href="dashboard.php"><i class="fa-solid fa-house nav-icon"></i> Dashboard</a>
        <a href="request.php" class="active"><i class="fa-solid fa-plane-departure nav-icon"></i> Apply Leave</a>
        <a href="history.php"><i class="fa-solid fa-clock-rotate-left nav-icon"></i> Leave History</a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="profile.php"><i class="fa-solid fa-user nav-icon"></i> My Profile</a>
        <a href="#"><i class="fa-solid fa-gear nav-icon"></i> Settings</a>
      </nav>
      
      <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="../manage/logout.php" onclick="if(!confirm('Are you sure you want to log out of the system?')) { event.preventDefault(); return false; }">
          <i class="fa-solid fa-right-from-bracket nav-icon"></i> Logout
        </a>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <h1 class="leave-request-heading">Submit Leave Request</h1>
        <div class="user-badge">
          <?php 
            $userAvatar = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png') 
                          ? 'uploads/' . $_SESSION['profile_pic'] 
                          : 'https://unsplash.com';
          ?>
          <img src="<?= $userAvatar; ?>" alt="User Avatar">
          <span><?= htmlspecialchars($_SESSION['name']); ?></span>
        </div>
      </header>

      <div class="form-container">
        <div class="card form-card">

          <?php if(!empty($message)): ?>
              <div class="<?= $messageClass; ?>"><?= $message; ?></div>
          <?php endif; ?>

          <form action="request.php" method="POST">
            <div class="form-group">
              <label for="leaveType">Leave Type</label>
              <select id="leaveType" name="leave_type" required>
                <option value="" disabled selected>Select Type</option>
                <option value="Casual Leave">Casual Leave</option>
                <option value="Sick Leave">Sick Leave</option>
                <option value="Annual Leave">Annual Leave</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="fromDate">From Date</label>
                <input type="date" id="fromDate" name="from_date" required>
              </div>
              <div class="form-group">
                <label for="toDate">To Date</label>
                <input type="date" id="toDate" name="to_date" required>
              </div>
            </div>

            <div class="form-group">
              <label for="reason">Reason</label>
              <textarea id="reason" name="reason" rows="4" placeholder="Provide a reason for your leave request..." required></textarea>
            </div>

            <button type="submit" name="submit_leave" class="btn-primary">Submit Request</button>
          </form>
        </div>
      </div>
    </main>
  </div>

</body>
</html>
