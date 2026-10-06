<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email'])) {
  header("Location: login-reg-index.php");
  exit();
}

if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'admin') {
  header("Location: dashboard.php");
  exit();
}

$targetDate = isset($_GET['status_date']) ? $_GET['from_date'] ?? $_GET['status_date'] : date('Y-m-d');
$targetDate = date('Y-m-d', strtotime($targetDate));

$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM user WHERE role != 'manager'");
$totalEmployees = $totalQuery->fetch_assoc()['total'] ?? 0;

$leaveQuery = $conn->prepare("
  SELECT COUNT(DISTINCT user_id) AS total 
  FROM leave_requests 
  WHERE status = 'Approved' 
  AND ? BETWEEN from_date AND to_date
");
$leaveQuery->bind_param("s", $targetDate);
$leaveQuery->execute();
$onLeaveCount = $leaveQuery->get_result()->fetch_assoc()['total'] ?? 0;
$leaveQuery->close();

$presentCount = max(0, $totalEmployees - $onLeaveCount);

$attendanceList = $conn->prepare("
  SELECT u.id, u.name, u.email, u.role, lr.leave_type, lr.from_date, lr.to_date
  FROM user u
  LEFT JOIN leave_requests lr ON u.id = lr.user_id 
    AND lr.status = 'Approved' 
    AND ? BETWEEN lr.from_date AND lr.to_date
  WHERE u.role != 'manager'
  ORDER BY u.name ASC
");
$attendanceList->bind_param("s", $targetDate);
$attendanceList->execute();
$attendanceResult = $attendanceList->get_result();
$attendanceList->close();

$displayPic = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png' && !empty($_SESSION['profile_pic'])) ? './uploads/' . $_SESSION['profile_pic'] : 'https://unsplash.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Company Attendance Status</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 25px; width: 100%; box-sizing: border-box; }
    .summary-card { position: relative; min-height: 126px; padding: 20px; background: var(--card-bg, #ffffff); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); }
    .summary-card p { color: var(--text-muted, #6b7280); font-size: .78rem; font-weight: 600; text-transform: uppercase; margin-bottom: 7px; }
    .summary-card strong { font-size: 1.6rem; display: block; color: var(--text-main, #1f2937); }
    .summary-icon { position: absolute; right: 18px; top: 20px; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; }
    .total-icon { background: #e0e7ff; color: #4f46e5; }
    .present-icon { background: #dcfce7; color: #15803d; }
    .leave-icon { background: #fee2e2; color: #b91c1c; }
    .filter-card { background: var(--card-bg, #ffffff); padding: 18px 20px; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); width: 100%; box-sizing: border-box; }
    .filter-container { display: flex; align-items: center; gap: 15px; justify-content: space-between; flex-wrap: wrap; }
    .date-picker-box { display: flex; align-items: center; gap: 10px; font-size: 0.95rem; font-weight: 500; color: var(--text-main, #1f2937); }
    .date-picker-box input { padding: 8px 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 6px; background: #fff; outline: none; font-size: 0.95rem; color: #1f2937; }
    .status-card { background: var(--card-bg, #ffffff); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); padding: 20px; width: 100%; box-sizing: border-box; }
    .status-table { width: 100%; border-collapse: collapse; text-align: left; }
    .status-table th, .status-table td { padding: 13px 15px; border-bottom: 1px solid var(--border-color, #e5e7eb); font-size: .85rem; }
    .status-table th { background: #f9fafb; color: var(--text-muted, #6b7280); font-size: .75rem; text-transform: uppercase; font-weight: 600; }
    .badge-status { padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; display: inline-block; text-transform: uppercase; }
    .badge-status.present { background: #dcfce7; color: #15803d; }
    .badge-status.on-leave { background: #fee2e2; color: #b91c1c; }
    .leave-type-info { font-size: 0.75rem; color: #6b7280; display: block; margin-top: 2px; font-style: italic; }
    @media (max-width: 768px) { .summary-grid { grid-template-columns: 1fr; } .filter-container { flex-direction: column; align-items: stretch; } }
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
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        
        <?php if ($_SESSION['role'] !== 'manager'): ?>
          <a href="request.php"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
          <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'manager'): ?>
          <a href="approve-requests.php"><i class="fa-solid fa-file-signature"></i> Approve Requests</a>
          <a href="leave-quotas.php"><i class="fa-solid fa-sliders"></i> Leave Quotas</a>
          <a href="company-status.php" class="active"><i class="fa-solid fa-users-viewfinder"></i> Company Status</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>
      
      <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="#" onclick="event.preventDefault(); showPortalModal('System Logout', 'Are you sure you want to log out of your session?', 'danger', false, function(confirmed){ if(confirmed){ window.location.href='logout.php'; } });">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </div>
    </aside>
    <main class="main-content">
      <header class="top-header">
        <h1>Company Attendance Status</h1>
        <div class="user-badge">
          <img src="<?= $displayPic; ?>" alt="User Avatar">
          <span><?= htmlspecialchars($_SESSION['name']); ?></span>
        </div>
      </header>

      <div class="filter-card">
        <div class="filter-container">
          <form action="company-status.php" method="GET" class="date-picker-box">
            <label for="status_date">Target Status Date:</label>
            <input type="date" id="status_date" name="status_date" value="<?= $targetDate; ?>" onchange="this.form.submit()">
          </form>
          <div style="font-size: 0.9rem; color: var(--text-muted);">
            Viewing records for: <strong><?= date('F d, Y', strtotime($targetDate)); ?></strong>
          </div>
        </div>
      </div>

      <div class="summary-grid">
        <div class="summary-card">
          <p>Total Workforce</p>
          <strong><?= $totalEmployees; ?> Employees</strong>
          <div class="summary-icon total-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="summary-card">
          <p>Active / Present</p>
          <strong><?= $presentCount; ?> Present</strong>
          <div class="summary-icon present-icon"><i class="fa-solid fa-user-check"></i></div>
        </div>
        <div class="summary-card">
          <p>Away / On Leave</p>
          <strong><?= $onLeaveCount; ?> On Leave</strong>
          <div class="summary-icon leave-icon"><i class="fa-solid fa-user-slash"></i></div>
        </div>
      </div>

      <div class="status-card">
        <table class="status-table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Email Address</th>
              <th>System Role</th>
              <th>Status (<?= date('M d', strtotime($targetDate)); ?>)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($attendanceResult->num_rows > 0): ?>
              <?php while ($row = $attendanceResult->fetch_assoc()): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($row['name']); ?></strong></td>
                  <td><?= htmlspecialchars($row['email']); ?></td>
                  <td style="text-transform: capitalize;"><?= htmlspecialchars($row['role']); ?></td>
                  <td>
                    <?php if (!empty($row['leave_type'])): ?>
                      <span class="badge-status on-leave">On Leave</span>
                      <span class="leave-type-info">
                        <?= htmlspecialchars(str_replace(' Leave', '', $row['leave_type'])); ?> (Until <?= date('M d', strtotime($row['to_date'])); ?>)
                      </span>
                    <?php else: ?>
                      <span class="badge-status present">Present</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">No registered employee tracks found in the database.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <div id="logout-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(4px); z-index:999999; justify-content:center; align-items:center; font-family:sans-serif;">
    <div style="background:#fff; padding:30px; border-radius:12px; width:100%; max-width:400px; text-align:center; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
      <i class="fa-solid fa-right-from-bracket" style="font-size:2.5rem; color:#ef4444; margin-bottom:15px;"></i>
      <h3 style="margin-bottom:10px; font-size:1.3rem; color:#1f2937; font-weight:700;">Confirm System Logout</h3>
      <p style="color:#6b7280; font-size:0.95rem; margin-bottom:25px; line-height:1.5;">Are you sure you want to securely log out of the LeavePortal system session?</p>
      <div style="display:flex; gap:12px; justify-content:center;">
        <button onclick="document.getElementById('logout-modal').style.display='none';" style="padding:10px 20px; background:#f3f4f6; color:#4b5563; border:none; border-radius:6px; font-weight:600; cursor:pointer; font-size:0.9rem;">Cancel</button>
        <a href="logout.php" style="padding:10px 20px; background:#ef4444; color:#fff; text-decoration:none; border-radius:6px; font-weight:600; font-size:0.9rem; display:inline-block;">Yes, Logout</a>
      </div>
    </div>
  </div>
  <script src="dashboard-script.js"></script>
</body>
</html>
