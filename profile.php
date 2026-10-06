<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email'])) {
  header("Location: login-reg-index.php");
  exit();
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, role, phone, dob, address, profile_pic FROM user WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nameParts = explode(" ", $user['name'], 2);
$firstName = $nameParts[0];
$lastName  = isset($nameParts[1]) ? $nameParts[1] : '';

$leaveLimits = ['Annual Leave' => 14, 'Casual Leave' => 7, 'Sick Leave' => 10]; // System fallback guidelines

$checkQuotaTable = $conn->query("SHOW TABLES LIKE 'leave_quotas'");
if ($checkQuotaTable && $checkQuotaTable->num_rows > 0) {
  $quotaRes = $conn->query("SELECT * FROM leave_quotas");
  if ($quotaRes) {
    while ($qRow = $quotaRes->fetch_assoc()) {
      $leaveLimits[$qRow['leave_type']] = (int)$qRow['max_days'];
    }
  }
}

$leaveTaken = ['Annual Leave' => 0, 'Casual Leave' => 0, 'Sick Leave' => 0];

$checkRequestsTable = $conn->query("SHOW TABLES LIKE 'leave_requests'");
if ($checkRequestsTable && $checkRequestsTable->num_rows > 0) {
  $takenQuery = $conn->query("SELECT leave_type, SUM(DATEDIFF(to_date, from_date) + 1) AS days FROM leave_requests WHERE user_id = $userId AND status = 'Approved' GROUP BY leave_type");
  if ($takenQuery) {
    while ($row = $takenQuery->fetch_assoc()) {
      $type = $row['leave_type'];
      if (isset($leaveTaken[$type])) {
        $leaveTaken[$type] = (int)$row['days'];
      }
    }
  }
}

// 2. Dynamic Remaining Balances Context Calculation
$annualRem  = max(0, ($leaveLimits['Annual Leave'] ?? 14) - ($leaveTaken['Annual Leave'] ?? 0));
$casualRem  = max(0, ($leaveLimits['Casual Leave'] ?? 7) - ($leaveTaken['Casual Leave'] ?? 0));
$medicalRem = max(0, ($leaveLimits['Sick Leave'] ?? 10) - ($leaveTaken['Sick Leave'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
  $formFirst = trim($_POST['first_name']);
  $formLast  = trim($_POST['last_name']);
  $formPhone = trim($_POST['phone']);
  $formDob   = trim($_POST['dob']);
  $formAddr  = trim($_POST['address']);
  $newName   = $formFirst . ' ' . $formLast;
  
  $avatarName = $user['profile_pic'];

  if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image_upload']['tmp_name'];
    $fileName    = $_FILES['image_upload']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    if (in_array($fileExtension, $allowedExtensions)) {
      $avatarName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
      $uploadDir  = './uploads/';
      if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
      move_uploaded_file($fileTmpPath, $uploadDir . $avatarName);
    }
  }

  $updateStmt = $conn->prepare("UPDATE user SET name = ?, phone = ?, dob = ?, address = ?, profile_pic = ? WHERE id = ?");
  $updateStmt->bind_param("sssssi", $newName, $formPhone, $formDob, $formAddr, $avatarName, $userId);
  
  if ($updateStmt->execute()) {
    $_SESSION['name'] = $newName;
    $_SESSION['profile_pic'] = $avatarName;
    header("Location: profile.php");
    exit();
  }
  $updateStmt->close();
}
$displayPic = (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default-avatar.png') ? './uploads/' . $user['profile_pic'] : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&q=80';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeavePortal - My Profile</title>
  <link rel="stylesheet" href="dashboard-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 25px; align-items: start; }
    .card { background-color: var(--card-bg, #ffffff); border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .profile-summary-card { text-align: center; }
    .profile-avatar-container { position: relative; width: 120px; height: 120px; margin: 0 auto 15px; }
    .profile-avatar-container img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color, #4f46e5); }
    .upload-btn { position: absolute; bottom: 0; right: 0; background-color: var(--primary-color, #4f46e5); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s; }
    .upload-btn:hover { background-color: var(--primary-hover, #4338ca); }
    .designation { color: var(--text-muted, #6b7280); font-size: 0.9rem; margin-top: 4px; text-transform: capitalize; }
    .badge { display: inline-block; background-color: #e0e7ff; color: var(--primary-color, #4f46e5); font-size: 0.8rem; padding: 4px 10px; border-radius: 20px; margin-top: 10px; font-weight: 600; text-transform: uppercase; }
    .divider { border: 0; border-top: 1px solid var(--border-color, #e5e7eb); margin: 20px 0; }
    .quick-info { display: flex; flex-direction: column; gap: 12px; text-align: left; font-size: 0.9rem; color: var(--text-muted, #6b7280); }
    .quick-info .info-item { display: flex; align-items: center; gap: 10px; }
    .quick-info i { color: var(--primary-color, #4f46e5); width: 16px; text-align: center; }
    .profile-details-column { display: flex; flex-direction: column; gap: 25px; }
    .leave-cards-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .leave-card { padding: 15px; border-radius: 8px; text-align: center; }
    .leave-card.annual { background-color: #e0f2fe; color: #0369a1; }
    .leave-card.casual { background-color: #fef3c7; color: #b45309; }
    .leave-card.medical { background-color: #dcfce7; color: #15803d; }
    .leave-card h4 { font-size: 0.85rem; margin-bottom: 5px; font-weight: 600; }
    .leave-card .leave-count { font-size: 1.5rem; font-weight: 700; }
    .leave-card small { font-size: 0.75rem; opacity: 0.8; }
    .profile-tabs { display: flex; gap: 10px; border-bottom: 2px solid var(--border-color, #e5e7eb); }
    .tab-btn { background: none; border: none; padding: 10px 15px; font-size: 0.95rem; font-weight: 600; color: var(--text-muted, #6b7280); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .tab-btn.active { color: var(--primary-color, #4f46e5); border-bottom-color: var(--primary-color, #4f46e5); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Structural Form Row Controls */
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; text-align: left; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted, #6b7280); display: block; margin-bottom: 2px; }
    .form-group input { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 6px; font-size: 0.95rem; outline: none; background-color: #f9fafb; color: var(--text-main, #1f2937); }
    .form-group input:focus:not(:disabled) { border-color: var(--primary-color, #4f46e5); background-color: #fff; }

    .form-actions { display: flex; justify-content: flex-end; margin-top: 20px; }
    .btn { padding: 10px 20px; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
    .btn-primary { background-color: var(--primary-color, #4f46e5); color: white; }
    .btn-primary:hover { background-color: var(--primary-hover, #4338ca); }
    .btn-secondary { background-color: #e5e7eb; color: var(--text-main, #1f2937); }
    .btn-secondary:hover { background-color: #d1d5db; }
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
          <a href="company-status.php"><i class="fa-solid fa-users-viewfinder"></i> Company Status</a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="add-employee.php"><i class="fa-solid fa-user-gear"></i> Manage Employees</a>
        <?php endif; ?>

        <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> My Profile</a>
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
        <h1>My Profile</h1>
        <div class="user-badge">
          <img src="<?= $displayPic; ?>" alt="Avatar">
          <span><?= htmlspecialchars($user['name']); ?></span>
        </div>
      </header>

      <form id="profileForm" action="profile.php" method="POST" enctype="multipart/form-data">
        <div class="profile-grid">

          <div class="card profile-summary-card">
            <div class="profile-avatar-container">
              <img id="profileImage" src="<?= $displayPic; ?>" alt="Profile">
              <label for="imageUpload" class="upload-btn">
                <i class="fa-solid fa-camera"></i>
                <input type="file" id="imageUpload" name="image_upload" accept="image/*" style="display:none;" disabled>
              </label>
            </div>
            <h2 id="displayName" style="margin-top: 10px; font-size: 1.4rem; color: var(--text-main); font-weight: 700;"><?= htmlspecialchars($user['name']); ?></h2>
            <p class="designation"><?= htmlspecialchars($user['role']); ?></p>
            <span class="badge"><?= htmlspecialchars($user['role']); ?> Profile</span>
            
            <div class="divider"></div>
            
            <div class="quick-info">
              <div class="info-item"><i class="fa-solid fa-envelope"></i> <span id="displayEmail"><?= htmlspecialchars($user['email']); ?></span></div>
              <div class="info-item"><i class="fa-solid fa-phone"></i> <span id="displayPhone"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not Provided'; ?></span></div>
            </div>
          </div>

          <div class="profile-details-column">
            <div class="card leave-summary-card">
              <h3 style="font-weight: 700; color: #172b4d;">Leave Quota Summary</h3>
              <div class="leave-cards-container" style="margin-top: 15px;">
                <div class="leave-card annual"><h4>Annual Leave</h4><div class="leave-count"><?= $annualRem; ?> / <?= $leaveLimits['Annual Leave']; ?></div><small>Days Remaining</small></div>
                <div class="leave-card casual"><h4>Casual Leave</h4><div class="leave-count"><?= $casualRem; ?> / <?= $leaveLimits['Casual Leave']; ?></div><small>Days Remaining</small></div>
                <div class="leave-card medical"><h4>Medical Leave</h4><div class="leave-count"><?= $medicalRem; ?> / <?= $leaveLimits['Sick Leave']; ?></div><small>Days Remaining</small></div>
              </div>
            </div>

            <div class="card account-form-card">
              <div class="profile-tabs">
                <button type="button" class="tab-btn active" onclick="openTab(event, 'personal')">Personal Info</button>
                <button type="button" class="tab-btn" onclick="openTab(event, 'employment')">Employment Details</button>
              </div>

              <div id="personal" class="tab-content active" style="margin-top: 20px;">
                <div class="form-row">
                  <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="firstName" name="first_name" value="<?= htmlspecialchars($firstName); ?>" disabled required>
                  </div>
                  <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="lastName" name="last_name" value="<?= htmlspecialchars($lastName); ?>" disabled>
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']); ?>" disabled style="background-color: #f3f4f6; color: #374151; cursor: not-allowed;">
                  </div>
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']); ?>" disabled placeholder="e.g. +1 234 567 890">
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($user['dob']); ?>" disabled>
                  </div>
                  <div class="form-group">
                    <label>Address</label>
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address']); ?>" disabled placeholder="e.g. 123 Main St, Tech City">
                  </div>
                </div>
              </div>

              <div id="employment" class="tab-content" style="margin-top: 20px;">
                <div class="form-row">
                  <div class="form-group">
                    <label>Employee ID</label>
                    <input type="text" value="EMP-<?= str_pad($userId, 4, '0', STR_PAD_LEFT); ?>" disabled style="background-color: #f3f4f6; color: #374151; cursor: not-allowed;">
                  </div>
                  <div class="form-group">
                    <label>System Designation</label>
                    <input type="text" style="text-transform: capitalize; background-color: #f3f4f6; color: #374151; cursor: not-allowed;" value="<?= htmlspecialchars($user['role']); ?>" disabled>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Employment Status</label>
                    <input type="text" value="Active Permanent Staff" disabled style="background-color: #f3f4f6; color: #374151; cursor: not-allowed;">
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button type="button" id="editBtn" class="btn btn-secondary"><i class="fa-solid fa-user-pen"></i> Edit Profile</button>
                <button type="submit" id="saveBtn" name="save_profile" class="btn btn-primary" style="display: none;"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
              </div>
            </div>

          </div>
        </div>
      </form>
    </main>

  </div>
  <script src="dashboard-script.js"></script>
  <script>
    function openTab(evt, tabName) {
      var i, tabcontent, tablinks;
      tabcontent = document.getElementsByClassName("tab-content");
      for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
        tabcontent[i].style.display = "none";
      }
      tablinks = document.getElementsByClassName("tab-btn");
      for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
      }
      document.getElementById(tabName).style.display = "block";
      document.getElementById(tabName).classList.add("active");
      evt.currentTarget.classList.add("active");
    }

    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const imageUpload = document.getElementById('imageUpload');
    const formInputs = document.querySelectorAll('#personal input:not([type="email"])');

    if(editBtn && saveBtn) {
      editBtn.addEventListener('click', () => {
        formInputs.forEach(input => input.removeAttribute('disabled'));
        if(imageUpload) imageUpload.removeAttribute('disabled');
        saveBtn.style.display = 'flex';
        editBtn.style.display = 'none';
      });
    }
  </script>
</body>
</html>
