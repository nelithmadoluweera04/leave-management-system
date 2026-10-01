<?php
session_start();
if(!isset($_SESSION['email'])){
  header("Location: login-reg-index.php");
  exit();
}

require_once 'login-reg-config.php';
$userId = $_SESSION['user_id'];

$approvedQuery = $conn->query("SELECT SUM(DATEDIFF(to_date, from_date) + 1) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Approved'");
$approvedRow = $approvedQuery->fetch_assoc();
$approvedLeaves = $approvedRow['total'] ?? 0;

$pendingQuery = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Pending'");
$pendingRow = $pendingQuery->fetch_assoc();
$pendingRequests = $pendingRow['total'] ?? 0;

$maxYearlyAllowance = 31;
$availableBalance = max(0, $maxYearlyAllowance - $approvedLeaves);

$recentLeave = $conn->query("SELECT leave_type, from_date, to_date, status FROM leave_requests WHERE user_id = $userId ORDER BY id DESC LIMIT 1");
$hasRecent = $recentLeave->num_rows > 0;
$recentRow = $recentLeave->fetch_assoc();
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
        <a href="request.php"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
        <a href="history.php"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>
      
      <div class="sidebar-footer" style="position: relative; z-index: 9999;">
        <a href="logout.php" onclick="if(!confirm('Are you sure you want to log out of the system?')) { event.preventDefault(); return false; }">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </div>


    </aside>

    <main class="main-content">
      
      <header class="top-header">
        <h1>Dashboard</h1>
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

      <section class="stats-grid">
        <div class="stat-card">
          <div class="stat-info">
            <h3>Available Balance</h3>
            <p><?= $availableBalance; ?> Days</p>
          </div>
          <div class="stat-icon balance">
            <i class="fa-solid fa-wallet"></i>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-info">
            <h3>Approved Leaves</h3>
            <p><?= $approvedLeaves; ?> Days</p>
          </div>
          <div class="stat-icon approved">
            <i class="fa-solid fa-circle-check"></i>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3>Pending Requests</h3>
            <p><?= $pendingRequests; ?> Requests</p>
          </div>
          <div class="stat-icon pending">
            <i class="fa-solid fa-hourglass-half"></i>
          </div>
        </div>
      </section>

      <div class="content-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
        <section class="data-card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
          <h2 class="card-title" style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Recent Leave Requests</h2>
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
                  <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">You haven't submitted any leave requests yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
        <section class="data-card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
          <h2 class="card-title" style="margin-bottom: 20px; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Leave Allowance Breakdown</h2>
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
                  <div class="progress-bar" style="width: <?= $percentage; ?>%; height: 100%; background-color: <?= $barColor; ?>; border-radius: 4px; transition: width 0.3s ease;"></div>
                </div>
              </div>
            <?php endforeach; ?>

          </div>
        </section>

      </div>


    </main>
  </div>
  <script src="dashboard-script.js"></script>
</body>
</html>
