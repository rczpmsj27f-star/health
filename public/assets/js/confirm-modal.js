// Custom confirmation modal
const ConfirmModal = {
    modalElement: null,
    
    init() {
        // Create modal HTML if not exists
        if (!document.getElementById('customConfirmModal')) {
            const modalHtml = `
                <div id="customConfirmModal" class="custom-modal" style="display: none;">
                    <div class="custom-modal-backdrop"></div>
                    <div class="custom-modal-content">
                        <div class="custom-modal-header">
                            <h3 id="confirmModalTitle">Confirm</h3>
                        </div>
                        <div class="custom-modal-body">
                            <p id="confirmModalMessage">Are you sure?</p>
                        </div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary" id="confirmModalCancel">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmModalConfirm">Confirm</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        this.modalElement = document.getElementById('customConfirmModal');
    },
    
    show(options) {
        return new Promise((resolve) => {
            this.init();
            
            const title = options.title || 'Confirm';
            const message = options.message || 'Are you sure?';
            const confirmText = options.confirmText || 'Confirm';
            const cancelText = options.cancelText || 'Cancel';
            const confirmClass = options.danger ? 'btn-danger' : 'btn-primary';
            
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModalConfirm').textContent = confirmText;
            document.getElementById('confirmModalConfirm').className = `btn ${confirmClass}`;
            document.getElementById('confirmModalCancel').textContent = cancelText;
            
            this.modalElement.style.display = 'flex';
            
            const handleConfirm = () => {
                this.hide();
                resolve(true);
            };
            
            const handleCancel = () => {
                this.hide();
                resolve(false);
            };
            
            document.getElementById('confirmModalConfirm').onclick = handleConfirm;
            document.getElementById('confirmModalCancel').onclick = handleCancel;
            document.querySelector('.custom-modal-backdrop').onclick = handleCancel;
        });
    },
    
    hide() {
        if (this.modalElement) {
            this.modalElement.style.display = 'none';
        }
    }
};

// Alert modal (single button)
const AlertModal = {
    show(options) {
        return new Promise((resolve) => {
            ConfirmModal.init();
            
            const title = options.title || 'Notice';
            const message = options.message || '';
            
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModalConfirm').textContent = 'OK';
            document.getElementById('confirmModalConfirm').className = 'btn btn-primary';
            document.getElementById('confirmModalCancel').style.display = 'none';
            
            ConfirmModal.modalElement.style.display = 'flex';
            
            document.getElementById('confirmModalConfirm').onclick = () => {
                document.getElementById('confirmModalCancel').style.display = '';
                ConfirmModal.hide();
                resolve();
            };
        });
    }
};

// Global helper functions
async function confirmAction(message, title = 'Confirm') {
    return ConfirmModal.show({ title, message });
}

async function showAlert(message, title = 'Notice') {
    return AlertModal.show({ title, message });
}
