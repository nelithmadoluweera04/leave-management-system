<?php
session_start();
if(!isset($_SESSION['email'])){
  header("Location: login-reg-index.php");
  exit();
}
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
        <a href="../request/request.html"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
        <a href="../history/history.html"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-plus"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="../profile.html"><i class="fa-solid fa-user"></i> My Profile</a>
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
          <img src="https://unsplash.com" alt="User Avatar">
          <span><?= htmlspecialchars($_SESSION['name']); ?></span>
        </div>
      </header>

      <section class="stats-grid">
        <div class="stat-card">
          <div class="stat-info">
            <h3>Available Balance</h3>
            <p>14 Days</p>
          </div>
          <div class="stat-icon balance">
            <i class="fa-solid fa-wallet"></i>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-info">
            <h3>Approved Leaves</h3>
            <p>8 Days</p>
          </div>
          <div class="stat-icon approved">
            <i class="fa-solid fa-circle-check"></i>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3>Pending Requests</h3>
            <p>1 Requests</p>
          </div>
          <div class="stat-icon pending">
            <i class="fa-solid fa-hourglass-half"></i>
          </div>
        </div>
      </section>

      <div class="content-grid">
        <section class="data-card">
          <h2 class="card-title">Recent Leave Requests</h2>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Leave Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Days</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Annual Leave</strong></td>
                  <td>Oct 12, 2026</td>
                  <td>Oct 14, 2026</td>
                  <td>3 Days</td>
                  <td><span class="status status-pending">Pending</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="data-card">
          <h2 class="card-title">Leave Allowance Breakdown</h2>
          <div class="leave-balance-list">
            <div class="balance-group">
              <div class="balance-item">
                <span>Annual Leave</span>
                <strong>0 / 15 Left</strong>
              </div>
              <div class="progress-bar-container">
                <div class="progress-bar annual-progress" style="width: 0%;"></div>
              </div>
            </div>
          </div>
        </section>
      </div>

    </main>
  </div>
  <script src="dashboard-script.js"></script>
</body>
</html>
