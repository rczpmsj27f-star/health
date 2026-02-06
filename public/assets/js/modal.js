/**
 * Modal.js - Reusable confirmation/success modal with auto-close
 * Displays modals for 3 seconds with animated countdown progress bar
 */

/**
 * Show a success modal with auto-close
 * @param {string} message - The message to display
 * @param {number} duration - Duration in milliseconds (default: 3000)
 * @param {function} onClose - Optional callback when modal closes
 */
function showSuccessModal(message, duration = 3000, onClose = null) {
    showModal(message, 'success', duration, onClose);
}

/**
 * Show a confirmation modal with auto-close
 * @param {string} message - The message to display
 * @param {number} duration - Duration in milliseconds (default: 3000)
 * @param {function} onClose - Optional callback when modal closes
 */
function showConfirmationModal(message, duration = 3000, onClose = null) {
    showModal(message, 'confirmation', duration, onClose);
}

/**
 * Show an error modal with auto-close
 * @param {string} message - The message to display
 * @param {number} duration - Duration in milliseconds (default: 3000)
 * @param {function} onClose - Optional callback when modal closes
 */
function showErrorModal(message, duration = 3000, onClose = null) {
    showModal(message, 'error', duration, onClose);
}

/**
 * Show a generic modal with auto-close
 * @param {string} message - The message to display
 * @param {string} type - Modal type: 'success', 'error', 'confirmation', 'info'
 * @param {number} duration - Duration in milliseconds (default: 3000)
 * @param {function} onClose - Optional callback when modal closes
 */
function showModal(message, type = 'success', duration = 3000, onClose = null) {
    // Remove any existing modals
    const existingModal = document.getElementById('successModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Determine icon and class based on type
    let icon = '✓';
    let modalClass = 'success-modal';
    let bodyClass = 'success-modal-body';
    
    switch(type) {
        case 'error':
            icon = '✗';
            modalClass = 'error-modal';
            bodyClass = 'error-modal-body';
            break;
        case 'confirmation':
            icon = '✓';
            modalClass = 'confirmation-modal';
            bodyClass = 'confirmation-modal-body';
            break;
        case 'info':
            icon = 'ℹ';
            modalClass = 'info-modal';
            bodyClass = 'info-modal-body';
            break;
        default:
            icon = '✓';
            modalClass = 'success-modal';
            bodyClass = 'success-modal-body';
    }

    // Create modal HTML
    const modalHTML = `
        <div class="${modalClass}" id="successModal">
            <div class="${bodyClass}">
                <div class="modal-icon">${icon}</div>
                <div class="modal-message">${message}</div>
                <div class="modal-countdown-bar">
                    <div class="countdown-fill"></div>
                </div>
            </div>
        </div>
    `;

    // Insert modal into body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modal = document.getElementById('successModal');
    const countdownFill = modal.querySelector('.countdown-fill');

    // Trigger animation
    setTimeout(() => {
        modal.classList.add('show');
        countdownFill.style.animation = `countdown ${duration}ms linear`;
    }, 10);

    // Auto-close after duration
    setTimeout(() => {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
            if (onClose && typeof onClose === 'function') {
                onClose();
            }
        }, 300); // Wait for fade-out animation
    }, duration);
}

/**
 * Close modal manually
 */
function closeModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}
