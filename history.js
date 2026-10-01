const pageSize = 6;
let currentPage = 1;
let activeView = 'calendar';

const latestLeave = leaveHistoryData.reduce((latest, leave) => new Date(leave.from) > latest ? new Date(leave.from) : latest, new Date());
let calendarDate = new Date(latestLeave.getFullYear(), latestLeave.getMonth(), 1);
let selectedDate = new Date(latestLeave.getFullYear(), latestLeave.getMonth(), latestLeave.getDate());

const tableBody = document.getElementById('historyTableBody'); 
const statusFilter = document.getElementById('statusFilter'); 
const typeFilter = document.getElementById('typeFilter'); 
const searchInput = document.getElementById('searchInput');

function filteredHistory() { 
  const query = searchInput.value.trim().toLowerCase(); 
  return leaveHistoryData.filter((leave) => {
    const matchesStatus = (statusFilter.value === 'ALL' || leave.status.toUpperCase() === statusFilter.value.toUpperCase());
    const matchesType = (typeFilter.value === 'ALL' || leave.type.toLowerCase().includes(typeFilter.value.toLowerCase()));
    const matchesSearch = (!query || leave.id.toLowerCase().includes(query) || leave.type.toLowerCase().includes(query));
    return matchesStatus && matchesType && matchesSearch;
  }); 
}

function render() { 
  const data = filteredHistory(); 
  const totalPages = Math.max(1, Math.ceil(data.length / pageSize)); 
  currentPage = Math.min(currentPage, totalPages); 
  
  const rows = data.slice((currentPage - 1) * pageSize, currentPage * pageSize); 
  
  tableBody.innerHTML = rows.length ? rows.map((leave) => `
    <tr>
      <td>${leave.id}</td>
      <td><span class="leave-type">${leave.type}</span></td>
      <td>${leave.from}</td>
      <td>${leave.to}</td>
      <td>${leave.days} Days</td>
      <td><span class="status-badge ${leave.status.toLowerCase()}">${leave.status}</span></td>
      <td>${leave.applied}</td>
      <td class="actions">
        <button class="action-link" onclick="alert('Reason for Leave:\\n${leave.reason.replace(/'/g, "\\'")}')">View</button>
        ${leave.status === 'Pending' ? `<button class="action-link cancel" data-cancel="\${leave.id}">Cancel</button>` : ''}
      </td>
    </tr>
  `).join('') : '<tr><td colspan="8" style="text-align:center; padding: 20px; color: var(--text-muted);">No leave requests found matching filters.</td></tr>'; 
  
  document.getElementById('resultInfo').textContent = `Showing ${data.length ? (currentPage - 1) * pageSize + 1 : 0} to ${Math.min(currentPage * pageSize, data.length)} of ${data.length} results`; 
  
  document.getElementById('pagination').innerHTML = `
    <button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}"><i class="fa-solid fa-chevron-left"></i></button>
    ${Array.from({length:totalPages}, (_, index) => `<button class="\${currentPage === index + 1 ? 'active' : ''}" data-page="\({index + 1}">\){index + 1}</button>`).join('')}
    <button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}"><i class="fa-solid fa-chevron-right"></i></button>
  `; 
  
  renderCalendar(); 
}

function dayKey(date) { 
  return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`; 
}

function leaveIncludesDate(leave, date) { 
  const start = new Date(leave.from); 
  const end = new Date(leave.to); 
  start.setHours(0, 0, 0, 0); 
  end.setHours(23, 59, 59, 999); 
  return date >= start && date <= end; 
}

function renderCalendar() {
  const year = calendarDate.getFullYear(); 
  const month = calendarDate.getMonth();
  const firstDay = new Date(year, month, 1); 
  const gridStart = new Date(year, month, 1 - firstDay.getDay());
  
  const data = filteredHistory(); 
  const grid = document.getElementById('calendarGrid');
  
  if (!grid) return;

  document.getElementById('calendarMonth').textContent = calendarDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  
  grid.innerHTML = Array.from({ length: 42 }, (_, index) => {
    const date = new Date(gridStart); 
    date.setDate(gridStart.getDate() + index);
    
    const events = data.filter((leave) => leaveIncludesDate(leave, date));
    const outside = date.getMonth() !== month ? ' outside-month' : '';
    
    return `
      <article class="calendar-day${outside}" data-date="${dayKey(date)}" role="button" tabindex="0">
        <span class="calendar-date">${date.getDate()}</span>
        ${events.map((leave) => `<span class="calendar-event \({leave.status.toLowerCase()}" title="\){leave.id}: \({leave.type} (\){leave.status})">\({leave.id} ·\){leave.type}</span>`).join('')}
      </article>
    `;
  }).join('');
  
  grid.querySelectorAll('.calendar-day').forEach((day) => {
    const isSelected = day.dataset.date === dayKey(selectedDate);
    day.classList.toggle('selected-day', isSelected);
    day.setAttribute('aria-pressed', isSelected);
  });
  
  renderCalendarDetails(data);
}

function renderCalendarDetails(data) {
  const dateLabel = selectedDate.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' });
  const leaves = data.filter((leave) => leaveIncludesDate(leave, selectedDate));
  
  const heading = document.getElementById('selectedDateHeading');
  const detailsBox = document.getElementById('calendarDetails');
  
  if (heading) heading.textContent = `Leaves on ${dateLabel}`;
  if (detailsBox) {
    detailsBox.innerHTML = leaves.length ? leaves.map((leave) => `
      <article class="leave-detail-card">
        <header>
          <strong>${leave.id}</strong>
          <span class="status-badge ${leave.status.toLowerCase()}">${leave.status}</span>
        </header>
        <p class="detail-type">${leave.type} Leave · ${leave.days} ${leave.days === 1 ? 'day' : 'days'}</p>
        <p class="detail-description">${leave.reason || `\({leave.type} leave request submitted on\){leave.applied}.`}</p>
      </article>
    `).join('') : '<p class="details-empty">No leave requests for this date.</p>';
  }
}

function setView(view) { 
  activeView = view; 
  document.getElementById('tableView').hidden = (view !== 'table'); 
  document.getElementById('calendarView').hidden = (view !== 'calendar'); 
  
  document.querySelectorAll('.view-button').forEach((button) => {
    button.classList.toggle('active', button.dataset.view === view);
  }); 
  
  if (view === 'calendar') renderCalendar(); 
}

if (statusFilter && typeFilter && searchInput) {
  [statusFilter, typeFilter, searchInput].forEach((control) => {
    control.addEventListener('input', () => { 
      currentPage = 1; 
      render(); 
    });
  });
}

document.getElementById('pagination').addEventListener('click', (event) => {
  const button = event.target.closest('button');
  if (button && button.dataset.page) {
    currentPage = Number(button.dataset.page);
    render();
  }
});

tableBody.addEventListener('click', (event) => {
  const cancelId = event.target.dataset.cancel;
  if (cancelId && confirm(`Are you completely sure you want to cancel request ${cancelId}?`)) {
    const rawNumericId = parseInt(cancelId.replace('LV-', ''), 10);
    window.location.href = `history.php?cancel_id=${rawNumericId}`;
  }
});

document.getElementById('exportButton').addEventListener('click', () => {
  const csvHeaders = 'Leave ID,Type,From,To,Days,Status,Applied On\n';
  const csvRows = filteredHistory().map((leave) => 
    `"${leave.id}","${leave.type}","${leave.from}","${leave.to}","${leave.days}","${leave.status}","${leave.applied}"`
  ).join('\n');
  
  const blob = new Blob([csvHeaders + csvRows], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const downloadLink = Object.assign(document.createElement('a'), {
    href: url,
    download: 'leave-history-report.csv'
  });
  
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
  URL.revokeObjectURL(url);
});

document.getElementById('previousMonth').addEventListener('click', () => {
  calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() - 1, 1);
  selectedDate = new Date(calendarDate);
  renderCalendar();
});

document.getElementById('nextMonth').addEventListener('click', () => {
  calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 1);
  selectedDate = new Date(calendarDate);
  renderCalendar();
});

document.getElementById('calendarGrid').addEventListener('click', (event) => {
  const dayCard = event.target.closest('.calendar-day');
  if (!dayCard) return;
  
  const [year, month, date] = dayCard.dataset.date.split('-').map(Number);
  selectedDate = new Date(year, month, date);
  renderCalendar();
});

document.getElementById('calendarGrid').addEventListener('keydown', (event) => {
  if (event.key !== 'Enter' && event.key !== ' ') return;
  const dayCard = event.target.closest('.calendar-day');
  if (!dayCard) return;
  
  event.preventDefault();
  const [year, month, date] = dayCard.dataset.date.split('-').map(Number);
  selectedDate = new Date(year, month, date);
  renderCalendar();
});

document.querySelectorAll('.view-button').forEach((button) => {
  button.addEventListener('click', () => setView(button.dataset.view));
});

render();
setView(activeView);
