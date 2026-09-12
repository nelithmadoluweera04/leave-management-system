const leaveHistoryData = [
  { id:'LV-011', type:'Casual', from:'Aug 20, 2026', to:'Aug 21, 2026', days:2, status:'Pending', applied:'Aug 12, 2026' }, { id:'LV-007', type:'Sick', from:'Aug 11, 2026', to:'Aug 12, 2026', days:2, status:'Pending', applied:'Aug 08, 2026' }, { id:'LV-003', type:'Annual', from:'Aug 05, 2026', to:'Aug 09, 2026', days:5, status:'Pending', applied:'Jul 25, 2026' }, { id:'LV-002', type:'Casual', from:'Jul 15, 2026', to:'Jul 16, 2026', days:2, status:'Approved', applied:'Jul 10, 2026' }, { id:'LV-001', type:'Sick', from:'Jul 01, 2026', to:'Jul 03, 2026', days:3, status:'Approved', applied:'Jun 28, 2026' }, { id:'LV-004', type:'Sick', from:'Jun 10, 2026', to:'Jun 10, 2026', days:1, status:'Approved', applied:'Jun 10, 2026' }, { id:'LV-006', type:'Annual', from:'May 18, 2026', to:'May 20, 2026', days:3, status:'Rejected', applied:'May 11, 2026' }, { id:'LV-005', type:'Casual', from:'Apr 08, 2026', to:'Apr 09, 2026', days:2, status:'Approved', applied:'Apr 02, 2026' }, { id:'LV-008', type:'Annual', from:'Mar 12, 2026', to:'Mar 14, 2026', days:3, status:'Approved', applied:'Mar 01, 2026' }, { id:'LV-009', type:'Sick', from:'Feb 05, 2026', to:'Feb 06, 2026', days:2, status:'Approved', applied:'Feb 04, 2026' }, { id:'LV-010', type:'Casual', from:'Jan 22, 2026', to:'Jan 22, 2026', days:1, status:'Approved', applied:'Jan 17, 2026' }
];
const pageSize = 6;
let currentPage = 1;
let activeView = 'calendar';
const latestLeave = leaveHistoryData.reduce((latest, leave) => new Date(leave.from) > latest ? new Date(leave.from) : latest, new Date(0));
let calendarDate = new Date(latestLeave.getFullYear(), latestLeave.getMonth(), 1);
let selectedDate = new Date(latestLeave.getFullYear(), latestLeave.getMonth(), latestLeave.getDate());
const tableBody = document.getElementById('historyTableBody'); const statusFilter = document.getElementById('statusFilter'); const typeFilter = document.getElementById('typeFilter'); const searchInput = document.getElementById('searchInput');
function filteredHistory() { const query = searchInput.value.trim().toLowerCase(); return leaveHistoryData.filter((leave) => (statusFilter.value === 'ALL' || leave.status === statusFilter.value) && (typeFilter.value === 'ALL' || leave.type === typeFilter.value) && (!query || leave.id.toLowerCase().includes(query) || leave.type.toLowerCase().includes(query))); }
function render() { const data = filteredHistory(); const totalPages = Math.max(1, Math.ceil(data.length / pageSize)); currentPage = Math.min(currentPage, totalPages); const rows = data.slice((currentPage - 1) * pageSize, currentPage * pageSize); tableBody.innerHTML = rows.length ? rows.map((leave) => `<tr><td>${leave.id}</td><td><span class="leave-type">${leave.type}</span></td><td>${leave.from}</td><td>${leave.to}</td><td>${leave.days}</td><td><span class="status-badge ${leave.status.toLowerCase()}">${leave.status}</span></td><td>${leave.applied}</td><td class="actions"><button class="action-link">View</button>${leave.status === 'Pending' ? `<button class="action-link cancel" data-cancel="${leave.id}">Cancel</button>` : ''}</td></tr>`).join('') : '<tr><td colspan="8" style="text-align:center">No leave requests found.</td></tr>'; document.getElementById('resultInfo').textContent = `Showing ${data.length ? (currentPage - 1) * pageSize + 1 : 0} to ${Math.min(currentPage * pageSize, data.length)} of ${data.length} results`; document.getElementById('pagination').innerHTML = `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}"><i class="fa-solid fa-chevron-left"></i></button>${Array.from({length:totalPages}, (_, index) => `<button class="${currentPage === index + 1 ? 'active' : ''}" data-page="${index + 1}">${index + 1}</button>`).join('')}<button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}"><i class="fa-solid fa-chevron-right"></i></button>`; renderCalendar(); }
function dayKey(date) { return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`; }
function leaveIncludesDate(leave, date) { const start = new Date(leave.from); const end = new Date(leave.to); start.setHours(0, 0, 0, 0); end.setHours(23, 59, 59, 999); return date >= start && date <= end; }
function renderCalendar() {
  const year = calendarDate.getFullYear(); const month = calendarDate.getMonth();
  const firstDay = new Date(year, month, 1); const gridStart = new Date(year, month, 1 - firstDay.getDay());
  const data = filteredHistory(); const grid = document.getElementById('calendarGrid');
  document.getElementById('calendarMonth').textContent = calendarDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  document.getElementById('calendarEmpty').hidden = data.length !== 0;
  grid.innerHTML = Array.from({ length: 42 }, (_, index) => {
    const date = new Date(gridStart); date.setDate(gridStart.getDate() + index);
    const events = data.filter((leave) => leaveIncludesDate(leave, date));
    const outside = date.getMonth() !== month ? ' outside-month' : '';
    return `<article class="calendar-day${outside}" aria-label="${date.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}"><span class="calendar-date">${date.getDate()}</span>${events.map((leave) => `<span class="calendar-event ${leave.status.toLowerCase()}" title="${leave.id}: ${leave.type} (${leave.status})">${leave.id} · ${leave.type}</span>`).join('')}</article>`;
  }).join('');
  grid.querySelectorAll('.calendar-day').forEach((day, index) => {
    const date = new Date(gridStart); date.setDate(gridStart.getDate() + index);
    const selected = dayKey(date) === dayKey(selectedDate);
    day.dataset.date = dayKey(date);
    day.classList.toggle('selected-day', selected);
    day.setAttribute('tabindex', '0');
    day.setAttribute('role', 'button');
    day.setAttribute('aria-pressed', selected);
  });
  renderCalendarDetails(data);
}
function renderCalendarDetails(data) {
  const dateLabel = selectedDate.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' });
  const leaves = data.filter((leave) => leaveIncludesDate(leave, selectedDate));
  document.getElementById('selectedDateHeading').textContent = `Leaves on ${dateLabel}`;
  document.getElementById('calendarDetails').innerHTML = leaves.length ? leaves.map((leave) => `<article class="leave-detail-card"><header><strong>${leave.id}</strong><span class="status-badge ${leave.status.toLowerCase()}">${leave.status}</span></header><p class="detail-type">${leave.type} Leave · ${leave.days} ${leave.days === 1 ? 'day' : 'days'}</p><p class="detail-description">${leave.description || `${leave.type} leave request submitted on ${leave.applied}.`}</p></article>`).join('') : '<p class="details-empty">No leave requests for this date.</p>';
}
function setView(view) { activeView = view; document.getElementById('tableView').hidden = view !== 'table'; document.getElementById('calendarView').hidden = view !== 'calendar'; document.querySelectorAll('.view-button').forEach((button) => button.classList.toggle('active', button.dataset.view === view)); if (view === 'calendar') renderCalendar(); }
function updateSummary() { document.getElementById('pendingCount').textContent = leaveHistoryData.filter((leave) => leave.status === 'Pending').length; document.getElementById('rejectedCount').textContent = leaveHistoryData.filter((leave) => leave.status === 'Rejected').length; document.getElementById('totalRequests').textContent = leaveHistoryData.length; document.getElementById('totalTaken').textContent = leaveHistoryData.filter((leave) => leave.status === 'Approved').reduce((sum, leave) => sum + leave.days, 0); }
[statusFilter, typeFilter, searchInput].forEach((control) => control.addEventListener('input', () => { currentPage = 1; render(); })); document.getElementById('pagination').addEventListener('click', (event) => { const page = event.target.closest('button')?.dataset.page; if (page) { currentPage = Number(page); render(); } }); tableBody.addEventListener('click', (event) => { const cancelId = event.target.dataset.cancel; if (cancelId && confirm(`Cancel request ${cancelId}?`)) { leaveHistoryData.splice(leaveHistoryData.findIndex((leave) => leave.id === cancelId), 1); updateSummary(); render(); } }); document.getElementById('exportButton').addEventListener('click', () => { const csv = ['Leave ID,Type,From,To,Days,Status,Applied On', ...filteredHistory().map((leave) => Object.values(leave).join(','))].join('\n'); const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([csv], {type:'text/csv'})), download:'leave-history.csv' }); link.click(); URL.revokeObjectURL(link.href); }); document.getElementById('previousMonth').addEventListener('click', () => { calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() - 1, 1); renderCalendar(); }); document.getElementById('nextMonth').addEventListener('click', () => { calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 1); renderCalendar(); }); document.querySelectorAll('.view-button').forEach((button) => button.addEventListener('click', () => setView(button.dataset.view))); updateSummary(); render(); setView(activeView);

document.getElementById('calendarGrid').addEventListener('click', (event) => {
  const day = event.target.closest('.calendar-day');
  if (!day) return;
  const [year, month, date] = day.dataset.date.split('-').map(Number);
  selectedDate = new Date(year, month, date);
  renderCalendar();
});
document.getElementById('calendarGrid').addEventListener('keydown', (event) => {
  if (event.key !== 'Enter' && event.key !== ' ') return;
  const day = event.target.closest('.calendar-day');
  if (!day) return;
  event.preventDefault();
  const [year, month, date] = day.dataset.date.split('-').map(Number);
  selectedDate = new Date(year, month, date);
  renderCalendar();
});
document.getElementById('previousMonth').addEventListener('click', () => {
  selectedDate = new Date(calendarDate);
  renderCalendar();
});
document.getElementById('nextMonth').addEventListener('click', () => {
  selectedDate = new Date(calendarDate);
  renderCalendar();
});
