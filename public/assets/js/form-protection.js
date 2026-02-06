/**
 * Form Submission Protection
 * Prevents double-click/double-submission issues with visual feedback
 */

(function() {
    'use strict';

    // Track submitted forms to prevent duplicates
    const submittedForms = new WeakSet();

    /**
     * Add loading state to a button
     * @param {HTMLButtonElement} button 
     */
    function setButtonLoading(button) {
        if (!button) return;
        
        // Store original text
        button.dataset.originalText = button.textContent;
        button.dataset.originalHtml = button.innerHTML;
        
        // Disable and show loading state
        button.disabled = true;
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
        
        // Add spinner
        button.innerHTML = '<span class="btn-spinner"></span> ' + button.textContent;
    }

    /**
     * Remove loading state from a button
     * @param {HTMLButtonElement} button 
     */
    function removeButtonLoading(button) {
        if (!button) return;
        
        button.disabled = false;
        button.style.opacity = '';
        button.style.cursor = '';
        
        // Restore original content
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }

    /**
     * Debounce function to limit how often a function can be called
     * @param {Function} func 
     * @param {number} wait 
     * @returns {Function}
     */
    function debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Protect a form from double submission
     * @param {HTMLFormElement} form 
     */
    function protectForm(form) {
        if (!form || submittedForms.has(form)) {
            return; // Already protected
        }

        form.addEventListener('submit', function(e) {
            // Check if form was already submitted
            if (form.dataset.submitting === 'true') {
                e.preventDefault();
                console.log('Form submission prevented - already submitting');
                return false;
            }

            // Mark form as submitting
            form.dataset.submitting = 'true';

            // Find submit button and add loading state
            const submitButton = form.querySelector('button[type="submit"]') || 
                                form.querySelector('input[type="submit"]');
            
            if (submitButton) {
                setButtonLoading(submitButton);
            }

            // If form submission fails (validation error), reset after a delay
            setTimeout(function() {
                if (form.dataset.submitting === 'true') {
                    form.dataset.submitting = 'false';
                    if (submitButton) {
                        removeButtonLoading(submitButton);
                    }
                }
            }, 5000); // Reset after 5 seconds if still on page
        });

        submittedForms.add(form);
    }

    /**
     * Protect a button from double clicks
     * @param {HTMLButtonElement} button 
     * @param {number} delay Delay in milliseconds before re-enabling
     */
    function protectButton(button, delay = 2000) {
        if (!button) return;

        button.addEventListener('click', function(e) {
            if (button.disabled) {
                e.preventDefault();
                return false;
            }

            // Don't protect if it's a form submit button (handled by protectForm)
            if (button.type === 'submit') {
                return;
            }

            setButtonLoading(button);

            setTimeout(function() {
                removeButtonLoading(button);
            }, delay);
        });
    }

    /**
     * Initialize protection on all forms and critical buttons
     */
    function initializeProtection() {
        // Protect all forms
        document.querySelectorAll('form').forEach(function(form) {
            protectForm(form);
        });

        // Protect standalone buttons with data-protect attribute
        document.querySelectorAll('button[data-protect], a[data-protect]').forEach(function(button) {
            protectButton(button);
        });
    }

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeProtection);
    } else {
        initializeProtection();
    }

    // Expose functions globally for manual use
    window.FormProtection = {
        protectForm: protectForm,
        protectButton: protectButton,
        setButtonLoading: setButtonLoading,
        removeButtonLoading: removeButtonLoading,
        debounce: debounce
    };
})();
