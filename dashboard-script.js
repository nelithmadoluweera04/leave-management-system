document.addEventListener("DOMContentLoaded", () => {
  const interactiveCards = document.querySelectorAll('.stat-card, .data-card');

  interactiveCards.forEach(card => {
    card.style.transition = "transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease";
    card.style.cursor = "pointer";

    card.addEventListener('mouseenter', () => {
      card.style.transform = "translateY(-6px) scale(1.02)";
      card.style.boxShadow = "0 12px 20px -5px rgba(79, 70, 229, 0.15), 0 8px 16px -8px rgba(0, 0, 0, 0.08)";
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = "translateY(0) scale(1)";
      card.style.boxShadow = "0 4px 6px -1px rgba(0, 0, 0, 0.05)";
    });
  });
});

function startDashboardClock() {
  const timeDisplay = document.getElementById('live-time');
  const dateDisplay = document.getElementById('live-date');
  
  if (!timeDisplay || !dateDisplay) return;

  setInterval(() => {
    const now = new Date();
    
    const timeString = now.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    });
    
    const dateString = now.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: '2-digit'
    });

    timeDisplay.textContent = timeString;
    dateDisplay.textContent = dateString;
  }, 1000);
}

document.addEventListener('DOMContentLoaded', startDashboardClock);

/**
 * Launches a custom modal window for alerts or choice confirmations
 * @param {string} title - Heading of the popup alert card
 * @param {string} text - Explanatory prompt message body text strings
 * @param {string} type - 'confirm', 'danger', or 'alert' formatting state rules
 * @param {function} callback - Execution method fired on success response
 */
function showPortalModal(title, text, type, hasInput, callback) {
  const overlay = document.createElement('div');
  overlay.className = 'portal-modal-overlay';
  
  let iconClass = 'fa-circle-question';
  let iconColorClass = '';
  let confirmBtnClass = 'modal-btn-confirm';
  let confirmText = 'Confirm';

  if (type === 'danger') {
    iconClass = 'fa-triangle-exclamation';
    iconColorClass = 'danger';
    confirmBtnClass = 'modal-btn-danger';
    confirmText = 'Yes, Proceed';
  } else if (type === 'alert') {
    iconClass = 'fa-circle-info';
    confirmText = 'OK';
  }

  overlay.innerHTML = `
    <div class="portal-modal-card">
      <div class="portal-modal-icon ${iconColorClass}">
        <i class="fa-solid ${iconClass}"></i>
      </div>
      <h3>${title}</h3>
      <p>${text}</p>
      
      <!-- DYNAMIC TEXT REASON ROW COMPONENT INPUT -->
      ${hasInput ? `
        <div style="margin-top: 15px; margin-bottom: 20px;">
          <textarea id="modalTextInput" rows="3" placeholder="Provide a reason or message for the employee..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; outline: none; background: #f9fafb; resize: none; font-family: inherit; box-sizing: border-box;"></textarea>
          <div id="modalInputError" style="color: #ef4444; font-size: 0.8rem; text-align: left; margin-top: 4px; display: none;">Please provide a reason before rejecting.</div>
        </div>
      ` : ''}

      <div class="portal-modal-actions">
        ${type !== 'alert' ? `<button class="modal-btn modal-btn-cancel" id="modalCancelBtn">Cancel</button>` : ''}
        <button class="modal-btn ${confirmBtnClass}" id="modalConfirmBtn">${confirmText}</button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);
  overlay.classList.add('active');

  const confirmBtn = overlay.querySelector('#modalConfirmBtn');
  const cancelBtn = overlay.querySelector('#modalCancelBtn');

  confirmBtn.addEventListener('click', () => {
    let resultValue = true;
    
    if (hasInput) {
      const inputElement = overlay.querySelector('#modalTextInput');
      const errorElement = overlay.querySelector('#modalInputError');
      resultValue = inputElement ? inputElement.value.trim() : '';
      
      if (resultValue === '') {
        if (errorElement) errorElement.style.display = 'block';
        if (inputElement) inputElement.style.borderColor = '#ef4444';
        return;
      }
    }
    
    closeModal();
    if (callback) callback(resultValue);
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => {
      closeModal();
      if (callback) callback(false);
    });
  }

  function closeModal() {
    overlay.classList.remove('active');
    overlay.remove();
  }
}


document.addEventListener("DOMContentLoaded", function () {
  const canvasElement = document.getElementById('dashboardBarChart');
  if (!canvasElement || !window.chartLabels) return;

  const ctx = canvasElement.getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: window.chartLabels,
      datasets: [{
        label: window.chartSeriesLabel,
        data: window.chartValues,
        backgroundColor: '#4f46e5',
        hoverBackgroundColor: '#4338ca',
        borderRadius: 6,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          position: 'top', 
          labels: { font: { family: 'sans-serif', size: 12, weight: '500' }, color: '#4b5563' } 
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 2, color: '#9ca3af' }, grid: { color: '#f3f4f6' } },
        x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
      }
    }
  });
});



