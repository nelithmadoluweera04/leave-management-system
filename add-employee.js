function toggleEmployeeView() {
    const addSection = document.getElementById('add-employee-section');
    const removeSection = document.getElementById('remove-employee-section');
    const toggleBtn = document.getElementById('toggle-view-btn');
    const titleText = document.getElementById('page-view-title');

    if (addSection.style.display === 'none') {
      addSection.style.display = 'block';
      removeSection.style.display = 'none';
      titleText.innerText = "Add New Employee Profile";
      toggleBtn.innerHTML = '<i class="fa-solid fa-list"></i> Switch to Directory';
      toggleBtn.style.backgroundColor = 'var(--text-muted)';
    } else {
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

function confirmLogout(event) {
  event.preventDefault(); 
  
  const userConfirmed = confirm("Are you sure you want to log out of the system?");
  
  if (userConfirmed) {
    window.location.href = "logout.php";
  }
}

function openEditEmailModal(userId, currentEmail) {
  showPortalModal(
    'Update Employee Email',
    `Modify the active corporate system email address context below for <strong>${currentEmail}</strong>:`,
    'confirm',
    true,
    function(newEmailInput) {
      if (newEmailInput !== false && newEmailInput !== '' && newEmailInput !== currentEmail) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = 'add-employee.php';

        let idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'edit_user_id';
        idInput.value = userId;
        form.appendChild(idInput);

        let oldEmailInput = document.createElement('input');
        oldEmailInput.type = 'hidden';
        oldEmailInput.name = 'old_email';
        oldEmailInput.value = currentEmail;
        form.appendChild(oldEmailInput);

        let nextEmailInput = document.createElement('input');
        nextEmailInput.type = 'hidden';
        nextEmailInput.name = 'new_email';
        nextEmailInput.value = newEmailInput;
        form.appendChild(nextEmailInput);

        let submitFlag = document.createElement('input');
        submitFlag.type = 'hidden';
        submitFlag.name = 'update_employee_email';
        submitFlag.value = '1';
        form.appendChild(submitFlag);

        document.body.appendChild(form);
        form.submit();
      }
    }
  );

  setTimeout(() => {
    const modalInput = document.getElementById('modalTextInput');
    if (modalInput) {
      modalInput.placeholder = "new.email@company.com";
      modalInput.value = currentEmail;
      modalInput.rows = 1;
      modalInput.style.height = "auto";
    }
  }, 20);
}



