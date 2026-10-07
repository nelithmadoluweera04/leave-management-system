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
  
  if (tableBody) {
    tableBody.innerHTML = rows.length ? rows.map((leave) => {
      const numericId = leave.id.replace('LV-', '');
      const rejectNote = leave.status === 'Rejected' ? (leave.reject_msg || 'No reason provided.') : '';

      return `
        <tr>
          <td>${leave.id}</td>
          <td><span class="leave-type">${leave.type}</span></td>
          <td>${leave.from}</td>
          <td>${leave.to}</td>
          <td>${leave.days} Days</td>
          <td><span class="status-badge ${leave.status.toLowerCase()}">${leave.status}</span></td>
          <td>${leave.applied}</td>
          <td class="actions" style="white-space: nowrap;">
            <button type="button" class="action-link view-details-btn" 
                    data-reason="${leave.reason.replace(/"/g, '&quot;')}" 
                    data-status="${leave.status}" 
                    data-reject-msg="${rejectNote.replace(/"/g, '&quot;')}">View</button>
            
            ${leave.status === 'Pending' ? `
              <button type="button" class="action-link cancel" onclick="openCancelModal('${numericId}', '${leave.id}')">Cancel</button>
            ` : `
              <span style="color:#9ca3af; font-style:italic; font-size:0.8rem;">Locked</span>
            `}
          </td>
        </tr>
      `;
    }).join('') : '<tr><td colspan="8" style="text-align:center; padding: 20px; color: var(--text-muted);">No leave requests found matching filters.</td></tr>';
    
    bindViewDetailsEvents();
  }
  
  const resultInfoEl = document.getElementById('resultInfo');
  if (resultInfoEl) {
    resultInfoEl.textContent = `Showing ${data.length ? (currentPage - 1) * pageSize + 1 : 0} to ${Math.min(currentPage * pageSize, data.length)} of ${data.length} results`; 
  }
  
  const paginationEl = document.getElementById('pagination');
  if (paginationEl) {
    let paginationHtml = `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}"><i class="fa-solid fa-chevron-left"></i></button>`;
    for (let i = 1; i <= totalPages; i++) {
      paginationHtml += `<button class="${currentPage === i ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }
    paginationHtml += `<button ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}"><i class="fa-solid fa-chevron-right"></i></button>`;
    paginationEl.innerHTML = paginationHtml;
  }
  
  renderCalendar(); 
}

function bindViewDetailsEvents() {
  const viewButtons = document.querySelectorAll('.view-details-btn');
  
  viewButtons.forEach(button => {
    button.addEventListener('click', function() {
      const reason = this.getAttribute('data-reason');
      const status = this.getAttribute('data-status');
      const rejectMsg = this.getAttribute('data-reject-msg');
      
      let contentMarkup = `<strong>My Application Reason:</strong><br>${reason}`;
      
      if (status === 'Rejected') {
        contentMarkup += `<br><br><strong style="color:#ef4444">Manager Rejection Note:</strong><br>${rejectMsg}`;
      }

      showPortalModal('Leave Request Details', contentMarkup, 'alert', false);
    });
  });
}

function openCancelModal(rawId, displayId) {
  showPortalModal(
    'Cancel Leave Request', 
    `Are you completely sure you want to retract and cancel your pending request <strong>${displayId}</strong>? This action cannot be undone.`, 
    'danger', 
    false,
    function(confirmed) {
      if (confirmed) {
        window.location.href = `history.php?cancel_id=${rawId}`;
      }
    }
  );
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
      <div class="calendar-day${outside}" data-date="${dayKey(date)}" style="cursor:pointer;">
        <span class="day-number" style="font-weight:600; font-size:0.8rem;">${date.getDate()}</span>
        ${events.map((leave) => `<span class="calendar-event ${leave.status.toLowerCase()}" style="display:block; padding:2px 4px; margin-top:2px; border-radius:4px; font-size:0.68rem; font-weight:600;" title="${leave.id}: ${leave.type}">${leave.type}</span>`).join('')}
      </div>
    `;
  }).join('');
  
  grid.querySelectorAll('.calendar-day').forEach((day) => {
    const isSelected = day.dataset.date === dayKey(selectedDate);
    day.classList.toggle('selected-day', isSelected);
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
      <div class="leave-detail-card" style="padding:12px; border:1px solid var(--border-color); border-radius:8px; margin-top:10px; background:#fafafa;">
        <header style="display:flex; justify-content:between; align-items:center;">
          <strong>${leave.id}</strong>
          <span class="status-badge ${leave.status.toLowerCase()}">${leave.status}</span>
        </header>
        <p class="detail-type" style="font-size:0.8rem; font-weight:700; margin-top:4px;">${leave.type} Leave · ${leave.days} Days</p>
        <p class="detail-description" style="font-size:0.75rem; color:var(--text-muted); line-height:1.4; margin-top:4px;">${leave.reason}</p>
      </div>
    `).join('') : '<p class="details-empty" style="color:#9ca3af; font-style:italic; font-size:0.85rem;">No leave requests for this date.</p>';
  }
}

function setView(view) { 
  activeView = view; 
  const tv = document.getElementById('tableView');
  const cv = document.getElementById('calendarView');
  if(tv) tv.hidden = (view !== 'table'); 
  if(cv) cv.hidden = (view !== 'calendar'); 
  
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

const paginationEl = document.getElementById('pagination');
if (paginationEl) {
  paginationEl.addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (button && button.dataset.page) {
      currentPage = Number(button.dataset.page);
      render();
    }
  });
}

const exportBtn = document.getElementById('exportButton');
if (exportBtn) {
  exportBtn.addEventListener('click', () => {
    const csvHeaders = 'Leave ID,Type,From,To,Days,Status\n';
    const csvRows = filteredHistory().map((leave) => 
      `"${leave.id}","${leave.type}","${leave.from}","${leave.to}","${leave.days}","${leave.status}"`
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
}

const prevBtn = document.getElementById('previousMonth');
if (prevBtn) {
prevBtn.addEventListener('click', () => {
  calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() - 1, 1);
  selectedDate = new Date(calendarDate);
  renderCalendar();
});
}
const nextBtn = document.getElementById('nextMonth');
if (nextBtn) {
  nextBtn.addEventListener('click', () => {
  calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 1);
  selectedDate = new Date(calendarDate);
  renderCalendar();
});
}
const gridEl = document.getElementById('calendarGrid');
if (gridEl) {
  gridEl.addEventListener('click', (event) => {
  const dayCard = event.target.closest('.calendar-day');
  if (!dayCard) return;
  const [year, month, date] = dayCard.dataset.date.split('-').map(Number);
  selectedDate = new Date(year, month, date);
  renderCalendar();
  });
}
document.querySelectorAll('.view-button').forEach((button) => {
  button.addEventListener('click', () => setView(button.dataset.view));
});

document.addEventListener("DOMContentLoaded", () => {
render();
setView(activeView);
});