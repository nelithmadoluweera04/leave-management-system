function toggleEmployeeView() {
    const addSection = document.getElementById('add-employee-section');
    const removeSection = document.getElementById('remove-employee-section');
    const toggleBtn = document.getElementById('toggle-view-btn');
    const titleText = document.getElementById('page-view-title');

    if (addSection.style.display === 'none') {
      // Revert back to Add Employee View Profile Window
      addSection.style.display = 'block';
      removeSection.style.display = 'none';
      titleText.innerText = "Add New Employee Profile";
      toggleBtn.innerHTML = '<i class="fa-solid fa-list"></i> Switch to Directory';
      toggleBtn.style.backgroundColor = 'var(--text-muted)';
    } else {
      // Render the Active Removal / Management Window View
      addSection.style.display = 'none';
      removeSection.style.display = 'block';
      titleText.innerText = "Remove Employee Accounts";
      toggleBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Add Form View';
      toggleBtn.style.backgroundColor = 'var(--primary-color)';
    }
}

function filterUserDirectory() {
    const targetFilterValue = document.getElementById('role-filter').value;
    const allTableRows = document.querySelectorAll('.user-row');
    let visibleRowCount = 0;

    allTableRows.forEach(row => {
      const userRole = row.getAttribute('data-role');
      
      if (targetFilterValue === 'all' || userRole === targetFilterValue) {
        row.style.display = '';
        visibleRowCount++;
      } else {
        row.style.display = 'none';
      }
    });

    // Check for an existing empty fallback row alert block
    let fallbackAlert = document.getElementById('empty-filter-fallback');
    
    if (visibleRowCount === 0) {
      if (!fallbackAlert) {
        fallbackAlert = document.createElement('tr');
        fallbackAlert.id = 'empty-filter-fallback';
        fallbackAlert.innerHTML = `<td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px;">No registered accounts match the selected role filter.</td>`;
        document.getElementById('directory-table-body').appendChild(fallbackAlert);
      }
    } else if (fallbackAlert) {
      fallbackAlert.remove();
    }
}
/**
 * Displays a popup confirmation window before securely logging out
 */
function confirmLogout(event) {
  // Prevent the default browser behavior of following the link instantly
  event.preventDefault(); 
  
  // Display standard browser confirmation popup
  const userConfirmed = confirm("Are you sure you want to log out of the system?");
  
  if (userConfirmed) {
    // If user clicks 'OK' (Yes), redirect them to the logout clean-up controller
    window.location.href = "logout.php";
  }
}


