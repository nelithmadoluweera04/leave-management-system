document.getElementById('leaveForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const requestData = {
    type: document.getElementById('leaveType').value,
    from: document.getElementById('fromDate').value,
    to: document.getElementById('toDate').value,
    reason: document.getElementById('reason').value
  };
  alert('Leave request submitted successfully!');
  e.target.reset();
});

function setMinToDate() {
      const fromDateVal = document.getElementById('fromDate').value;
      const toDateInput = document.getElementById('toDate');
      if (fromDateVal && toDateInput) {
        toDateInput.min = fromDateVal;
      }
    }

    function validateLeaveDates() {
      const fromDateVal = document.getElementById('fromDate').value;
      const toDateVal = document.getElementById('toDate').value;

      if (fromDateVal && toDateVal) {
        const fromDate = new Date(fromDateVal);
        const toDate = new Date(toDateVal);

        if (toDate < fromDate) {
          alert("Validation Error: 'To Date' cannot be earlier than your selected 'From Date'. Please pick a valid duration timeline.");
          return false;
        }
      }
      return true;
    }
