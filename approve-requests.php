<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'manager') {
  header("Location: login-reg-index.php");
  exit();
}

$message = '';
$messageClass = '';

if (isset($_GET['action']) && isset($_GET['req_id'])) {
  $requestId = intval($_GET['req_id']);
  $actionStatus = ($_GET['action'] === 'approve') ? 'Approved' : 'Rejected';

  $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ? AND status = 'Pending'");
  $stmt->bind_param("si", $actionStatus, $requestId);
  
  if ($stmt->execute()) {
    $message = "Request row status modified to " . $actionStatus . " successfully.";
    $messageClass = "success-message";
  }
  $stmt->close();
}

$pendingRequests = $conn->query("
  SELECT lr.id, lr.leave_type, lr.from_date, lr.to_date, lr.reason, lr.created_at, lr.attachment, u.name AS employee_name, u.role AS employee_role
  FROM leave_requests lr
  JOIN user u ON lr.user_id = u.id
  WHERE lr.status = 'Pending'
  ORDER BY lr.id DESC
");

$displayPic = (isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default-avatar.png') ? 'uploads/' . $_SESSION['profile_pic'] : 'https://unsplash.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Review Requests</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .btn-approve { padding: 6px 12px; background: #def7ec; color: #03543f; border-radius: 4px; border:none; font-weight:600; cursor:pointer; text-decoration:none; font-size:0.8rem; margin-right:5px; }
    .btn-reject { padding: 6px 12px; background: #fee2e2; color: #a42834; border-radius: 4px; border:none; font-weight:600; cursor:pointer; text-decoration:none; font-size:0.8rem; }
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
          <a href="approve-requests.php" class="active"><i class="fa-solid fa-file-signature"></i> Approve Requests</a>
          <a href="leave-quotas.php"><i class="fa-solid fa-sliders"></i> Leave Quotas</a>
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
        <h1>Review Employee Leave Requests</h1>
        <div class="user-badge"><img src="<?= $displayPic; ?>"><span><?= htmlspecialchars($_SESSION['name']); ?></span></div>
      </header>

      <?php if(!empty($message)): ?><div class="<?= $messageClass; ?>"><?= $message; ?></div><?php endif; ?>

      <div class="filter-card" style="background: var(--card-bg); padding: 18px 20px; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,.05);">
        <div class="filter-container" style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <label for="type-filter" style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Leave Type:</label>
            <select id="type-filter" onchange="filterApprovalRequests()" style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; outline: none;">
              <option value="all">All Types</option>
              <option value="Casual">Casual</option>
              <option value="Sick">Sick</option>
              <option value="Annual">Annual</option>
            </select>
          </div>

          <div style="display: flex; align-items: center; gap: 10px;">
            <label for="role-filter" style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Employee Type:</label>
            <select id="role-filter" onchange="filterApprovalRequests()" style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: #fff; outline: none;">
              <option value="all">All Employees</option>
              <option value="employee">Standard Employee</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
      </div>

      <section class="data-card" style="background: var(--card-bg); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div class="table-wrapper">
          <table style="width:100%; border-collapse:collapse; text-align:left;">
            <thead>
              <tr style="border-bottom: 1px solid var(--border-color); background:#f9fafb;">
                <th style="padding:12px;">Employee</th>
                <th style="padding:12px;">Type</th>
                <th style="padding:12px;">Duration</th>
                <th style="padding:12px;">Reason</th>
                <th style="padding:12px;">Attachment</th>
                <th style="padding:12px; text-align:center;">Actions</th>
              </tr>
            </thead>
            <tbody id="approvalTableBody">
              <?php if ($pendingRequests->num_rows > 0): ?>
                <?php while ($row = $pendingRequests->fetch_assoc()): 
                  $cleanType = str_replace(' Leave', '', $row['leave_type']);
                  $days = (strtotime($row['to_date']) - strtotime($row['from_date'])) / (60 * 60 * 24) + 1;
                ?>
                  <tr class="request-row" data-type="<?= htmlspecialchars($cleanType); ?>" data-role="<?= htmlspecialchars($row['employee_role']); ?>" style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding:15px 12px;">
                      <strong><?= htmlspecialchars($row['employee_name']); ?></strong>
                      <br><small style="color: var(--text-muted); text-transform: capitalize;">(<?= htmlspecialchars($row['employee_role']); ?>)</small>
                    </td>
                    <td style="padding:15px 12px;"><span class="leave-type" style="border: 1px solid var(--border-color); border-radius: 5px; padding: 3px 7px; font-size: .75rem;"><?= htmlspecialchars($cleanType); ?></span></td>
                    <td style="padding:15px 12px; font-size:0.85rem;"><?= date('M d', strtotime($row['from_date'])); ?> - <?= date('M d', strtotime($row['to_date'])); ?> (<strong><?= $days; ?> Days</strong>)</td>
                    <td style="padding:15px 12px; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($row['reason']); ?>"><?= htmlspecialchars($row['reason']); ?></td>
                    <td>
                      <?php if (!empty($row['attachment'])): ?>
                        <a href="uploads/<?= htmlspecialchars($row['attachment']); ?>" download style="color: #4f46e5; font-weight: 600; text-decoration: none;" target="_blank">
                          <i class="fa-solid fa-file-arrow-down"></i> Download Document
                        </a>
                      <?php else: ?>
                        <span style="color: #9ca3af; font-style: italic;">No attachment</span>
                      <?php endif; ?>
                    </td>
                    <td style="padding:15px 12px; text-align:center; white-space:nowrap;">
                      <a href="#" class="btn-approve" onclick="event.preventDefault(); showPortalModal('Approve Request', 'Do you want to approve this leave request?', 'confirm', function(confirmed){ if(confirmed){ window.location.href='approve-requests.php?action=approve&req_id=<?= $row['id']; ?>'; } });">Approve</a>
                      <a href="#" class="btn-reject" onclick="event.preventDefault(); showPortalModal('Reject Request', 'Do you want to reject this leave request?', 'danger', function(confirmed){ if(confirmed){ window.location.href='approve-requests.php?action=reject&req_id=<?= $row['id']; ?>'; } });">Reject</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr class="no-records-fallback"><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">No pending leave requests require review.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>

  </div>
  <script>
    function filterApprovalRequests() {
      const selectedType = document.getElementById('type-filter').value;
      const selectedRole = document.getElementById('role-filter').value;
      const requestRows = document.querySelectorAll('.request-row');
      const tableBody = document.getElementById('approvalTableBody');
      let matchingRowCount = 0;

      requestRows.forEach(row => {
        const rowType = row.getAttribute('data-type');
        const rowRole = row.getAttribute('data-role');
        
        const typeMatches = (selectedType === 'all' || rowType === selectedType);
        const roleMatches = (selectedRole === 'all' || rowRole === selectedRole);

        if (typeMatches && roleMatches) {
          row.style.display = '';
          matchingRowCount++;
        } else {
          row.style.display = 'none';
        }
      });

      let fallbackRow = document.getElementById('filter-empty-alert');
      
      if (matchingRowCount === 0) {
        if (!fallbackRow) {
          fallbackRow = document.createElement('tr');
          fallbackRow.id = 'filter-empty-alert';
          fallbackRow.innerHTML = '<td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No pending leave requests match the selected filters.</td>';
          tableBody.appendChild(fallbackRow);
        }
      } else if (fallbackRow) {
        fallbackRow.remove();
      }
    }
  </script>
  <script src="dashboard-script.js"></script>
</body>
</html>
