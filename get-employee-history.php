<?php
session_start();
require_once 'login-reg-config.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'manager') {
  echo '<p style="color:#ef4444; padding:10px;">Unauthorized Access</p>';
  exit();
}

if (isset($_GET['user_id'])) {
  $empId = intval($_GET['user_id']);
  
  $historyQuery = $conn->prepare("
    SELECT id, leave_type, from_date, to_date, status, rejection_reason 
    FROM leave_requests 
    WHERE user_id = ? 
    ORDER BY from_date DESC
  ");
  $historyQuery->bind_param("i", $empId);
  $historyQuery->execute();
  $result = $historyQuery->get_result();
  
  if ($result->num_rows > 0) {
    echo '<table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.85rem;">';
    echo '<thead style="background:#f9fafb;">';
    echo '<tr style="border-bottom:1px solid var(--border-color);">';
    echo '<th style="padding:8px;">ID</th><th style="padding:8px;">Type</th><th style="padding:8px;">Dates</th><th style="padding:8px;">Status</th>';
    echo '</tr></thead><tbody>';
    
    while ($row = $result->fetch_assoc()) {
      $cleanType = str_replace(' Leave', '', $row['leave_type']);
      $badgeClass = strtolower($row['status']);
      $dateRange = date('M d', strtotime($row['from_date'])) . ' - ' . date('M d, Y', strtotime($row['to_date']));
      
      echo "<tr style='border-bottom:1px solid var(--border-color);'>";
      echo "<td style='padding:10px 8px;'>LV-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT) . "</td>";
      echo "<td style='padding:10px 8px;'>$cleanType</td>";
      echo "<td style='padding:10px 8px;'>$dateRange</td>";
      echo "<td style='padding:10px 8px;'><span class='status-badge $badgeClass'>{$row['status']}</span></td>";
      echo "</tr>";
      
      if (!empty($row['rejection_reason'])) {
        echo "<tr><td colspan='4' style='padding:0 8px 8px 24px; color:#ef4444; font-size:0.78rem;'>";
        echo "<strong>Rejection Note:</strong> " . htmlspecialchars($row['rejection_reason']) . "</td></tr>";
      }
    }
    echo '</tbody></table>';
  } else {
    echo '<p style="color:var(--text-muted); font-style:italic; padding:15px; font-size:0.85rem;">This employee has no past leave requests.</p>';
  }
  $historyQuery->close();
}
?>
