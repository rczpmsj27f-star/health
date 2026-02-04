const express = require('express');
const cron = require('node-cron');
const bodyParser = require('body-parser');
const cors = require('cors');
const fs = require('fs').promises;
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// OneSignal configuration
const ONESIGNAL_APP_ID = process.env.ONESIGNAL_APP_ID || 'YOUR_ONESIGNAL_APP_ID';
const ONESIGNAL_API_KEY = process.env.ONESIGNAL_API_KEY || 'YOUR_ONESIGNAL_API_KEY';

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(express.static(path.join(__dirname, '../pwa')));

// File paths for simple file-based storage (replace with DB in production)
const MEDICATIONS_FILE = path.join(__dirname, 'medications.json');
const SETTINGS_FILE = path.join(__dirname, 'settings.json');

// Helper functions for file-based storage
async function readJSONFile(filePath, defaultValue = []) {
  try {
    const data = await fs.readFile(filePath, 'utf8');
    return JSON.parse(data);
  } catch (error) {
    if (error.code === 'ENOENT') {
      return defaultValue;
    }
    throw error;
  }
}

async function writeJSONFile(filePath, data) {
  await fs.writeFile(filePath, JSON.stringify(data, null, 2), 'utf8');
}

// API Routes

// Get OneSignal App ID (for frontend initialization)
app.get('/api/onesignal-config', (req, res) => {
  res.json({ appId: ONESIGNAL_APP_ID });
});

// Get all medications
app.get('/api/medications', async (req, res) => {
  try {
    const medications = await readJSONFile(MEDICATIONS_FILE, []);
    res.json(medications);
  } catch (error) {
    console.error('Error getting medications:', error);
    res.status(500).json({ error: error.message });
  }
});

// Add or update medication
app.post('/api/medications', async (req, res) => {
  try {
    const medication = req.body;
    const medications = await readJSONFile(MEDICATIONS_FILE, []);
    
    if (medication.id) {
      // Update existing
      const index = medications.findIndex(m => m.id === medication.id);
      if (index !== -1) {
        medications[index] = { ...medications[index], ...medication };
      }
    } else {
      // Add new
      medication.id = Date.now().toString();
      medication.createdAt = new Date().toISOString();
      medications.push(medication);
    }
    
    await writeJSONFile(MEDICATIONS_FILE, medications);
    res.status(201).json(medication);
  } catch (error) {
    console.error('Error saving medication:', error);
    res.status(500).json({ error: error.message });
  }
});

// Delete medication
app.delete('/api/medications/:id', async (req, res) => {
  try {
    const medications = await readJSONFile(MEDICATIONS_FILE, []);
    const filtered = medications.filter(m => m.id !== req.params.id);
    await writeJSONFile(MEDICATIONS_FILE, filtered);
    res.json({ success: true });
  } catch (error) {
    console.error('Error deleting medication:', error);
    res.status(500).json({ error: error.message });
  }
});

// Mark medication as taken
app.post('/api/medications/:id/taken', async (req, res) => {
  try {
    const { scheduleTime } = req.body;
    const medications = await readJSONFile(MEDICATIONS_FILE, []);
    
    const medication = medications.find(m => m.id === req.params.id);
    if (!medication) {
      return res.status(404).json({ error: 'Medication not found' });
    }
    
    // Initialize takenLog if it doesn't exist
    if (!medication.takenLog) {
      medication.takenLog = [];
    }
    
    // Add taken record
    const takenRecord = {
      scheduleTime,
      takenAt: new Date().toISOString(),
      date: new Date().toISOString().split('T')[0]
    };
    
    medication.takenLog.push(takenRecord);
    
    await writeJSONFile(MEDICATIONS_FILE, medications);
    res.json({ success: true, medication });
  } catch (error) {
    console.error('Error marking medication as taken:', error);
    res.status(500).json({ error: error.message });
  }
});

// Get user settings
app.get('/api/settings', async (req, res) => {
  try {
    const settings = await readJSONFile(SETTINGS_FILE, {
      notifyAtTime: true,
      notifyAfter10Min: true,
      notifyAfter20Min: true,
      notifyAfter30Min: true,
      notifyAfter60Min: false
    });
    res.json(settings);
  } catch (error) {
    console.error('Error getting settings:', error);
    res.status(500).json({ error: error.message });
  }
});

// Update user settings
app.post('/api/settings', async (req, res) => {
  try {
    const settings = req.body;
    await writeJSONFile(SETTINGS_FILE, settings);
    res.json({ success: true, settings });
  } catch (error) {
    console.error('Error updating settings:', error);
    res.status(500).json({ error: error.message });
  }
});

// Function to send push notification via OneSignal
async function sendPushNotification(payload) {
  try {
    const notificationPayload = {
      app_id: ONESIGNAL_APP_ID,
      included_segments: ['All'],
      headings: { en: payload.title },
      contents: { en: payload.body },
      data: payload.data,
      web_url: payload.data?.url || '/',
      chrome_web_icon: payload.icon,
      chrome_web_badge: payload.badge,
      tag: payload.tag
    };

    const response = await fetch('https://onesignal.com/api/v1/notifications', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Basic ${ONESIGNAL_API_KEY}`
      },
      body: JSON.stringify(notificationPayload)
    });

    if (!response.ok) {
      const error = await response.text();
      throw new Error(`OneSignal API error: ${error}`);
    }

    const result = await response.json();
    console.log('Push notification sent successfully via OneSignal:', result.id);
    return true;
  } catch (error) {
    console.error('Error sending push notification:', error);
    return false;
  }
}

// Function to check if medication was taken
function wasMedicationTaken(medication, scheduleTime, toleranceMinutes = 5) {
  if (!medication.takenLog || medication.takenLog.length === 0) {
    return false;
  }
  
  const today = new Date().toISOString().split('T')[0];
  
  return medication.takenLog.some(log => {
    if (log.date !== today) return false;
    if (log.scheduleTime !== scheduleTime) return false;
    return true;
  });
}

// Function to get minutes difference
function getMinutesDifference(time1, time2) {
  const date1 = new Date(`2000-01-01 ${time1}`);
  const date2 = new Date(`2000-01-01 ${time2}`);
  return (date2 - date1) / 60000;
}

// Notification scheduler - runs every minute
cron.schedule('* * * * *', async () => {
  try {
    const medications = await readJSONFile(MEDICATIONS_FILE, []);
    const settings = await readJSONFile(SETTINGS_FILE, {
      notifyAtTime: true,
      notifyAfter10Min: true,
      notifyAfter20Min: true,
      notifyAfter30Min: true,
      notifyAfter60Min: false
    });
    
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    
    // Check each medication
    for (const medication of medications) {
      if (!medication.scheduledTimes || medication.scheduledTimes.length === 0) {
        continue;
      }
      
      // Check each scheduled time
      for (const scheduleTime of medication.scheduledTimes) {
        // Skip if already taken
        if (wasMedicationTaken(medication, scheduleTime)) {
          continue;
        }
        
        const minutesDiff = getMinutesDifference(scheduleTime, currentTime);
        
        let shouldNotify = false;
        let notificationType = '';
        
        // Check if we should notify
        if (minutesDiff === 0 && settings.notifyAtTime) {
          shouldNotify = true;
          notificationType = 'scheduled';
        } else if (minutesDiff === 10 && settings.notifyAfter10Min) {
          shouldNotify = true;
          notificationType = 'reminder-10';
        } else if (minutesDiff === 20 && settings.notifyAfter20Min) {
          shouldNotify = true;
          notificationType = 'reminder-20';
        } else if (minutesDiff === 30 && settings.notifyAfter30Min) {
          shouldNotify = true;
          notificationType = 'reminder-30';
        } else if (minutesDiff === 60 && settings.notifyAfter60Min) {
          shouldNotify = true;
          notificationType = 'reminder-60';
        }
        
        if (shouldNotify) {
          const payload = {
            title: 'Medication Reminder',
            body: notificationType === 'scheduled' 
              ? `Time to take ${medication.name}${medication.dose ? ' - ' + medication.dose : ''}`
              : `Reminder: You haven't taken ${medication.name} (${minutesDiff} min overdue)`,
            icon: '/icons/icon-192x192.png',
            badge: '/icons/badge-72x72.png',
            tag: `medication-${medication.id}-${scheduleTime}`,
            data: {
              medicationId: medication.id,
              scheduleTime: scheduleTime,
              type: notificationType,
              url: '/'
            }
          };
          
          // Send notification via OneSignal
          await sendPushNotification(payload);
        }
      }
    }
  } catch (error) {
    console.error('Error in notification scheduler:', error);
  }
});

// Start server
app.listen(PORT, () => {
  console.log(`Medication Reminder Server running on port ${PORT}`);
  console.log(`OneSignal App ID: ${ONESIGNAL_APP_ID}`);
  console.log('Notification scheduler is active (runs every minute)');
});
