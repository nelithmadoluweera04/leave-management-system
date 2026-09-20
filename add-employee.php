<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email'])) {
  header("Location: login-reg-index.php");
  exit();
}

if ($_SESSION['role'] !== 'admin') {
  header("Location: dashboard.php");
  exit();
}

$message = '';
$messageClass = '';

if (isset($_GET['delete_email'])) {
  $deleteEmail = trim($_GET['delete_email']);
  

  if ($deleteEmail === $_SESSION['email']) {
    $message = "You cannot delete your own active admin account!";
    $messageClass = "error-message";
  } 
  else {
    $deleteUser = $conn->prepare("DELETE FROM user WHERE email = ?");
    $deleteUser->bind_param("s", $deleteEmail);
    $deleteUser->execute();
    $deleteUser->close();
    
    $deleteAllowed = $conn->prepare("DELETE FROM allowed_emails WHERE email = ?");
    $deleteAllowed->bind_param("s", $deleteEmail);

    if ($deleteAllowed->execute()) {
      $message = "Employee authorization and account successfully removed.";
      $messageClass = "success-message";
    } else {
      $message = "Failed to remove employee from whitelist. Try again.";
      $messageClass = "error-message";
    }
    $deleteAllowed->close();
  }
}


$employees = $conn->query("
  SELECT ae.email, ae.role, u.id, u.name 
  FROM allowed_emails ae 
  LEFT JOIN user u ON ae.email = u.email 
  ORDER BY u.name ASC, ae.email ASC
");

if (isset($_POST['add_employee'])) {
  $email = trim($_POST['email']);
  $role = $_POST['role'];

  $checkUser = $conn->prepare("SELECT email FROM user WHERE email = ?");
  $checkUser->bind_param("s", $email);
  $checkUser->execute();
  $userResult = $checkUser->get_user_result ?? $checkUser->get_result();

  if ($userResult->num_rows > 0) {
    $message = "This email address is already fully registered in the user database!";
    $messageClass = "error-message";
  } else {
    // 2. ONLY insert into allowed_emails table. User is NOT added to 'user' table yet.
    $stmtAllowed = $conn->prepare("INSERT INTO allowed_emails (email, role) VALUES (?, ?)");
    $stmtAllowed->bind_param("ss", $email, $role);
    
    if ($stmtAllowed->execute()) {
      $message = "Email successfully authorized! The employee can now register their account using this email.";
      $messageClass = "success-message";
    } else {
      // Check if email already exists on the guest list
      if ($conn->errno == 1062) { 
        $message = "This email is already on the authorized whitelist invite list!";
      } else {
        $message = "Something went wrong. Please try again.";
      }
      $messageClass = "error-message";
    }
    $stmtAllowed->close();
  }
  $checkUser->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - Add Employee</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .form-wrapper {
      background: var(--card-bg);
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      max-width: 600px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 20px;
    }
    .form-group label {
      font-weight: 500;
      margin-bottom: 8px;
      color: var(--text-main);
    }
    .form-group input, .form-group select {
      padding: 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 0.95rem;
      outline: none;
      background-color: #f9fafb;
    }
    .form-group input:focus, .form-group select:focus {
      border-color: var(--primary-color);
    }
    .submit-btn {
      background-color: var(--primary-color);
      color: #fff;
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }
    .submit-btn:hover {
      background-color: var(--primary-hover);
    }
    .success-message {
      padding: 12px;
      background: #def7ec;
      border-radius: 6px;
      font-size: 0.95rem;
      color: #03543f;
      margin-bottom: 20px;
    }
    .error-message {
      padding: 12px;
      background: #f8d7da;
      border-radius: 6px;
      font-size: 0.95rem;
      color: #a42834;
      margin-bottom: 20px;
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
        <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="../request/request.html"><i class="fa-solid fa-plane-departure"></i> Apply Leave</a>
        <a href="../history/history.html"><i class="fa-solid fa-clock-rotate-left"></i> Leave History</a>
        <a href="add-employee.php" class="active"><i class="fa-solid fa-user-plus"></i> Manage Employees</a>
        <a href="../profile.html"><i class="fa-solid fa-user"></i> My Profile</a>
        <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
      </nav>
      <div class="sidebar-footer">
         <a href="#" onclick="confirmLogout(event)"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </aside>

    <main class="main-content">
      <header class="top-header">
        <div style="display: flex; align-items: center; gap: 15px;">
          <h1 id="page-view-title">Add New Employee Profile</h1>
          <button id="toggle-view-btn" class="submit-btn" style="padding: 6px 15px; font-size: 0.85rem; background-color: var(--text-muted);" onclick="toggleEmployeeView()">
            <i class="fa-solid fa-list"></i> Switch to Directory
          </button>
        </div>
        <div class="user-badge">
          <img src="https://unsplash.com" alt="User Avatar">
          <span><?= htmlspecialchars($_SESSION['name']); ?></span>
        </div>
      </header>

      <?php if(!empty($message)): ?>
          <div class="<?= $messageClass; ?>"><?= $message; ?></div>
      <?php endif; ?>

      <div id="add-employee-section" class="form-wrapper">
        <form action="add-employee.php" method="POST">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" placeholder="" required>
          </div>
          <div class="form-group">
            <label for="email">E-mail Address</label>
            <input type="email" id="email" name="email" placeholder="" required>
          </div>
          <div class="form-group">
            <label for="role">Assign System Role</label>
            <select id="role" name="role" required>
              <option value="employee" selected>Employee</option>
              <option value="manager">Manager</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group">
            <label for="password">Temporary Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>
          <button type="submit" name="add_employee" class="submit-btn">Create Employee Account</button>
        </form>
      </div>

      <!-- 2. REMOVE EMPLOYEE / DIRECTORY CONTAINER SECTION (Hidden by default) -->
      <div id="remove-employee-section" class="data-card" style="display: none;">
        <!-- DYNAMIC ROLE FILTER CONTROLLER -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
          <h2 class="card-title" style="margin: 0;">Active System Directory</h2>
          <div style="display: flex; align-items: center; gap: 10px;">
            <label for="role-filter" style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">Filter by Role:</label>
            <select id="role-filter" onchange="filterUserDirectory()" style="padding: 6px 12px; border: 1px solid var(--border-color); border-radius: 6px; background-color: #f9fafb; font-size: 0.9rem; outline: none; margin: 0; width: auto;">
              <option value="all">All Roles</option>
              <option value="admin">Admin Only</option>
              <option value="manager">Manager Only</option>
              <option value="employee">Employee Only</option>
            </select>
          </div>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th style="text-align: center;">Action</th>
              </tr>
            </thead>
            <tbody id="directory-table-body"> 
                <?php if($employees->num_rows > 0): ?>
                  <?php while($row = $employees->fetch_assoc()): ?>
                    <!-- Embedded data attribute helps JavaScript read roles directly -->
                    <tr class="user-row" data-role="<?= htmlspecialchars($row['role']); ?>">
                      <!-- If name is empty, it means they haven't registered on the register page yet -->
                      <td>
                        <strong><?= !empty($row['name']) ? htmlspecialchars($row['name']) : '<em style="color:var(--text-muted)">Pending Registration</em>'; ?></strong>
                      </td>
                      <td><?= htmlspecialchars($row['email']); ?></td>
                      <td>
                        <span class="status status-approved" style="text-transform: capitalize; background-color: #e0e7ff; color: #4f46e5;">
                          <?= htmlspecialchars($row['role']); ?>
                        </span>
                      </td>
                      <td style="text-align: center;">
                        <?php if(empty($row['id']) || $row['id'] !== $_SESSION['user_id']): ?>
                          <!-- Pass the email instead of ID to the delete trigger so it handles unregistered invites too -->
                          <a href="add-employee.php?delete_email=<?= urlencode($row['email']); ?>" 
                            style="color: #ef4444; font-size: 1rem;"
                            onclick="return confirm('Are you sure you want to remove this authorization/user?');">
                            <i class="fa-solid fa-trash-can"></i>
                          </a>
                        <?php else: ?>
                          <span style="color: var(--text-muted); font-size: 0.85rem; font-style: italic;">You (Active)</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr class="no-records-row">
                    <td colspan="4" style="text-align: center; color: var(--text-muted);">No employees whitelisted in the system.</td>
                  </tr>
                <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="add-employee.js"></script>
</body>
</html>
