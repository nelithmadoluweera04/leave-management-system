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
