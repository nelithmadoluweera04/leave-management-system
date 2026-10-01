<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: login-reg-index.php");
  exit();
}

require_once 'login-reg-config.php';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$currentYear  = date('Y');
$currentMonth = date('m');

if ($userRole === 'manager') {
  $receivedQuery = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE YEAR(created_at) = '$currentYear' AND MONTH(created_at) = '$currentMonth'");
  $monthReceived = $receivedQuery->fetch_assoc()['total'] ?? 0;

  $approvedQuery = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE status = 'Approved' AND YEAR(created_at) = '$currentYear' AND MONTH(created_at) = '$currentMonth'");
  $monthApproved = $approvedQuery->fetch_assoc()['total'] ?? 0;

  $rejectedQuery = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE status = 'Rejected' AND YEAR(created_at) = '$currentYear' AND MONTH(created_at) = '$currentMonth'");
  $monthRejected = $rejectedQuery->fetch_assoc()['total'] ?? 0;

  $employeeSummary = $conn->query("
    SELECT u.id, u.name, u.email, u.role,
           COALESCE(SUM(CASE WHEN lr.status = 'Approved' THEN DATEDIFF(lr.to_date, lr.from_date) + 1 ELSE 0 END), 0) AS days_taken,
           COALESCE(SUM(CASE WHEN lr.status = 'Pending' THEN 1 ELSE 0 END), 0) AS pending_count
    FROM user u
    LEFT JOIN leave_requests lr ON u.id = lr.user_id
    WHERE u.role != 'manager'
    GROUP BY u.id
    ORDER BY u.name ASC
  ");
} else {
  $approvedQuery = $conn->query("SELECT SUM(DATEDIFF(to_date, from_date) + 1) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Approved'");
  $approvedLeaves = $approvedQuery->fetch_assoc()['total'] ?? 0;

  $pendingQuery = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Pending'");
  $pendingRequests = $pendingQuery->fetch_assoc()['total'] ?? 0;

  $maxYearlyAllowance = 31;
  $availableBalance = max(0, $maxYearlyAllowance - $approvedLeaves);

  $recentLeave = $conn->query("SELECT leave_type, from_date, to_date, status FROM leave_requests WHERE user_id = $userId ORDER BY id DESC LIMIT 1");
  $hasRecent = $recentLeave->num_rows > 0;
  $recentRow = $recentLeave->fetch_assoc();
}

$displayPic = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png') ? 'uploads/' . $_SESSION['profile_pic'] : 'https://unsplash.com';
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Dashboard</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="dashboard-container">

    <aside class="sidebar">
      <div class="sidebar-header">
        <i class="fa-solid fa-calendar-check logo-icon"></i>
        <h2>LeavePortal</h2>
      </div>
      
      <nav class="sidebar-menu">
        <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
        
        <?php if ($_SESSION['role'] !== 'manager'): ?>
          <a href="request.php"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
          <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'manager'): ?>
          <a href="approve-requests.php"><i class="fa-solid fa-file-signature"></i> Approve Requests</a>
          <a href="leave-quotas.php"><i class="fa-solid fa-sliders"></i> Leave Quotas</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>


      
      <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="#" onclick="event.preventDefault(); showPortalModal('System Logout', 'Are you sure you want to log out of the LeavePortal system session?', 'danger', function(confirmed){ if(confirmed){ window.location.href='logout.php'; } });">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </div>



    </aside>

    <main class="main-content">
      <header class="top-header">
        <h1>Dashboard</h1>
        <div class="user-badge">
          <img src="<?= $displayPic; ?>" alt="User Avatar">
          <span><?= htmlspecialchars($_SESSION['name']); ?></span>
        </div>
      </header>

      <?php if ($_SESSION['role'] === 'manager'): ?>
        
        <section class="stats-grid">
          <div class="stat-card">
            <div class="stat-info">
              <h3>Received This Month</h3>
              <p><?= $monthReceived; ?><small style="font-size:0.8rem; color:var(--text-muted);"> Requests</small></p>
            </div>
            <div class="stat-icon balance" style="background:#e0e7ff; color:#4f46e5;">
              <i class="fa-solid fa-folder-open"></i>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-info">
              <h3>Approved This Month</h3>
              <p><?= $monthApproved; ?><small style="font-size:0.8rem; color:var(--text-muted);"> Requests Approved</small></p>
            </div>
            <div class="stat-icon approved">
              <i class="fa-solid fa-circle-check"></i>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-info">
              <h3>Rejected/Cancelled</h3>
              <p><?= $monthRejected; ?><small style="font-size:0.8rem; color:var(--text-muted);"> Requests</small></p>
            </div>
            <div class="stat-icon pending" style="background:#fee2e2; color:#ef4444;">
              <i class="fa-solid fa-circle-xmark"></i>
            </div>
          </div>
        </section>

        <section class="data-card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-top: 25px;">
          <h2 class="card-title" style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700;">Active Employee Leave Summary</h2>
          <div class="table-wrapper">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
              <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background:#f9fafb;">
                  <th style="padding: 12px;">Employee Name</th>
                  <th style="padding: 12px;">Email Address</th>
                  <th style="padding: 12px;">System Role</th>
                  <th style="padding: 12px;">Total Days Approved</th>
                  <th style="padding: 12px; text-align: center;">Pending Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($employeeSummary->num_rows > 0): ?>
                  <?php while ($emp = $employeeSummary->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                      <td style="padding: 15px 12px;"><strong><?= htmlspecialchars($emp['name']); ?></strong></td>
                      <td style="padding: 15px 12px; color: var(--text-muted);"><?= htmlspecialchars($emp['email']); ?></td>
                      <td style="padding: 15px 12px; text-transform: capitalize;"><span style="font-size: 0.85rem; font-weight: 500;"><?= htmlspecialchars($emp['role']); ?></span></td>
                      <td style="padding: 15px 12px;"><strong><?= htmlspecialchars($emp['days_taken']); ?> Days</strong> taken</td>
                      <td style="padding: 15px 12px; text-align: center;">
                        <?php if ($emp['pending_count'] > 0): ?>
                          <span class="status status-pending" style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                            <?= $emp['pending_count']; ?> Awaiting Review
                          </span>
                        <?php else: ?>
                          <span style="color: #10b981; font-size: 0.85rem; font-style: italic;"><i class="fa-solid fa-circle-check"></i> All Clear</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No registered employees found in the database.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

      <?php else: ?>
        
        <section class="stats-grid">
          <div class="stat-card">
            <div class="stat-info"><h3>Available Balance</h3><p><?= $availableBalance; ?> Days</p></div>
            <div class="stat-icon balance"><i class="fa-solid fa-wallet"></i></div>
          </div>
          <div class="stat-card">
            <div class="stat-info"><h3>Approved Leaves</h3><p><?= $approvedLeaves; ?> Days</p></div>
            <div class="stat-icon approved"><i class="fa-solid fa-circle-check"></i></div>
          </div>
          <div class="stat-card">
            <div class="stat-info"><h3>Pending Requests</h3><p><?= $pendingRequests; ?> Requests</p></div>
            <div class="stat-icon pending"><i class="fa-solid fa-hourglass-half"></i></div>
          </div>
        </section>

        <div class="content-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; margin-top: 25px;">
          <section class="data-card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h2 class="card-title" style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700;">Recent Leave Requests</h2>
            <div class="table-wrapper">
              <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                  <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Leave Type</th>
                    <th style="padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">From</th>
                    <th style="padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">To</th>
                    <th style="padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Days</th>
                    <th style="padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($hasRecent): 
                    $days = (strtotime($recentRow['to_date']) - strtotime($recentRow['from_date'])) / (60 * 60 * 24) + 1;
                  ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                      <td style="padding: 15px 12px;"><strong><?= htmlspecialchars(str_replace(' Leave', '', $recentRow['leave_type'])); ?></strong></td>
                      <td style="padding: 15px 12px;"><?= date('M d, Y', strtotime($recentRow['from_date'])); ?></td>
                      <td style="padding: 15px 12px;"><?= date('M d, Y', strtotime($recentRow['to_date'])); ?></td>
                      <td style="padding: 15px 12px;"><?= $days; ?> Days</td>
                      <td style="padding: 15px 12px;">
                        <span class="status status-<?= strtolower($recentRow['status']); ?>" style="padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                          <?= htmlspecialchars($recentRow['status']); ?>
                        </span>
                      </td>
                    </tr>
                  <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">You haven't submitted any leave requests yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section class="data-card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h2 class="card-title" style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700;">Leave Allowance Breakdown</h2>
            <div class="leave-balance-list" style="display: flex; flex-direction: column; gap: 20px;">
              <?php
              $quotaLimits = ['Annual Leave' => 14, 'Casual Leave' => 7, 'Sick Leave' => 10];
              $quotaTaken  = ['Annual Leave' => 0, 'Casual Leave' => 0, 'Sick Leave' => 0];

              $barQuery = $conn->query("SELECT leave_type, SUM(DATEDIFF(to_date, from_date) + 1) AS days FROM leave_requests WHERE user_id = $userId AND status = 'Approved' GROUP BY leave_type");
              while ($barRow = $barQuery->fetch_assoc()) {
                $t = $barRow['leave_type'];
                if (isset($quotaTaken[$t])) { $quotaTaken[$t] = (int)$barRow['days']; }
              }

              foreach ($quotaLimits as $typeTitle => $limitMax):
                $takenCount = $quotaTaken[$typeTitle];
                $remCount   = max(0, $limitMax - $takenCount);
                $percentage = ($limitMax > 0) ? ($remCount / $limitMax) * 100 : 0;
                $barColor = '#27c79a';
                if ($typeTitle === 'Casual Leave') $barColor = '#22b6ea';
                if ($typeTitle === 'Sick Leave') $barColor = '#fb5a7d';
              ?>
                <div class="balance-group">
                  <div class="balance-item" style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; font-weight: 500;">
                    <span style="color: var(--text-muted);"><?= $typeTitle; ?></span>
                    <strong style="color: var(--text-main);"><?= $remCount; ?> / <?= $limitMax; ?> Left</strong>
                  </div>
                  <div class="progress-bar-container" style="width: 100%; height: 6px; background-color: #f3f4f6; border-radius: 4px; overflow: hidden;">
                    <div class="progress-bar" style="width: <?= $percentage; ?>%; height: 100%; background-color: <?= $barColor; ?>; border-radius: 4px;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        </div>

      <?php endif; ?>
    </main>  

  </div>
  <script src="dashboard-script.js"></script>
</body>
</html>
