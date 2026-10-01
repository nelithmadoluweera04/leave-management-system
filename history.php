<?php
session_start();
require_once 'login-reg-config.php';

if ($_SESSION['role'] === 'manager') {
  header("Location: dashboard.php");
  exit();
}

if (!isset($_SESSION['email'])) {
  header("Location: ../manage/login-reg-index.php");
  exit();
}

$userId = $_SESSION['user_id'];

if (isset($_GET['cancel_id'])) {
  $cancelId = trim($_GET['cancel_id']);
  $stmtCancel = $conn->prepare("DELETE FROM leave_requests WHERE id = ? AND user_id = ? AND status = 'Pending'");
  $stmtCancel->bind_param("si", $cancelId, $userId);
  $stmtCancel->execute();
  $stmtCancel->close();
  header("Location: history.php");
  exit();
}

$totalTakenQuery = $conn->query("SELECT SUM(DATEDIFF(to_date, from_date) + 1) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Approved'");
$totalTaken      = $totalTakenQuery->fetch_assoc()['total'] ?? 0;

$pendingQuery    = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Pending'");
$pendingCount    = $pendingQuery->fetch_assoc()['total'] ?? 0;

$rejectedQuery   = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE user_id = $userId AND status = 'Rejected'");
$rejectedCount   = $rejectedQuery->fetch_assoc()['total'] ?? 0;

$totalReqQuery   = $conn->query("SELECT COUNT(*) AS total FROM leave_requests WHERE user_id = $userId");
$totalRequests   = $totalReqQuery->fetch_assoc()['total'] ?? 0;

$leaveDataList = [];
$fetchLeaves = $conn->query("SELECT id, leave_type, from_date, to_date, reason, status, created_at FROM leave_requests WHERE user_id = $userId ORDER BY from_date DESC");

while ($row = $fetchLeaves->fetch_assoc()) {
  $daysCount = (strtotime($row['to_date']) - strtotime($row['from_date'])) / (60 * 60 * 24) + 1;
  $leaveDataList[] = [
    'id'      => 'LV-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
    'type'    => str_replace(' Leave', '', $row['leave_type']),
    'from'    => date('M d, Y', strtotime($row['from_date'])),
    'to'      => date('M d, Y', strtotime($row['to_date'])),
    'days'    => $daysCount,
    'status'  => $row['status'],
    'applied' => date('M d, Y', strtotime($row['created_at'])),
    'reason'  => $row['reason']
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Leave History</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 25px; }
    .summary-card { position: relative; min-height: 126px; padding: 20px; background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); }
    .summary-card p { color: var(--text-muted); font-size: .78rem; font-weight: 600; text-transform: uppercase; margin-bottom: 7px; }
    .summary-card strong { font-size: 1.6rem; display: block; }
    .summary-card small { color: var(--text-muted); }
    .summary-icon { position: absolute; right: 18px; top: 20px; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; }
    .taken { background: #dcfce7; color: #15803d; }
    .pending-icon { background: #fef3c7; color: #b45309; }
    .rejected-icon { background: #fee2e2; color: #b91c1c; }
    .total-icon { background: #e0e7ff; color: var(--primary-color); }
    .filter-card { background: var(--card-bg); padding: 18px 20px; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); }
    .filter-container { display: flex; align-items: center; gap: 15px; justify-content: space-between; flex-wrap: wrap; }
    .search-box { display: flex; align-items: center; gap: 9px; flex: 1; max-width: 340px; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; }
    .search-box input { width: 100%; border: 0; outline: 0; color: var(--text-main); }
    .filter-select { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; outline: none; }
    .view-toggle { display: flex; border: 1px solid var(--border-color); border-radius: 7px; overflow: hidden; }
    .view-button { border: 0; background: #fff; color: var(--text-muted); padding: 8px 15px; cursor: pointer; }
    .view-button.active { color: var(--primary-color); background: #f5f3ff; font-weight: 600; }
    .export-button { padding: 8px 15px; background: var(--primary-color); color: #fff; border: none; border-radius: 6px; cursor: pointer; }
    .history-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05); padding: 20px; }
    .history-table { width: 100%; border-collapse: collapse; text-align: left; }
    .history-table th, .history-table td { padding: 13px 15px; border-bottom: 1px solid var(--border-color); font-size: .85rem; }
    .history-table th { background: #f9fafb; color: var(--text-muted); font-size: .75rem; text-transform: uppercase; }
    .leave-type { border: 1px solid var(--border-color); border-radius: 5px; padding: 3px 7px; font-size: .75rem; }
    .status-badge { padding: 4px 9px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
    .status-badge.approved { background: #dcfce7; color: #15803d; }
    .status-badge.pending { background: #fef3c7; color: #b45309; }
    .status-badge.rejected { background: #fee2e2; color: #b91c1c; }
    .action-link { border: 0; background: none; padding: 4px 7px; color: var(--primary-color); cursor: pointer; font-size: .8rem; }
    .action-link.cancel { color: #dc2626; }
    .table-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 15px; font-size: .8rem; color: var(--text-muted); }
    .pagination { display: flex; gap: 6px; }
    .pagination button { min-width: 28px; height: 28px; border: 1px solid var(--border-color); background: #fff; border-radius: 5px; cursor: pointer; }
    .pagination button.active { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
    .calendar-layout { display: grid; grid-template-columns: 1fr 285px; gap: 20px; }
    .calendar-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
    .calendar-nav { width: 34px; height: 34px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; cursor: pointer; }
    .calendar-weekdays, .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
    .calendar-weekdays span { padding: 10px; text-align: center; font-size: .72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
    .calendar-grid { border-top: 1px solid var(--border-color); border-left: 1px solid var(--border-color); }
    .calendar-day { min-height: 100px; padding: 8px; background: #fff; border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 4px; cursor: pointer; text-align: left; }
    .calendar-day.outside-month { background: #fafafa; color: #9ca3af; }
    .calendar-day:hover { background: #f5f3ff; }
    .calendar-day.selected-day { outline: 2px solid var(--primary-color); outline-offset: -2px; }
    .calendar-event { display: block; padding: 2px 4px; border-radius: 4px; font-size: .65rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .calendar-event.approved { background: #dcfce7; color: #15803d; }
    .calendar-event.pending { background: #fef3c7; color: #b45309; }
    .calendar-event.rejected { background: #fee2e2; color: #b91c1c; }
    .calendar-details { border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; background: #fff; }
    .leave-detail-card { padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; margin-top: 10px; }
    .leave-detail-card header { display: flex; justify-content: space-between; align-items: center; }
    .detail-type { font-size: .78rem; font-weight: 600; margin: 5px 0; }
    .detail-description { font-size: .75rem; color: var(--text-muted); }
    @media (max-width: 992px) { .summary-grid { grid-template-columns: repeat(2, 1fr); } .calendar-layout { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .summary-grid { grid-template-columns: 1fr; } .filter-container { flex-direction: column; align-items: stretch; } .search-box { max-width: none; } }
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
        <a href="request.php"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
        <a href="history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>

        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
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
        <h1>Leave History</h1>
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

      <div class="summary-grid">
        <div class="summary-card">
          <p>Total Taken</p>
          <strong><?= $totalTaken; ?> Days</strong> <small>this year</small>
          <div class="summary-icon taken"><i class="fa-solid fa-calendar-check"></i></div>
        </div>
        <div class="summary-card">
          <p>Pending Approval</p>
          <strong><?= $pendingCount; ?> Awaiting</strong> <small>review</small>
          <div class="summary-icon pending-icon"><i class="fa-solid fa-hourglass-half"></i></div>
        </div>
        <div class="summary-card">
          <p>Rejected Requests</p>
          <strong><?= $rejectedCount; ?> Requests</strong> <small>denied</small>
          <div class="summary-icon rejected-icon"><i class="fa-solid fa-circle-xmark"></i></div>
        </div>
        <div class="summary-card">
          <p>Total Requests</p>
          <strong><?= $totalRequests; ?> All</strong> <small>time</small>
          <div class="summary-icon total-icon"><i class="fa-solid fa-folder-open"></i></div>
        </div>
      </div>

      <div class="filter-card">
        <div class="filter-container">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search by Leave ID or Type...">
          </div>
          <select id="statusFilter" class="filter-select">
            <option value="ALL">All Statuses</option>
            <option value="APPROVED">Approved</option>
            <option value="PENDING">Pending</option>
            <option value="REJECTED">Rejected</option>
          </select>
          <select id="typeFilter" class="filter-select">
            <option value="ALL">All Types</option>
            <option value="Casual">Casual</option>
            <option value="Sick">Sick</option>
            <option value="Annual">Annual</option>
          </select>
          <div class="view-toggle">
            <button class="view-button active" data-view="calendar"><i class="fa-solid fa-calendar-days"></i> Grid</button>
            <button class="view-button" data-view="table"><i class="fa-solid fa-list"></i> Table</button>
          </div>
          <button id="exportButton" class="export-button"><i class="fa-solid fa-download"></i> Export</button>
        </div>
      </div>

      <div class="history-card">
        <div id="tableView" hidden>
          <table class="history-table">
            <thead>
              <tr>
                <th>Leave ID</th>
                <th>Type</th>
                <th>From</th>
                <th>To</th>
                <th>Days</th>
                <th>Status</th>
                <th>Applied On</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="historyTableBody"></tbody>
          </table>
          <div class="table-footer">
            <span id="resultInfo"></span>
            <div id="pagination" class="pagination"></div>
          </div>
        </div>

        <div id="calendarView">
          <div class="calendar-layout">
            <div class="calendar-panel">
              <div class="calendar-header">
                <button id="previousMonth" class="calendar-nav"><i class="fa-solid fa-chevron-left"></i></button>
                <h2 id="calendarMonth"></h2>
                <button id="nextMonth" class="calendar-nav"><i class="fa-solid fa-chevron-right"></i></button>
              </div>
              <div class="calendar-weekdays">
                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
              </div>
              <div id="calendarGrid" class="calendar-grid"></div>
              <div id="calendarEmpty" class="calendar-empty" hidden>No leave requests match filters.</div>
            </div>
            <div class="calendar-details">
              <h2 id="selectedDateHeading"></h2>
              <div id="calendarDetails"></div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script>
    const leaveHistoryData = <?php echo json_encode($leaveDataList); ?>;
  </script>
  <script src="history.js"></script>
  <script src="dashboard-script.js"></script>
</body>
</html>
