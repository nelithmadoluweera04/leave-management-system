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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
  $userId    = $_SESSION['user_id'];
  $leaveType = $_POST['leave_type'];
  $fromDate  = $_POST['from_date'];
  $toDate    = $_POST['to_date'];
  $reason    = trim($_POST['reason']);
  $isSpecial = isset($_POST['is_special_request']) ? 1 : 0;

  if (!empty($leaveType) && !empty($fromDate) && !empty($toDate) && !empty($reason)) {
    
    $todayDate = date('Y-m-d');
    
    if ($fromDate < $todayDate || $toDate < $todayDate) {
      $message = "Application Denied: You cannot select a leave date in the past. Please choose a date from today onward.";
      $messageClass = "error-message";
    } 
    elseif ($toDate < $fromDate) {
      $message = "Application Denied: The 'To Date' cannot be earlier than your 'From Date'.";
      $messageClass = "error-message";
    } 
    else {
      $overlapCheck = $conn->prepare("
        SELECT id, leave_type, status FROM leave_requests 
        WHERE user_id = ? 
        AND status IN ('Pending', 'Approved')
        AND (
          (from_date <= ? AND to_date >= ?) OR
          (from_date <= ? AND to_date >= ?) OR
          (from_date >= ? AND to_date <= ?)
        )
        LIMIT 1
      ");

      $overlapCheck->bind_param("issssss", $userId, $fromDate, $fromDate, $toDate, $toDate, $fromDate, $toDate);
      $overlapCheck->execute();
      $overlapResult = $overlapCheck->get_result();
      
      if ($overlapResult->num_rows > 0) {
        $existingLeave = $overlapResult->fetch_assoc();
        $message = "Application Denied: You already have a conflicting request (" . htmlspecialchars($existingLeave['leave_type']) . ") with a status of [" . htmlspecialchars($existingLeave['status']) . "] within this date range. You cannot book overlapping dates.";
        $messageClass = "error-message";
        $overlapCheck->close();
      } 
      else {
        $overlapCheck->close();
        
        $requestedDays = (strtotime($toDate) - strtotime($fromDate)) / (60 * 60 * 24) + 1;

        $quotaStmt = $conn->prepare("SELECT max_days, max_days_per_month FROM leave_quotas WHERE leave_type = ?");
        $quotaStmt->bind_param("s", $leaveType);
        $quotaStmt->execute();
        $quotaRes = $quotaStmt->get_result()->fetch_assoc();
        $quotaStmt->close();

        $maxYearly  = $quotaRes['max_days'] ?? 14;
        $maxMonthly = $quotaRes['max_days_per_month'] ?? 3;

        $targetYear  = date('Y', strtotime($fromDate));
        $targetMonth = date('m', strtotime($fromDate));

        $monthCheck = $conn->prepare("
          SELECT SUM(DATEDIFF(to_date, from_date) + 1) AS total 
          FROM leave_requests 
          WHERE user_id = ? AND leave_type = ? AND status = 'Approved' 
          AND YEAR(from_date) = ? AND MONTH(from_date) = ?
        ");
        $monthCheck->bind_param("isii", $userId, $leaveType, $targetYear, $targetMonth);
        $monthCheck->execute();
        $daysTakenThisMonth = $monthCheck->get_result()->fetch_assoc()['total'] ?? 0;
        $monthCheck->close();

        $yearCheck = $conn->prepare("
          SELECT SUM(DATEDIFF(to_date, from_date) + 1) AS total 
          FROM leave_requests 
          WHERE user_id = ? AND leave_type = ? AND status = 'Approved'
        ");
        $yearCheck->bind_param("is", $userId, $leaveType);
        $yearCheck->execute();
        $daysTakenThisYear = $yearCheck->get_result()->fetch_assoc()['total'] ?? 0;
        $yearCheck->close();

        $exceedsMonth = ($daysTakenThisMonth + $requestedDays) > $maxMonthly;
        $exceedsYear  = ($daysTakenThisYear + $requestedDays) > $maxYearly;

        if (($exceedsMonth || $exceedsYear) && $isSpecial === 0) {
          $message = "Application Denied: This request exceeds your remaining leave quota or monthly threshold. If this is an emergency, please check the 'Apply as Special Request' box below.";
          $messageClass = "error-message";
        } else {
          $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, from_date, to_date, reason, is_special_request) VALUES (?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("issssi", $userId, $leaveType, $fromDate, $toDate, $reason, $isSpecial);

          if ($stmt->execute()) {
            $message = $isSpecial ? "Special Request submitted successfully for Manager review!" : "Standard leave request submitted successfully!";
            $messageClass = "success-message";
          } else {
            $message = "Something went wrong. Please try again.";
            $messageClass = "error-message";
          }
          $stmt->close();
        }
      }
    }
  } else {
    $message = "Please fill in all fields.";
    $messageClass = "error-message";
  }
}



$displayPic = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png') ? 'uploads/' . $_SESSION['profile_pic'] : 'https://unsplash.com';
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
        <a href="settings.php"><i class="fa-solid fa-gear nav-icon"></i> Settings</a>
      </nav>
      
      <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="#" onclick="event.preventDefault(); showPortalModal('System Logout', 'Are you sure you want to log out of your session?', 'danger', false, function(confirmed){ if(confirmed){ window.location.href='logout.php'; } });">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
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

          <form action="request.php" method="POST" onsubmit="return validateLeaveDates()" enctype="multipart/form-data">
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
                <input type="date" id="fromDate" name="from_date" min="<?= date('Y-m-d'); ?>" required>
              </div>
              <div class="form-group">
                <label for="toDate">To Date</label>
                <input type="date" id="toDate" name="to_date" min="<?= date('Y-m-d'); ?>" required>
              </div>
            </div>


            <div class="form-group">
              <label for="reason">Reason</label>
              <textarea id="reason" name="reason" rows="4" placeholder="Provide a reason for your leave request..." required></textarea>
            </div>

            <div class="form-group" style="display: flex; flex-direction: row; align-items: center; gap: 10px; background: #fdf2f8; padding: 12px; border-radius: 6px; border: 1px solid #fbcfe8; margin-bottom: 20px;">
              <input type="checkbox" id="isSpecialRequest" name="is_special_request" style="width: auto; cursor: pointer;">
              <label for="isSpecialRequest" style="font-weight: 600; color: #9d174d; cursor: pointer; margin: 0; font-size: 0.9rem;">
                Apply as a Special Request (Check this if you have exceeded your leave quota or monthly threshold)
              </label>
            </div>

            <div class="form-group">
              <label for="attachment">Attachment (Optional)</label>
              <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            </div>

            <button type="submit" name="submit_leave" class="btn-primary">Submit Request</button>
          </form>
        </div>
      </div>
    </main>
  </div>
  <script src="dashboard-script.js"></script>
</body>
</html>
