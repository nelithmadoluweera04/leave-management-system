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

/**
 * Launches a custom modal window for alerts or choice confirmations
 * @param {string} title - Heading of the popup alert card
 * @param {string} text - Explanatory prompt message body text strings
 * @param {string} type - 'confirm', 'danger', or 'alert' formatting state rules
 * @param {function} callback - Execution method fired on success response
 */
function showPortalModal(title, text, type, callback) {
  // Create and inject overlay structure container elements dynamically
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

  // Build the layout card inner body structure safely
  overlay.innerHTML = `
    <div class="portal-modal-card">
      <div class="portal-modal-icon ${iconColorClass}">
        <i class="fa-solid ${iconClass}"></i>
      </div>
      <h3>${title}</h3>
      <p>${text}</p>
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
    closeModal();
    if (callback) callback(true);
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

