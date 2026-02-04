// API Configuration
const API_URL = 'http://localhost:3000/api';

// State Management
let medications = [];
let settings = {
    notifyAtTime: true,
    notifyAfter10Min: true,
    notifyAfter20Min: true,
    notifyAfter30Min: true,
    notifyAfter60Min: false
};
let currentEditingId = null;

// Service Worker Registration
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(registration => {
            console.log('Service Worker registered:', registration);
        })
        .catch(error => {
            console.error('Service Worker registration failed:', error);
        });
}

// Initialize app
document.addEventListener('DOMContentLoaded', async () => {
    await loadMedications();
    await loadSettings();
    checkNotificationPermission();
    setupEventListeners();
    renderMedications();
    updateNotificationUI();
});

// Event Listeners
function setupEventListeners() {
    // Navigation
    document.getElementById('settingsBtn').addEventListener('click', showSettings);
    document.getElementById('addMedBtn').addEventListener('click', showAddMedication);
    document.getElementById('backFromAddBtn').addEventListener('click', showHome);
    document.getElementById('backFromSettingsBtn').addEventListener('click', showHome);
    document.getElementById('cancelMedBtn').addEventListener('click', showHome);
    
    // Medication form
    document.getElementById('medicationForm').addEventListener('submit', handleMedicationSubmit);
    document.getElementById('addTimeBtn').addEventListener('click', addTimeInput);
    
    // Settings
    document.getElementById('enableNotificationsBtn').addEventListener('click', requestNotificationPermission);
    
    // Settings toggles
    ['notifyAtTime', 'notifyAfter10Min', 'notifyAfter20Min', 'notifyAfter30Min', 'notifyAfter60Min'].forEach(id => {
        document.getElementById(id).addEventListener('change', handleSettingChange);
    });
}

// View Navigation
function showView(viewId) {
    document.querySelectorAll('.view').forEach(view => view.classList.remove('active'));
    document.getElementById(viewId).classList.add('active');
}

function showHome() {
    showView('homeView');
    currentEditingId = null;
}

function showSettings() {
    showView('settingsView');
}

function showAddMedication(medicationId = null) {
    currentEditingId = medicationId;
    
    // Reset form
    document.getElementById('medicationForm').reset();
    document.getElementById('scheduledTimes').innerHTML = '';
    
    if (medicationId) {
        // Edit mode
        const medication = medications.find(m => m.id === medicationId);
        if (medication) {
            document.getElementById('addMedicationTitle').textContent = 'Edit Medication';
            document.getElementById('medName').value = medication.name;
            document.getElementById('medDose').value = medication.dose || '';
            document.getElementById('medInstructions').value = medication.instructions || '';
            
            // Add time inputs
            medication.scheduledTimes.forEach(time => addTimeInput(time));
        }
    } else {
        // Add mode
        document.getElementById('addMedicationTitle').textContent = 'Add Medication';
        addTimeInput(); // Add one empty time input
    }
    
    showView('addMedicationView');
}

// Medication Management
async function loadMedications() {
    try {
        const response = await fetch(`${API_URL}/medications`);
        if (response.ok) {
            medications = await response.json();
        } else {
            // Fallback to localStorage
            const stored = localStorage.getItem('medications');
            medications = stored ? JSON.parse(stored) : [];
        }
    } catch (error) {
        console.log('Using localStorage for medications');
        const stored = localStorage.getItem('medications');
        medications = stored ? JSON.parse(stored) : [];
    }
}

async function saveMedication(medication) {
    try {
        const response = await fetch(`${API_URL}/medications`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(medication)
        });
        
        if (response.ok) {
            const savedMed = await response.json();
            return savedMed;
        }
    } catch (error) {
        console.log('Using localStorage for saving medication');
    }
    
    // Fallback to localStorage
    if (!medication.id) {
        medication.id = Date.now().toString();
    }
    
    const index = medications.findIndex(m => m.id === medication.id);
    if (index !== -1) {
        medications[index] = medication;
    } else {
        medications.push(medication);
    }
    
    localStorage.setItem('medications', JSON.stringify(medications));
    return medication;
}

async function deleteMedication(id) {
    try {
        await fetch(`${API_URL}/medications/${id}`, {
            method: 'DELETE'
        });
    } catch (error) {
        console.log('Using localStorage for deleting medication');
    }
    
    medications = medications.filter(m => m.id !== id);
    localStorage.setItem('medications', JSON.stringify(medications));
}

async function markMedicationTaken(medicationId, scheduleTime) {
    try {
        const response = await fetch(`${API_URL}/medications/${medicationId}/taken`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ scheduleTime })
        });
        
        if (response.ok) {
            await loadMedications();
            renderMedications();
            return;
        }
    } catch (error) {
        console.log('Using localStorage for marking taken');
    }
    
    // Fallback to localStorage
    const medication = medications.find(m => m.id === medicationId);
    if (medication) {
        if (!medication.takenLog) {
            medication.takenLog = [];
        }
        
        medication.takenLog.push({
            scheduleTime,
            takenAt: new Date().toISOString(),
            date: new Date().toISOString().split('T')[0]
        });
        
        localStorage.setItem('medications', JSON.stringify(medications));
        renderMedications();
    }
}

// Form Handlers
function addTimeInput(time = '') {
    const container = document.getElementById('scheduledTimes');
    const timeInputGroup = document.createElement('div');
    timeInputGroup.className = 'time-input-group';
    
    timeInputGroup.innerHTML = `
        <input type="time" value="${time}" required>
        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(timeInputGroup);
}

async function handleMedicationSubmit(e) {
    e.preventDefault();
    
    const name = document.getElementById('medName').value.trim();
    const dose = document.getElementById('medDose').value.trim();
    const instructions = document.getElementById('medInstructions').value.trim();
    
    const timeInputs = document.querySelectorAll('#scheduledTimes input[type="time"]');
    const scheduledTimes = Array.from(timeInputs).map(input => input.value).filter(v => v);
    
    if (scheduledTimes.length === 0) {
        alert('Please add at least one scheduled time');
        return;
    }
    
    const medication = {
        id: currentEditingId,
        name,
        dose,
        instructions,
        scheduledTimes,
        takenLog: currentEditingId ? medications.find(m => m.id === currentEditingId)?.takenLog || [] : []
    };
    
    await saveMedication(medication);
    await loadMedications();
    renderMedications();
    showHome();
}

// Rendering
function renderMedications() {
    const container = document.getElementById('medicationsList');
    
    if (medications.length === 0) {
        container.innerHTML = '<p class="empty-state">No medications scheduled. Add one to get started!</p>';
        return;
    }
    
    const today = new Date().toISOString().split('T')[0];
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    
    container.innerHTML = medications.map(med => {
        const scheduleItems = med.scheduledTimes.map(time => {
            const wasTaken = med.takenLog?.some(log => 
                log.date === today && log.scheduleTime === time
            );
            
            const isPast = time < currentTime;
            const status = wasTaken ? 'taken' : (isPast ? 'overdue' : 'pending');
            
            return `
                <div class="schedule-item">
                    <span class="schedule-time">${formatTime(time)}</span>
                    <div class="schedule-status">
                        ${wasTaken 
                            ? '<span class="status-badge taken">✓ Taken</span>'
                            : `
                                ${isPast ? '<span class="status-badge overdue">⚠ Overdue</span>' : ''}
                                <button class="btn btn-success btn-sm" onclick="markTaken('${med.id}', '${time}')">
                                    ✓ Mark Taken
                                </button>
                            `
                        }
                    </div>
                </div>
            `;
        }).join('');
        
        return `
            <div class="medication-card">
                <div class="medication-header">
                    <div class="medication-info">
                        <h3>${med.name}</h3>
                        ${med.dose ? `<p class="medication-dose">${med.dose}</p>` : ''}
                        ${med.instructions ? `<p class="text-muted">${med.instructions}</p>` : ''}
                    </div>
                    <div class="medication-actions">
                        <button class="btn btn-secondary btn-sm" onclick="editMedication('${med.id}')">✏️</button>
                        <button class="btn btn-danger btn-sm" onclick="removeMedication('${med.id}')">🗑️</button>
                    </div>
                </div>
                <div class="schedule-times">
                    ${scheduleItems}
                </div>
            </div>
        `;
    }).join('');
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
    return `${displayHour}:${minutes} ${ampm}`;
}

// Global functions for inline event handlers
window.markTaken = async (medicationId, scheduleTime) => {
    await markMedicationTaken(medicationId, scheduleTime);
};

window.editMedication = (medicationId) => {
    showAddMedication(medicationId);
};

window.removeMedication = async (medicationId) => {
    if (confirm('Are you sure you want to delete this medication?')) {
        await deleteMedication(medicationId);
        renderMedications();
    }
};

// Settings Management
async function loadSettings() {
    try {
        const response = await fetch(`${API_URL}/settings`);
        if (response.ok) {
            settings = await response.json();
        } else {
            const stored = localStorage.getItem('settings');
            settings = stored ? JSON.parse(stored) : settings;
        }
    } catch (error) {
        const stored = localStorage.getItem('settings');
        settings = stored ? JSON.parse(stored) : settings;
    }
    
    // Update UI
    Object.keys(settings).forEach(key => {
        const element = document.getElementById(key);
        if (element) {
            element.checked = settings[key];
        }
    });
}

async function handleSettingChange(e) {
    const key = e.target.id;
    const value = e.target.checked;
    
    settings[key] = value;
    
    try {
        await fetch(`${API_URL}/settings`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings)
        });
    } catch (error) {
        console.log('Using localStorage for settings');
    }
    
    localStorage.setItem('settings', JSON.stringify(settings));
}

// Notification Management
function checkNotificationPermission() {
    if (!('Notification' in window)) {
        console.log('This browser does not support notifications');
        return;
    }
    
    updateNotificationUI();
}

function updateNotificationUI() {
    const notificationStatus = document.getElementById('notificationStatus');
    const notificationSettings = document.getElementById('notificationSettings');
    
    if (Notification.permission === 'granted') {
        notificationStatus.style.display = 'none';
        notificationSettings.style.display = 'block';
    } else {
        notificationStatus.style.display = 'block';
        notificationSettings.style.display = 'none';
    }
}

async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        alert('This browser does not support notifications');
        return;
    }
    
    const permission = await Notification.requestPermission();
    
    if (permission === 'granted') {
        console.log('Notification permission granted');
        await subscribeToPushNotifications();
        updateNotificationUI();
    } else {
        alert('Notification permission denied. You will not receive medication reminders.');
    }
}

async function subscribeToPushNotifications() {
    try {
        const registration = await navigator.serviceWorker.ready;
        
        // Get VAPID public key from server
        const keyResponse = await fetch(`${API_URL}/vapid-public-key`);
        const { publicKey } = await keyResponse.json();
        
        // Subscribe to push notifications
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey)
        });
        
        // Send subscription to server
        await fetch(`${API_URL}/subscriptions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription)
        });
        
        console.log('Push subscription successful');
    } catch (error) {
        console.error('Failed to subscribe to push notifications:', error);
    }
}

// Helper function to convert VAPID key
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
}
