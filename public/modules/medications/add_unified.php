<?php 
session_start();
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medication</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .unified-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 80px 16px 40px 16px;
        }
        
        .form-section {
            margin-bottom: 32px;
            padding: 24px;
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
        }
        
        .form-section-title {
            background: var(--color-primary);
            color: var(--color-bg-white);
            padding: 12px 20px;
            margin: -24px -24px 20px -24px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            font-weight: 600;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        <a href="/modules/medications/list.php">💊 Medications</a>
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div class="unified-form-container">
        <div style="text-align: center; margin-bottom: 32px;">
            <h2 style="color: var(--color-primary); font-size: 32px; margin: 0 0 8px 0;">💊 Add New Medication</h2>
            <p style="color: var(--color-text-secondary); margin: 0;">Complete all fields to add your medication</p>
        </div>

        <form method="POST" action="/modules/medications/add_unified_handler.php" id="medForm">
            <!-- Section 1: Medication Search -->
            <div class="form-section">
                <div class="form-section-title">1. Medication Information</div>
                
                <div class="form-group">
                    <label>Medication Name *</label>
                    <input type="text" name="med_name" id="med_name" autocomplete="off" placeholder="Enter medication name" required>
                </div>

                <input type="hidden" name="nhs_med_id" id="selected_med_id">
            </div>

            <!-- Section 2: Dosage -->
            <div class="form-section">
                <div class="form-section-title">2. Dosage Information</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Dose Amount *</label>
                        <input type="number" step="0.01" name="dose_amount" placeholder="e.g., 500" required>
                    </div>

                    <div class="form-group">
                        <label>Unit *</label>
                        <select name="dose_unit" required>
                            <option value="">Select unit...</option>
                            <option value="mg">mg (milligrams)</option>
                            <option value="ml">ml (milliliters)</option>
                            <option value="tablet">tablet(s)</option>
                            <option value="capsule">capsule(s)</option>
                            <option value="g">g (grams)</option>
                            <option value="mcg">mcg (micrograms)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Schedule -->
            <div class="form-section">
                <div class="form-section-title">3. Medication Schedule</div>
                
                <div class="form-group">
                    <label>Frequency *</label>
                    <select name="frequency_type" id="freq" onchange="updateScheduleUI()" required>
                        <option value="per_day">Times per day</option>
                        <option value="per_week">Times per week</option>
                    </select>
                </div>

                <div id="daily">
                    <div class="form-group">
                        <label>Times per day *</label>
                        <input type="number" name="times_per_day" id="times_per_day" min="1" max="6" value="1" onchange="updateTimeInputs()">
                    </div>
                    
                    <div id="time_inputs_container">
                        <!-- Time inputs will be dynamically generated here -->
                    </div>
                </div>

                <div id="weekly" style="display:none;">
                    <div class="form-group">
                        <label>Times per week *</label>
                        <input type="number" name="times_per_week" min="1" max="7" value="1">
                    </div>

                    <div class="form-group">
                        <label>Days of Week</label>
                        <div class="day-toggle-container">
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Mon">
                                <span class="day-toggle-btn">Mon</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Tue">
                                <span class="day-toggle-btn">Tue</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Wed">
                                <span class="day-toggle-btn">Wed</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Thu">
                                <span class="day-toggle-btn">Thu</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Fri">
                                <span class="day-toggle-btn">Fri</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Sat">
                                <span class="day-toggle-btn">Sat</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Sun">
                                <span class="day-toggle-btn">Sun</span>
                            </label>
                        </div>
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            Select the days when this medication should be taken
                        </small>
                    </div>
                </div>
            </div>

            <!-- Section 4: Instructions -->
            <div class="form-section">
                <div class="form-section-title">4. Special Instructions</div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take with water">
                        💧 Take with water
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take on empty stomach">
                        🍽️ Take on empty stomach
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take with food">
                        🍴 Take with food
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Do not crush or chew">
                        💊 Do not crush or chew
                    </label>
                </div>

                <div class="form-group">
                    <label>Other Instructions (optional)</label>
                    <textarea name="other_instruction" rows="3" placeholder="Enter any additional instructions..."></textarea>
                </div>
            </div>

            <!-- Section 5: Condition -->
            <div class="form-section">
                <div class="form-section-title">5. Condition Being Treated</div>
                
                <div class="form-group">
                    <label>Condition Name *</label>
                    <input type="text" name="condition_name" id="condition_name" autocomplete="off" placeholder="e.g., High blood pressure" required>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" type="submit">✅ Add Medication</button>
                <a class="btn btn-secondary" href="/modules/medications/list.php">❌ Cancel</a>
            </div>
        </form>
    </div>

    <script>
    // Update time inputs based on times per day
    function updateTimeInputs() {
        let timesPerDay = parseInt(document.getElementById("times_per_day").value) || 1;
        let container = document.getElementById("time_inputs_container");
        
        if (timesPerDay > 1) {
            let html = '<div style="margin-top:10px;"><strong>Dose Times:</strong></div>';
            for (let i = 1; i <= timesPerDay; i++) {
                html += `<label>Time ${i}:</label>`;
                html += `<input type="time" name="dose_time_${i}" id="dose_time_${i}">`;
            }
            
            // Add evenly split button
            html += '<button type="button" class="btn btn-secondary" onclick="evenlySpitTimes()" style="margin-top: 12px;">⏰ Evenly split (7am - 10pm)</button>';
            
            container.innerHTML = html;
        } else {
            container.innerHTML = '';
        }
    }
    
    // Evenly split times throughout the day (7am - 10pm)
    function evenlySpitTimes() {
        let timesPerDay = parseInt(document.getElementById("times_per_day").value) || 1;
        
        if (timesPerDay < 2) return;
        
        // Start at 7:00 (420 minutes from midnight)
        // End at 22:00 (1320 minutes from midnight)
        const startMinutes = 7 * 60; // 7:00 AM = 420 minutes
        const endMinutes = 22 * 60;  // 10:00 PM = 1320 minutes
        
        // Calculate interval
        let interval;
        if (timesPerDay === 2) {
            interval = endMinutes - startMinutes;
        } else {
            interval = (endMinutes - startMinutes) / (timesPerDay - 1);
        }
        
        // Set each time input
        for (let i = 1; i <= timesPerDay; i++) {
            let totalMinutes = startMinutes + (interval * (i - 1));
            let hours = Math.floor(totalMinutes / 60);
            let minutes = Math.round(totalMinutes % 60);
            
            // Format as HH:MM
            let timeString = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
            
            let input = document.getElementById(`dose_time_${i}`);
            if (input) {
                input.value = timeString;
            }
        }
    }

    // Schedule UI toggle
    function updateScheduleUI() {
        let f = document.getElementById("freq").value;
        
        if (f === "per_day") {
            document.querySelector('[name="times_per_week"]').value = "";
            document.getElementById("daily").style.display = "block";
            document.getElementById("weekly").style.display = "none";
            updateTimeInputs();
        } else {
            document.querySelector('[name="times_per_day"]').value = "";
            document.getElementById("daily").style.display = "none";
            document.getElementById("weekly").style.display = "block";
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateTimeInputs();
    });
    </script>
</body>
</html>
